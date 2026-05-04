from pydantic import BaseModel, Field
from typing import List, Optional

class NarrativeBeat(BaseModel):
    title: str = Field(description="Tiêu đề gợi nhớ của nhịp truyện")
    causality: str = Field(description="Nguyên nhân và kết quả logic dẫn đến nhịp truyện này từ dữ liệu mô phỏng")
    impact_scale: str = Field(description="Tầm vóc: Vi mô, Vĩ mô, Xã hội, Tôn giáo, v.v.")

class HistoricalOutline(BaseModel):
    summary: str = Field(description="Tóm tắt bối cảnh tổng quan của Kỷ nguyên/Thời kỳ này")
    beats: List[NarrativeBeat] = Field(description="Danh sách 5-8 nhịp truyện chính (causality chain)")

class Scene(BaseModel):
    setting: str = Field(description="Bối cảnh, thời tiết, âm thanh nền (Không khí)")
    camera_angle: str = Field(description="Góc máy và nhịp điệu (VD: Cận cảnh đôi mắt, Toàn cảnh chiến trường)")
    central_conflict: str = Field(description="Mâu thuẫn và diễn biến hành động cốt lõi trong phân cảnh")
    involved_characters: List[str] = Field(description="Danh sách ID hoặc Tên nhân vật/phe phái tham gia")

class VfxConfig(BaseModel):
    primary_color: str = Field(description="Mã màu HEX đại diện cho kỷ nguyên này (VD: #ff4500 cho Lửa)")
    distortion: float = Field(description="Mức độ biến dạng thực tại từ 0.0 đến 1.0")
    particle_density: int = Field(description="Mật độ hạt ánh sáng/năng lượng (Dày hay mỏng)")
    atmosphere_filter: str = Field(description="Loại filter không khí: 'mist', 'glitch', 'sepia', 'neon'")

class Storyboard(BaseModel):
    title: str = Field(description="Tên của phân đoạn hoặc chương truyện điện ảnh")
    scenes: List[Scene] = Field(description="Danh sách các phân cảnh chi tiết, nối tiếp nhau")
    vfx_config: VfxConfig = Field(description="Cấu hình thị giác chung cho phân đoạn này")

class CriticReview(BaseModel):
    score: int = Field(description="Điểm đánh giá chất lượng văn bản từ 1-10 (10 là kiệt tác)")
    feedbacks: List[str] = Field(description="Danh sách các điểm cần sửa chữa hoặc thêm thắt để tăng tính drama (Ghi rõ cần sửa đoạn nào)")
    is_passed: bool = Field(description="True nếu văn bản đã đủ tốt (score >= 7) và không cần sửa thêm, False nếu cần phải viết lại")


# ── VAF Animation Script Models ─────────────────────────

class VAFBackground(BaseModel):
    type: str = Field(description="Background type: gradient | solid | pattern")
    colors: List[str] = Field(description="List of hex color codes")
    description: str = Field(description="Descriptive text for procedural generation")

class VAFAtmosphere(BaseModel):
    filter: str = Field(description="Atmosphere filter: mist | sepia | grain | glitch | aurora | dust | none")
    intensity: float = Field(description="Filter intensity 0.0 - 1.0")
    weather: str | None = Field(default=None, description="Weather effect: rain | snow | fire_embers | sandstorm | None")

class VAFCameraMovement(BaseModel):
    type: str = Field(description="Camera type: static | zoom_in | zoom_out | pan_left | pan_right | dolly | shake")
    speed: float = Field(description="Movement speed 0.1 (slow) - 2.0 (fast)")
    easing: str = Field(description="Easing function: ease-in | ease-out | ease-in-out | linear")

class VAFEffect(BaseModel):
    type: str = Field(description="Effect type: particles | screen_shake | flash | ripple | energy_burst | glow")
    intensity: float = Field(description="Effect intensity 0.0 - 1.0")
    color: str | None = Field(default=None, description="Hex color, None = use primary_color from vfx_config")
    trigger_at_ms: int = Field(description="Trigger time relative to scene start in milliseconds")

class VAFTransition(BaseModel):
    type: str = Field(description="Transition type: fade | dissolve | wipe_left | wipe_right | zoom_through | cut")
    duration_ms: int = Field(description="Transition duration 300-1500ms")

class VAFScene(BaseModel):
    id: str = Field(description="Scene identifier e.g. scene_1")
    type: str = Field(description="Scene type: establishing | action | tension | climax | resolution")
    duration_ms: int = Field(description="Scene duration 3000-15000ms")
    background: VAFBackground
    atmosphere: VAFAtmosphere
    camera: VAFCameraMovement
    effects: List[VAFEffect] = Field(default_factory=list)
    narration: str = Field(description="Short narration text overlay for this scene")
    transition: VAFTransition

class AnimationScript(BaseModel):
    total_duration_ms: int = Field(description="Total animation duration in milliseconds (15000-60000)")
    scenes: List[VAFScene] = Field(description="Sequential list of 2-8 scenes")
