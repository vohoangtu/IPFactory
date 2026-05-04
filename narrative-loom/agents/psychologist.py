from core.agent_wrapper import agent_node
from core.logging import get_logger
from typing import Dict, Any
from state import NarrativeState
import httpx
import os

from utils.llm_factory import get_llm, get_llm_for_agent
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.output_parsers import StrOutputParser

log = get_logger(__name__)

# Phóng viên Điều tra (Investigative Reporter / Psychologist)
psychologist_prompt = ChatPromptTemplate.from_messages([
    ("system", """Ngươi là Phóng viên Điều tra (Investigative Reporter) của Tòa soạn NarrativeLoom.
Nhiệm vụ của ngươi là "đọc vị" tâm lý và động cơ của các nhân vật trong bản tin.
Dựa trên Dàn Ý Lịch Sử và Hồ Sơ Nhân Vật, hãy phân tích Động Lực, Nỗi Sợ và Trạng Thái Tinh Thần của họ.
Hãy tìm ra "sự thật ngầm hiểu" đằng sau các hành động thô.
"""),
    ("human", """Bản tin sử học:
{outline}
    
Hồ sơ nhân vật (JSON):
{profiles}
""")
])

@agent_node("psychologist")

async def psychologist_agent(state: NarrativeState, config: Dict[str, Any] = None) -> NarrativeState:
    """
    Node B: The Psychologist. 
    Lấy thông tin Outline và gọi Character API từ WorldOS để fetch profiles.
    """
    log.info("agent.run", agent="psychologist")
    
    outline_data = state.get("historical_outline", {})
    if isinstance(outline_data, dict):
        import json
        outline = json.dumps(outline_data, ensure_ascii=False, indent=2)
    else:
        outline = str(outline_data)
        
    chronicles = state.get("raw_chronicles", [])
    
    # Trích xuất dữ liệu tâm lý học (Numerical 18D Vectors & World State) từ raw_payload
    fetched_profiles = {}
    for c in chronicles:
        raw = c.get("raw_payload") or {}
        if isinstance(raw, str):
            try:
                import json
                raw = json.loads(raw)
            except (json.JSONDecodeError, ValueError):
                raw = {}
                
        if isinstance(raw, dict) and "context" in raw:
            ctx = raw.get("context", {})
            if "vm_state" in ctx:
                tick = c.get("from_tick", "unknown")
                archetype = ctx.get("archetype", "unknown_actor")
                
                # Bác sĩ tâm lý được quyền "nhìn thấu" toàn bộ chỉ số toán học
                fetched_profiles[f"Tick_{tick}_{archetype}"] = {
                    "traits_18d_vector": ctx["vm_state"].get("traits"),
                    "metrics": {
                        "energy": ctx["vm_state"].get("energy"),
                        "starving": ctx["vm_state"].get("starving"),
                        "is_heroic": ctx["vm_state"].get("is_heroic")
                    },
                    "environmental_pressure": {
                        "collapse_active": ctx["vm_state"].get("collapse_active"),
                        "causal_integrity": ctx["vm_state"].get("causal_integrity")
                    }
                }
                
    if not fetched_profiles:
        fetched_profiles = {"warning": "Không tìm thấy dữ liệu số học bên trong raw_payload. Có thể các event này tạo ra từ phiên bản engine cũ."}
    
    # 2. DYNAMIC ROUTING: Cấp AI trình độ cao cho nhà tâm lý học
    llm = get_llm_for_agent(
        "psychologist",
        world_id=state.get("world_id"),
        current_tick=state.get("tick_end"),
        ai_runtime=state.get("ai_runtime"),
    )
    chain = psychologist_prompt | llm | StrOutputParser()
    
    try:
        result = await chain.ainvoke({
            "outline": outline,
            "profiles": str(fetched_profiles)
        })
    except Exception as e:
        import logging
        logging.error(f"Psychologist LLM call failed: {e}")
        return {}

    return {
        "psychological_profiles": {"analysis": result}
    }


