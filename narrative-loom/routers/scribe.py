"""History scribe and asset generation routers."""
from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from typing import Dict, Any, List, Optional

from core.logging import get_logger
from agents.history_scribe import history_scribe_api
from agents.art_director import generate_visual_asset
from agents.audio_director import AudioDirectorAgent

log = get_logger(__name__)
router = APIRouter()

class ScribeHistoryRequest(BaseModel):
    event_type: str
    impact_score: float
    trigger_data: Dict[str, Any]
    world_id: int

@router.post("/scribe-history")
async def scribe_history(request: ScribeHistoryRequest):
    """Scribe History Event into Narrative via history_scribe"""
    try:
        state = {
            "event_type": request.event_type,
            "impact_score": request.impact_score,
            "trigger_data": request.trigger_data,
            "world_id": request.world_id,
        }
        result = await history_scribe_api(state)
        return {"message": "Success", "chronicle": result}
    except Exception as e:
        log.exception("scribe.failed", error=str(e))
        raise HTTPException(status_code=500, detail=str(e))

class PaintAssetRequest(BaseModel):
    prompt: str
    is_portrait: bool = True

@router.post("/paint-asset")
async def paint_asset(request: PaintAssetRequest):
    """Sinh ảnh từ mô tả text thông qua Art Director (DALL-E)"""
    try:
        url = await generate_visual_asset(request.prompt, request.is_portrait)
        if url:
            return {"message": "Success", "image_url": url}
        else:
            return {"message": "Failed", "image_url": None}
    except Exception as e:
        log.exception("paint.failed", error=str(e))
        raise HTTPException(status_code=500, detail=str(e))

class ComposeTrackRequest(BaseModel):
    epoch_name: str
    core_theme: str

@router.post("/compose-track")
async def compose_track(request: ComposeTrackRequest):
    """Sinh URL nhạc Ambient dựa trên chủ đề của Kỷ nguyên"""
    try:
        audio_director = AudioDirectorAgent()
        result = await audio_director.compose_soundtrack(request.epoch_name, request.core_theme)
        return result
    except Exception as e:
        log.exception("compose.failed", error=str(e))
        raise HTTPException(status_code=500, detail=str(e))
