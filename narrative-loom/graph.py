from langgraph.graph import StateGraph, END
from state import NarrativeState
from engines.event_normalizer import event_normalizer_node
from engines.entropy_engine import entropy_engine_node
from engines.attractor_engine import attractor_engine_node
from engines.arc_engine import arc_engine_node
from engines.phase_engine import phase_engine_node
from engines.singularity_engine import singularity_engine_node
from engines.style_analyzer import style_analyzer_node
from nodes.universe_bridge import universe_bridge_node
from agents.chief_editor import chief_editor_agent
from agents.historian import historian_agent
from agents.psychologist import psychologist_agent
from agents.director import director_agent
from agents.wordsmith import wordsmith_agent
from agents.critic import critic_agent
from agents.archivist import archivist_agent
from agents.mythologist import mythologist_agent
from agents.news_anchor import news_anchor_agent
from agents.vfx_director import vfx_director_agent

# Khởi tạo Graph
workflow = StateGraph(NarrativeState)

# 1. Thêm Nodes
workflow.add_node("Event_Normalizer", event_normalizer_node)
workflow.add_node("Entropy_Engine", entropy_engine_node)
workflow.add_node("Attractor_Engine", attractor_engine_node)
workflow.add_node("Dramatic_Arc", arc_engine_node)
workflow.add_node("Phase_Engine", phase_engine_node)
workflow.add_node("Singularity_Engine", singularity_engine_node)
workflow.add_node("Style_Analyzer", style_analyzer_node)
workflow.add_node("Universe_Bridge", universe_bridge_node)
workflow.add_node("Chief_Editor", chief_editor_agent)
workflow.add_node("The_Historian", historian_agent)
workflow.add_node("The_Psychologist", psychologist_agent)
workflow.add_node("The_Director", director_agent)
workflow.add_node("The_Wordsmith", wordsmith_agent)
workflow.add_node("The_Critic", critic_agent)
workflow.add_node("The_Archivist", archivist_agent)
workflow.add_node("The_Mythologist", mythologist_agent)
workflow.add_node("News_Anchor", news_anchor_agent)
workflow.add_node("VFX_Director", vfx_director_agent)

# 2. Tuần tự kết nối
workflow.set_entry_point("Event_Normalizer")

workflow.add_edge("Event_Normalizer", "Universe_Bridge")
workflow.add_edge("Universe_Bridge", "Entropy_Engine")
workflow.add_edge("Entropy_Engine", "Attractor_Engine")
workflow.add_edge("Attractor_Engine", "Style_Analyzer")
workflow.add_edge("Style_Analyzer", "Dramatic_Arc")
workflow.add_edge("Dramatic_Arc", "Phase_Engine")
workflow.add_edge("Phase_Engine", "Singularity_Engine")
workflow.add_edge("Singularity_Engine", "Chief_Editor")
workflow.add_edge("Chief_Editor", "The_Historian")
workflow.add_edge("The_Historian", "The_Mythologist")
workflow.add_edge("The_Mythologist", "The_Psychologist")
workflow.add_edge("The_Psychologist", "The_Director")

workflow.add_edge("The_Director", "The_Wordsmith")

workflow.add_edge("The_Wordsmith", "The_Critic")

def check_revision(state: NarrativeState):
    fb = state.get("feedback", {})
    if fb.get("is_passed", True):
        return "The_Archivist"
    if state.get("revision_count", 0) >= 2: # Max 2 vòng lặp để tránh infinite loop
        return "The_Archivist"
    return "The_Wordsmith"

workflow.add_conditional_edges(
    "The_Critic",
    check_revision,
    {
        "The_Archivist": "The_Archivist",
        "The_Wordsmith": "The_Wordsmith"
    }
)

workflow.add_edge("The_Archivist", "News_Anchor")
workflow.add_edge("News_Anchor", "VFX_Director")
workflow.add_edge("VFX_Director", END)

# Tương lai có thể thêm Edge có Điều Kiện (Conditional Edges): 
# Ví dụ: Nếu Storyboard dở -> Yêu cầu Director viết lại thay vì đi đến Wordsmith.

# Compile the App
app = workflow.compile()
