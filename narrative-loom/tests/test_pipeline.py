"""End-to-end LangGraph pipeline test with mocked LLM agents."""
import pytest
from unittest.mock import MagicMock, AsyncMock

from state import NarrativeState


@pytest.fixture
def mock_all_llms(mocker):
    """Mock get_llm_for_agent for every agent that participates in the pipeline."""

    async def mock_ainvoke(prompt):
        if "Psychologist" in str(prompt) or "Tâm Lý Gia" in str(prompt):
            return '{"analysis": "Mocked LLM Response", "archetypes": []}'
        return "Mocked LLM Response"

    dummy_llm = MagicMock()
    dummy_llm.ainvoke = AsyncMock(side_effect=mock_ainvoke)
    dummy_llm.invoke = mock_ainvoke

    structured_mock = MagicMock()
    structured_mock.ainvoke = AsyncMock(side_effect=mock_ainvoke)
    structured_mock.invoke = mock_ainvoke
    dummy_llm.with_structured_output.return_value = structured_mock

    # Patch get_llm_for_agent (the current API, not deprecated get_llm).
    agents = [
        "historian", "psychologist", "director", "wordsmith",
        "critic", "chief_editor", "news_anchor", "intent_agent",
    ]
    for agent in agents:
        mocker.patch(f"agents.{agent}.get_llm_for_agent", return_value=dummy_llm)

    return dummy_llm


@pytest.mark.asyncio
async def test_complete_narrative_pipeline(mock_all_llms):
    """Verify the full LangGraph pipeline produces expected outputs with mocked LLMs."""
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
        "feedback": {},
    }

    final_state = await app.ainvoke(initial_state)

    assert "Mocked LLM Response" in final_state["historical_outline"]
    assert "Mocked LLM Response" in final_state["psychological_profiles"].get("analysis", "")
    assert "Mocked LLM Response" in final_state["storyboard"]
    assert "Mocked LLM Response" in final_state["final_prose"]

    assert final_state["current_agent"] == "critic"
