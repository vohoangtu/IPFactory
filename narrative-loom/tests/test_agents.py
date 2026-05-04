import pytest
import asyncio
import json
from langchain_core.runnables import RunnableLambda

from agents.historian import historian_agent
from agents.psychologist import psychologist_agent
from agents.director import director_agent
from agents.wordsmith import wordsmith_agent
from state import NarrativeState

@pytest.fixture
def mock_llm(mocker):
    from unittest.mock import MagicMock, AsyncMock

    # Psychologist expects JSON output, others expect strings
    async def mock_invoke(prompt):
        if "Psychologist" in str(prompt) or "Tâm Lý Gia" in str(prompt):
            return '{"analysis": "Mocked LLM Response", "archetypes": []}'
        return "Mocked LLM Response"

    dummy_llm = MagicMock()
    dummy_llm.ainvoke = AsyncMock(side_effect=mock_invoke)
    dummy_llm.invoke = mock_invoke

    # with_structured_output returns another mock that supports ainvoke
    structured_mock = MagicMock()
    structured_mock.ainvoke = AsyncMock(side_effect=mock_invoke)
    structured_mock.invoke = mock_invoke
    dummy_llm.with_structured_output.return_value = structured_mock

    # Patch get_llm_for_agent in each module scope
    mocker.patch("agents.historian.get_llm_for_agent", return_value=dummy_llm)
    mocker.patch("agents.psychologist.get_llm_for_agent", return_value=dummy_llm)
    mocker.patch("agents.director.get_llm_for_agent", return_value=dummy_llm)
    mocker.patch("agents.wordsmith.get_llm_for_agent", return_value=dummy_llm)

    return dummy_llm

@pytest.fixture
def mock_narrative_state():
    return {
        "world_id": 1,
        "tick_start": 100,
        "tick_end": 120,
        "raw_chronicles": [
            {
                "from_tick": 105,
                "type": "meaning_crisis",
                "raw_payload": {"description": "Philosophical crisis emerges."}
            }
        ],
        "historical_outline": "",
        "psychological_profiles": {"analysis": "Mock analysis"},
        "storyboard": "",
        "final_prose": "",
        "current_agent": "start",
        "feedback": {}
    }

@pytest.mark.asyncio
async def test_historian_agent(mocker, mock_narrative_state):
    mocker.patch("agents.historian.get_llm_for_agent", return_value=mocker.MagicMock())
    state = await historian_agent(mock_narrative_state)
    assert state["current_agent"] == "historian"

@pytest.mark.asyncio
async def test_psychologist_agent(mocker, mock_narrative_state):
    mocker.patch("agents.psychologist.get_llm_for_agent", return_value=mocker.MagicMock())
    state = await psychologist_agent(mock_narrative_state)
    assert state["current_agent"] == "psychologist"

@pytest.mark.asyncio
async def test_director_agent(mocker, mock_narrative_state):
    mocker.patch("agents.director.get_llm_for_agent", return_value=mocker.MagicMock())
    state = await director_agent(mock_narrative_state)
    assert state["current_agent"] == "director"

@pytest.mark.asyncio
async def test_wordsmith_agent(mocker, mock_narrative_state):
    mocker.patch("agents.wordsmith.get_llm_for_agent", return_value=mocker.MagicMock())
    state = await wordsmith_agent(mock_narrative_state)
    assert state["current_agent"] == "wordsmith"


from agents.vfx_director import vfx_director_agent

@pytest.fixture
def mock_vfx_llm(mocker):
    """Mock LLM that returns valid animation script JSON."""
    vfx_response = json.dumps({
        "vfx_config": {
            "primary_color": "#ff4500",
            "distortion": 0.6,
            "particle_density": 120,
            "atmosphere_filter": "dust"
        },
        "animation_script": {
            "total_duration_ms": 20000,
            "scenes": [
                {
                    "id": "scene_1",
                    "type": "establishing",
                    "duration_ms": 8000,
                    "background": {"type": "gradient", "colors": ["#1a0a2e", "#ff6b35"], "description": "Sunset over ruins"},
                    "atmosphere": {"filter": "dust", "intensity": 0.6, "weather": "fire_embers"},
                    "camera": {"type": "zoom_in", "speed": 0.3, "easing": "ease-in"},
                    "effects": [{"type": "particles", "intensity": 0.4, "color": "#ff6b35", "trigger_at_ms": 0}],
                    "narration": "The ancient fortress stood silent...",
                    "transition": {"type": "dissolve", "duration_ms": 800}
                },
                {
                    "id": "scene_2",
                    "type": "resolution",
                    "duration_ms": 7000,
                    "background": {"type": "gradient", "colors": ["#2d1b69", "#0d0d0d"], "description": "Darkness falls"},
                    "atmosphere": {"filter": "mist", "intensity": 0.5, "weather": None},
                    "camera": {"type": "zoom_out", "speed": 0.2, "easing": "ease-out"},
                    "effects": [],
                    "narration": "Only ash remained.",
                    "transition": {"type": "fade", "duration_ms": 1500}
                }
            ]
        }
    })

    async def mock_invoke(prompt):
        return vfx_response

    dummy_llm = RunnableLambda(mock_invoke)
    mocker.patch("agents.vfx_director.get_llm_for_agent", return_value=dummy_llm)
    return dummy_llm


