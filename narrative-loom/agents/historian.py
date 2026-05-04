from core.agent_wrapper import agent_node
from core.logging import get_logger
import json
import os
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.output_parsers import StrOutputParser
from typing import Dict, Any

from state import NarrativeState
from utils.llm_factory import get_llm, get_llm_for_agent
from schemas import HistoricalOutline
from utils.memory_manager import EpisodicMemoryManager

log = get_logger(__name__)

memory_db = EpisodicMemoryManager()

historian_prompt = ChatPromptTemplate.from_messages([
    ("system", """Ngươi là Phóng viên Sử học (Reporter Historian) của Tòa soạn NarrativeLoom.
Nhiệm vụ của ngươi là tiếp nhận dữ liệu thô và bản chỉ thị từ Tổng Biên Tập để viết một bản dàn ý lịch sử.

BỐI CẢNH KỶ NGUYÊN HIỆN TẠI (ERA): {world_era}
Hãy điều chỉnh góc nhìn và ngôn ngữ của bản dàn ý sao cho phù hợp với trình độ văn minh này.
- Ví dụ: Thời Tiền sử tập trung vào sự sinh tồn và linh hồn; Thời Phong kiến tập trung vào lòng trung thành và vương quyền; Thời Hiện đại tập trung vào hạ tầng và tài chính.

LƯU Ý QUAN TRỌNG VỀ ĐỘ TIN CẬY (EPISTEMIC NOISE: {epistemic_noise} - Tầng: {epistemic_tier}):
1. Nếu Noise thấp (< 0.2): Hãy viết bản tin với sự khẳng định tuyệt đối (Canonical).
2. Nếu Noise trung bình (0.2-0.5): Hãy dùng các ngôn từ như "Một số nguồn ghi lại", "Có vẻ như".
3. Nếu Noise cao (> 0.5): Hãy chuyển sang phong cách "Huyền Sử" (Mythic). Dữ liệu lúc này mờ nhạt, hãy tập trung vào các biểu tượng thay vì con số. Một số sự kiện có thể bị mất mát (…).

CỘNG HƯỞNG LỊCH SỬ (RESONANCE):
{resonance_scars}
Nếu có các sẹo lịch sử cộng hưởng, hãy lồng ghép chúng vào dàn ý như những "bóng ma của quá khứ" đang ám ảnh sự kiện hiện tại.

Phân tích:
1. Nguyên nhân - Kết quả (Causality): Tại sao sự kiện này dẫn tới sự kiện kia?
2. 5-8 Mốc sự kiện chính (Narrative Beats): Chia nhỏ chuỗi dữ liệu thành các "nhịp" tin tức.
3. Tiếng vọng từ Đa vũ trụ (Whispers): Kết hợp các "lời thì thầm" từ các vũ trụ song song khác.
Đầu ra PHẢI tuân thủ nghiêm ngặt Schema JSON quy định.
"""),
    ("human", "Dữ liệu thô (Tick {tick_start} đến {tick_end}):\n\n{raw_payload}\n\nCHỈ THỊ TỪ TÒA SOẠN:\n{past_memories}\n\nTIẾNG VỌNG ĐA VŨ TRỤ:\n{whispers}")
])

@agent_node("historian")

async def historian_agent(state: NarrativeState, config: Dict[str, Any] = None) -> NarrativeState:
    """
    Hàm xử lý Node 'The Historian' trong LangGraph. 
    Lấy dữ liệu thô và gọi LLM để viết Historical Outline.
    """
    log.info("agent.run", agent="historian")
    
    # 1. Trích xuất Payload từ State
    chronicles = state.get("raw_chronicles", [])
    if not chronicles:
        return {"historical_outline": "Kho lưu trữ trống. Không có sự kiện nào xảy ra."}
    
    tick_start = state.get("tick_start", "N/A")
    tick_end = state.get("tick_end", "N/A")
    
    # Lược bỏ bớt thông tin dư thừa của mảng JSON để nhét vừa Context Window
    max_events = 100
    if len(chronicles) > max_events:
        chronicles_to_process = chronicles[:20] + chronicles[-(max_events-20):]
    else:
        chronicles_to_process = chronicles
        
    optimized_payload = []
    for c in chronicles_to_process:
        raw_payload = c.get("raw_payload") or {}
        if isinstance(raw_payload, str):
            try:
                raw_payload = json.loads(raw_payload)
            except (json.JSONDecodeError, ValueError):
                raw_payload = {}
        
        # The Historian is "blind" to math. Only extract high-level blurred intent.
        blurred_data = raw_payload
        if isinstance(raw_payload, dict) and "context" in raw_payload:
            ctx = raw_payload.get("context", {})
            blurred_data = {
                "action": ctx.get("action"),
                "intent": ctx.get("intent"),
                "archetype": ctx.get("archetype")
            }

        optimized_payload.append({
            "tick": c.get("from_tick"),
            "type": c.get("type"),
            "event_summary": blurred_data
        })
        
    payload_str = json.dumps(optimized_payload, ensure_ascii=False, indent=2)
    
    # 2. Setup Configuration cho LLM - DYNAMIC ROUTING
    llm = get_llm_for_agent(
        "historian",
        world_id=state.get("world_id"),
        current_tick=state.get("tick_end"),
        ai_runtime=state.get("ai_runtime"),
    )
    
    # Tích hợp Trí Nhớ Voi (Episodic Memory)
    events = state.get("normalized_events", [])
    actors = set()
    for e in events:
        for a in e.get("actors", []):
            actors.add(str(a))
            
    if actors and memory_db.enabled:
        query = f"Hậu quả nhân quả và diễn biến của các nhân vật: {', '.join(list(actors)[:10])}"
        memories = memory_db.retrieve_memories(query, k=2)
        past_memories = "\n---\n".join(memories) if memories else "Chưa có ký ức nào được ghi nhận trong Vector DB."
        log.debug("agent.detail", agent="historian", stage="memories_retrieved", count=len(memories))
    else:
        past_memories = "Hệ thống Memory Database đang tắt hoặc không có nhân vật nào đáng chú ý."

    # 3. Chains - Sử dụng Pydantic Structured Outputs
    whispers = state.get("cross_pollination_whispers", [])
    whispers_str = "\n- ".join(whispers) if whispers else "Không có tiếng vọng nào từ đa vũ trụ."
    
    structured_llm = llm.with_structured_output(HistoricalOutline)
    chain = historian_prompt | structured_llm
    
    try:
        result = await chain.ainvoke({
            "world_era": state.get("world_era", "genesis"),
            "tick_start": tick_start,
            "tick_end": tick_end,
            "raw_payload": payload_str,
            "past_memories": past_memories,
            "whispers": whispers_str,
            "epistemic_noise": state.get("epistemic_noise", 0.0),
            "epistemic_tier": state.get("epistemic_tier", "Chân Thực"),
            "resonance_scars": "\n- ".join(state.get("resonance_scars", [])) if state.get("resonance_scars") else "Không có cộng hương đáng kể."
        })
    except Exception as e:
        log.debug("agent.detail", agent="historian", stage="llm_call_error", exc=str(e))
        result = None

    if not result:
        log.debug("agent.detail", agent="historian", stage="json_parse_failed_fallback")
        outline_data = {"summary": "Lỗi phân tích cú pháp JSON.", "beats": []}
    else:
        outline_data = result.model_dump()
    
    log.debug("agent.detail", agent="historian", beats_count=len(outline_data.get("beats", [])))
    
    # 5. Cập nhật State
    return {"historical_outline": outline_data, "past_memories": past_memories}


