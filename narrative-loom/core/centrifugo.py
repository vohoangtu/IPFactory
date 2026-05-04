"""
Centrifugo HTTP API publisher for Narrative Loom.

Uses `httpx` to publish pipeline progress events to Centrifugo
channels so the frontend can display real-time agent status via WebSocket.

Channel naming convention:
    narrative:{world_id}:{task_id}

Event types (field: `type`):
    pipeline_started   — pipeline kicked off by Celery worker
    agent_started      — an individual agent node has begun
    agent_done         — agent completed successfully
    agent_error        — agent raised an exception
    pipeline_progress  — aggregate progress (completed/total)
    pipeline_done      — entire pipeline completed, carries final output
    pipeline_error     — pipeline failed, carries error message
"""
import os
import time
from typing import Any

import httpx
from core.logging import get_logger

log = get_logger(__name__)

_CENTRIFUGO_URL = os.getenv("CENTRIFUGO_URL", "http://centrifugo:8000")
_CENTRIFUGO_API_KEY = os.getenv("CENTRIFUGO_API_KEY", os.getenv("CENTRIFUGO_KEY", ""))


def _channel(world_id: int | str, task_id: str) -> str:
    return f"narrative:{world_id}:{task_id}"


def publish_event(
    world_id: int | str,
    task_id: str,
    event_type: str,
    payload: dict[str, Any] | None = None,
) -> None:
    """
    Publish a pipeline event to the Centrifugo channel.
    Never raises — logs the error and returns silently so the pipeline
    is never blocked by a Centrifugo connectivity issue.
    """
    if not _CENTRIFUGO_API_KEY:
        log.debug("centrifugo.skip_no_key", event_type=event_type)
        return

    data: dict[str, Any] = {
        "type": event_type,
        "ts": int(time.time() * 1000),  # ms epoch
        **(payload or {}),
    }

    try:
        channel = _channel(world_id, task_id)
        response = httpx.post(
            f"{_CENTRIFUGO_URL}/api",
            headers={"Authorization": f"apikey {_CENTRIFUGO_API_KEY}"},
            json={"method": "publish", "params": {"channel": channel, "data": data}},
            timeout=5.0,
        )
        response.raise_for_status()
        log.info("centrifugo.published", channel=channel, event_type=event_type)
    except Exception as exc:
        log.warning(
            "centrifugo.publish_failed",
            event_type=event_type,
            world_id=world_id,
            task_id=task_id,
            error=str(exc),
        )


# ── Typed helper shortcuts ──────────────────────────────────────────────

def publish_pipeline_started(world_id: int, task_id: str, total_agents: int) -> None:
    publish_event(world_id, task_id, "pipeline_started", {"total_agents": total_agents})


def publish_agent_started(world_id: int, task_id: str, agent: str) -> None:
    publish_event(world_id, task_id, "agent_started", {"agent": agent})


def publish_agent_done(
    world_id: int,
    task_id: str,
    agent: str,
    duration_ms: int,
    completed: int,
    total: int,
) -> None:
    publish_event(
        world_id,
        task_id,
        "agent_done",
        {
            "agent": agent,
            "duration_ms": duration_ms,
            "progress": {"completed": completed, "total": total, "pct": round(completed / max(total, 1) * 100)},
        },
    )


def publish_agent_error(world_id: int, task_id: str, agent: str, error: str) -> None:
    publish_event(world_id, task_id, "agent_error", {"agent": agent, "error": error})


def publish_pipeline_done(world_id: int, task_id: str, result: dict[str, Any]) -> None:
    publish_event(world_id, task_id, "pipeline_done", result)


def publish_pipeline_error(world_id: int, task_id: str, error: str) -> None:
    publish_event(world_id, task_id, "pipeline_error", {"error": error})
