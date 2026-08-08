FROM python:3.12-slim

RUN pip install --no-cache-dir fastapi uvicorn httpx

WORKDIR /app
COPY claude_proxy.py .

EXPOSE 8082
CMD ["uvicorn", "claude_proxy:app", "--host", "0.0.0.0", "--port", "8082"]
