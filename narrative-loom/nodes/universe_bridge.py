from core.agent_wrapper import agent_node
from core.logging import get_logger
import redis
import os
import random
import json
from state import NarrativeState
from typing import Dict, Any

log = get_logger(__name__)

_redis_pool = None

def _get_redis():
    global _redis_pool
    if _redis_pool is None:
        redis_url = os.environ.get("REDIS_URL", "redis://redis:6379/0")
        _redis_pool = redis.ConnectionPool.from_url(redis_url, decode_responses=True)
    return redis.Redis(connection_pool=_redis_pool)

@agent_node("universe_bridge")
async def universe_bridge_node(state: NarrativeState, config: Dict[str, Any] = None) -> NarrativeState:
    """
    Universe Bridge Node: Lấy các tin tức/sự kiện từ các vũ trụ song song khác.
    Dùng để tạo hiệu ứng 'Deja Vu' hoặc 'Cross-Pollination' giữa các thế giới.
    """
    log.debug("bridge.running")

    r = _get_redis()

    whisper_key = "worldos:multiverse:whispers"
    current_world_id = state.get("world_id", 0)

    try:
        # 1. Lấy tất cả whispers hiện có
        all_whispers = r.lrange(whisper_key, 0, 50) # Lấy 50 cái mới nhất

        # 2. Lọc bỏ các whispers từ chính vũ trụ hiện tại (tránh loop)
        foreign_whispers = []
        for w_raw in all_whispers:
            try:
                w_data = json.loads(w_raw)
                if w_data.get("world_id") != current_world_id:
                    foreign_whispers.append(w_data.get("summary", ""))
            except (json.JSONDecodeError, ValueError, KeyError):
                continue

        # 3. Chọn ngẫu nhiên 2-3 whispers để đưa vào state
        selected_whispers = random.sample(foreign_whispers, min(len(foreign_whispers), 3))

        if selected_whispers:
            log.debug("bridge.whispers_found", count=len(selected_whispers))
        else:
            log.debug("bridge.no_whispers")

        return {"cross_pollination_whispers": selected_whispers}

    except Exception as e:
        log.warning("bridge.failed", error=str(e))
        return {"cross_pollination_whispers": []}

@agent_node("universe_bridge")
def record_universe_whisper(state: NarrativeState):
    """Hàm helper để ghi lại 'tiếng vọng' của vũ trụ này cho các thế giới khác"""
    r = _get_redis()
    
    whisper_key = "worldos:multiverse:whispers"
    
    # Chỉ ghi lại nếu câu chuyện đủ 'kịch tính' (như có Singularity)
    is_epic = state.get("singularity") is not None or state.get("phase_score", 0) > 0.8
    
    if is_epic:
        payload = {
            "world_id": state.get("world_id"),
            "era": state.get("world_era"),
            "summary": state.get("news_headline", "Một sự kiện kì lạ vừa diễn ra."),
            "timestamp": str(os.getenv("CURRENT_TICK", "0"))
        }
        # Đẩy vào list và giữ độ dài tối đa 100
        r.lpush(whisper_key, json.dumps(payload))
        r.ltrim(whisper_key, 0, 99)
        log.debug("bridge.epic_recorded")


