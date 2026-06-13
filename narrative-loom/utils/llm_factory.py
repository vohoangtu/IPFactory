import json
import os
from typing import Any, Optional
import httpx

import redis
from langchain.globals import set_llm_cache
from langchain_community.cache import RedisCache, RedisSemanticCache
from langchain_community.embeddings.fastembed import FastEmbedEmbeddings
from langchain_anthropic import ChatAnthropic
from langchain_core.caches import BaseCache
from langchain_core.language_models.chat_models import BaseChatModel
from langchain_google_genai import ChatGoogleGenerativeAI
from langchain_openai import ChatOpenAI

from core.logging import get_logger
from core.exceptions import TransientLLMError
from utils.cache_manager import cache_manager
import time

log = get_logger(__name__)

_pool_key_cache: dict = {}
_POOL_KEY_TTL_SECONDS = int(os.getenv("LOOM_POOL_KEY_TTL", "300"))


class TickBasedCache(BaseCache):
    """LangChain-compatible cache with tick lifespan awareness."""

    def __init__(self, world_id: int, current_tick: int, provider: str = "unknown"):
        self.world_id = world_id
        self.current_tick = current_tick
        self.provider = provider

    def lookup(self, prompt: str, llm_string: str) -> Optional[Any]:
        full_query = f"v1:{self.provider}:{llm_string}:{prompt}"
        return cache_manager.get_cached_narrative(self.world_id, self.current_tick, full_query)

    def update(self, prompt: str, llm_string: str, return_val: Any) -> None:
        full_query = f"v1:{self.provider}:{llm_string}:{prompt}"
        if hasattr(return_val, "content"):
            cache_manager.set_cached_narrative(self.world_id, self.current_tick, full_query, return_val.content)

    def clear(self, **kwargs: Any) -> None:
        cache_manager.invalidate_world_cache(self.world_id)


def get_llm_for_agent(
    agent_id: str,
    world_id: int = None,
    current_tick: int = None,
    ai_runtime: dict | None = None,
) -> BaseChatModel:
    """Dynamic routing for internal Narrative Loom agents."""
    runtime = _normalize_ai_runtime(ai_runtime)

    if runtime and runtime.get("api_key"):
        log.debug("llm.routing_runtime", agent=agent_id, provider=runtime["provider"], model=runtime.get("model_name"), world_id=world_id, tick=current_tick)
        return get_llm(
            provider=runtime["provider"],
            model_name=runtime.get("model_name"),
            world_id=world_id,
            current_tick=current_tick,
            api_key=runtime.get("api_key"),
            base_url=runtime.get("base_url"),
        )

    # Pool-first resolution via backend API — always pass provider+model from config
    # so the backend respects the agent's configured model instead of falling back
    # to a hard-coded default.
    config_path = os.path.join(os.path.dirname(__file__), "..", "configs", "agent_routing.json")
    routing_provider = None
    routing_model = None
    try:
        with open(config_path, "r", encoding="utf-8") as fh:
            routing = json.load(fh)
        agent_cfg = routing.get(agent_id, routing.get("failover", {}))
        routing_provider = agent_cfg.get("provider")
        routing_model = agent_cfg.get("model")
    except Exception:
        pass

    try:
        pool_data = _get_pool_key(agent_id, provider=routing_provider, model=routing_model)
        log.debug("llm.routing_pool", agent=agent_id, provider=pool_data["provider"], model=pool_data["model"], world_id=world_id, tick=current_tick)
        return get_llm(
            provider=pool_data["provider"],
            model_name=pool_data["model"],
            world_id=world_id,
            current_tick=current_tick,
            api_key=pool_data["api_key"],
            base_url=pool_data.get("base_url"),
        )
    except Exception as pool_error:
        log.warning("llm.routing_fallback_db", agent=agent_id, error=str(pool_error))

        backend_url = os.getenv("WORLDOS_API_URL", "http://nginx/api")
        try:
            response = httpx.get(f"{backend_url}/ai-settings/loom-agents", timeout=5.0)
            response.raise_for_status()
            agents = response.json()
            agent_data = next((a for a in agents if a.get("key") == f"loom_agents.{agent_id}"), None)
            if not agent_data:
                raise Exception(f"Agent config not found: {agent_id}")
            agent_config = agent_data.get("value", {})
            provider = agent_config.get("provider", "openai")
            model = agent_config.get("model")
            pool_data2 = _get_pool_key(agent_id, provider=provider, model=model)
            log.debug("llm.routing_db_then_pool", agent=agent_id, provider=provider, model=model, world_id=world_id, tick=current_tick)
            return get_llm(
                provider=pool_data2["provider"],
                model_name=pool_data2["model"],
                world_id=world_id,
                current_tick=current_tick,
                api_key=pool_data2["api_key"],
                base_url=pool_data2.get("base_url"),
            )
        except Exception as db_error:
            log.warning("llm.routing_fallback_file", agent=agent_id, error=str(db_error))
            config_path = os.path.join(os.path.dirname(__file__), "..", "configs", "agent_routing.json")
            try:
                with open(config_path, "r", encoding="utf-8") as fh:
                    routing = json.load(fh)
                agent_config = routing.get(agent_id, routing.get("failover", {}))
                provider = agent_config.get("provider", "openai")
                model = agent_config.get("model")
                pool_data3 = _get_pool_key(agent_id, provider=provider, model=model)
                log.debug("llm.routing_file_then_pool", agent=agent_id, provider=provider, model=model, world_id=world_id, tick=current_tick)
                return get_llm(
                    provider=pool_data3["provider"],
                    model_name=pool_data3["model"],
                    world_id=world_id,
                    current_tick=current_tick,
                    api_key=pool_data3["api_key"],
                    base_url=pool_data3.get("base_url"),
                )
            except Exception:
                log.warning("llm.routing_fallback_local", agent=agent_id)
                return get_llm(provider="local", world_id=world_id, current_tick=current_tick)


