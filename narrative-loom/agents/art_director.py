from core.agent_wrapper import agent_node
from core.logging import get_logger
import httpx
from typing import Optional

from utils.llm_factory import _get_pool_key

log = get_logger(__name__)

@agent_node("art_director")
async def generate_visual_asset(prompt_text: str, is_portrait: bool = True) -> Optional[str]:
    """
    Sinh ảnh Visual Asset (Portrait hoặc Blueprint) thông qua OpenAI DALL-E 3 API.
    Sử dụng httpx để gọi REST API trực tiếp. Key resolved từ backend AI Pool.
    """
    log.info("agent.run", agent="art_director")

    try:
        pool_data = _get_pool_key("art_director", provider="openai")
        api_key = pool_data.get("api_key")
        if not api_key:
            log.warning("agent.warning", agent="art_director", reason="pool_returned_empty_key")
            return None
    except Exception as e:
        log.warning("agent.warning", agent="art_director", reason="pool_resolution_failed", error=str(e))
        return None

    style_suffix = (
        " Digital art, highly detailed portrait, sci-fi cyberpunk and dark fantasy aesthetic. "
        "Intricate lighting, glowing accents, cinematic composition, masterpiece, 8k resolution."
    ) if is_portrait else (
        " Highly detailed technical blueprint, glowing schematic on a dark grid background. "
        "Sci-fi artifact, mysterious technology, isometric view, intricate glowing circuits."
    )

    final_prompt = f"{prompt_text}. {style_suffix}"
    # DALL-E 3 limit prompt length to 1000 characters
    final_prompt = final_prompt[:950]

    try:
        async with httpx.AsyncClient(timeout=30.0) as client:
            response = await client.post(
                "https://api.openai.com/v1/images/generations",
                headers={
                    "Authorization": f"Bearer {api_key}",
                    "Content-Type": "application/json"
                },
                json={
                    "model": "dall-e-3",
                    "prompt": final_prompt,
                    "n": 1,
                    "size": "1024x1024",
                    "quality": "standard"
                }
            )
            
            if response.status_code == 200:
                data = response.json()
                image_url = data['data'][0]['url']
                log.info("agent.detail", agent="art_director", stage="asset_generated", image_url=image_url)
                return image_url
            else:
                log.error("agent.error", agent="art_director", stage="dalle3_failed", detail=response.text)
                return None
    except Exception as e:
        log.error("agent.error", agent="art_director", stage="exception", exc=str(e))
        return None


