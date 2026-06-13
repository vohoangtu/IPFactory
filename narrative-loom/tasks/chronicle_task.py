"""
Chronicle weaving Celery task.

Executes the LangGraph parallel pipeline synchronously within a Celery worker,
but uses asyncio internally since LangGraph nodes are async.
Publishes overall pipeline state via Centrifugo.
"""
import asyncio
import os
from typing import Any

import httpx
from celery import Task
from celery.exceptions import SoftTimeLimitExceeded

from core.celery_app import celery_app
from core.centrifugo import publish_pipeline_done, publish_pipeline_error, publish_pipeline_started
from core.langfuse_setup import flush_langfuse, get_langfuse_callback
from core.logging import get_logger
from graph_builder import build_graph

log = get_logger(__name__)

# Backend webhook URL configuration.
# Trỏ tới endpoint Narrative (LoomWebhookController::receive) — được thiết kế đúng cho
# payload này — thay vì bản WorldOS (yêu cầu auth:sanctum mà worker không có token).
# Bảo vệ bằng shared secret qua header X-Loom-Secret.
BACKEND_WEBHOOK_URL = os.getenv("BACKEND_WEBHOOK_URL", "http://nginx/api/narrative-loom/webhook")

# Compile graph once per worker process instead of per task
# ensuring it's ready in memory
try:
    _loom_app = build_graph()
except Exception as e:
    log.error("graph.build_failed", error=str(e))
    # Fallback to importing graph from module if needed
    from graph_builder import app as _loom_app


@celery_app.task(
    bind=True,
    name="tasks.weave_chronicle_task",
    queue="narrative",
    # Give it plenty of time, but enforce limits
    soft_time_limit=1800,  # 30 mins
    time_limit=1860,       # 31 mins
    max_retries=1,         # mostly rely on node-level tenacity retry
)
def weave_chronicle_task(self: Task, initial_state: dict[str, Any], world_id: int, task_id: str) -> dict[str, Any]:
    """
    Executes the entire Narrative Loom generation pipeline in a background worker.
    """
    log.info("task.started", task_id=task_id, world_id=world_id)

    # Note: total_agents should roughly match the number of nodes in graph
    publish_pipeline_started(world_id, task_id, total_agents=18)

    # Initialize Langfuse tracking for this specific task
    langfuse_cb = get_langfuse_callback(task_id=task_id, world_id=world_id)
    callbacks = [langfuse_cb] if langfuse_cb else []

    try:
        # LangGraph nodes are async, so we must run the ainvoke inside an event loop
        result_state = asyncio.run(
            _loom_app.ainvoke(
                initial_state,
                config={"callbacks": callbacks}
            )
        )

        log.info("task.completed", task_id=task_id, world_id=world_id)
        
        # Extract the necessary payload to return/publish
        pipeline_result = {
            "task_id": task_id,
            "world_id": world_id,
            "historical_outline": result_state.get("historical_outline"),
            "storyboard": result_state.get("storyboard"),
            "final_prose": result_state.get("final_prose"),
            "news_headline": result_state.get("news_headline"),
            "news_slogan": result_state.get("news_slogan"),
            "vfx_config": result_state.get("vfx_config") or result_state.get("vfx_hints"),
            "completed_agents": result_state.get("completed_agents", []),
        }

        publish_pipeline_done(world_id, task_id, pipeline_result)

        # Call backend webhook to notify completion
        try:
            webhook_payload = {
                "type": "pipeline_done",
                "task_id": task_id,
                "world_id": world_id,
                "tick_start": initial_state.get("tick_start"),
                "tick_end": initial_state.get("tick_end"),
                **pipeline_result
            }
            with httpx.Client(timeout=10) as client:
                response = client.post(
                    BACKEND_WEBHOOK_URL,
                    json=webhook_payload,
                    headers={"X-Loom-Secret": os.getenv("LOOM_SHARED_SECRET", "")},
                )
                if response.status_code == 200:
                    log.info("webhook.success", task_id=task_id, status=response.status_code)
                else:
                    log.warning("webhook.failed", task_id=task_id, status=response.status_code)
        except Exception as e:
            log.error("webhook.error", task_id=task_id, error=str(e))
            # Không fail task nếu webhook thất bại

        # Trả về kết quả cho Celery Result Backend (Redis)
        return pipeline_result

    except SoftTimeLimitExceeded as exc:
        log.error("task.timeout", task_id=task_id, world_id=world_id)
        publish_pipeline_error(world_id, task_id, "Pipeline timed out after 30 minutes")
        raise

    except Exception as exc:
        log.exception("task.error", task_id=task_id, world_id=world_id, error=str(exc))
        publish_pipeline_error(world_id, task_id, str(exc))
        raise

    finally:
        # Ensure Langfuse events are sent to cloud before worker finishes the task
        flush_langfuse()
