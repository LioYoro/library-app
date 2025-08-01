import os, math, pickle, difflib
import pandas as pd
from flask import Flask, request, jsonify, redirect
from sentence_transformers import util
from recommender import Recommender

app = Flask(__name__)

# ── 1.  Paths ────────────────────────────────────────────────────
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DATA_DIR = os.path.join(BASE_DIR, "..", "data")

csv_path = os.path.join(DATA_DIR, "LIBRARY_DATA_FINAL_clean.csv")
emb_path = os.path.join(DATA_DIR, "corpus_embeddings.pkl")

# ── 2.  Load data & embeddings once ──────────────────────────────
df = pd.read_csv(csv_path)
with open(emb_path, "rb") as f:
    corpus_embeddings = pickle.load(f)

# Initialize the recommender instance
recommender = Recommender(df, corpus_embeddings)

titles = df["TITLE"].fillna("").tolist()

# Helper to clean NaN
def _clean(val, default=""):
    if val is None or (isinstance(val, float) and math.isnan(val)) \
       or (isinstance(val, str) and val.lower() == "nan"):
        return default
    return val

def serialize_row(idx, seed_vec):
    row = df.loc[idx]
    return {
        "title": row["TITLE"],
        "author": _clean(row.get("AUTHOR"), "Unknown"),
        "call_no": _clean(row.get("CALL NUMBER"), "N/A"),
        "likes": int(row["Like"]),
        "dislikes": int(row["Dislike"]),
        "general_cat": row["General_Category"],
        "sub_cat": _clean(row.get("Sub_Category"), "N/A"),
        "similar": float(util.cos_sim(seed_vec, recommender.emb[idx]).item()),
    }

from intuitive_bot import ask as bot_ask

@app.route("/api/chat", methods=["POST"])
def api_chat():
    data = request.get_json(force=True)
    print("Received data:", data)  # Debug print
    question = (data or {}).get("question", "").strip()
    if not question:
        return jsonify({"error": "missing question"}), 400
    
    try:
        res = bot_ask(question)
        print("Bot response:", res)  # Debug print
    except Exception as e:
        print("Error in bot_ask:", e)
        return jsonify({"error": "Internal error"}), 500

    return jsonify(res)

# ── 3.  /api/recommend ───────────────────────────────────────────
@app.route("/api/recommend", strict_slashes=False)
def api_recommend():
    # accept either ?id= or ?title=
    if "title" in request.args and "id" not in request.args:
        q = request.args["title"].strip().lower()
        hits = df.index[df["TITLE"].str.lower() == q].tolist()          # exact
        if not hits:
            hits = df.index[df["TITLE"].str.lower().str.contains(q)].tolist()  # substring
        if not hits:
            best = difflib.get_close_matches(q, [t.lower() for t in titles], n=1, cutoff=0.3)
            if not best:
                return jsonify({"error": "Title not found"}), 404
            hit_lower = best[0]
            hits = df.index[df["TITLE"].str.lower() == hit_lower].tolist()
        seed_idx = hits[0]
    else:
        try:
            seed_idx = int(request.args.get("id", -1))
        except (TypeError, ValueError):
            return jsonify({"error": "Bad id"}), 400

    if seed_idx < 0 or seed_idx >= len(df):
        return jsonify({"error": "Bad id"}), 400

    res = recommender.recommend(seed_idx)
    seed_vec = recommender.emb[seed_idx]
    seed_row = df.loc[seed_idx]

    ser = lambda idx_list: [serialize_row(i, seed_vec) for i in idx_list]

    return jsonify({
        "seed": {
            "title": seed_row["TITLE"],
            "author": _clean(seed_row.get("AUTHOR"), "Unknown"),
            "general_cat": seed_row["General_Category"],
            "sub_cat": _clean(seed_row.get("Sub_Category"), "N/A")
        },
        "similar": ser(res["similar"]),
        "trending": ser(res["trending"]),
        "author": ser(res["author"]),
    })

@app.route("/api/recommend", methods=["POST"])
def api_recommend_post():
    data = request.get_json(force=True)
    title = (data or {}).get("title", "").strip().lower()
    if not title:
        return jsonify({"error": "missing title"}), 400

    # Try to find the exact match first
    hits = df.index[df["TITLE"].str.lower() == title].tolist()
    if not hits:
        # Try substring match
        hits = df.index[df["TITLE"].str.lower().str.contains(title)].tolist()
    if not hits:
        best = difflib.get_close_matches(title, [t.lower() for t in titles], n=1, cutoff=0.3)
        if not best:
            return jsonify({"error": "Title not found"}), 404
        hit_lower = best[0]
        hits = df.index[df["TITLE"].str.lower() == hit_lower].tolist()

    seed_idx = hits[0]
    seed_vec = recommender.emb[seed_idx]
    res = recommender.recommend(seed_idx)

    def short_sum(s, max_len=180):
        if not isinstance(s, str): return ""
        return s.strip()[:max_len].rstrip(".") + "..."

    related_books = []
    for idx in res["similar"]:
        row = df.loc[idx]
        related_books.append({
            "title": row["TITLE"],
            "author": _clean(row.get("AUTHOR"), "Unknown"),
            "call_no": _clean(row.get("CALL NUMBER"), "N/A"),
            "short": short_sum(row.get("SUMMARY", ""))  # ✅ FIXED HERE
        })

    return jsonify({"recommended": related_books})


