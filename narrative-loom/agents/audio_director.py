from core.agent_wrapper import agent_node
from core.logging import get_logger
import asyncio
import os
import random

log = get_logger(__name__)

class AudioDirectorAgent:
    """
    Agent chịu trách nhiệm sinh nhạc Ambient (Soundtrack) cho từng Kỷ Nguyên.
    Trong V1.0, chúng ta Mock Pipeline lấy 1 URL mp3 dựa trên motif Kỷ Nguyên.
    """

    def __init__(self):
        # Các file âm thanh Mocking tương ứng với Motif/Vibe của Kỷ Nguyên
        self.mock_tracks = {
            "peaceful": "https://cdn.pixabay.com/download/audio/2022/05/16/audio_9b9eeb25ec.mp3?filename=ambient-piano-amp-strings-10711.mp3",
            "chaotic": "https://cdn.pixabay.com/download/audio/2022/03/15/audio_27d727b11d.mp3?filename=epic-hollywood-trailer-9489.mp3",
            "mystical": "https://cdn.pixabay.com/download/audio/2021/08/04/audio_0625c1539c.mp3?filename=deep-meditation-192828.mp3",
            "default": "https://cdn.pixabay.com/download/audio/2022/01/18/audio_d0a13f69d2.mp3?filename=cinematic-time-lapse-115672.mp3"
        }

    @agent_node("audio_director")

    async def compose_soundtrack(self, epoch_name: str, core_theme: str) -> dict:
        """
        Nhận vào tên Kỷ nguyên và Chủ đề lõi, phân tích để pick nhạc phù hợp.
        Chỗ này sau này có thể là hàm gọi API tới HuggingFace AudioGen / Suno.
        """
        log.info("agent.run", agent="audio_director", epoch_name=epoch_name)
        delay = float(os.environ.get("AUDIO_PROCESSING_DELAY", "0"))
        if delay > 0:
            await asyncio.sleep(delay)

        core_theme_lower = core_theme.lower()
        selected_style = "default"

        if any(word in core_theme_lower for word in ["war", "blood", "chaos", "destruction", "fall"]):
            selected_style = "chaotic"
        elif any(word in core_theme_lower for word in ["peace", "dawn", "golden", "light"]):
            selected_style = "peaceful"
        elif any(word in core_theme_lower for word in ["magic", "unknown", "whisper", "god", "soul"]):
            selected_style = "mystical"
        else:
            # Ngẫu nhiên nếu không phân loại được
            selected_style = random.choice(list(self.mock_tracks.keys()))

        track_url = self.mock_tracks.get(selected_style, self.mock_tracks["default"])

        log.info("agent.detail", agent="audio_director", stage="soundtrack_complete", style=selected_style)

        return {
            "epoch_name": epoch_name,
            "style": selected_style,
            "stream_url": track_url,
            "success": True
        }


