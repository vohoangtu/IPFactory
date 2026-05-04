from core.agent_wrapper import agent_node
from core.logging import get_logger
import os
from typing import Dict, Any
from state import NarrativeState
from utils.llm_factory import get_llm, get_llm_for_agent
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.output_parsers import StrOutputParser

log = get_logger(__name__)

# Phóng viên Văn hóa (Cultural Reporter / Mythologist)
mythologist_prompt = ChatPromptTemplate.from_messages([
    ("system", """Ngươi là Phóng viên Văn hóa (Cultural Reporter) của Tòa soạn NarrativeLoom. 
Nhiệm vụ của ngươi là tiếp nhận bản tin từ Phóng viên Sử học và "Thần thoại hóa" nó.
Hãy bám sát "Góc nhìn" (The Angle) mà Tổng Biên Tập đã đề ra.
Tìm kiếm các biểu tượng, điển tích và tiềm năng thần thánh trong các sự kiện.
"""),
    ("human", """Bản tin sử học:
{outline}

Chỉ thị từ Tổng Biên Tập:
{style}
""")
])

@agent_node("mythologist")

async def mythologist_agent(state: NarrativeState, config: Dict[str, Any] = None) -> NarrativeState:
    log.info("agent.run", agent="mythologist")
    
    outline = state.get("historical_outline", "")
    style = state.get("style_guidelines", "")
    
    # 🌟 DYNAMIC ROUTING: Phân bổ mô hình cho Mythologist (GD2)
    llm = get_llm_for_agent(
        "mythologist",
        world_id=state.get("world_id"),
        current_tick=state.get("tick_end"),
        ai_runtime=state.get("ai_runtime"),
    )
    chain = mythologist_prompt | llm | StrOutputParser()
    
    result = await chain.ainvoke({
        "outline": str(outline),
        "style": style
    })
    
    return {"mythic_fragments": result}