# Add this mapping at the top of app.py after the imports
MAJOR_CATEGORY_MAP = {
    "AB Political Science": ["Politics", "History", "Social Science"],
    "AB Psychology": ["Psychology", "Self-Help"],
    "BA Broadcasting": ["Art & Media"],
    "BA History": ["History"],
    "BS Accountancy": ["Business & Career"],
    "BS Architecture": ["Art & Media"],
    "BS Civil Engineering": ["Science"],
    "BS Computer Engineering": ["Science"],
    "BS Dentistry": ["Health"],
    "BS ECE": ["Science"],
    # ... add all other majors
}

STRAND_CATEGORY_MAP = {
    "ABM": ["Business & Career", "Economics"],
    "STEM": ["Science", "Academic"],
    "HUMSS": ["History", "Politics", "Social Science"],
    "GAS": ["Non-Fiction", "Education"],
    "TVL": ["Craft", "Culinary"],
    "Arts and Design": ["Art & Media"]
}


import math # Make sure to import math at the top of your app.py if it's not already there

@app.route('/recommend_by_field', methods=['POST'])
def recommend_by_field():
    import mysql.connector
    data = request.json
    user_id = data.get('user_id')

    if not user_id:
        return jsonify([])

    try:
        # Connect to your MySQL DB
        conn = mysql.connector.connect(
            host='localhost',
            user='root',
            password='',
            database='library_test_db'
        )
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT education_level, major, strand FROM users WHERE id = %s", (user_id,))
        user = cursor.fetchone()
        cursor.close()
        conn.close()

        if not user:
            return jsonify({"recommendations": []}) # Return empty list in "recommendations" key

        edu_level = user['education_level'].lower()
        field = user['major'] if edu_level == "kolehiyo" else user['strand']

        if edu_level == "kolehiyo":
            categories = MAJOR_CATEGORY_MAP.get(field, [])
        else:
            categories = STRAND_CATEGORY_MAP.get(field, [])

        print("User field (strand or major):", field)
        print("Matched categories:", categories)

        if not categories:
            return jsonify({"recommendations": []}) # Return empty list in "recommendations" key

        # Recommend based on matched categories
        # Ensure df is accessible here (e.g., loaded globally or passed)
        if 'df' not in globals():
            # This is a placeholder. In a real app, df should be loaded once.
            # Example: df = pd.read_csv('your_books_data.csv') or loaded from DB
            # For now, assume df is available from a broader scope.
            print("Warning: 'df' (DataFrame) not found. Recommendations will fail.")
            return jsonify({"recommendations": []})


        # Handle cases where 'General_Category' might be missing or not a string
        filtered_books = df[df['General_Category'].astype(str).str.contains('|'.join(categories), na=False)]


        recommendedBooks = filtered_books[['TITLE', 'AUTHOR', 'General_Category', 'Sub_Category', 'Like']] \
            .sort_values(by='Like', ascending=False) \
            .head(5) \
            .to_dict(orient='records')

        # --- START OF NaN HANDLING FIX ---
        # Process recommendedBooks to replace NaN with None for JSON compatibility
        cleaned_recommended_books = []
        for book in recommendedBooks:
            cleaned_book = {}
            for key, value in book.items():
                # Check specifically for float NaNs from pandas/numpy
                if isinstance(value, float) and math.isnan(value):
                    cleaned_book[key] = None # Python's None becomes JSON's null
                else:
                    cleaned_book[key] = value
            cleaned_recommended_books.append(cleaned_book)
        # --- END OF NaN HANDLING FIX ---

        print("Final recommendations (cleaned):", cleaned_recommended_books)
        return jsonify({"recommendations": cleaned_recommended_books})

    except Exception as e:
        print("Error in recommend_by_field:", e)
        # It's better to return an empty list in the "recommendations" key on error too
        return jsonify({"recommendations": []})


# ── 4.  Root → redirect to PHP UI ───────────────────────────────
@app.route("/")
def home():
    return redirect("http://127.0.0.1/library-app/index.php", code=302)

# ── 5.  Run server ──────────────────────────────────────────────
if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5001, debug=True)
