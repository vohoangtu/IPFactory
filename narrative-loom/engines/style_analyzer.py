from core.agent_wrapper import agent_node
from state import NarrativeState
from typing import Dict, Any

@agent_node("style_analyzer")
async def style_analyzer_node(state: NarrativeState, config: Dict[str, Any] = None) -> NarrativeState:
    """
    Style Analyzer: Phân tích Genre và thiết lập Style Guidelines cho câu chuyện.
    Giúp Wordsmith và Director bám sát phong cách đặc thù của World.
    """
    genre = state.get("genre", "generic").lower()

    # Map genre sang style guidelines
    style_map = {
        "wuxia": "Phong cách Kiếm hiệp, ngôn từ hoa mỹ, chú trọng vào võ công, danh dự và nội tâm cao thâm.",
        "cyberpunk": "Phong cách Cyberpunk, ngôn từ lạnh lùng, kỹ thuật, chú trọng vào sự tương phản giữa High-tech và Low-life.",
        "historical": "Phong cách Sử thi chính thống, ngôn từ trang trọng, khách quan nhưng hào hùng.",
        "dark_fantasy": "Phong cách Dark Fantasy, không khí u ám, tuyệt vọng, chú trọng vào nỗi sợ và sự hy sinh.",
        "solarpunk": "Phong cách Solarpunk, tươi sáng, hy vọng, chú trọng vào sự hài hòa giữa thiên nhiên và công nghệ.",
        "cosmic_horror": "Phong cách Cosmic Horror, ngôn từ gợi sự điên rồ, cái vô hạn và sự nhỏ bé của con người trước vũ trụ.",
    }

    guidelines = style_map.get(genre, "Phong cách kể chuyện trung tính, rõ ràng và mạch lạc.")

    return {"style_guidelines": guidelines}


