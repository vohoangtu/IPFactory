"""Chronicle weaving routers."""
import uuid
from fastapi import APIRouter, HTTPException, Request
from pydantic import BaseModel, field_validator
from typing import Optional, List, Dict, Any
import httpx
import os

from core.logging import get_logger
from core.celery_app import celery_app
from tasks.chronicle_task import weave_chronicle_task
from celery.result import AsyncResult
from utils.manifesto_loader import loader

log = get_logger(__name__)
router = APIRouter()

class ChronicleRequest(BaseModel):
    world_id: int
    world_era: str | None = "genesis"
    tick_start: int | None = None
    tick_end: int | None = None
    genre: str | None = "generic"
    power_system: str | None = None
    whispers: list[str] | None = []
    ai_runtime: Dict[str, Any] | None = None

    @field_validator("world_id")
    @classmethod
    def world_id_positive(cls, v):
        if v <= 0:
            raise ValueError("world_id must be positive")
        return v

    @field_validator("tick_end")
    @classmethod
    def tick_end_after_start(cls, v, info):
        tick_start = info.data.get("tick_start")
        if tick_start is not None and v is not None and v < tick_start:
            raise ValueError("tick_end must be >= tick_start")
        return v

@router.post("/weave-chronicles")
async def weave_chronicles(req: ChronicleRequest):
    """
    Submits a narrative weaving task to the Celery worker queue.
    Returns immediately with a task_id.
    """
    task_id = str(uuid.uuid4())
    log.info("chronicle.submit", world_id=req.world_id, task_id=task_id)

    backend_url = os.getenv("WORLDOS_API_URL", "http://nginx/api")
    
    # 1. Fetch raw chronicles from WorldOS (We still do this in the API layer 
    # for immediate validation, or we could move it to the worker)
    # Keeping it here for now to ensure data exists before queuing.
    async with httpx.AsyncClient(timeout=30.0) as client:
        try:
            response = await client.get(
                f"{backend_url}/loom/v1/narrative/chronicles",
                params={"world_id": req.world_id, "tick_start": req.tick_start, "tick_end": req.tick_end}
            )
            response.raise_for_status()
            data = response.json()
        except httpx.HTTPError as e:
            log.error("worldos.fetch_failed", error=str(e))
            raise HTTPException(status_code=500, detail=f"Failed to fetch from WorldOS: {str(e)}")

    # 2. Prepare initial state
    power_manifesto = loader.get_power_manifesto(req.power_system, req.world_era) if req.power_system else ""
    era_context = loader.get_era_context(req.world_era) if req.world_era else ""
    vfx_hints = loader.get_vfx_hints(req.world_era) if req.world_era else {}
    
    initial_state = {
        "world_id": req.world_id,
        "world_era": req.world_era or "genesis",
        "tick_start": req.tick_start,
        "tick_end": req.tick_end,
        "ai_runtime": req.ai_runtime or None,
        "genre": req.genre or "generic",
        "cross_pollination_whispers": req.whispers or [],
        "raw_chronicles": data.get("data", []),
        "historical_outline": "",
        "psychological_profiles": {},
        "storyboard": "",
        "final_prose": "",
        "feedback": {},
        "revision_count": 0,
        "current_agent": "system",
        "epistemic_noise": 0.0,
        "epistemic_tier": "Chân Thực",
        "resonance_scars": [],
        "power_system": req.power_system,
        "power_system_manifesto": power_manifesto,
        "era_context": era_context,
        "vfx_hints": vfx_hints,
        "task_id": task_id,
        "completed_agents": []
    }

    # 3. Dispatch to Celery
    weave_chronicle_task.apply_async(
        args=[initial_state, req.world_id, task_id],
        task_id=task_id
    )

    return {
        "message": "Narrative weaving task submitted.",
        "task_id": task_id,
        "world_id": req.world_id,
        "channel": f"narrative:{req.world_id}:{task_id}"
    }

@router.get("/tasks/{task_id}/status")
async def get_task_status(task_id: str):
    """
    Get the status and result of a narrative weaving task.
    """
    result = AsyncResult(task_id, app=celery_app)

    response = {
        "task_id": task_id,
        "status": result.status,
    }

    if result.ready():
        if result.successful():
            response["result"] = result.result
        else:
            response["error"] = str(result.result)

    return response

@router.get("/config/agent/{agent_id}")
async def get_agent_config(agent_id: str):
    """
    Get AI configuration for a specific Loom agent from backend database.
    """
    backend_url = os.getenv("WORLDOS_API_URL", "http://nginx/api")

    async with httpx.AsyncClient(timeout=10.0) as client:
        try:
            # Query all loom agents config from backend
            response = await client.get(
                f"{backend_url}/intelligence/ai-settings/loom-agents"
            )
            response.raise_for_status()
            agents = response.json()

            # Find the specific agent
            for agent in agents:
                if agent.get("agent_name") == agent_id:
                    return agent

            # If not found, return 404
            raise HTTPException(status_code=404, detail=f"Agent config not found: {agent_id}")

        except httpx.HTTPError as e:
            log.error("backend.fetch_failed", error=str(e))
            raise HTTPException(status_code=500, detail=f"Failed to fetch from backend: {str(e)}")
