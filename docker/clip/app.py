"""
CLIP sidecar — embedding ẢNH (và text, cross-modal) cho visual search.

Hợp đồng (ClipEmbedder PHP gọi):
  POST /embed  { "image_base64": "<base64>", "mime": "image/jpeg" }
            |  { "text": "..." }
       -> 200 { "vector": [float...], "dim": int, "model": "<modelKey>" }
  GET  /health -> 200 { "status": "ok" }

Vector được CHUẨN HOÁ (L2) để cosine trên Qdrant ổn định. Model mặc định
clip-ViT-B-32 (dim 512); đổi qua env CLIP_MODEL. modelKey (định danh collection
+ cột `model` phía app) lấy từ CLIP_MODEL_KEY — phải khớp IMAGE_EMBEDDING_MODEL.
"""
import base64
import io
import os

from fastapi import FastAPI, HTTPException
from PIL import Image
from pydantic import BaseModel
from sentence_transformers import SentenceTransformer

MODEL_NAME = os.environ.get("CLIP_MODEL", "clip-ViT-B-32")
MODEL_KEY = os.environ.get("CLIP_MODEL_KEY", "clip_vit_b32")

app = FastAPI(title="cmb-clip", version="1.0")
_model: SentenceTransformer | None = None


def model() -> SentenceTransformer:
    global _model
    if _model is None:
        _model = SentenceTransformer(MODEL_NAME)
    return _model


class EmbedRequest(BaseModel):
    image_base64: str | None = None
    mime: str | None = None
    text: str | None = None


@app.get("/health")
def health() -> dict:
    return {"status": "ok", "model": MODEL_KEY}


@app.post("/embed")
def embed(req: EmbedRequest) -> dict:
    if req.image_base64:
        try:
            raw = base64.b64decode(req.image_base64)
            img = Image.open(io.BytesIO(raw)).convert("RGB")
        except Exception as exc:  # noqa: BLE001
            raise HTTPException(status_code=400, detail=f"ảnh không hợp lệ: {exc}")
        payload = img
    elif req.text:
        payload = req.text
    else:
        raise HTTPException(status_code=400, detail="cần image_base64 hoặc text")

    vec = model().encode(payload, normalize_embeddings=True)
    vector = [float(x) for x in vec.tolist()]
    return {"vector": vector, "dim": len(vector), "model": MODEL_KEY}
