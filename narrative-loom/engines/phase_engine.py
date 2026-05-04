from core.agent_wrapper import agent_node
from typing import Dict, Any, List
from state import NarrativeState
from .arc_engine import TENSION_MAP

class NarrativePhaseEngine:
    def event_density(self, events: List[Dict[str, Any]], window: int = 100) -> float:
        if not events:
            return 0.0
        ticks = [e.get("tick", 0) or 0 for e in events]
        span = (max(ticks) - min(ticks)) + 1
        return min(1.0, len(events) / max(1, span) * window)

    def actor_diversity(self, events: List[Dict[str, Any]]) -> float:
        s = set(a for e in events for a in e.get("actors", []))
        return min(1.0, len(s) / 100.0)

    def tension_energy(self, events: List[Dict[str, Any]]) -> float:
        if not events:
            return 0.0
        vals = [TENSION_MAP.get((e.get("type") or "").lower(), 0.4) for e in events]
        return min(1.0, sum(vals) / max(1, len(vals)))

    def detect(self, events: List[Dict[str, Any]]) -> Dict[str, Any]:
        d = self.event_density(events)
        v = self.actor_diversity(events)
        t = self.tension_energy(events)
        score = 0.4 * d + 0.3 * v + 0.3 * t
        if score < 0.2:
            phase = "micro"
        elif score < 0.4:
            phase = "local"
        elif score < 0.6:
            phase = "regional"
        elif score < 0.8:
            phase = "civilization"
        else:
            phase = "mythic"
        return {"narrative_phase": phase, "phase_score": score}

@agent_node("phase_engine")
async def phase_engine_node(state: NarrativeState, config: Dict[str, Any] = None) -> NarrativeState:
    events = state.get("filtered_events", [])
    engine = NarrativePhaseEngine()
    res = engine.detect(events)
    return {**res}