def _get_pool_key(agent_id: str, provider: str | None = None, model: str | None = None, exclude_key_id: int | None = None) -> dict:
    """Resolve an API key from the backend AI Key Pool."""
    cache_key = (agent_id, provider, model)
    now = time.time()

    if not exclude_key_id and cache_key in _pool_key_cache:
        cached = _pool_key_cache[cache_key]
        if cached.get("expires_at", 0) > now:
            log.debug("llm.pool_key_cache_hit", agent=agent_id, provider=provider, model=model)
            return cached
        del _pool_key_cache[cache_key]

    backend_url = os.getenv("WORLDOS_API_URL", "http://nginx/api")
    try:
        response = httpx.post(
            f"{backend_url}/ai-settings/loom-key",
            json={
                "agent_id": agent_id,
                "provider": provider,
                "model": model,
                "exclude_key_id": exclude_key_id,
            },
            headers={"X-Loom-Secret": os.getenv("LOOM_SHARED_SECRET", "")},
            timeout=10.0,
        )
        response.raise_for_status()
        data = response.json()
    except Exception as e:
        log.warning("llm.pool_key_failed", agent=agent_id, error=str(e))
        raise TransientLLMError(f"Failed to resolve pool key for {agent_id}: {e}") from e

    result = {
        "provider": data.get("provider", provider or "openai"),
        "model": data.get("model", model),
        "api_key": data.get("api_key"),
        "base_url": data.get("base_url"),
        "key_entry_id": data.get("key_entry_id"),
        "expires_at": now + _POOL_KEY_TTL_SECONDS,
    }

    if not result["api_key"]:
        raise TransientLLMError(f"Pool returned empty api_key for agent {agent_id}")

    _pool_key_cache[cache_key] = result
    log.debug("llm.pool_key_fetched", agent=agent_id, provider=result["provider"], model=result["model"])
    return result


def _normalize_ai_runtime(ai_runtime: dict | None) -> dict[str, str] | None:
    if not isinstance(ai_runtime, dict):
        return None

    provider = ai_runtime.get("provider")
    if not isinstance(provider, str) or not provider.strip():
        return None

    runtime: dict[str, str] = {"provider": provider.strip()}

    for key in ("model_name", "api_key", "base_url"):
        value = ai_runtime.get(key)
        if isinstance(value, str) and value.strip():
            runtime[key] = value.strip()

    return runtime