@pytest.fixture
def vfx_narrative_state():
    """State with all fields VFX Director needs."""
    return {
        "world_id": 1,
        "tick_start": 100,
        "tick_end": 120,
        "ai_runtime": None,
        "event_scores": {"total_entropy": 0.7},
        "singularity": {"distortion": 0.4},
        "genre": "dark_fantasy",
        "news_headline": "The Iron Gate has fallen",
        "final_prose": "The ancient fortress, once proud sentinel of the Northern Pass, crumbled under the siege.",
        "dramatic_arc": {"rising_action": 0.6, "climax": 0.8},
        "current_agent": "archivist",
        "completed_agents": [],
        "task_id": "test-123",
    }


@pytest.mark.asyncio
async def test_vfx_director_produces_animation_script(mock_vfx_llm, vfx_narrative_state):
    state = await vfx_director_agent(vfx_narrative_state)
    assert state["current_agent"] == "vfx_director"
    assert "vfx_config" in state
    assert state["vfx_config"]["primary_color"] == "#ff4500"
    assert "animation_script" in state
    assert state["animation_script"]["total_duration_ms"] == 20000
    assert len(state["animation_script"]["scenes"]) == 2
    assert state["animation_script"]["scenes"][0]["type"] == "establishing"


@pytest.mark.asyncio
async def test_vfx_director_fallback_on_error(mocker, vfx_narrative_state):
    """When LLM fails, vfx_config has defaults and animation_script is None."""
    async def failing_invoke(prompt):
        raise RuntimeError("LLM unavailable")

    dummy_llm = RunnableLambda(failing_invoke)
    mocker.patch("agents.vfx_director.get_llm_for_agent", return_value=dummy_llm)

    state = await vfx_director_agent(vfx_narrative_state)
    assert state["vfx_config"]["primary_color"] == "#8b5cf6"  # fallback color
    assert state["animation_script"] is None


# ─── Tests for archivist, chief_editor, critic, mythologist, news_anchor ──────

from agents.archivist import archivist_agent
from agents.chief_editor import chief_editor_agent
from agents.critic import critic_agent
from agents.mythologist import mythologist_agent
from agents.news_anchor import news_anchor_agent
from schemas import CriticReview
from unittest.mock import MagicMock


@pytest.fixture
def extended_narrative_state():
    """State with all fields needed by the new agents."""
    return {
        "world_id": 1,
        "tick_start": 100,
        "tick_end": 120,
        "ai_runtime": None,
        "raw_chronicles": [],
        "normalized_events": [],
        "historical_outline": "Outline of historical events...",
        "psychological_profiles": {},
        "style_guidelines": "Epic and dramatic",
        "storyboard": "Scene 1: The battle begins.",
        "final_prose": "The fortress fell in the dead of night.",
        "past_memories": "",
        "event_scores": {"total_entropy": 0.75},
        "narrative_phase": "climax",
        "singularity": {"distortion": 0.5},
        "genre": "dark_fantasy",
        "mythic_fragments": "",
        "news_headline": "",
        "news_slogan": "",
        "feedback": {},
        "revision_count": 0,
        "current_agent": "start",
        "completed_agents": [],
        "task_id": "test-task-001",
    }


# ─── archivist ────────────────────────────────────────────────────────────────

@pytest.mark.asyncio
async def test_archivist_agent(mocker, extended_narrative_state):
    """Archivist skips store_memory when memory_db.enabled is False."""
    # Patch EpisodicMemoryManager to return disabled instance
    mock_mem = mocker.patch("agents.archivist.EpisodicMemoryManager")
    mock_instance = mocker.MagicMock()
    mock_instance.enabled = False
    mock_mem.return_value = mock_instance

    state = await archivist_agent(extended_narrative_state)

    assert state["current_agent"] == "archivist"
    # All other state keys must be preserved
    assert state["world_id"] == extended_narrative_state["world_id"]


