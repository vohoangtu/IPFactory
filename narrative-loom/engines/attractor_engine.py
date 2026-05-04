from core.agent_wrapper import agent_node
from typing import Dict, Any, List
from state import NarrativeState

class AttractorFieldEngine:
    MAP = {
        "assassination": "power_struggle",
        "coup": "power_struggle",
        "rebellion": "power_struggle",
        "civil_war": "collapse",
        "war": "collapse",
        "religion_split": "religious_conflict",
        "heresy": "religious_conflict",
        "marriage": "dynasty",
        "birth": "dynasty",
        "law_change": "political_order",
        "battle": "warfare",
    }

    def classify(self, e: Dict[str, Any]) -> str:
        t = (e.get("type") or "").lower()
        return self.MAP.get(t, "misc")

    def group(self, events: List[Dict[str, Any]]) -> Dict[str, List[Dict[str, Any]]]:
        clusters: Dict[str, List[Dict[str, Any]]] = {}
        for e in events:
            a = self.classify(e)
            clusters.setdefault(a, []).append(e)
        return clusters

    def strength(self, clusters: Dict[str, List[Dict[str, Any]]]) -> Dict[str, float]:
        total = sum(len(v) for v in clusters.values()) or 1
        return {k: len(v)/total for k, v in clusters.items()}

@agent_node("attractor_engine")
async def attractor_engine_node(state: NarrativeState, config: Dict[str, Any] = None) -> NarrativeState:
    events = state.get("filtered_events", [])
    engine = AttractorFieldEngine()
    clusters = engine.group(events)
    strength = engine.strength(clusters)
    return {"attractor_clusters": clusters, "attractor_strength": strength}


