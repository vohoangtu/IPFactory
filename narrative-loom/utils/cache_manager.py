import os
import redis
import json
import hashlib
from typing import Any, Optional

from core.logging import get_logger

log = get_logger(__name__)

class WorldOsCacheManager:
    def __init__(self):
        self.redis_available = False
        self.redis_client = None
        # Tăng thời hạn Cache lên 800 ticks (hoặc lấy từ env) để tránh việc dệt lại quá thường xuyên
        self.ttl_ticks = int(os.getenv("CACHE_TTL_TICKS", 800))

        try:
            redis_url = os.getenv("REDIS_URL", "redis://localhost:6379/0")
            self.redis_client = redis.from_url(redis_url, decode_responses=True)
            self.redis_client.ping()
            self.redis_available = True
            log.info("cache.redis_connected")
        except Exception as e:
            log.warning("cache.redis_unavailable", error=str(e))

    def _get_key(self, world_id: int, prompt_hash: str) -> str:
        return f"worldos:loom:cache_v6:{world_id}:{prompt_hash}"

    def get_cached_narrative(self, world_id: int, current_tick: int, prompt: str) -> Optional[str]:
        if not self.redis_available:
            return None

        prompt_hash = hashlib.sha256(prompt.encode()).hexdigest()
        key = self._get_key(world_id, prompt_hash)

        cached = self.redis_client.get(key)
        if not cached:
            return None

        try:
            data = json.loads(cached)
            tick_produced = data.get("tick", 0)

            # Kiểm tra thời hạn 80 ticks
            if (current_tick - tick_produced) > self.ttl_ticks:
                log.debug("cache.expired", world_id=world_id, age_ticks=current_tick - tick_produced)
                self.redis_client.delete(key)
                return None

            log.debug("cache.hit", world_id=world_id, age_ticks=current_tick - tick_produced)
            return data.get("content")
        except Exception as e:
            log.warning("cache.parse_error", error=str(e))
            return None

    def set_cached_narrative(self, world_id: int, current_tick: int, prompt: str, content: str):
        if not self.redis_available:
            return

        prompt_hash = hashlib.sha256(prompt.encode()).hexdigest()
        key = self._get_key(world_id, prompt_hash)

        payload = {
            "content": content,
            "tick": current_tick,
            "hash": prompt_hash
        }

        # Redis TTL thực tế (fallback nếu simulation không tick) - 24 tiếng
        self.redis_client.set(key, json.dumps(payload), ex=86400)

        # Thêm vào tập hợp (Set) của world để dễ dàng invalidation
        self.redis_client.sadd(f"worldos:loom:indices:{world_id}", key)

    def invalidate_world_cache(self, world_id: int):
        """Xóa toàn bộ cache của một World cụ thể"""
        if not self.redis_available:
            return

        index_key = f"worldos:loom:indices:{world_id}"
        keys_to_del = self.redis_client.smembers(index_key)

        if keys_to_del:
            self.redis_client.delete(*keys_to_del)

        self.redis_client.delete(index_key)
        log.info("cache.invalidated", world_id=world_id)

# Singleton instance
cache_manager = WorldOsCacheManager()
