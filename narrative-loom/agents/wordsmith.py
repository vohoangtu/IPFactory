from core.agent_wrapper import agent_node
from core.logging import get_logger
import os
from typing import Dict, Any
from state import NarrativeState
from utils.llm_factory import get_llm, get_llm_for_agent
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.output_parsers import StrOutputParser

log = get_logger(__name__)

# Phóng viên Viết (Staff Writer / Wordsmith)
wordsmith_prompt = ChatPromptTemplate.from_messages([
    ("system", """Ngươi là Phóng viên Viết (Staff Writer) của Tòa soạn NarrativeLoom. 
Nhiệm vụ của ngươi là biến Cấu trúc bài viết (Storyboard) từ Thư ký Tòa soạn thành một Bài báo sử thi (Feature Article).

KỶ NGUYÊN (ERA): {world_era}
PHONG CÁCH CHỈ ĐỊNH: {style_guidelines}

{era_context}

{power_system_manifesto}

Hãy sử dụng vốn từ vựng (Lexicon) và giọng văn (Vibe) phù hợp tuyệt đối với Kỷ nguyên này. 
Dùng kỹ thuật "Show, Don't Tell" để bài viết sống động.
"""),
    ("human", """Cấu trúc bài viết (Storyboard):
{storyboard}
""")
])

@agent_node("wordsmith")

async def wordsmith_agent(state: NarrativeState, config: Dict[str, Any] = None) -> NarrativeState:
    """
    Node D: The Wordsmith. 
    Bộ lọc cuối cùng biến mọi dữ liệu tẻ nhạt thành tiểu thuyết đỉnh cao.
    """
    log.info("agent.run", agent="wordsmith")
    
    # 🌟 DYNAMIC ROUTING: Tự động chọn mô hình chất lượng nhất cho Wordsmith
    llm = get_llm_for_agent(
        "wordsmith",
        world_id=state.get("world_id"),
        current_tick=state.get("tick_end"),
        ai_runtime=state.get("ai_runtime"),
    )
    style_guidelines = state.get("style_guidelines", "Phong cách kể chuyện tự do.")
    chain = wordsmith_prompt | llm | StrOutputParser()
    
    # 🌟 KIỂM TRA XEM ĐÂY CÓ PHẢI LÀ VÒNG REVISION KHÔNG?
    feedback_dict = state.get("feedback", {})
    # Nếu is_passed == False và đã có revision_count > 0, tức là The Critic chê!
    if not feedback_dict.get("is_passed", True) and state.get("revision_count", 0) > 0:
        log.info("agent.detail", agent="wordsmith", stage="revision_mode")
        feedbacks = "\n- ".join(feedback_dict.get("feedbacks", []))
        revision_prompt = (
            f"[LỆNH TỪ NHÀ PHÊ BÌNH: BẢN NHÁP CỦA BẠN BỊ TỪ CHỐI]\n\n"
            f"--- BẢN NHÁP CŨ ---\n{state.get('final_prose')}\n\n"
            f"--- LOG LỖI TỪ CRITIC ---\n- {feedbacks}\n\n"
            f"YÊU CẦU ĐỘC ĐOÁN: Dựa trên bản nháp cũ, hãy VIẾT LẠI MỘT PHIÊN BẢN MỚI tinh tế hơn, hùng tráng hơn và tuân thủ tuyệt đối các đóng góp phía trên. Đảm bảo Show, Don't Tell!"
        )
        scene_result = await chain.ainvoke({
            "storyboard": revision_prompt, 
            "style_guidelines": style_guidelines,
            "world_era": state.get("world_era", "genesis"),
            "power_system_manifesto": state.get("power_system_manifesto", ""),
            "era_context": state.get("era_context", "")
        })
        
        final_prose = scene_result if isinstance(scene_result, str) else str(scene_result.content if hasattr(scene_result, 'content') else scene_result)
        log.debug("agent.detail", agent="wordsmith", stage="revision_complete", prose_length=len(final_prose))
        return {"final_prose": final_prose}
        
    # 🌟 NẾU LÀ LẦN VIẾT ĐẦU TIÊN: Tách storyboard thành từng Scene
    storyboard_data = state.get("storyboard", {})
    
    # fallback
    if isinstance(storyboard_data, str):
        scenes_data = storyboard_data.split("[SCENE]")
    else:
        scenes_data = storyboard_data.get("scenes", [])
        
    chapter_content = []
    
    # Nếu không detect được "Scene", thì fallback lại dùng nguyên cục
    if not scenes_data:
        log.debug("agent.detail", agent="wordsmith", stage="single_take_expansion")
        result = await chain.ainvoke({
            "storyboard": str(storyboard_data), 
            "style_guidelines": style_guidelines,
            "world_era": state.get("world_era", "genesis"),
            "power_system_manifesto": state.get("power_system_manifesto", ""),
            "era_context": state.get("era_context", "")
        })
        chapter_content.append(result)
    else:
        log.debug("agent.detail", agent="wordsmith", stage="batch_expansion_start", scenes_count=len(scenes_data))
        batch_inputs = []
        for i, scene in enumerate(scenes_data):
            if isinstance(scene, str):
                if not scene.strip(): continue
                scene_text = f"[SCENE] {scene}"
            else:
                scene_text = (
                    f"Bối cảnh: {scene.get('setting', '')}\n"
                    f"Góc máy: {scene.get('camera_angle', '')}\n"
                    f"Nhân vật tham gia: {', '.join(str(x) for x in scene.get('involved_characters', []))}\n"
                    f"Mâu thuẫn trung tâm: {scene.get('central_conflict', '')}"
                )
            
            batch_inputs.append({
                "storyboard": f"Phân cảnh {i+1}:\n{scene_text}", 
                "style_guidelines": style_guidelines,
                "world_era": state.get("world_era", "genesis"),
                "power_system_manifesto": state.get("power_system_manifesto", ""),
                "era_context": state.get("era_context", "")
            })
 
        # 🌟 Giai đoạn 3.2: Gửi batch đồng thời cho vLLM
        try:
            results = await chain.abatch(batch_inputs)
            chapter_content.extend(results)
            log.debug("agent.detail", agent="wordsmith", stage="batch_expansion_complete", results_count=len(results))
        except Exception as e:
            log.warning("agent.detail", agent="wordsmith", stage="batch_failed", error=str(e))
            # Fallback: gọi từng scene một
            for batch_input in batch_inputs:
                try:
                    result = await chain.ainvoke(batch_input)
                    chapter_content.append(result)
                except Exception as inner_e:
                    log.warning("agent.detail", agent="wordsmith", stage="scene_failed", error=str(inner_e))
                    chapter_content.append(f"[Lỗi xử lý phân cảnh: {inner_e}]")
 
    final_prose = "\n\n".join(chapter_content)
    log.debug("agent.detail", agent="wordsmith", stage="prose_finalized", prose_length=len(final_prose))
    
    return {"final_prose": final_prose}


