"""
Agent node middleware for Narrative Loom.

The `agent_node` decorator wraps any LangGraph node function with:
  1. Structured logging (structlog)
  2. Centrifugo publish on start / done / error
  3. Tenacity retry (up to 3 attempts, exponential backoff)
  4. Duration tracking

Usage in agent files:
    from core.agent_wrapper import agent_node

    @agent_node("historian")
    async def historian_agent(state: NarrativeState, config=None) -> NarrativeState:
        ...
"""
import os
import time
from functools import wraps
from typing import Any, Callable, Coroutine

from tenacity import (
    retry,
    retry_if_exception_type,
    stop_after_attempt,
    wait_exponential,
)

from core.centrifugo import (
    publish_agent_done,
    publish_agent_error,
    publish_agent_started,
)
from core.exceptions import TransientLLMError
from core.logging import get_logger
from core.metrics import metrics

log = get_logger(__name__)

# Number of agents in the full pipeline — used for progress % calculation
TOTAL_AGENTS = int(os.getenv("LOOM_TOTAL_AGENTS", "18"))

# Exception types that are worth retrying (transient LLM errors)
_RETRYABLE = (TransientLLMError, ConnectionError, TimeoutError)


def _make_retrying(fn: Callable) -> Callable:
    """Wrap async callable with tenacity retry policy."""
    name = fn.__name__

    def _before_sleep(retry_state):
        exc = retry_state.outcome.exception()
        err_str = str(exc).lower()
        if any(k in err_str for k in ("401", "403", "429", "unauthorized", "rate limit", "quota")):
            from utils.llm_factory import _pool_key_cache
            _pool_key_cache.clear()
            log.info("agent.pool_cache_cleared", agent=name, reason="auth_or_rate_limit_error", attempt=retry_state.attempt_number)
        wait_time = retry_state.next_action.sleep
        log.warning("agent.retry", agent=name, attempt=retry_state.attempt_number, wait=wait_time, error=str(exc))

    @retry(
        stop=stop_after_attempt(3),
        wait=wait_exponential(multiplier=1, min=1),
        retry=retry_if_exception_type(_RETRYABLE),
        before_sleep=_before_sleep,
        reraise=True,
    )
    @wraps(fn)
    async def _inner(*args, **kwargs):
        return await fn(*args, **kwargs)

    return _inner


def agent_node(name: str) -> Callable:
    """
    Decorator factory.

    @agent_node("historian")
    async def historian_agent(state, config=None) -> NarrativeState: ...
    """

    def decorator(fn: Callable[..., Coroutine[Any, Any, Any]]) -> Callable:
        @wraps(fn)
        async def wrapper(state: dict, config: dict | None = None) -> dict:
            world_id: int = state.get("world_id", 0)
            task_id: str = state.get("task_id", "unknown")
            completed: list = state.get("completed_agents", [])

            log.info("agent.start", agent=name, world_id=world_id, task_id=task_id)
            publish_agent_started(world_id, task_id, name)

            t_start = time.perf_counter()

            try:
                result: dict = await fn(state, config)
                
                duration_ms = int((time.perf_counter() - t_start) * 1000)
                metrics.record_agent(name, duration_ms, success=True)
                new_completed = completed + [name]

                log.info(
                    "agent.done",
                    agent=name,
                    world_id=world_id,
                    task_id=task_id,
                    duration_ms=duration_ms,
                    completed=len(new_completed),
                    total=TOTAL_AGENTS,
                )
                publish_agent_done(
                    world_id, task_id, name, duration_ms, len(new_completed), TOTAL_AGENTS
                )

                # Only return the keys the node actually modified.
                # Never merge full state or add per-node metadata to avoid
                # InvalidUpdateError when multiple nodes run in parallel.
                return result

            except Exception as exc:
                duration_ms = int((time.perf_counter() - t_start) * 1000)
                log.error(
                    "agent.error",
                    agent=name,
                    world_id=world_id,
                    task_id=task_id,
                    duration_ms=duration_ms,
                    error=str(exc),
                )
                metrics.record_agent(name, duration_ms, success=False)
                publish_agent_error(world_id, task_id, name, str(exc))
                raise

        return wrapper

    return decorator
