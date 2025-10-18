import sys
import json
import faiss
import numpy as np
import pickle
import os
from sentence_transformers import SentenceTransformer

def main():
    if len(sys.argv) < 5:
        print("Usage: python3 semantic_match_faiss.py <candidate_text> <index_path> <ids_path> <filtered_job_ids_json>")
        sys.exit(1)

    candidate_text = sys.argv[1]
    index_path = sys.argv[2]
    ids_path = sys.argv[3]
    filtered_job_ids_path = sys.argv[4]

    # Modell laden
    model_path = os.path.join(os.environ.get('TRANSFORMERS_CACHE', '/opt/venv/transformers_cache'), 'all-MiniLM-L6-v2')
    model = SentenceTransformer(model_path)

    # FAISS Index laden
    index = faiss.read_index(index_path)

    # Alle Job IDs laden
    with open(ids_path, "rb") as f:
        job_ids = pickle.load(f)

    # Nur die gefilterten Job IDs (nach Ort) verwenden
    with open(filtered_job_ids_path, "r") as f:
        filtered_job_ids = json.load(f)

    # Indices der gefilterten Jobs im FAISS-Index finden
    filtered_indices = [i for i, jid in enumerate(job_ids) if jid in filtered_job_ids]
    if not filtered_indices:
        print(json.dumps([]))
        return

    # Vektoren für gefiltertes Subset rekonstruieren
    subset_vectors = np.array([index.reconstruct(i) for i in filtered_indices], dtype='float32')
    faiss.normalize_L2(subset_vectors)

    # Candidate embedding
    cand_emb = model.encode(candidate_text, normalize_embeddings=True).astype('float32')
    cand_emb = np.expand_dims(cand_emb, axis=0)

    # Cosine Similarity
    scores = np.dot(cand_emb, subset_vectors.T)
    top_k = 10
    top_idx = np.argsort(-scores[0])[:top_k]

    # Ergebnisse
    results = [{"id": filtered_job_ids[i], "score": float(scores[0][i])} for i in top_idx]
    print(json.dumps(results, ensure_ascii=False))

if __name__ == "__main__":
    main()
