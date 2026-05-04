from core.agent_wrapper import agent_node
from typing import Dict, Any, List, Tuple
from state import NarrativeState

def temporal_weight(event: Dict[str, Any], events: List[Dict[str, Any]]) -> float:
    if not events:
        return 0.0
    ticks = [e.get("tick", 0) or 0 for e in events]
    mid = sum(ticks) / max(1, len(ticks))
    mt = max(ticks) or 1
    return abs((event.get("tick", 0) or 0) - mid) / mt

class NarrativeEntropyEngine:
    def __init__(self, events: List[Dict[str, Any]]):
        self.events = events
        self.type_counts: Dict[str, int] = {}
        for e in events:
            t = e.get("type") or ""
            self.type_counts[t] = self.type_counts.get(t, 0) + 1

    def rarity(self, e: Dict[str, Any]) -> float:
        t = e.get("type") or ""
        c = self.type_counts.get(t, 1)
        return 1.0 / c

    def character_impact(self, e: Dict[str, Any]) -> float:
        return min(1.0, len(e.get("actors", [])) / 10.0)

    def political_impact(self, e: Dict[str, Any]) -> float:
        t = (e.get("type") or "").lower()
        if t in ("coup", "rebellion", "assassination", "law_change", "war", "civil_war"):
            return 0.8
        return 0.3

    def chaos_delta(self, e: Dict[str, Any]) -> float:
        t = (e.get("type") or "").lower()
        if t in ("battle", "assassination", "rebellion", "civil_war"):
            return 0.7
        if t in ("marriage", "birth"):
            return 0.1
        return 0.3

    def score_event(self, e: Dict[str, Any]) -> float:
        ci = self.character_impact(e)
        pi = self.political_impact(e)
        ra = self.rarity(e)
        cd = self.chaos_delta(e)
        tw = temporal_weight(e, self.events)
        return 0.30*ci + 0.25*pi + 0.20*ra + 0.15*cd + 0.10*tw

    def select_events(self, k: int) -> Tuple[List[Dict[str, Any]], Dict[str, float]]:
        scored: List[Tuple[Dict[str, Any], float]] = []
        for e in self.events:
            s = self.score_event(e)
            scored.append((e, s))
        scored.sort(key=lambda x: x[1], reverse=True)
        picked: List[Dict[str, Any]] = []
        per_actor: Dict[str, int] = {}
        per_type: Dict[str, int] = {}
        scores: Dict[str, float] = {}
        limit = min(k, len(scored))
        for e, s in scored:
            if len(picked) >= limit:
                break
            if any(per_actor.get(a, 0) >= 4 for a in e.get("actors", [])):
                continue
            if per_type.get(e.get("type") or "", 0) >= max(2, max(1, limit // 8)):
                continue
            picked.append(e)
            for a in e.get("actors", []):
                per_actor[a] = per_actor.get(a, 0) + 1
            t = e.get("type") or ""
            per_type[t] = per_type.get(t, 0) + 1
            key = f"{e.get('tick')}-{t}-{len(picked)}"
            scores[key] = s
        picked.sort(key=lambda x: x.get("tick", 0) or 0)
        return picked, scores

@agent_node("entropy_engine")
async def entropy_engine_node(state: NarrativeState, config: Dict[str, Any] = None) -> NarrativeState:
    events = state.get("normalized_events", [])
    engine = NarrativeEntropyEngine(events)
    k = 20
    filtered, scores = engine.select_events(k)
    return {"filtered_events": filtered, "event_scores": scores}


