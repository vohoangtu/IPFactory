import pytest
pytest.skip('Network-dependent test: requires LLM API connectivity.', allow_module_level=True)
import asyncio
import os
import sys

# Đảm bảo import được module
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

from graph import app
from state import NarrativeState

async def main():
    print("--- TESTING NEXT-GEN NARRATIVE LOOM ---")
    
    initial_state: NarrativeState = {
        "world_id": 999,
        "tick_start": 100,
        "tick_end": 105,
        "raw_chronicles": [
            {"id": 1, "desc": "God creates the sky", "actors": [0]}
        ],
        "normalized_events": [
            {
                "id": 1, 
                "actors": [0], 
                "description": "The void shakes as the architect awakens.",
                "importance": 0.9,
                "emotional_valences": {"awe": 1.0}
            }
        ],
        "event_scores": {},
        "attractor_clusters": {},
        "attractor_strength": {},
        "dramatic_arc": {},
        "narrative_phase": "Setup",
        "phase_score": 0.5,
        "singularity": None,
        "historical_outline": "",
        "psychological_profiles": {
            "0": "The Architect - A being of pure logic."
        },
        "storyboard": "",
        "final_prose": "",
        "feedback": {},
        "revision_count": 0,
        "past_memories": "",
        "current_agent": ""
    }
    
    try:
        print("Starting Graph Execution...")
        # Lược bớt event normalizer để tiết kiệm time
        final_state = await app.ainvoke(initial_state)
        
        print("\n=== FINAL RESULT ===")
        print("Past Memories Extracted:", final_state.get("past_memories"))
        print("\nHistorical Outline:", final_state.get("historical_outline"))
        print("\nStoryboard:", final_state.get("storyboard"))
        print("\nProse length:", len(final_state.get("final_prose", "")))
        print("\nCritic Passed:", final_state.get("feedback", {}).get("is_passed"))
        print("Revisions count:", final_state.get("revision_count"))
        print("Final Agent:", final_state.get("current_agent"))
        print("SUCCESS!")
    except Exception as e:
        print(f"FAILED: {str(e)}")

if __name__ == "__main__":
    asyncio.run(main())