def get_llm(
    provider: str = "openai",
    model_name: str = None,
    world_id: int = None,
    current_tick: int = None,
    api_key: str = None,
    base_url: str = None,
) -> BaseChatModel:
    log.debug("llm.get", provider=provider, model=model_name, world_id=world_id, tick=current_tick, has_custom_key=bool(api_key))

    provider = provider.lower().strip()
    if provider == "gemini":
        provider = "google"

    effective_api_key = api_key

    if os.getenv("SEMANTIC_CACHE_ENABLED") == "true":
        redis_url = os.getenv("REDIS_URL", "redis://redis:6379/0")
        try:
            embeddings = FastEmbedEmbeddings(model_name="BAAI/bge-base-en-v1.5")
            set_llm_cache(
                RedisSemanticCache(
                    redis_url=redis_url,
                    embedding=embeddings,
                    score_threshold=0.96,
                )
            )
        except Exception as e:
            log.warning("llm.semantic_cache_failed", error=str(e))
            try:
                set_llm_cache(RedisCache(redis.from_url(redis_url)))
            except Exception:
                pass

    cache = TickBasedCache(world_id, current_tick, provider=provider) if world_id is not None and current_tick is not None else None

    if provider == "openai":
        return ChatOpenAI(
            model_name=model_name or "gpt-4o",
            temperature=0.7,
            api_key=effective_api_key,
            base_url=base_url,
            timeout=int(os.getenv("LOOM_LLM_TIMEOUT", "20")),
            cache=cache,
        )

    if provider == "zai":
        return ChatOpenAI(
            model_name=model_name or "GLM-4.5-Flash",
            temperature=0.7,
            api_key=effective_api_key,
            base_url=base_url or "https://api.z.ai/api/paas/v4",
            timeout=int(os.getenv("LOOM_LLM_TIMEOUT", "20")),
            cache=cache,
        )

    if provider == "anthropic":
        return ChatAnthropic(
            model_name=model_name or "claude-3-opus-20240229",
            temperature=0.7,
            api_key=effective_api_key,
            cache=cache,
        )

    if provider == "google":
        return ChatGoogleGenerativeAI(
            model=model_name or "gemini-1.5-pro-latest",
            temperature=0.7,
            google_api_key=effective_api_key,
            cache=cache,
        )

    if provider == "local":
        local_url = os.getenv("LOCAL_LLM_URL", "http://localhost:1234").strip().rstrip("/")
        if local_url and not (local_url.startswith("http://") or local_url.startswith("https://")):
            local_url = "http://" + local_url

        model = model_name or os.getenv("LOCAL_MODEL_NAME", "qwen3.5-9b-uncensored-hauhaucs-aggressive")

        if "/v1" in local_url:
            log.debug("llm.local", url=local_url, model=model)
            return ChatOpenAI(
                base_url=local_url,
                model=model,
                temperature=0.7,
                api_key="not-needed",
                timeout=int(os.getenv("LOCAL_LLM_TIMEOUT", "360")),
            )

        log.debug("llm.local", url=local_url, model=model)
        return ChatOpenAI(
            base_url=f"{local_url}/api/v1",
            model=model,
            temperature=0.7,
            api_key="not-needed",
            timeout=int(os.getenv("LOCAL_LLM_TIMEOUT", "360")),
        )

    if provider == "openrouter":
        or_key = effective_api_key
        if not or_key:
            log.warning("llm.openrouter_no_key")
            return get_llm(provider="local", model_name=model_name, world_id=world_id, current_tick=current_tick)

        return ChatOpenAI(
            model_name=model_name or "google/gemini-flash-1.5",
            base_url=base_url or "https://openrouter.ai/api/v1",
            api_key=or_key,
            temperature=0.7,
            timeout=30,
            default_headers={
                "HTTP-Referer": "https://worldos.v6",
                "X-Title": "WorldOS Narrative Loom",
            },
            cache=cache,
        )

    if provider in ("alibaba", "dashscope", "qwen"):
        dashscope_key = effective_api_key
        return ChatOpenAI(
            model_name=model_name or "qwen-max",
            base_url=base_url or "https://dashscope.aliyuncs.com/compatible-mode/v1",
            api_key=dashscope_key,
            temperature=0.7,
            timeout=int(os.getenv("LOOM_LLM_TIMEOUT", "20")),
            cache=cache,
        )

    raise ValueError(f"Provider {provider} chưa được hỗ trợ.")