# ─── chief_editor ─────────────────────────────────────────────────────────────

@pytest.fixture
def mock_chief_editor_llm(mocker):
    async def mock_invoke(prompt):
        return "Editorial angle: Focus on the fall of civilizations."

    dummy_llm = RunnableLambda(mock_invoke)
    mocker.patch("agents.chief_editor.get_llm_for_agent", return_value=dummy_llm)
    return dummy_llm


@pytest.mark.asyncio
async def test_chief_editor_agent(mock_chief_editor_llm, extended_narrative_state):
    """Chief editor appends an editorial angle to past_memories."""
    state = await chief_editor_agent(extended_narrative_state)

    assert state["current_agent"] == "chief_editor"
    assert "[CHIEF EDITOR ANGLE]" in state["past_memories"]
    assert "Editorial angle" in state["past_memories"]


# ─── critic ───────────────────────────────────────────────────────────────────

@pytest.mark.asyncio
async def test_critic_agent_pass(mocker, extended_narrative_state):
    """Critic returns is_passed=True when score is high."""
    mock_base_llm = MagicMock()

    async def mock_structured_invoke(input_dict):
        return CriticReview(score=8, feedbacks=[], is_passed=True)

    structured_runnable = RunnableLambda(mock_structured_invoke)
    mock_base_llm.with_structured_output.return_value = structured_runnable
    mocker.patch("agents.critic.get_llm_for_agent", return_value=mock_base_llm)

    state = await critic_agent(extended_narrative_state)

    assert state["current_agent"] == "critic"
    assert state["feedback"]["is_passed"] is True
    assert state["feedback"]["score"] == 8
    assert state["revision_count"] == 1


@pytest.mark.asyncio
async def test_critic_agent_fail(mocker, extended_narrative_state):
    """Critic returns is_passed=False when score is low."""
    mock_base_llm = MagicMock()

    async def mock_structured_invoke(input_dict):
        return CriticReview(score=4, feedbacks=["Needs work"], is_passed=False)

    structured_runnable = RunnableLambda(mock_structured_invoke)
    mock_base_llm.with_structured_output.return_value = structured_runnable
    mocker.patch("agents.critic.get_llm_for_agent", return_value=mock_base_llm)

    state = await critic_agent(extended_narrative_state)

    assert state["current_agent"] == "critic"
    assert state["feedback"]["is_passed"] is False
    assert state["feedback"]["score"] == 4
    assert "Needs work" in state["feedback"]["feedbacks"]
    assert state["revision_count"] == 1


# ─── mythologist ──────────────────────────────────────────────────────────────

@pytest.fixture
def mock_mythologist_llm(mocker):
    async def mock_invoke(prompt):
        return "The gods wept as the last fortress fell."

    dummy_llm = RunnableLambda(mock_invoke)
    mocker.patch("agents.mythologist.get_llm_for_agent", return_value=dummy_llm)
    return dummy_llm


@pytest.mark.asyncio
async def test_mythologist_agent(mock_mythologist_llm, extended_narrative_state):
    """Mythologist writes mythic_fragments from the historical outline."""
    state = await mythologist_agent(extended_narrative_state)

    assert state["current_agent"] == "mythologist"
    assert "gods wept" in state["mythic_fragments"]


# ─── news_anchor ──────────────────────────────────────────────────────────────

@pytest.fixture
def mock_news_anchor_llm(mocker):
    async def mock_invoke(prompt):
        return '{"headline": "Test Headline", "slogan": "Test Slogan"}'

    dummy_llm = RunnableLambda(mock_invoke)
    mocker.patch("agents.news_anchor.get_llm_for_agent", return_value=dummy_llm)
    return dummy_llm


@pytest.mark.asyncio
async def test_news_anchor_agent(mock_news_anchor_llm, extended_narrative_state):
    """News anchor extracts headline and slogan from final_prose."""
    state = await news_anchor_agent(extended_narrative_state)

    assert state["current_agent"] == "news_anchor"
    assert state["news_headline"] == "Test Headline"
    assert state["news_slogan"] == "Test Slogan"


