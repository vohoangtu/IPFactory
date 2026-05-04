from core.agent_wrapper import agent_node
from typing import Dict, Any, List
from state import NarrativeState

class NarrativeSingularityEngine:
    def actor_gravity(self, events: List[Dict[str, Any]]) -> float:
        s = {}
        for e in events:
            for a in e.get("actors", []):
                s[a] = s.get(a, 0) + 1
        if not s:
            return 0.0
        m = max(s.values())
        return min(1.0, m / max(1, sum(s.values())))

    def event_energy(self, events: List[Dict[str, Any]]) -> float:
        c = 0
        for e in events:
            t = (e.get("type") or "").lower()
            if t in ("war", "civil_war", "rebellion", "assassination", "battle"):
                c += 1
        return min(1.0, c / max(1, len(events)))

    def attractor_convergence(self, strength: Dict[str, float]) -> float:
        vals = sorted(strength.values(), reverse=True)
        if not vals:
            return 0.0
        return min(1.0, sum(vals[:3]))

    def detect(self, state: NarrativeState) -> Dict[str, Any] | None:
        events = state.get("filtered_events", [])
        strength = state.get("attractor_strength", {})
        phase_score = state.get("phase_score", 0.0)
        ag = self.actor_gravity(events)
        ee = self.event_energy(events)
        ac = self.attractor_convergence(strength)
        score = 0.35 * ag + 0.35 * ee + 0.20 * ac + 0.10 * phase_score
        if score < 0.5:
            return None
        t = "era"
        title = "Emergent Era"
        return {"score": score, "type": t, "title": title}

@agent_node("singularity_engine")
async def singularity_engine_node(state: NarrativeState, config: Dict[str, Any] = None) -> NarrativeState:
    engine = NarrativeSingularityEngine()
    res = engine.detect(state)
    return {"singularity": res}


