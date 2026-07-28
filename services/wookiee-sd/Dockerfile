FROM python:3.11-slim

# libgl/libglib are pulled in by pillow/transformers image handling; without
# them the first import fails at runtime rather than at build.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libgl1 libglib2.0-0 \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY requirements.txt ./
# CPU wheel index: keeps the image ~1.5GB instead of ~5GB by excluding the
# CUDA runtime, which is dead weight on a box with no GPU.
RUN pip install --no-cache-dir \
      --extra-index-url https://download.pytorch.org/whl/cpu \
      -r requirements.txt

COPY app.py ./

# Weights live on a volume, not in the image. ~2GB per checkpoint, they
# change independently of this code, and baking them in would mean
# re-downloading 4GB on every rebuild.
ENV HF_HOME=/models
ENV PORT=7860
EXPOSE 7860

# One worker on purpose - generation is serialised behind a lock anyway, and
# a second worker would double peak RAM for no throughput gain on CPU.
CMD ["sh", "-c", "uvicorn app:app --host 0.0.0.0 --port ${PORT} --workers 1"]
