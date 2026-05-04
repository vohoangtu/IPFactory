import pytest
pytest.skip('Network-dependent test: requires LLM API connectivity.', allow_module_level=True)
import os
import sys
from utils.llm_factory import get_llm

def test_local_llm():
    print("--- TESTING LOCAL LLM CONNECTION ---")
    try:
        # Force 'local' provider
        llm = get_llm(provider="local", model_name="qwen2.5:7b")
        print(f"LLM Instance: {llm}")
        
        response = llm.invoke("Say 'NarrativeLoom Connected' if you can read this.")
        print(f"Response: {response.content}")
        
        if "NarrativeLoom Connected" in response.content:
            print("[SUCCESS] Local LLM is fully functional and reachable.")
        else:
            print("[WARNING] Received unexpected response, but connection worked.")
            
    except Exception as e:
        print(f"[ERROR] Failed to connect to local LLM: {str(e)}")
        sys.exit(1)

if __name__ == "__main__":
    test_local_llm()
