from core.agent_wrapper import agent_node
from core.logging import get_logger
import asyncio
from typing import Dict, Any
from state import NarrativeState
from utils.memory_manager import EpisodicMemoryManager
from utils.llm_factory import _get_pool_key, get_llm_for_agent

log = get_logger(__name__)


@agent_node("archivist")
async def archivist_agent(state: NarrativeState, config: Dict[str, Any] = None) -> NarrativeState:
    log.info("agent.run", agent="archivist")

    prose = state.get("final_prose", "")

    # Lazy init memory manager with pool key
    memory_db = None
    pool_data = None
    try:
        pool_data = _get_pool_key("archivist")
        memory_db = EpisodicMemoryManager(api_key=pool_data.get("api_key"))
    except Exception as e:
        log.warning("archivist.pool_key_failed", error=str(e))
        memory_db = EpisodicMemoryManager()

    if prose and memory_db.enabled:
        # Lấy metadata
        world_id = state.get("world_id", 0)
        tick_start = state.get("tick_start", 0)
        tick_end = state.get("tick_end", 0)

        # Gom góp actor ids
        events = state.get("normalized_events", [])
        actors = set()
        for e in events:
            for a in e.get("actors", []):
                actors.add(str(a))

        # Resolve model name dynamically instead of hardcoding
        model_name = "unknown"
        try:
            llm = get_llm_for_agent("archivist", world_id=world_id)
            model_name = getattr(llm, "model_name", getattr(llm, "model", "unknown"))
        except Exception:
            if pool_data:
                model_name = pool_data.get("model", "unknown")

        metadata = {
            "world_id": world_id,
            "tick_start": tick_start if tick_start is not None else 0,
            "tick_end": tick_end if tick_end is not None else 0,
            "actors": ",".join(list(actors)),
            "agent": model_name
        }

        await asyncio.to_thread(memory_db.store_memory, prose, metadata)
        log.debug("agent.detail", agent="archivist", model=model_name, stage="memory_stored")
    elif not memory_db.enabled:
        log.warning("agent.warning", agent="archivist", reason="memory_db_disabled_missing_vector_db_libraries")

    return {}
