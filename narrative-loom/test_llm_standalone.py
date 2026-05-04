import pytest
pytest.skip('Network-dependent test: requires LLM API connectivity.', allow_module_level=True)
import time
import os
import sys

# Thêm đường dẫn app để import được utils
sys.path.append('/app')

from utils.llm_factory import get_llm

start = time.time()
try:
    print("Initializing LLM...")
    llm = get_llm(provider='local', model_name='qwen2.5:7b')
    print("Sending request to local LLM...")
    res = llm.invoke('hi')
    print(f"Response: {res.content}")
except Exception as e:
    print(f"Error Type: {type(e)}")
    print(f"Error: {e}")
finally:
    print(f"Time taken: {time.time() - start:.2f}s")
