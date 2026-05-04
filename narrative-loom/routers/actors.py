"""Actor and intent related routers."""
from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from typing import Dict, Any, List, Optional

from core.logging import get_logger
from agents.intent_agent import ActorIntentRequest, ActorIntentResponse, intent_agent
from agents.celebrity_synthesizer import celebrity_synthesizer_api
from agents.artifact_forger import artifact_forger_api

log = get_logger(__name__)
router = APIRouter()

@router.post("/actor-intent", response_model=ActorIntentResponse)
async def actor_intent(req: ActorIntentRequest):
    """
    Real-time LLM decision: nhận actor state + universe context.
    """
    try:
        return await intent_agent(req)
    except Exception as e:
        log.exception("intent.agent_failed", error=str(e))
        raise HTTPException(
            status_code=503,
            detail=f"Intent agent failed: {str(e)}"
        )

@router.post("/weave-celebrity")
async def api_weave_celebrity(req: dict):
    """Called by Laravel when CelebrityEmerged event fires."""
    try:
        return await celebrity_synthesizer_api(req)
    except Exception as e:
        log.exception("celebrity.failed", error=str(e))
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/forge-artifact")
async def api_forge_artifact(req: dict):
    """Called by Laravel when ArtifactDiscovered event fires."""
    try:
        return await artifact_forger_api(req)
    except Exception as e:
        log.exception("artifact.failed", error=str(e))
        raise HTTPException(status_code=500, detail=str(e))
