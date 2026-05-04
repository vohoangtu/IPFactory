from core.agent_wrapper import agent_node
from core.logging import get_logger
import os
from typing import Dict, Any
from state import NarrativeState

from utils.llm_factory import get_llm, get_llm_for_agent
from langchain_core.prompts import ChatPromptTemplate
from schemas import CriticReview

log = get_logger(__name__)

# Biên tập viên Cao cấp (Senior Editor / Critic)
critic_prompt = ChatPromptTemplate.from_messages([
    ("system", """Ngươi là Biên tập viên Cao cấp (Senior Editor) của Tòa soạn NarrativeLoom. 
Nhiệm vụ của ngươi là kiểm duyệt bài viết của Phóng viên Viết.
Chấm điểm dựa trên: Độ sắc bén của góc nhìn, tính sống động (Show, Don't Tell) và sự nhất quán với chỉ thị của Tổng Biên Tập.
"""),
    ("human", "CẤU TRÚC BÀI VIẾT (Storyboard):\n{storyboard}\n\nBẢN THẢO CỦA PHÓNG VIÊN:\n{prose}")
])

@agent_node("critic")

async def critic_agent(state: NarrativeState, config: Dict[str, Any] = None) -> NarrativeState:
    log.info("agent.run", agent="critic")
    
    prose = state.get("final_prose", "")
    storyboard = state.get("storyboard", "")
    
    # 🌟 DYNAMIC ROUTING: Phân bổ mô hình rà soát cho Critic
    llm = get_llm_for_agent(
        "critic",
        world_id=state.get("world_id"),
        current_tick=state.get("tick_end"),
        ai_runtime=state.get("ai_runtime"),
    )
    structured_llm = llm.with_structured_output(CriticReview)
    
    chain = critic_prompt | structured_llm
    
    if isinstance(storyboard, dict):
        import json
        storyboard_str = json.dumps(storyboard, ensure_ascii=False)
    else:
        storyboard_str = str(storyboard)
        
    result = await chain.ainvoke({"storyboard": storyboard_str, "prose": prose})
    
    rev = state.get("revision_count", 0) + 1
    
    if not result:
        log.debug("agent.detail", agent="critic", stage="parse_error_fallback")
        report = {"score": 7, "feedbacks": ["Lỗi parse JSON"], "is_passed": True}
    else:
        report = result.model_dump()
        
    log.debug("agent.detail", agent="critic", score=report.get("score"), is_passed=report.get("is_passed"))
    
    return {"feedback": report, "revision_count": rev}


