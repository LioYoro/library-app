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
df  = pd.read_csv(csv_path)
with open(emb_path, "rb") as f:
    corpus_embeddings = pickle.load(f)

rec     = Recommender(df, corpus_embeddings)
titles  = df["TITLE"].fillna("").tolist()

# helper to clean NaN
def _clean(val, default=""):
    if val is None or (isinstance(val, float) and math.isnan(val)) \
       or (isinstance(val, str) and val.lower() == "nan"):
        return default
    return val

def serialize_row(idx, seed_vec):
    row = df.loc[idx]
    return {
        "title"        : row["TITLE"],
        "author"       : _clean(row.get("AUTHOR"), "Unknown"),
        "call_no"      : _clean(row.get("CALL NUMBER"), "N/A"),
        "likes"        : int(row["Like"]),
        "dislikes"     : int(row["Dislike"]),
        "general_cat"  : row["General_Category"],
        "sub_cat"      : _clean(row.get("Sub_Category"), "N/A"),
        "similar"      : float(util.cos_sim(seed_vec, rec.emb[idx]).item()),
    }

from intuitive_bot import ask as bot_ask

@app.route("/api/chat", methods=["POST"])
def api_chat():
    data = request.get_json(force=True)
    question = (data or {}).get("question", "").strip()
    if not question:
        return jsonify({"error":"missing question"}), 400
    res = bot_ask(question)
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

    res      = rec.recommend(seed_idx)
    seed_vec = rec.emb[seed_idx]
    seed_row = df.loc[seed_idx]

    ser = lambda idx_list: [serialize_row(i, seed_vec) for i in idx_list]

    return jsonify({
    "seed": {
        "title"       : seed_row["TITLE"],
        "author"      : _clean(seed_row.get("AUTHOR"), "Unknown"),
        "general_cat" : seed_row["General_Category"],
        "sub_cat"     : _clean(seed_row.get("Sub_Category"), "N/A")
    },
    "similar"  : ser(res["similar"]),
    "trending" : ser(res["trending"]),
    "author"   : ser(res["author"]),
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
    seed_vec = rec.emb[seed_idx]
    res = rec.recommend(seed_idx)

    def short_sum(s, max_len=180):
        if not isinstance(s, str): return ""
        return s.strip()[:max_len].rstrip(".") + "..."

    related_books = []
    for idx in res["similar"]:
        row = df.loc[idx]
        related_books.append({
            "title"   : row["TITLE"],
            "author"  : _clean(row.get("AUTHOR"), "Unknown"),
            "call_no" : _clean(row.get("CALL NUMBER"), "N/A"),
            "short"   : short_sum(row.get("Summary", ""))
        })

    return jsonify({"recommended": related_books})


# ── 4.  Root → redirect to PHP UI ───────────────────────────────
@app.route("/")
def home():
    return redirect("http://127.0.0.1/library-app/index.php", code=302)

# ── 5.  Run server ──────────────────────────────────────────────
if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5001, debug=True)