from core.agent_wrapper import agent_node
from typing import Dict, Any, List
from state import NarrativeState

class EventNormalizer:
    TYPE_MAP = {
        "king_killed": "assassination",
        "political_murder": "assassination",
        "death_event": "death",
    }

    def normalize_event(self, e: Dict[str, Any]) -> Dict[str, Any]:
        t = e.get("type")
        nt = self.TYPE_MAP.get(t, t)
        payload = e.get("raw_payload") or {}
        if isinstance(payload, str):
            import json
            try:
                payload = json.loads(payload)
            except (json.JSONDecodeError, ValueError):
                payload = {}

        actors = []
        if isinstance(payload, dict):
            a = payload.get("actors") or payload.get("targets") or []

            # Cố gắng bóc Actor ID từ nested Context (Định dạng mới)
            if not a and "context" in payload:
                ctx = payload.get("context", {})
                vm = ctx.get("vm_state", {})
                act_id = vm.get("id") or vm.get("actor_id")
                if act_id:
                    a = [act_id]

            # Fallback
            if not a and e.get("actor_id"):
                a = [e.get("actor_id")]

            if isinstance(a, list):
                actors = [str(x) for x in a]
            elif isinstance(a, str) or isinstance(a, int):
                actors = [str(a)]
        return {
            "tick": e.get("from_tick") or e.get("tick"),
            "type": nt,
            "actors": actors,
            "payload": payload,
        }

@agent_node("event_normalizer")
async def event_normalizer_node(state: NarrativeState, config: Dict[str, Any] = None) -> NarrativeState:
    raw = state.get("raw_chronicles", [])
    normalizer = EventNormalizer()
    normalized = [normalizer.normalize_event(e) for e in raw]
    return {"normalized_events": normalized}


