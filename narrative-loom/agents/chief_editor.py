from core.agent_wrapper import agent_node
from core.logging import get_logger
import os
from typing import Dict, Any
from state import NarrativeState
from utils.llm_factory import get_llm, get_llm_for_agent
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.output_parsers import StrOutputParser

log = get_logger(__name__)

# Tổng Biên Tập (Chief Editor) - Người đặt ra "Góc nhìn" và "Thông điệp" cho bài viết
chief_editor_prompt = ChatPromptTemplate.from_messages([
    ("system", """Ngươi là Tổng Biên Tập (Chief Editor) của Tòa soạn NarrativeLoom. 
Nhiệm vụ của ngươi là tiếp nhận các phân tích dữ liệu (Entropy, Attractor, Arc) và đặt ra "Góc nhìn" (The Angle) cho bài viết sắp tới.
Ngươi phải quyết định:
1. Thông điệp chủ đạo (Theme): Bài viết này về sự hy vọng, sự sụp đổ, hay sự huyền bí?
2. Tiêu đề dự kiến (Working Title): Một tiêu đề mang tính báo chí hoặc sử thi.
3. Chỉ thị cho phóng viên: Dặn dò Phóng viên Sử gia (Historian) và Phóng viên Văn hóa (Mythologist) cần tập trung vào khía cạnh nào.
Giữ cho chỉ thị ngắn gọn, sắc bén và có tính định hướng cao.
"""),
    ("human", """Dữ liệu tòa soạn thu thập được:
- Entropy: {entropy}
- Giai đoạn (Phase): {phase}
- Điểm kỳ dị (Singularity): {singularity}
- Phong cách chủ đạo (Genre): {genre}

Hãy đưa ra góc nhìn biên tập cho số báo này.
""")
])

@agent_node("chief_editor")

async def chief_editor_agent(state: NarrativeState, config: Dict[str, Any] = None) -> NarrativeState:
    log.info("agent.run", agent="chief_editor")
    
    entropy = state.get("event_scores", {}).get("total_entropy", 0.5)
    phase = state.get("narrative_phase", "Unknown")
    singularity = state.get("singularity", "None")
    genre = state.get("genre", "Generic")
    
    # 🌟 DYNAMIC ROUTING: Cấp quyền Tổng biên tập sử dụng Model Pro nhất
    llm = get_llm_for_agent(
        "chief_editor",
        world_id=state.get("world_id"),
        current_tick=state.get("tick_end"),
        ai_runtime=state.get("ai_runtime"),
    )
    chain = chief_editor_prompt | llm | StrOutputParser()
    
    result = await chain.ainvoke({
        "entropy": str(entropy),
        "phase": phase,
        "singularity": str(singularity),
        "genre": genre
    })
    
    # Lưu chỉ thị vào state
    editorial_instruction = f"[CHIEF EDITOR ANGLE]:\n{result}"
    
    return {"past_memories": state.get("past_memories", "") + "\n" + editorial_instruction}


