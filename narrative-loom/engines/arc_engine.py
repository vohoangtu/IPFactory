from core.agent_wrapper import agent_node
from typing import Dict, Any, List
from state import NarrativeState

TENSION_MAP = {
    "battle": 0.9,
    "assassination": 0.8,
    "rebellion": 0.7,
    "law_change": 0.5,
    "marriage": 0.2,
    "birth": 0.1,
}

class DramaticArcEngine:
    def tension(self, e: Dict[str, Any]) -> float:
        return TENSION_MAP.get((e.get("type") or "").lower(), 0.4)

    def build(self, events: List[Dict[str, Any]]) -> Dict[str, List[Dict[str, Any]]]:
        ev = sorted(events, key=lambda x: x.get("tick", 0) or 0)
        if not ev:
            return {"setup": [], "conflict": [], "crisis": [], "climax": [], "aftermath": []}
        tensions = [self.tension(e) for e in ev]
        climax_idx = max(range(len(ev)), key=lambda i: tensions[i])
        a = max(1, climax_idx // 3)
        b = max(1, (2 * climax_idx) // 3)
        setup = ev[:a]
        conflict = ev[a:b]
        crisis = ev[b:climax_idx]
        climax = [ev[climax_idx]]
        aftermath = ev[climax_idx + 1:]
        return {"setup": setup, "conflict": conflict, "crisis": crisis, "climax": climax, "aftermath": aftermath}

@agent_node("arc_engine")
async def arc_engine_node(state: NarrativeState, config: Dict[str, Any] = None) -> NarrativeState:
    events = state.get("filtered_events", [])
    engine = DramaticArcEngine()
    arc = engine.build(events)
    return {"dramatic_arc": arc}


