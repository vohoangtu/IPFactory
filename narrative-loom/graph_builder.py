"""
Narrative Loom Configurable Graph Builder.

Constructs a LangGraph StateGraph that utilizes parallel fan-out execution
to significantly reduce pipeline wall-clock time.
"""
import os
from langgraph.graph import END, StateGraph

from agents.archivist import archivist_agent
from agents.chief_editor import chief_editor_agent
from agents.critic import critic_agent
from agents.director import director_agent
from agents.historian import historian_agent
from agents.mythologist import mythologist_agent
from agents.news_anchor import news_anchor_agent
from agents.psychologist import psychologist_agent
from agents.vfx_director import vfx_director_agent
from agents.wordsmith import wordsmith_agent
from engines.arc_engine import arc_engine_node
from engines.attractor_engine import attractor_engine_node
from engines.entropy_engine import entropy_engine_node
from engines.event_normalizer import event_normalizer_node
from engines.phase_engine import phase_engine_node
from engines.singularity_engine import singularity_engine_node
from engines.style_analyzer import style_analyzer_node
from nodes.universe_bridge import universe_bridge_node
from state import NarrativeState


_MAX_REVISIONS = int(os.getenv("LOOM_MAX_REVISIONS", "2"))


def check_revision(state: NarrativeState) -> str:
    """Conditional edge logic after The_Critic."""
    fb = state.get("feedback", {})
    if fb.get("is_passed", True):
        return "The_Archivist"
    if state.get("revision_count", 0) >= _MAX_REVISIONS:
        # Max 2 vòng lặp để tránh infinite loop
        return "The_Archivist"
    return "The_Wordsmith"


def build_graph() -> StateGraph:
    """Build and compile the Narrative Loom parallel execution graph."""
    workflow = StateGraph(NarrativeState)

    # 1. Add all nodes
    workflow.add_node("Event_Normalizer", event_normalizer_node)
    workflow.add_node("Universe_Bridge", universe_bridge_node)
    workflow.add_node("Entropy_Engine", entropy_engine_node)
    workflow.add_node("Style_Analyzer", style_analyzer_node)
    workflow.add_node("Attractor_Engine", attractor_engine_node)
    workflow.add_node("Dramatic_Arc", arc_engine_node)
    workflow.add_node("Phase_Engine", phase_engine_node)
    workflow.add_node("Singularity_Engine", singularity_engine_node)
    
    workflow.add_node("Chief_Editor", chief_editor_agent)
    workflow.add_node("The_Historian", historian_agent)
    workflow.add_node("The_Mythologist", mythologist_agent)
    workflow.add_node("The_Psychologist", psychologist_agent)
    workflow.add_node("The_Director", director_agent)
    workflow.add_node("The_Wordsmith", wordsmith_agent)
    workflow.add_node("The_Critic", critic_agent)
    
    workflow.add_node("The_Archivist", archivist_agent)
    workflow.add_node("News_Anchor", news_anchor_agent)
    workflow.add_node("VFX_Director", vfx_director_agent)

    # 2. Define Execution Graph

    # Initial normalization sequence
    workflow.set_entry_point("Event_Normalizer")
    workflow.add_edge("Event_Normalizer", "Universe_Bridge")

    # PARALLEL FAN-OUT 1: Engines (Entropy & Style run concurrently)
    workflow.add_edge("Universe_Bridge", "Entropy_Engine")
    workflow.add_edge("Universe_Bridge", "Style_Analyzer")
    
    # Fan-in: Attractor waits for both Entropy and Style
    workflow.add_edge("Entropy_Engine", "Attractor_Engine")
    workflow.add_edge("Style_Analyzer", "Attractor_Engine")

    # Sequential middle
    workflow.add_edge("Attractor_Engine", "Dramatic_Arc")
    workflow.add_edge("Dramatic_Arc", "Phase_Engine")
    workflow.add_edge("Phase_Engine", "Singularity_Engine")
    workflow.add_edge("Singularity_Engine", "Chief_Editor")

    # PARALLEL FAN-OUT 2: Historian & Mythologist (Heavy LLMs independent of each other)
    workflow.add_edge("Chief_Editor", "The_Historian")
    workflow.add_edge("Chief_Editor", "The_Mythologist")

    # Fan-in: Psychologist synthesizes history + myth
    workflow.add_edge("The_Historian", "The_Psychologist")
    workflow.add_edge("The_Mythologist", "The_Psychologist")

    # The creation sequence
    workflow.add_edge("The_Psychologist", "The_Director")
    workflow.add_edge("The_Director", "The_Wordsmith")
    workflow.add_edge("The_Wordsmith", "The_Critic")

    # Conditional logic
    workflow.add_conditional_edges(
        "The_Critic",
        check_revision,
        {
            "The_Archivist": "VFX_Director",
            "The_Wordsmith": "The_Wordsmith"
        }
    )

    # VFX Director runs after Critic approves (needs final_prose from Wordsmith)
    workflow.add_edge("VFX_Director", "The_Archivist")

    # PARALLEL FAN-OUT 3: Final export tasks after archiving
    workflow.add_edge("The_Archivist", "News_Anchor")

    # End of pipeline
    workflow.add_edge("News_Anchor", END)

    return workflow.compile()

# For backward compatibility / easy import during transition
app = build_graph()
