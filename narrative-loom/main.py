"""
NarrativeLoom API v2.
Refactored for non-blocking task orchestration and observability.
"""
import os
from fastapi import FastAPI
from dotenv import load_dotenv

from core.logging import get_logger
from routers import chronicle, actors, scribe, system

load_dotenv()

log = get_logger(__name__)

app = FastAPI(
    title="NarrativeLoom API", 
    version="2.0.0",
    description="Decoupled Agentic Narrative Orchestration"
)

# Include modules
app.include_router(system.router, tags=["System"])
app.include_router(chronicle.router, prefix="/api/v1" if os.getenv("API_PREFIX") else "", tags=["Chronicle"])
app.include_router(actors.router, prefix="/api/v1" if os.getenv("API_PREFIX") else "", tags=["Actors"])
app.include_router(scribe.router, prefix="/api/v1" if os.getenv("API_PREFIX") else "", tags=["Scribe"])

# Note: We can add an additional legacy prefix if needed by Laravel backend.
# Current WorldOS backend seems to expect these at root based on previous main.py.

log.info("api.start", version="2.0.0")
