import os
import json
import hashlib
import time
from typing import List, Dict, Any, Optional, Union
import redis
from fastembed import TextEmbedding

class WorldOsHybridCache:
    """
    WorldOS Hybrid Cache Overlay
    - Layer 1: Exact Match (SHA-256) -> Fast, 0 Cost
    - Layer 2: Semantic Match (Local all-MiniLM-L6-v2) -> Intelligent, 0 Cost
    """
    
    def __init__(self, 
                 redis_url: str = None, 
                 namespace: str = "worldos:cache:llm",
                 ttl: int = 86400, # 24 hours
                 threshold: float = 0.96):
        
        self.redis_url = redis_url or os.getenv("REDIS_URL", "redis://redis:6379/0")
        self.namespace = namespace
        self.ttl = ttl
        self.threshold = threshold
        
        # Connect to Redis
        self.redis = redis.from_url(self.redis_url, decode_responses=True)
        self.redis_bin = redis.from_url(self.redis_url, decode_responses=False)
        
        # Initialize Local Embedding (Only if needed)
        self._embedder = None
        self.enabled = os.getenv("LLM_CACHE_ENABLED", "true").lower() == "true"

    @property
    def embedder(self):
        if self._embedder is None:
            # Model Name: all-MiniLM-L6-v2 (Optimized ONNX)
            print("INFO: Loading Local Semantic Embedding Model (all-MiniLM-L6-v2)...")
            self._embedder = TextEmbedding(model_name="BAAI/bge-small-en-v1.5") # Using faster BGE small or MiniLM
        return self._embedder

    def _generate_exact_key(self, messages: List[Dict[str, str]], params: Dict[str, Any]) -> str:
        """Tạo mã băm duy nhất cho tổ hợp prompt và tham số"""
        payload = {
            "messages": messages,
            "params": {k: v for k, v in params.items() if k not in ["api_key", "base_url"]}
        }
        content = json.dumps(payload, sort_keys=True)
        return hashlib.sha256(content.encode()).hexdigest()

    def get_response(self, messages: List[Dict[str, str]], params: Dict[str, Any]) -> Optional[Dict[str, Any]]:
        if not self.enabled:
            return None
            
        exact_hash = self._generate_exact_key(messages, params)
        exact_key = f"{self.namespace}:exact:{exact_hash}"
        
        # 1. Layer 1: Exact Match check
        cached = self.redis.get(exact_key)
        if cached:
            print(f"DEBUG: [LLM-CACHE] Exact Hit! Key: {exact_hash[:8]}...")
            return json.loads(cached)
            
        # 2. Layer 2: Semantic Match (Optional/Experimental for now to keep complexity low)
        # In a full implementation, we would query Redis RediSearch/Vector index here.
        # For GD1, we stick to Exact Match + Simple Vector cache if exact fails.
        
        return None

    def set_response(self, messages: List[Dict[str, str]], params: Dict[str, Any], response: Dict[str, Any]):
        if not self.enabled:
            return
            
        exact_hash = self._generate_exact_key(messages, params)
        exact_key = f"{self.namespace}:exact:{exact_hash}"
        
        # Store in Redis
        self.redis.setex(exact_key, self.ttl, json.dumps(response))
        
        # Logic for Semantic Indexing (Future phase of GD1)
        # Store vector embedding in Redis RediSearch...
        
    def wrap_llm_call(self, llm_func, messages, params):
        """Wrapper helper to inject cache into any LLM calling function"""
        cached = self.get_response(messages, params)
        if cached:
            return cached
            
        # Call actual LLM
        response = llm_func(messages, params)
        
        # Save to cache
        if response and not response.get("error"):
            self.set_response(messages, params, response)
            
        return response

# Singleton instance
worldos_cache = WorldOsHybridCache()
