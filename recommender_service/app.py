import os, math, pickle, difflib
import pandas as pd
from flask import Flask, request, jsonify, redirect
from sentence_transformers import util
from recommender import Recommender
import logging

# Add this near the top after your imports
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

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
        hits = df.index[df["TITLE"].str.lower() == q].tolist()  # exact
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
    # Arts, Humanities, and Social Sciences
    "AB Political Science": ["History", "Law", "Non-Fiction"],
    "AB Psychology": ["Science", "Non-Fiction"],
    "BA Broadcasting": ["Art & Media", "Non-Fiction"],
    "BA History": ["History", "Non-Fiction"],
    "BA Political Science": ["History", "Law", "Non-Fiction"],

    # Business & Economics
    "BS Accountancy": ["Non-Fiction"],
    "BS Management Accounting": ["Non-Fiction"],
    "BSBA Financial Management": ["Non-Fiction"],
    "BSBA Human Resource Management": ["Non-Fiction"],
    "BSBA Marketing Management": ["Non-Fiction"],
    "BS Entrepreneurship": ["Non-Fiction"],
    "BS Economics": ["Non-Fiction"],

    # Psychology
    "BS Psychology": ["Science", "Non-Fiction"],

    # Engineering & Technology
    "BS Architecture": ["Art & Media", "Science"],
    "BS Civil Engineering": ["Science", "Non-Fiction"],
    "BS Computer Engineering": ["Science", "Non-Fiction"],
    "BS ECE": ["Science", "Non-Fiction"],
    "BS Electrical Engineering": ["Science", "Non-Fiction"],
    "BS Electronics Engineering": ["Science", "Non-Fiction"],
    "BS Industrial Engineering": ["Science", "Non-Fiction"],
    "BS Information Technology": ["Science", "Non-Fiction"],
    "BS Mechanical Engineering": ["Science", "Non-Fiction"],

    # Education
    "BS Education": ["Non-Fiction"],
    "BS Education Major in Filipino": ["Non-Fiction"],
    "BS Education Major in Math": ["Science", "Non-Fiction"],
    "BS Education Major in Science": ["Science", "Non-Fiction"],
    "BS Education Major in Social Studies": ["History", "Non-Fiction"],
    "BS Elementary Education": ["Children", "Non-Fiction"],
    "BSE Filipino": ["Non-Fiction"],
    "BSE Math": ["Science", "Non-Fiction"],
    "BSE Science": ["Science", "Non-Fiction"],
    "BSE Social Studies": ["History", "Non-Fiction"],
    "BSED Filipino": ["Non-Fiction"],
    "BSED ICT": ["Science", "Non-Fiction"],
    "BSED Science": ["Science", "Non-Fiction"],
    "BSES Social Studies": ["History", "Non-Fiction"],

    # Health & Medical
    "BS Dentistry": ["Science", "Non-Fiction"],
    "BS Nursing": ["Science", "Non-Fiction"],

    # Hospitality & Office Work
    "BS Hospitality Management": ["Culinary", "Non-Fiction"],
    "BS Office Administration": ["Non-Fiction"],

    # Specialized
    "BTVTED Garments, Fashion and Design": ["Art & Media", "Non-Fiction"],
}

STRAND_CATEGORY_MAP = {
    "ABM": ["Non-Fiction"],
    "STEM": ["Science"],
    "HUMSS": ["History", "Non-Fiction"],
    "GAS": ["Non-Fiction"],
    "TVL": ["Culinary"],
    "Arts and Design": ["Art & Media"]
}

@app.route('/recommend_by_field', methods=['POST'])
def recommend_by_field():
    import mysql.connector
    data = request.json
    user_id = data.get('user_id')

    logger.info(f"Recommendation request for user_id: {user_id}")
    logger.debug(f"Full request data: {data}")

    if not user_id:
        return jsonify({"recommendations": []})

    try:
        # Connect to MySQL
        conn = mysql.connector.connect(
            host='localhost',
            user='root',
            password='',
            database='library_test_db'
        )
        cursor = conn.cursor(dictionary=True)
        cursor.execute(
            "SELECT education_level, major, strand FROM users WHERE id = %s",
            (user_id,)
        )
        user = cursor.fetchone()
        cursor.close()
        conn.close()

        if not user:
            return jsonify({"recommendations": []})

        # Normalize fields
        edu_level = (user.get('education_level') or '').strip().lower()
        if edu_level == "college":
            field = (user.get('major') or '').strip()
            categories = MAJOR_CATEGORY_MAP.get(field, [])
        elif edu_level == "shs":
            field = (user.get('strand') or '').strip().upper()
            categories = STRAND_CATEGORY_MAP.get(field, [])
        else:
            field = ""
            categories = []

        # Fallback to all categories if mapping empty
        if not categories:
            categories = df['General_Category'].dropna().unique().tolist()

        logger.info(f"User: {edu_level} - {field} -> mapped categories: {categories}")

        df['General_Category'] = df['General_Category'].str.strip()

        ## Filter books
        filtered_books = df[df['General_Category'].isin(categories)].copy()

        if filtered_books.empty:
            filtered_books = df.copy()

        # Ensure Like column exists
        if 'Like' not in filtered_books.columns:
            filtered_books['Like'] = 0
        filtered_books['Like'] = filtered_books['Like'].fillna(0)

        # Handle missing cover_image_url
        if 'cover_image_url' not in filtered_books.columns:
            filtered_books['cover_image_url'] = ""

        # Columns to include
        cols = ['TITLE', 'AUTHOR', 'General_Category', 'cover_image_url']

        # Take top 5 by likes
        recommendedBooks = filtered_books.nlargest(5, 'Like')[cols]

        # DEBUG: log recommended book titles
        logger.info("Recommended books for user_id %s: %s",
                    user_id,
                    recommendedBooks['TITLE'].tolist())

        # Clean NaNs / missing values
        cleaned_recommended_books = []
        for book in recommendedBooks.to_dict(orient='records'):
            cleaned_book = {k: ("" if v is None or (isinstance(v, float) and math.isnan(v)) else v)
                            for k, v in book.items()}
            cleaned_recommended_books.append(cleaned_book)

        return jsonify({"recommendations": cleaned_recommended_books})

    except Exception as e:
        logger.error(f"Error in recommend_by_field: {e}", exc_info=True)
        return jsonify({"recommendations": []})


# ── 4.  Root → redirect to PHP UI ───────────────────────────────
@app.route("/")
def home():
    return redirect("http://127.0.0.1/library-app/index.php", code=302)

# ── 5.  Run server ──────────────────────────────────────────────
if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5001, debug=True)
