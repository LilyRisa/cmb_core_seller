# CLIP sidecar — embedding ẢNH cho visual search (SPEC 2026-06-16).
# Build: docker compose build clip   ·   Image ~1.5GB (torch CPU + model tải lúc chạy).
FROM python:3.11-slim

WORKDIR /app

# torch CPU-only (tránh kéo CUDA ~vài GB).
RUN pip install --no-cache-dir torch==2.2.2 --index-url https://download.pytorch.org/whl/cpu

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Cache model vào image (tải sẵn lúc build để khởi động nhanh & chạy offline).
ENV CLIP_MODEL=clip-ViT-B-32
RUN python -c "from sentence_transformers import SentenceTransformer; SentenceTransformer('clip-ViT-B-32')"

COPY app.py .

EXPOSE 8000
CMD ["uvicorn", "app:app", "--host", "0.0.0.0", "--port", "8000"]
