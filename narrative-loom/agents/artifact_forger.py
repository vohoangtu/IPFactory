from core.agent_wrapper import agent_node
from core.logging import get_logger
import json
from langchain_core.prompts import ChatPromptTemplate
from typing import Dict, Any
from pydantic import BaseModel, Field

from state import NarrativeState
from utils.llm_factory import get_llm_for_agent

log = get_logger(__name__)


class ArtifactOutput(BaseModel):
    name: str = Field(description="Tên của Tạo tác")
    lore: str = Field(description="Đoạn truyện ngắn về Tạo tác")


artifact_prompt = ChatPromptTemplate.from_messages([
    ("system", """Ngươi là Rèn sư Truyền thuyết (Artifact Forger) của WorldOS.
Nhiệm vụ của ngươi là biến một khối vật chất (raw mass & knowledge) thành một Cổ vật / Vật phẩm huyền thoại.

THÔNG SỐ RAW:
- ID: {artifact_id}
- Zone: {zone_id}
- Khối lượng (Mass): {mass}
- Mức tri thức mã hóa: {knowledge}
- Kỷ nguyên: {world_era}

YÊU CẦU:
Hãy rèn ra tên của Tạo tác này và viết một đoạn truyện ngắn (2-3 câu) về đặc điểm nhận dạng, vẻ đẹp vật lý, cũng như quyền năng/tri thức nó chứa đựng.
Trả về dữ liệu JSON bao gồm: "name" và "lore".
"""),
    ("human", "Hãy rèn tạo tác này.")
])


@agent_node("artifact_forger")
async def forge_artifact(state: NarrativeState, config: Dict[str, Any] = None) -> dict:
    req_data = state
    llm = get_llm_for_agent("director", world_id=req_data.get("world_id"))
    structured_llm = llm.with_structured_output(ArtifactOutput)
    chain = artifact_prompt | structured_llm

    try:
        result = await chain.ainvoke({
            "artifact_id": req_data.get("artifact_id"),
            "zone_id": req_data.get("zone_id"),
            "mass": req_data.get("mass"),
            "knowledge": req_data.get("knowledge"),
            "world_era": req_data.get("world_era", "genesis")
        })
        if result:
            return result.model_dump() if hasattr(result, "model_dump") else result
        return {"name": "Khối Vật Chất Vô Danh", "lore": "Vật phẩm này tỏa ra ánh sáng rực rỡ nhưng không ai biết nguồn gốc của nó."}
    except Exception as e:
        log.debug("agent.detail", agent="artifact_forger", stage="forge_error", exc=str(e))
        return {"name": "Khối Vật Chất Vô Danh", "lore": "Vật phẩm này tỏa ra ánh sáng rực rỡ nhưng không ai biết nguồn gốc của nó."}


async def artifact_forger_api(req: dict) -> dict:
    """API wrapper called from main.py when /forge-artifact is hit."""
    return await forge_artifact(req)
