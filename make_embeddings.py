import pandas as pd, pickle, torch
from sentence_transformers import SentenceTransformer

CSV  = "data/LIBRARY_DATA_FINAL_clean.csv"
OUT  = "data/corpus_embeddings.pkl"

df = pd.read_csv(CSV)
texts = (
    df["TITLE"].fillna("") + " " +
    df["Keywords"].fillna("") + " " +
    df["Summary"].fillna("")
).tolist()

print("Encoding", len(texts), "rows …")
model = SentenceTransformer("all-MiniLM-L6-v2")
emb   = model.encode(texts, convert_to_tensor=True)

with open(OUT, "wb") as f:
    pickle.dump(emb, f)

print("✅ wrote", OUT, "size=", emb.shape)