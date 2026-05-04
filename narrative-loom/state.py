"""
NarrativeState — the shared state dict flowing through the LangGraph pipeline.

We keep TypedDict (not Pydantic BaseModel) for native LangGraph reducer
compatibility, but add two new tracking fields:
  - task_id:          Celery task ID — used for Centrifugo channel routing
  - completed_agents: accumulated list of agent names that have finished
"""
from typing import Any, Dict, List, Optional, TypedDict


class NarrativeState(TypedDict, total=False):
    # ── Core identifiers ────────────────────────────────────────
    world_id: int
    world_era: Optional[str]
    tick_start: Optional[int]
    tick_end: Optional[int]
    ai_runtime: Optional[Dict[str, Any]]

    # ── Task tracking (new in v2) ────────────────────────────────
    task_id: str                   # Celery task ID for Centrifugo routing
    completed_agents: List[str]    # agents that have completed (for progress)

    # ── Raw input ────────────────────────────────────────────────
    raw_chronicles: List[dict]

    # ── Engine outputs ───────────────────────────────────────────
    normalized_events: List[dict]
    filtered_events: List[dict]
    event_scores: Dict[str, float]
    attractor_clusters: Dict[str, List[dict]]
    attractor_strength: Dict[str, float]
    dramatic_arc: Dict[str, Any]
    narrative_phase: str
    phase_score: float
    singularity: Optional[Dict[str, Any]]

    # ── Style / genre ────────────────────────────────────────────
    genre: str
    style_guidelines: str

    # ── Cross-pollination ────────────────────────────────────────
    cross_pollination_whispers: List[str]

    # ── Agent outputs ────────────────────────────────────────────
    historical_outline: Any        # str | dict (HistoricalOutline)
    psychological_profiles: dict
    storyboard: Any                # str | dict (StoryboardSchema)
    final_prose: str

    # ── News / VFX ───────────────────────────────────────────────
    news_headline: str
    news_slogan: str
    vfx_config: dict
    vfx_hints: Optional[Dict[str, Any]]
    animation_script: Optional[dict]

    # ── Critic loop ──────────────────────────────────────────────
    feedback: dict
    revision_count: int

    # ── Memory ───────────────────────────────────────────────────
    past_memories: str

    # ── Epistemic layer ──────────────────────────────────────────
    epistemic_noise: float
    epistemic_tier: str
    resonance_scars: List[str]
    reality_stability: float

    # ── Reality knowledge ────────────────────────────────────────
    power_system: Optional[str]
    power_system_manifesto: Optional[str]
    era_context: Optional[str]

    # ── Pipeline metadata ────────────────────────────────────────
    current_agent: str
