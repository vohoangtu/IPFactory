from typing import Dict, Any

def get_mock_historian_state(noise: float = 0.05) -> Dict[str, Any]:
    """
    Tạo dữ liệu giả lập cho Historian Agent với các mức độ Epistemic Noise khác nhau.
    """
    tier = "Chân Thực"
    if noise > 0.8:
        tier = "Hư Vô"
    elif noise > 0.5:
        tier = "Huyền Sử"
    elif noise > 0.2:
        tier = "Mơ Hồ"

    return {
        "world_id": 1,
        "world_era": "medieval_feudal",
        "tick_start": 45000,
        "tick_end": 45100,
        "raw_chronicles": [
            {
                "from_tick": 45010,
                "type": "ActorDied",
                "raw_payload": {
                    "context": {
                        "action": "Tiêu diệt mục tiêu",
                        "intent": "Chiếm đoạt vương quyền",
                        "archetype": "Bạo chúa"
                    }
                }
            },
            {
                "from_tick": 45050,
                "type": "CityBurned",
                "raw_payload": {
                    "context": {
                        "action": "Hủy diệt cấu trúc",
                        "intent": "Xóa bỏ tàn tích cũ",
                        "archetype": "Kẻ rồ dại"
                    }
                }
            }
        ],
        "normalized_events": [],
        "epistemic_noise": noise,
        "epistemic_tier": tier,
        "resonance_scars": [
            "Cuộc đại chiến 1000 năm trước để lại bóng ma của sự phản bội.",
            "Lời nguyền của dòng tộc Crimson ám ảnh các vương triều sau này."
        ],
        "reality_stability": 1.0 - noise,
        "cross_pollination_whispers": ["Ở vũ trụ 2, vị vua này đã sống sót."],
        "past_memories": "Chưa có dữ liệu tiền sử.",
        "current_agent": "start"
    }
