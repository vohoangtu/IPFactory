FROM python:3.11-slim

WORKDIR /app

# Install system deps (fastembed / chromadb cần)
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

# Non-root user
RUN useradd -r -s /bin/false appuser && chown -R appuser:appuser /app
USER appuser

EXPOSE 8001

# MODE env variable controls startup:
#   api    → uvicorn (default)
#   worker → celery worker
CMD ["sh", "-c", "\
  if [ \"$MODE\" = 'worker' ]; then \
    celery -A core.celery_app worker -Q narrative -c ${CELERY_CONCURRENCY:-2} --loglevel=info; \
  else \
    python -c 'import uvloop; uvloop.install()' 2>/dev/null || true; \
    uvicorn main:app --host 0.0.0.0 --port 8001 --loop uvloop; \
  fi"]
