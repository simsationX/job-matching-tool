import sys
import json
import os
import pickle
import faiss
import numpy as np
from sentence_transformers import SentenceTransformer
from tqdm import tqdm

# Argumente
batch_file = sys.argv[1]
faiss_index_file = sys.argv[2]
job_ids_file = sys.argv[3]

# Modell laden
model_path = os.path.join(os.environ.get('TRANSFORMERS_CACHE', '/opt/venv/transformers_cache'), 'all-MiniLM-L6-v2')
model = SentenceTransformer(model_path)

# Zielordner anlegen
os.makedirs(os.path.dirname(faiss_index_file), exist_ok=True)

# Index laden oder neu anlegen
if os.path.exists(faiss_index_file):
    index = faiss.read_index(faiss_index_file)
    with open(job_ids_file, "rb") as f:
        job_ids = pickle.load(f)
    print(f"Loaded existing FAISS index with {len(job_ids)} jobs")
else:
    index = None
    job_ids = []
    print("Creating new FAISS index")

# Batch laden
with open(batch_file, "r") as f:
    jobs = json.load(f)

# Embeddings berechnen
texts = [f"{job['position']} {job['description']}".strip() for job in jobs]
embeddings = model.encode(texts, convert_to_numpy=True, show_progress_bar=True)
faiss.normalize_L2(embeddings)

# FAISS Index initialisieren (falls neu)
if index is None:
    index = faiss.IndexFlatIP(embeddings.shape[1])

# Daten hinzufügen
index.add(embeddings)
job_ids.extend([job["id"] for job in jobs])

# Speichern
faiss.write_index(index, faiss_index_file)
with open(job_ids_file, "wb") as f:
    pickle.dump(job_ids, f)

print(f"✅ Added {len(jobs)} jobs (total {len(job_ids)})")
