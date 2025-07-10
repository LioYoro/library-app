"""
Recommender for LIBRARY_DATA_FINAL_clean.csv
Layer A : 1) specific Sub_Category + Gen‑Cat
          2) keyword overlap
          3) same General_Category
          4) same call_prefix
          5) semantic fallback
Layer B : top‑liked in same General_Category (global fallback if empty)
"""

from typing import List, Dict
import pandas as pd
from sentence_transformers import util


class Recommender:
    def __init__(self, books_df: pd.DataFrame, embeddings, like_col: str = "Like"):
        self.df  = books_df.reset_index(drop=True)
        self.emb = embeddings
        self.df["Like"]    = self.df[like_col].fillna(0)
        self.df["Dislike"] = self.df["Dislike"].fillna(0)

        # Pre‑compute sub‑category frequency (row share)
        self._sub_freq = (
            self.df["Sub_Category"].str.strip().str.lower().value_counts(normalize=True)
        )

    # ──────────────────────────────────────────
    # Cosine‑similarity helper
    # ──────────────────────────────────────────
    def _similar(self, seed_idx: int, k: int = 5, thresh: float = 0.45) -> List[int]:
        sims = util.semantic_search(self.emb[seed_idx], self.emb, top_k=k + 1)[0]
        return [
            hit["corpus_id"]
            for hit in sims
            if hit["corpus_id"] != seed_idx and hit["score"] >= thresh
        ][:k]

        # ──────────────────────────────────────────
    # Layer A – content / metadata driven
    # ──────────────────────────────────────────
    def _category_recs(self, seed_idx: int, k: int = 5) -> List[int]:
        seed     = self.df.iloc[seed_idx]
        gen_cat  = str(seed.get("General_Category", "")).strip().lower()
        sub_cat  = str(seed.get("Sub_Category", "")).strip().lower()
        prefix   = str(seed.get("call_prefix", "")).strip().lower()

        kw_seed  = set(str(seed.get("Keywords", "")).lower().split(","))
        has_kw   = bool(kw_seed and kw_seed != {""})

        # ── decide if sub_cat is “specific” ───────────────────────
        sub_freq = self._sub_freq.get(sub_cat, 0.0)   # share 0‑1
        use_sub  = (
            sub_cat and sub_cat != gen_cat and sub_freq < 0.05
        )

        chosen: List[int] = []

        # 1️⃣ Specific Sub‑Category + same General_Category
        if use_sub:
            mask = (
                (self.df["Sub_Category"].fillna("").str.lower() == sub_cat) &
                (self.df["General_Category"].fillna("").str.lower() == gen_cat) &
                (self.df.index != seed_idx)
            )
            sub_df = self.df[mask].copy()
            sub_df["pop"] = sub_df["Like"] - sub_df["Dislike"]
            chosen.extend(
                sub_df.sort_values(["pop", "Like"], ascending=False)
                      .head(k).index
            )

        # 2️⃣ Keyword overlap
        if len(chosen) < k and has_kw:
            for idx, row in self.df.iterrows():
                if idx == seed_idx or idx in chosen:
                    continue
                kw_other = set(str(row.get("Keywords", "")).lower().split(","))
                if kw_seed & kw_other:
                    chosen.append(idx)
                if len(chosen) == k:
                    break

        # 3️⃣ Same General_Category
        if len(chosen) < k and gen_cat:
            rem  = k - len(chosen)
            mask = (
                self.df["General_Category"].fillna("").str.lower() == gen_cat
            ) & (~self.df.index.isin(chosen + [seed_idx]))
            cat_df = self.df[mask].copy()
            cat_df["pop"] = cat_df["Like"] - cat_df["Dislike"]
            chosen.extend(
                cat_df.sort_values(["pop", "Like"], ascending=False)
                      .head(rem).index
            )

        # 4️⃣ Same call_prefix
        if len(chosen) < k and prefix:
            rem  = k - len(chosen)
            mask = (
                self.df["call_prefix"].fillna("").str.lower() == prefix
            ) & (~self.df.index.isin(chosen + [seed_idx]))
            pr_df = self.df[mask].copy()
            pr_df["pop"] = pr_df["Like"] - pr_df["Dislike"]
            chosen.extend(
                pr_df.sort_values(["pop", "Like"], ascending=False)
                     .head(rem).index
            )

        # 5️⃣ Semantic fallback  – accept **only** same‑category hits
        if len(chosen) < k:
            need  = k - len(chosen)
            extra = self._similar(seed_idx, k=need * 3)   # cosine ≥ 0.40 already
            for idx in extra:
                if idx in chosen:
                    continue
                if self.df.at[idx, "General_Category"].strip().lower() != gen_cat:
                    continue      # 🛑 skip different category
                if has_kw:
                    kw_other = set(str(self.df.at[idx, "Keywords"]).lower().split(","))
                    if kw_seed and not (kw_seed & kw_other):
                        continue  # optional keyword screen
                chosen.append(idx)
                if len(chosen) == k:
                    break

        return chosen[:k]

    # ──────────────────────────────────────────
    # Layer B – trending in same General_Category
    # ──────────────────────────────────────────
    def _trending_in_cat(self, gen_cat: str, exclude: List[int], k: int = 5) -> List[int]:
        mask = (
            self.df["General_Category"].fillna("").str.lower() == gen_cat.lower()
        ) & (~self.df.index.isin(exclude))
        cat_df = self.df[mask].copy()
        cat_df["pop"] = cat_df["Like"] - cat_df["Dislike"]
        return cat_df.sort_values(["pop", "Like"], ascending=False).head(k).index.tolist()

    # ──────────────────────────────────────────
    # Author‑specific list
    # ──────────────────────────────────────────
    def _author_works(self, author: str, exclude: List[int], k: int = 5) -> List[int]:
        if not author or str(author).lower() == "nan":
            return []
        mask = (
            self.df["AUTHOR"].fillna("").str.lower() == author.lower()
        ) & (~self.df.index.isin(exclude))
        df = self.df[mask].copy()
        df["pop"] = df["Like"] - df["Dislike"]
        return df.sort_values(["pop", "Like"], ascending=False).head(k).index.tolist()

    # ──────────────────────────────────────────
    # Public interface
    # ──────────────────────────────────────────
    def recommend(
        self, seed_idx: int,
        n_similar: int = 5, n_trending: int = 5, n_author: int = 5
    ) -> Dict[str, List[int]]:

        sim_ids   = self._category_recs(seed_idx, k=n_similar)
        gen_cat   = self.df.at[seed_idx, "General_Category"]
        author    = self.df.at[seed_idx, "AUTHOR"]

        trend_ids = self._trending_in_cat(gen_cat,
                                          exclude=[seed_idx] + sim_ids,
                                          k=n_trending)

        author_ids = self._author_works(author,
                                        exclude=[seed_idx] + sim_ids + trend_ids,
                                        k=n_author)

        # Global fallback for trending
        if not trend_ids:
            pop_df = self.df.copy()
            pop_df["pop"] = pop_df["Like"] - pop_df["Dislike"]
            trend_ids = (pop_df
                         .drop(index=[seed_idx] + sim_ids, errors="ignore")
                         .sort_values(["pop", "Like"], ascending=False)
                         .head(n_trending).index.tolist())

        return {
            "similar":  sim_ids,
            "trending": trend_ids,
            "author":   author_ids
        }

    # ──────────────────────────────────────────
    # Pretty printer
    # ──────────────────────────────────────────
    def format_lines(self, indices: List[int], seed_idx: int) -> str:
        seed_vec = self.emb[seed_idx]
        out = []
        for idx in indices:
            row = self.df.loc[idx]
            sim = util.cos_sim(seed_vec, self.emb[idx]).item() * 100
            out.append(
                f"• *{row['TITLE']}* — {row.get('AUTHOR', 'Unknown')} "
                f"(Call: {row.get('CALL NUMBER', 'N/A')})\n"
                f"  👍 {int(row['Like'])}   👎 {int(row['Dislike'])} "
                f"| 📚 {row['General_Category']} | 🔗 {sim:4.1f}% similar"
            )
        return "\n".join(out)
