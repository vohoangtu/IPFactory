from core.agent_wrapper import agent_node
from core.logging import get_logger
import os
from typing import Dict, Any
from state import NarrativeState

from utils.llm_factory import get_llm, get_llm_for_agent
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.output_parsers import StrOutputParser

from schemas import Storyboard

log = get_logger(__name__)

# Thư ký Tòa soạn (Managing Editor / Director)
director_prompt = ChatPromptTemplate.from_messages([
    ("system", """Ngươi là Thư ký Tòa soạn (Managing Editor) của Tòa soạn NarrativeLoom. 
Nhiệm vụ của ngươi là tổng hợp các bản tin từ Phóng viên Sử học, Phóng viên Văn hóa và Phóng viên Điều tra để xây dựng một Cấu trúc bài viết (Storyboard).

KỶ NGUYÊN HIỆN TẠI (ERA): {world_era}
Hãy bám sát "Góc nhìn" (The Angle) mà Tổng Biên Tập đã đề ra. 
Chia nhỏ bài viết thành các phân đoạn (Scenes), xác định bối cảnh, nhân vật và xung đột trung tâm cho mỗi đoạn.

QUY TẮC THỊ GIÁC (VFX):
Hãy thiết kế `vfx_config` phản ánh đúng tinh thần của {world_era}.
- Paleo/Cave: Màu lửa (#ff4500), biến dạng cao (distortion: 0.8), hạt dày (100).
- Feudal: Màu vàng hoàng tộc (#ffd700), ổn định (distortion: 0.2), sương mù (mist).
- Cyberpunk: Màu xanh Neon (#00f3ff), nhiễu loạn (distortion: 0.5), glitch.

Đầu ra PHẢI tuân thủ nghiêm ngặt chuẩn định dạng JSON Schema của Storyboard.
"""),
    ("human", """Bản tin sử học & Thần thoại:
{outline}
    
Phân tích tâm lý & Động cơ:
{psychology}

Bối cảnh thế giới hiện tại:
{world_state}
""")
])

@agent_node("director")

async def director_agent(state: NarrativeState, config: Dict[str, Any] = None) -> NarrativeState:
    """
    Node C: The Director. 
    Tổng hợp Outline, Psychology và WorldState để tạo Storyboard kịch tính.
    """
    log.info("agent.run", agent="director")
    
    # Lôi World State từ raw payload (Causal Integrity / Collapse Threat)
    chronicles = state.get("raw_chronicles", [])
    world_state = "Trạng thái màng thực tại ổn định."
    for c in chronicles:
        raw = c.get("raw_payload") or {}
        if isinstance(raw, str):
            import json
            try: raw = json.loads(raw)
            except (json.JSONDecodeError, ValueError): raw = {}
        if isinstance(raw, dict) and "context" in raw:
            vm = raw["context"].get("vm_state", {})
            causal = vm.get("causal_integrity")
            collapse = vm.get("collapse_active")
            if causal is not None:
                world_state = f"Độ nguyên vẹn nhân quả (Causal Integrity): {causal}%. Tình trạng sụp đổ (Collapse Active): {collapse}."
                break
    
    # 🌟 DYNAMIC ROUTING: Cấp AI trình độ cao cho Tổng đạo diễn kịch bản
    llm = get_llm_for_agent(
        "director",
        world_id=state.get("world_id"),
        current_tick=state.get("tick_end"),
        ai_runtime=state.get("ai_runtime"),
    )
    structured_llm = llm.with_structured_output(Storyboard)
    chain = director_prompt | structured_llm
    
    # Historian có thể là Dictionary do đã nâng cấp Pydantic bên kia
    outline_data = state.get("historical_outline", {})
    if isinstance(outline_data, dict):
        import json
        outline_str = json.dumps(outline_data, ensure_ascii=False, indent=2)
    else:
        outline_str = str(outline_data)
        
    try:
        result = await chain.ainvoke({
            "world_era": state.get("world_era", "genesis"),
            "outline": outline_str,
            "psychology": state.get("psychological_profiles", {}).get("analysis", ""),
            "world_state": world_state
        })
        
        if not result:
            log.debug("agent.detail", agent="director", stage="json_parse_failed")
            result_dict = {"title": "Lỗi phân cảnh", "scenes": []}
        else:
            result_dict = result.model_dump()
    except Exception as e:
        log.warning("agent.detail", agent="director", stage="structured_output_failed", error=str(e))
        # Fallback: tạo storyboard đơn giản từ outline
        result_dict = {
            "title": outline_data.get("summary", "Chronicle")[:50],
            "scenes": [
                {
                    "setting": "Không gian chính",
                    "camera_angle": "Wide shot",
                    "involved_characters": [],
                    "central_conflict": outline_data.get("summary", "Sự kiện chính")[:100]
                }
            ]
        }
        
    log.debug("agent.detail", agent="director", scenes_count=len(result_dict.get("scenes", [])))
    
    return {"storyboard": result_dict}


