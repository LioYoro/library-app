# intuitive_bot.py  ✨ 2025‑06‑29
import os, pickle, pathlib
import pandas as pd
from sentence_transformers import SentenceTransformer, util
from openai import OpenAI
from dotenv import load_dotenv

load_dotenv()  # expects OPENAI_API_KEY in .env

# ── Paths & data ───────────────────────────────────────────────
BASE_DIR = pathlib.Path(__file__).resolve().parent
DATA_DIR = BASE_DIR / ".." / "data"

df = pd.read_csv(DATA_DIR / "LIBRARY_DATA_FINAL_clean.csv")

text_col = (
    df["TITLE"].fillna("") + " " +
    df["SUMMARY"].fillna("") + " " +
    df["AUTHOR"].fillna("") + " " +
    df["CALL NUMBER"].fillna("")
).tolist()

emb_path = DATA_DIR / "bot_corpus_embeddings.pkl"
if emb_path.exists():
    corpus_embeddings = pickle.loads(emb_path.read_bytes())
else:
    embedder = SentenceTransformer("all-MiniLM-L6-v2")
    corpus_embeddings = embedder.encode(text_col, convert_to_tensor=True)
    emb_path.write_bytes(pickle.dumps(corpus_embeddings))

client = OpenAI()  # uses env var automatically

# ── helpers ────────────────────────────────────────────────────
def get_or_unknown(val, default="Unknown"):
    return default if pd.isna(val) or str(val).strip() == "" else str(val).strip()

def shorten(text: str, max_len: int = 180) -> str:
    text = str(text).strip()
    return text if len(text) <= max_len else text[:max_len].rstrip(".") + "…"

# ── public API ─────────────────────────────────────────────────
def ask(question: str) -> dict:
    """Return dict with 'answer', 'main', 'related'."""

    embedder = SentenceTransformer("all-MiniLM-L6-v2")

    # 1️⃣ Locate seed book
    exact_hits = df.index[df["TITLE"].str.strip().str.lower() == question.strip().lower()]
    if not exact_hits.empty:
        seed_idx = int(exact_hits[0])
    else:
        q_vec = embedder.encode(question, convert_to_tensor=True)
        hits = util.semantic_search(q_vec, corpus_embeddings, top_k=5)[0]
        if not hits:
            raise ValueError("No semantic matches found.")
        seed_idx = hits[0]["corpus_id"]

    # 2️⃣ Seed metadata
    seed = df.iloc[seed_idx]
    top_title = seed["TITLE"]
    top_summary = str(seed["SUMMARY"])
    top_author = get_or_unknown(seed.get("AUTHOR"))
    top_call_no = get_or_unknown(seed.get("CALL NUMBER"), "N/A")

    # 3️⃣ Short summary for seed
    short_summary = client.chat.completions.create(
        model="gpt-4",
        messages=[{
            "role": "user",
            "content": f"Summarize the book summary in 2‑3 concise sentences.\n\nSummary:\n{top_summary}"
        }],
        max_tokens=120,
        temperature=0.4,
    ).choices[0].message.content.strip()

    # 4️⃣ Related books – new semantic search
    seed_vec = embedder.encode(top_title + " " + top_summary, convert_to_tensor=True)
    related_hits = util.semantic_search(seed_vec, corpus_embeddings, top_k=5)[0]

    related = []
    for hit in related_hits:
        if hit["corpus_id"] == seed_idx or hit["score"] < 0.30 or len(related) >= 2:
            continue

        r = df.iloc[hit["corpus_id"]]
        title_r = get_or_unknown(r.get("TITLE"))
        summary_r = str(r["SUMMARY"]).strip()

        # Skip if summary too short or title looks like placeholder
        if len(summary_r) < 50:
            continue
        title_lc = title_r.lower()
        if title_lc.startswith("color print") or "cassette" in title_lc:
            continue

        rel_short = client.chat.completions.create(
            model="gpt-4",
            messages=[{
                "role": "user",
                "content": (f'Summarize this book titled "{title_r}" in 2‑3 sentences:\n\n{summary_r}')
            }],
            max_tokens=80,
            temperature=0.4,
        ).choices[0].message.content.strip()

        related.append({
            "title": title_r,
            "author": get_or_unknown(r.get("AUTHOR")),
            "call_no": get_or_unknown(r.get("CALL NUMBER"), "N/A"),
            "short": shorten(rel_short, 240)
        })

    # 5️⃣ Book-intro line
    book_intro = client.chat.completions.create(
        model="gpt-4",
        messages=[{
            "role": "user",
            "content": (f'Briefly explain what the book titled "{top_title}" is about '
                        'in your own words. Make it 1–2 sentences and avoid copying any summary.')
        }],
        max_tokens=100,
        temperature=0.6,
    ).choices[0].message.content.strip()

    # 6️⃣ GPT answer to the user
    gpt_prompt = f"""
You are a helpful library assistant. The user asked: "{question}"

Use ONLY the following book summary to answer the question. If the summary clearly answers
or hints at a response, explain it directly—do NOT write “the summary says …”. Avoid repetition.

If the summary does NOT contain relevant information:
Reply exactly: **"The current library materials unfortunately does not have specific information about your inquiry."**
You may then add a concise, factual answer based on your general knowledge.

Summary:
{top_summary.strip()}

After answering, add this single line:
f'For a deeper understanding, I recommend reading "{top_title}"{" by " + top_author if top_author != "Unknown" else ""} (Call Number: {top_call_no}). {book_intro}'
"""
    gpt_final = client.chat.completions.create(
        model="gpt-4",
        messages=[{"role": "user", "content": gpt_prompt}],
        max_tokens=600,
        temperature=0.7,
    ).choices[0].message.content.strip()

    return {
        "answer": gpt_final,
        "main": {
            "title": top_title,
            "author": top_author,
            "call_no": top_call_no,
            "short_summary": short_summary,
        },
        "related": related
    }
