"""
Langfuse observability setup for Narrative Loom.

Creates a CallbackHandler per LangGraph run so every LLM call, tool
invocation, and chain step is traced as a Langfuse Span inside the
parent Trace identified by `task_id`.

Usage in Celery task:
    from core.langfuse_setup import get_langfuse_callback, flush_langfuse
    cb = get_langfuse_callback(task_id=task_id, world_id=world_id)
    result = await loom_app.ainvoke(state, config={"callbacks": [cb]})
    flush_langfuse()
"""
import os

from core.logging import get_logger

log = get_logger(__name__)

_LANGFUSE_SECRET_KEY = os.getenv("LANGFUSE_SECRET_KEY", "")
_LANGFUSE_PUBLIC_KEY = os.getenv("LANGFUSE_PUBLIC_KEY", "")
_LANGFUSE_HOST = os.getenv("LANGFUSE_HOST", "https://cloud.langfuse.com")
_ENABLED = bool(_LANGFUSE_SECRET_KEY and _LANGFUSE_PUBLIC_KEY)

# Langfuse client singleton (for flushing)
_langfuse_client = None


def _get_langfuse_client():
    global _langfuse_client
    if _langfuse_client is None and _ENABLED:
        try:
            from langfuse import Langfuse

            _langfuse_client = Langfuse(
                secret_key=_LANGFUSE_SECRET_KEY,
                public_key=_LANGFUSE_PUBLIC_KEY,
                host=_LANGFUSE_HOST,
            )
        except Exception as exc:
            log.warning("langfuse.init_failed", error=str(exc))
    return _langfuse_client


def get_langfuse_callback(task_id: str, world_id: int | str | None = None):
    """
    Return a Langfuse CallbackHandler for use as a LangChain callback.
    Returns None if Langfuse is not configured — callers should filter.
    """
    if not _ENABLED:
        log.debug("langfuse.disabled_no_keys")
        return None

    try:
        from langfuse.callback import CallbackHandler

        session_id = f"world-{world_id}" if world_id else None
        handler = CallbackHandler(
            secret_key=_LANGFUSE_SECRET_KEY,
            public_key=_LANGFUSE_PUBLIC_KEY,
            host=_LANGFUSE_HOST,
            trace_id=task_id,
            session_id=session_id,
            tags=["narrative-loom", f"world-{world_id}"],
        )
        log.debug("langfuse.callback_created", task_id=task_id, world_id=world_id)
        return handler
    except Exception as exc:
        log.warning("langfuse.callback_failed", error=str(exc))
        return None


def flush_langfuse() -> None:
    """Flush pending Langfuse events — call at end of each Celery task."""
    client = _get_langfuse_client()
    if client:
        try:
            client.flush()
        except Exception as exc:
            log.warning("langfuse.flush_failed", error=str(exc))
