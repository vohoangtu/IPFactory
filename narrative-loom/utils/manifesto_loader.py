import json
import os
from typing import Dict, Any, Optional

from core.logging import get_logger

log = get_logger(__name__)

class ManifestoLoader:
    """
    ManifestoLoader: Truy xuất 'Chân lý' về các quy luật thực tại và bối cảnh từ Knowledge Base.
    Tách biệt tri thức khỏi Orchestrator.
    """
    def __init__(self):
        self.k_base = os.path.dirname(__file__)
        self.p_path = os.path.join(self.k_base, '../knowledge/power_manifestos.json')
        self.e_path = os.path.join(self.k_base, '../knowledge/era_definitions.json')
        
        self.power_manifestos = self._load_json(self.p_path)
        self.era_definitions = self._load_json(self.e_path)

    def _load_json(self, path: str) -> Dict[str, Any]:
        try:
            if os.path.exists(path):
                with open(path, 'r', encoding='utf-8') as f:
                    return json.load(f)
        except Exception as e:
            log.error("manifesto_loader.load_failed", path=path, error=str(e))
        return {}

    def get_era_context(self, era_id: str) -> str:
        """Lấy bối cảnh chi tiết về kỷ nguyên (Themes, Sociology, Materiality, Conflicts)."""
        e = self.era_definitions.get(era_id)
        if not e:
            return f"BỐI CẢNH: {era_id.upper()}\n(Không có dữ liệu chi tiết)"
        
        themes_str = ", ".join(e.get('themes', []))
        mat = e.get('materiality', {})
        conflicts = "\n".join([f"- {c}" for c in e.get('conflicts', [])])
        
        return (
            f"BỐI CẢNH TRI THỨC ({e['emoji']} {e['name']}):\n"
            f"CHỦ ĐỀ CHÍNH: {themes_str}\n"
            f"VIBE KỂ CHUYỆN: {e['vibe']}\n\n"
            f"CƠ CẤU XÃ HỘI: {e.get('sociology', 'Chưa xác định')}\n\n"
            f"VẬT CHẤT ĐẶC TRƯNG:\n"
            f"  + Trang phục: {mat.get('clothing', 'N/A')}\n"
            f"  + Vũ khí: {mat.get('weapons', 'N/A')}\n"
            f"  + Kiến trúc: {mat.get('housing', 'N/A')}\n\n"
            f"MÂU THUẪN ĐẶC THÙ:\n{conflicts}"
        )

    def get_vfx_hints(self, era_id: str) -> Dict[str, Any]:
        """Lấy cấu hình thị giác cho Frontend."""
        e = self.era_definitions.get(era_id) or {}
        return e.get('vfx_hints', {"primary_color": "#FFFFFF", "particle_style": "default"})

    def get_power_manifesto(self, power_id: str, era: str = 'genesis') -> Optional[str]:
        """Xây dựng chuỗi chỉ dẫn AI dựa trên Power ID và Kỷ nguyên hiện tại."""
        m = self.power_manifestos.get(power_id)
        if not m:
            return None
        
        rules_str = "\n".join([f"- {r}" for r in m.get('rules', [])])
        era_m = m.get('era_mappings', {}).get(era, {})
        
        terms = era_m.get('terminology', {})
        terms_str = "\n".join([f"  + {k} ({v})" for k, v in terms.items()])
        
        vibe = era_m.get('vibe', 'Nghiêm túc, mô phỏng đúng quy luật.')
        
        return (
            f"BỘ QUY LUẬT THỰC TẠI ({m['name']}):\n"
            f"{m['description']}\n\n"
            f"CHỈ DẪN VẬT LÝ:\n{rules_str}\n\n"
            f"ÁNH XẠ NGÔN NGỮ ({era.upper()}):\n{terms_str}\n\n"
            f"VIBE KỂ CHUYỆN: {vibe}"
        )

# Singleton loader
loader = ManifestoLoader()
