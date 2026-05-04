<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\Intelligence\Models\Actor;
use Illuminate\Support\Facades\Log;

/**
 * ActorIdentityService - Lớp tổng hợp bản sắc cá nhân từ khía cạnh vật chất.
 * Kết nối: Material Profile -> Occupation -> Equipment.
 */
class ActorIdentityService
{
    /**
     * Đồng bộ bản sắc vật chất cho Actor.
     * 
     * @param Actor $actor
     * @param array $zoneProfile Hồ sơ vật chất của Zone (từ zone.state.material_profile)
     * @param float $techLevel Cấp độ kỹ thuật của vũ trụ
     */
    public function syncMaterialIdentity(Actor $actor, array $zoneProfile, float $techLevel): void
    {
        $metrics = $actor->metrics ?? [];
        $physic = $metrics['physic'] ?? array_fill(0, 16, 0.5);
        
        // 1. Xác định nghề nghiệp (Occupation) dựa trên livelihood của vùng
        $livelihood = $zoneProfile['livelihood'] ?? 'foraging';
        $metrics['occupation'] = $this->deriveOccupation($livelihood, $actor->traits ?? []);

        // 2. Tác động vật lý (Physical Impact) từ nghề nghiệp
        // Physic index 0: Vitality, 1: Strength, 2: Agility, 4: Resilience
        $physic = $this->applyPhysicalImpact($metrics['occupation'], $physic);
        $metrics['physic'] = $physic;

        // 3. Xác định trang bị (Equipment) dựa trên vật liệu và tech_level
        $dominantMaterial = $zoneProfile['dominant_material'] ?? 'stone';
        $metrics['equipment'] = $this->deriveEquipment($dominantMaterial, $metrics['occupation'], $techLevel);

        $actor->metrics = $metrics;
    }

    /**
     * Suy luận nghề nghiệp từ nguồn sống của vùng và tính cách cá nhân.
     */
    private function deriveOccupation(string $livelihood, array $traits): string
    {
        $dominance = (float) ($traits[0] ?? 0.5); // Dom
        $ambition  = (float) ($traits[1] ?? 0.5); // Amb
        $curiosity = (float) ($traits[8] ?? 0.5); // Cur

        return match ($livelihood) {
            'fishing' => $ambition > 0.7 ? 'Thuyền trưởng' : 'Ngư dân',
            'mining'  => $dominance > 0.7 ? 'Quản đốc mỏ' : 'Thợ đào mỏ',
            'farming' => $ambition > 0.6 ? 'Điền chủ' : 'Nông dân',
            'pastoral' => $curiosity > 0.7 ? 'Trinh sát du mục' : 'Hành giả chăn gia súc',
            'foraging' => $curiosity > 0.6 ? 'Dược sư' : 'Người thu hái',
            default => 'Lao động tự do'
        };
    }

    /**
     * Suy luận trang bị từ vật liệu chủ đạo, nghề nghiệp và trình độ kỹ thuật.
     */
    private function deriveEquipment(string $material, string $occupation, float $techLevel): string
    {
        $matNames = [
            'stone' => 'Đá', 'copper' => 'Đồng', 'bronze' => 'Đồng thiếc',
            'iron' => 'Sắt', 'steel' => 'Thép', 'alloy' => 'Hợp kim', 'advanced' => 'Nano'
        ];
        $matName = $matNames[$material] ?? ucfirst($material);
        
        return match (true) {
            str_contains($occupation, 'Thuyền') || str_contains($occupation, 'Ngư') => "Lao {$matName}",
            str_contains($occupation, 'đào') || str_contains($occupation, 'Quản đốc') => "Cuốc {$matName}",
            str_contains($occupation, 'Nông') || str_contains($occupation, 'Điền') => $techLevel > 0.3 ? "Cày {$matName}" : "Cuốc đất {$matName}",
            str_contains($occupation, 'Trinh sát') || str_contains($occupation, 'chăn gia') => "Gậy {$matName}",
            str_contains($occupation, 'Dược') || str_contains($occupation, 'thu hái') => "Liềm {$matName}",
            default => "Công cụ {$matName}"
        };
    }

    /**
     * Áp dụng tác động vật lý dựa trên nghề nghiệp.
     */
    private function applyPhysicalImpact(string $occupation, array $physic): array
    {
        if (str_contains($occupation, 'Thợ đào') || str_contains($occupation, 'Nông')) {
            $physic[1] = min(1.0, ($physic[1] ?? 0.5) + 0.05); // +Strength
            $physic[4] = min(1.0, ($physic[4] ?? 0.5) + 0.03); // +Resilience
        }
        if (str_contains($occupation, 'Trinh sát') || str_contains($occupation, 'Ngư')) {
            $physic[2] = min(1.0, ($physic[2] ?? 0.5) + 0.05); // +Agility
        }
        return $physic;
    }
}
