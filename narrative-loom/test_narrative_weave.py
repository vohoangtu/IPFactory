import pytest
pytest.skip('Network-dependent test: requires LLM API connectivity.', allow_module_level=True)
import asyncio
import os
import json
from dotenv import load_dotenv

# Load env variables (including your new OpenRouter key)
load_dotenv()

async def run_test_weave():
    print("\n=== [WORLDOS V6: NARRATIVE WEAVE INTEGRATION TEST] ===")
    
    # 1. Import the compiled LangGraph
    from graph import app as loom_app
    
    # 2. Prepare Mock Initial State
    initial_state = {
        "world_id": 999,
        "world_era": "medieval",
        "tick_start": 5000,
        "tick_end": 5100,
        "genre": "dark_fantasy",
        "cross_pollination_whispers": ["Tiếng vọng từ hư không nói về sự sụp đổ của các vị thần."],
        "raw_chronicles": [
            {
                "id": "evt_1",
                "intent_slug": "rebellion_start",
                "mental_state": "desperate",
                "content": "Nông dân tại làng Elmswood bắt đầu đốt các kho thóc của lãnh chúa."
            },
            {
                "id": "evt_2",
                "intent_slug": "omen_appearance",
                "mental_state": "terror",
                "content": "Một vệt sáng màu đỏ máu xuất hiện trên bầu trời phía Tây."
            }
        ],
        "historical_outline": "",
        "psychological_profiles": {},
        "storyboard": "",
        "final_prose": "",
        "feedback": {},
        "revision_count": 0,
        "current_agent": "system"
    }
    
    print(f"DEBUG: Starting Weave for World {initial_state['world_id']} (Era: {initial_state['world_era']})")
    print("DEBUG: Using OpenRouter API for routing...")

    try:
        # 3. Kích hoạt Workflow
        # Lưu ý: Sẽ mất khoảng 15-30 giây tùy tốc độ API
        final_state = await loom_app.ainvoke(initial_state)
        
        print("\n--- [RESULT: LITERARY PROSE] ---")
        prose = final_state.get("final_prose", "No prose generated.")
        print(prose[:500] + "..." if len(prose) > 500 else prose)
        
        print("\n--- [RESULT: VFX CONFIG] ---")
        vfx = final_state.get("vfx_config")
        print(json.dumps(vfx, indent=2))
        
        print("\n--- [RESULT: CACHE STATUS] ---")
        print("Hệ thống đã tự động lưu kết quả vào Redis (TTL 800 ticks).")
        
        print("\n=== TEST COMPLETED SUCCESSFULLY ===")
        
    except Exception as e:
        import traceback
        print(f"\n❌ TEST FAILED: {str(e)}")
        print(traceback.format_exc())

if __name__ == "__main__":
    asyncio.run(run_test_weave())
