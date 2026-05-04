import pytest
pytest.skip('Network-dependent test: requires LLM API connectivity.', allow_module_level=True)
import asyncio
import json
import os
import sys

# Thêm path để import local modules
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

from agents.historian import historian_agent
from agents.psychologist import psychologist_agent
from agents.director import director_agent
from agents.wordsmith import wordsmith_agent
from mock_data import get_mock_historian_state

async def run_historian(noise: float = 0.05):
    print(f">>> Stage 1: Testing Historian (Noise: {noise})...")
    state = get_mock_historian_state(noise)
    
    result = await historian_agent(state)
    with open("test_historian_output.json", "w", encoding="utf-8") as f:
        json.dump(result, f, ensure_ascii=False, indent=2)
    print(f"Historian Stage Done. Output saved to test_historian_output.json. Length: {len(result.get('historical_outline', ''))}")
    return result

async def run_psychologist(prev_state):
    print("\n>>> Stage 2: Testing Psychologist...")
    result = await psychologist_agent(prev_state)
    with open("test_psychologist_output.json", "w", encoding="utf-8") as f:
        json.dump(result, f, ensure_ascii=False, indent=2)
    print(f"Psychologist Stage Done. Output saved. Length: {len(str(result.get('psychological_profiles', '')))}")
    return result

async def run_director(prev_state):
    print("\n>>> Stage 3: Testing Director...")
    result = await director_agent(prev_state)
    with open("test_director_output.json", "w", encoding="utf-8") as f:
        json.dump(result, f, ensure_ascii=False, indent=2)
    print(f"Director Stage Done. Output saved. Length: {len(result.get('storyboard', ''))}")
    return result

async def run_wordsmith(prev_state):
    print("\n>>> Stage 4: Testing Wordsmith...")
    result = await wordsmith_agent(prev_state)
    with open("test_wordsmith_output.json", "w", encoding="utf-8") as f:
        json.dump(result, f, ensure_ascii=False, indent=2)
    print(f"Wordsmith Stage Done. Output saved. Total Prose Length: {len(result.get('final_prose', ''))}")
    return result

async def main():
    stage = sys.argv[1] if len(sys.argv) > 1 else "all"
    
    try:
        if stage == "historian" or stage == "all":
            noise = float(sys.argv[2]) if len(sys.argv) > 2 else 0.05
            state = await run_historian(noise)
        else:
            with open("test_historian_output.json", "r", encoding="utf-8") as f:
                state = json.load(f)
                
        if stage == "psychologist" or stage == "all":
            state = await run_psychologist(state)
        elif stage != "historian":
            with open("test_psychologist_output.json", "r", encoding="utf-8") as f:
                state = json.load(f)

        if stage == "director" or stage == "all":
            state = await run_director(state)
        elif stage != "historian" and stage != "psychologist":
            with open("test_director_output.json", "r", encoding="utf-8") as f:
                state = json.load(f)

        if stage == "wordsmith" or stage == "all":
            await run_wordsmith(state)
            
    except Exception as e:
        print(f"CRITICAL ERROR in stage {stage}: {e}")
        import traceback
        traceback.print_exc()

if __name__ == "__main__":
    asyncio.run(main())
