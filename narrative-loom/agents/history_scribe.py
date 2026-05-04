from core.agent_wrapper import agent_node
from core.logging import get_logger
import json
from langchain_core.prompts import ChatPromptTemplate
from typing import Dict, Any

from state import NarrativeState
from utils.llm_factory import get_llm_for_agent

log = get_logger(__name__)

history_prompt = ChatPromptTemplate.from_messages([
    ("system", """Ngươi là Sử Quan (History Scribe) của WorldOS.
Hệ thống lõi (Engine) vừa đo được một biến động chấn động mức độ vĩ mô. Nhiệm vụ của ngươi là đặt tên cho Sự kiện / Kỷ nguyên này và mô tả nó.

THÔNG SỐ RAW:
- Loại Sự kiện: {event_type}
- Zone chịu ảnh hưởng lớn nhất: {zone_id}
- Hệ số Tác động (Impact Score): {impact_score}
- Dữ liệu kích hoạt (Trigger Data): {trigger_data}

YÊU CẦU:
Dựa vào loại sự kiện (vd: collapse, golden_age), đặt một Tên Sự Kiện thật hoành tráng.
Viết 1 đoạn Văn bia Lịch sử (2-3 câu) trang trọng để khắc vào Dòng thờigian (Timeline).
Trả về JSON chứa: "event_name" và "chronicle".
"""),
    ("human", "Ghi chép sự kiện này vào Sử sách.")
])


@agent_node("history_scribe")
async def scribe_history(state: NarrativeState, config: Dict[str, Any] = None) -> dict:
    req_data = state
    llm = get_llm_for_agent("historian", world_id=req_data.get("world_id"))
    structured_llm = llm.with_structured_output(schema={"type": "object", "properties": {"event_name": {"type": "string"}, "chronicle": {"type": "string"}}, "required": ["event_name", "chronicle"]})
    chain = history_prompt | structured_llm

    try:
        result = await chain.ainvoke({
            "event_type": req_data.get("event_type"),
            "zone_id": req_data.get("zone_id", "Unknown"),
            "impact_score": req_data.get("impact_score"),
            "trigger_data": json.dumps(req_data.get("trigger_data", {})),
        })
        return result
    except Exception as e:
        log.debug("agent.detail", agent="history_scribe", stage="scribe_error", exc=str(e))
        return {"event_name": "Sự Kiện Dị Thường", "chronicle": "Một chuyển động chưa từng có đã quét qua vùng không gian này."}


async def history_scribe_api(state: NarrativeState, config: Dict[str, Any] = None) -> dict:
    """
    Adaptive Token Logic: Chỉ dùng AI khi impact_score >= 5.0
    """
    impact_score = state.get("impact_score", 0.0)
    if impact_score < 5.0:
        # Fallback to Rule-based / Raw text to save API cost
        event_type = state.get("event_type", "")
        world_id = state.get("world_id", 0)
        log.debug("agent.detail", agent="history_scribe", stage="skipped_low_impact", impact_score=impact_score)
        name = event_type.replace("_", " ").title()
        return {
            "event_name": f"Minor Event: {name}",
            "chronicle": f"Hệ thống ghi nhận sự kiện '{name}' tại Vũ trụ #{world_id}. Tác động vi mô không đủ để khắc sâu vào lịch sử."
        }

    # Trigger LLM
    log.info("agent.detail", agent="history_scribe", stage="llm_triggered", impact_score=impact_score)
    return await scribe_history(state)
