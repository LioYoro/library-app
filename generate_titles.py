# generate_titles.py
import pandas as pd, json, pathlib

csv_path = pathlib.Path("data/LIBRARY_DATA_FINAL_clean.csv")   # relative to project root
out_path = pathlib.Path("project/static/book_titles.json")     # write here
out_path.parent.mkdir(parents=True, exist_ok=True)

df = pd.read_csv(csv_path)
titles = (
    df["TITLE"]
      .dropna()
      .astype(str)
      .str.strip()
      .unique()
      .tolist()
)

with open(out_path, "w", encoding="utf-8") as f:
    json.dump(titles, f, ensure_ascii=False, indent=2)

print(f"✅ wrote {len(titles)} titles to {out_path}")
