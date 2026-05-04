import json
import os
from typing import Dict, Any, Optional

class RealityLoader:
    """
    RealityLoader: Tải tri thức 'Deep Reality' (Eras & Power Systems) cho Social Engine.
    Dùng chung nguồn tri thức với Narrative Loom để đảm bảo nhất quán toàn vũ trụ.
    """
    def __init__(self):
        # Đường dẫn tương đối từ sim/social-engine/app/utils/ sang narrative-loom/knowledge/
        # Workspace: v6/
        #  - narrative-loom/knowledge/
        #  - sim/social-engine/app/utils/
        self.k_base = os.path.abspath(os.path.join(os.path.dirname(__file__), '../../../../narrative-loom/knowledge'))
        self.p_path = os.path.join(self.k_base, 'power_manifestos.json')
        self.e_path = os.path.join(self.k_base, 'era_definitions.json')
        
        self.power_manifestos = self._load_json(self.p_path)
        self.era_definitions = self._load_json(self.e_path)

    def _load_json(self, path: str) -> Dict[str, Any]:
        try:
            if os.path.exists(path):
                with open(path, 'r', encoding='utf-8') as f:
                    return json.load(f)
        except Exception as e:
            print(f"FAILED TO LOAD REALITY KNOWLEDGE AT {path}: {e}")
        return {}

    def get_era_context(self, era_id: str) -> str:
        """Lấy bối cảnh chi tiết về kỷ nguyên phục vụ sinh Persona."""
        e = self.era_definitions.get(era_id)
        if not e:
            return ""
        
        themes = ", ".join(e.get('themes', []))
        mat = e.get('materiality', {})
        
        return (
            f"KỶ NGUYÊN: {e['name']}\n"
            f"CHỦ ĐỀ: {themes}\n"
            f"XÃ HỘI: {e.get('sociology', '')}\n"
            f"VẬT CHẤT ĐẶC TRƯNG:\n"
            f"  - Trang phục: {mat.get('clothing', '')}\n"
            f"  - Vũ khí: {mat.get('weapons', '')}\n"
            f"XUNG ĐỘT QUAN TRỌNG: {', '.join(e.get('conflicts', []))}"
        )

    def get_power_context(self, power_id: str, era_id: str) -> str:
        """Lấy thuật ngữ sức mạnh cho Persona."""
        m = self.power_manifestos.get(power_id)
        if not m:
            return ""
        
        era_m = m.get('era_mappings', {}).get(era_id, {})
        terms = era_m.get('terminology', {})
        terms_str = ", ".join([f"{k} là '{v}'" for k, v in terms.items()])
        
        return f"HỆ THỐNG SỨC MẠNH ({m['name']}): Thuật ngữ bối cảnh: {terms_str}."

# Singleton loader for Oasis
reality_loader = RealityLoader()
