import pytest
pytest.skip('Network-dependent test: requires LLM API connectivity.', allow_module_level=True)
import pytest
from langgraph.graph import StateGraph
from langchain_core.runnables import RunnableLambda
from state import NarrativeState

@pytest.fixture
def mock_llm_factory(mocker):
    async def mock_invoke(prompt):
        if "Psychologist" in str(prompt) or "Tâm Lý Gia" in str(prompt):
            return '{"analysis": "Mocked LLM Response", "archetypes": []}'
        return "Mocked LLM Response"
    
    dummy_llm = RunnableLambda(mock_invoke)
    
    # Patch get_llm in each module scope where used
    mocker.patch("agents.historian.get_llm", return_value=dummy_llm)
    mocker.patch("agents.psychologist.get_llm", return_value=dummy_llm)
    mocker.patch("agents.director.get_llm", return_value=dummy_llm)
    mocker.patch("agents.wordsmith.get_llm", return_value=dummy_llm)
    # The Critic doesn't use the LLM directly, so no need to mock agents.critic.get_llm
    
    return dummy_llm

@pytest.mark.asyncio
async def test_complete_narrative_pipeline(mock_llm_factory):
    from graph import app
    
    initial_state = {
        "world_id": 1,
        "tick_start": 50,
        "tick_end": 100,
        "raw_chronicles": [
            {"from_tick": 60, "type": "crisis", "raw_payload": {"event": "test"}}
        ],
        "historical_outline": "",
        "psychological_profiles": {"analysis": ""},
        "storyboard": "",
        "final_prose": "",
        "current_agent": "start",
        "feedback": {}
    }

    final_state = await app.ainvoke(initial_state)

    assert "Mocked LLM Response" in final_state["historical_outline"]
    assert "Mocked LLM Response" in final_state["psychological_profiles"].get("analysis", "")
    assert "Mocked LLM Response" in final_state["storyboard"]
    assert "Mocked LLM Response" in final_state["final_prose"]
    
    assert final_state["current_agent"] == "critic"