@pytest.mark.asyncio
async def test_news_anchor_fallback(mocker, extended_narrative_state):
    """News anchor uses fallback values when LLM raises an exception."""
    async def failing_invoke(prompt):
        raise RuntimeError("LLM unavailable")

    dummy_llm = RunnableLambda(failing_invoke)
    mocker.patch("agents.news_anchor.get_llm_for_agent", return_value=dummy_llm)

    state = await news_anchor_agent(extended_narrative_state)

    assert state["current_agent"] == "news_anchor"
    assert state["news_headline"] == "BREAKING NEWS: SỰ KIỆN LỚN ĐANG DIỄN RA"
    assert state["news_slogan"] == "Theo dõi để biết thêm chi tiết."


# ─── Tests for artifact_forger, celebrity_synthesizer, history_scribe ─────────

from agents.artifact_forger import forge_artifact, artifact_forger_api
from agents.celebrity_synthesizer import synthesize_celebrity, celebrity_synthesizer_api
from agents.history_scribe import scribe_history, history_scribe_api


@pytest.fixture
def mock_artifact_llm(mocker):
    """Mock LLM that returns a valid artifact dict via with_structured_output."""
    mock_base_llm = MagicMock()

    async def mock_structured_invoke(input_val):
        return {"name": "Blade of Eternity", "lore": "A blade forged in the heart of a dying star."}

    structured_runnable = RunnableLambda(mock_structured_invoke)
    mock_base_llm.with_structured_output.return_value = structured_runnable
    mocker.patch("agents.artifact_forger.get_llm_for_agent", return_value=mock_base_llm)
    return mock_base_llm


@pytest.fixture
def mock_celebrity_llm(mocker):
    """Mock LLM that returns a valid celebrity dict via with_structured_output."""
    mock_base_llm = MagicMock()

    async def mock_structured_invoke(input_val):
        return {"name": "Aria Stormborn", "biography": "Born under a blood moon, she reshaped the empire."}

    structured_runnable = RunnableLambda(mock_structured_invoke)
    mock_base_llm.with_structured_output.return_value = structured_runnable
    mocker.patch("agents.celebrity_synthesizer.get_llm_for_agent", return_value=mock_base_llm)
    return mock_base_llm


@pytest.fixture
def mock_history_llm(mocker):
    """Mock LLM that returns a valid history event dict via with_structured_output."""
    mock_base_llm = MagicMock()

    async def mock_structured_invoke(input_val):
        return {"event_name": "The Great Collapse", "chronicle": "Civilizations crumbled in a single night."}

    structured_runnable = RunnableLambda(mock_structured_invoke)
    mock_base_llm.with_structured_output.return_value = structured_runnable
    mocker.patch("agents.history_scribe.get_llm_for_agent", return_value=mock_base_llm)
    return mock_base_llm


@pytest.mark.asyncio
async def test_artifact_forger(mock_artifact_llm):
    """Artifact forger returns name and lore from structured LLM output."""
    state = {
        "world_id": 1,
        "artifact_id": "art_001",
        "zone_id": "zone_a",
        "mass": 100,
        "knowledge": "ancient",
        "world_era": "genesis",
    }
    result = await forge_artifact(state)
    assert result["name"] == "Blade of Eternity"
    assert "dying star" in result["lore"]


@pytest.mark.asyncio
async def test_celebrity_synthesizer(mock_celebrity_llm):
    """Celebrity synthesizer returns name and biography from structured LLM output."""
    state = {
        "world_id": 1,
        "agent_id": "agent_001",
        "zone_id": "zone_b",
        "fame": 0.8,
        "vocation": "warrior",
        "world_era": "iron_age",
    }
    result = await synthesize_celebrity(state)
    assert result["name"] == "Aria Stormborn"
    assert "blood moon" in result["biography"]


@pytest.mark.asyncio
async def test_history_scribe_high_impact(mock_history_llm):
    """History scribe triggers LLM when impact_score >= 5.0."""
    state = {
        "event_type": "collapse",
        "impact_score": 7.5,
        "trigger_data": {"region": "north"},
        "world_id": 1,
    }
    result = await history_scribe_api(state)
    assert result["event_name"] == "The Great Collapse"
    assert "Civilizations crumbled" in result["chronicle"]


@pytest.mark.asyncio
async def test_history_scribe_low_impact_fallback():
    """History scribe uses rule-based fallback when impact_score < 5.0."""
    state = {
        "event_type": "trade_dispute",
        "impact_score": 2.0,
        "trigger_data": {},
        "world_id": 42,
    }
    result = await history_scribe_api(state)
    assert result["event_name"] == "Minor Event: Trade Dispute"
    assert "Vũ trụ #42" in result["chronicle"]
