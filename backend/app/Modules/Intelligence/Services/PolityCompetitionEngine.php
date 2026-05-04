<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\Log;

/**
 * Phase 49: Geopolitical Competition Engine (Địa chính trị) ⚔️🗺️
 * 
 * Quản lý các thế lực (Polity) và sự cạnh tranh giữa các quốc gia/đế chế.
 */
class PolityCompetitionEngine
{
    /**
     * Cập nhật bản đồ địa chính trị.
     */
    public function step(Universe $universe): void
    {
        $stateVector = $universe->state_vector;
        $polities = $stateVector['polities'] ?? [];

        if (empty($polities)) {
            $this->initInitialPolities($universe, $polities);
        }

        foreach ($polities as $id => &$polity) {
            $this->calculatePower($polity, $universe);
            $this->simulateExpansion($polity, $polities, $universe);
        }

        $stateVector['polities'] = $polities;
        $universe->state_vector = $stateVector;
    }

    private function initInitialPolities(Universe $universe, array &$polities): void
    {
        // Khởi tạo các bang quốc/bộ lạc ban đầu
        for ($i = 0; $i < 4; $i++) {
            $id = "polity_" . bin2hex(random_bytes(2));
            $polities[$id] = [
                'id' => $id,
                'name' => "Quốc gia " . ($i + 1),
                'power' => 100,
                'territory_size' => 1,
                'cohesion' => 1.0,
                'culture_id' => "culture_" . $i,
                'is_empire' => false
            ];
        }
    }

    private function calculatePower(array &$polity, Universe $universe): void
    {
        $fields = $universe->state_vector['fields'] ?? [];
        
        // power = (power_field) * territory_size * cohesion
        $basePower = ($fields['power'] ?? 0.1) * 1000;
        $polity['power'] = $basePower * $polity['territory_size'] * $polity['cohesion'];

        // Empire instability: Over-expansion reduces cohesion
        if ($polity['territory_size'] > 5) {
            $polity['is_empire'] = true;
            $polity['cohesion'] = max(0.3, $polity['cohesion'] - 0.005 * ($polity['territory_size'] - 5));
        }
        
        // Fragmentation check
        if ($polity['cohesion'] < 0.4 && rand(0, 100) < 10) {
            $this->fragmentPolity($polity);
        }
    }

    private function simulateExpansion(array &$actor, array &$all, Universe $universe): void
    {
        // Cạnh tranh tài nguyên - Polity mạnh nuốt polity yếu
        $targetId = array_rand($all);
        if ($targetId === $actor['id']) return;

        $target = &$all[$targetId];
        $powerRatio = $actor['power'] / ($target['power'] + 1);

        if ($powerRatio > 1.5 && rand(0, 100) < 5) {
            Log::info("EXPANSION: {$actor['name']} has absorbed territory from {$target['name']}");
            $actor['territory_size'] += 1;
            $target['territory_size'] = max(0, $target['territory_size'] - 1);
            
            if ($target['territory_size'] === 0) {
                unset($all[$targetId]);
            }
        }
    }

    private function fragmentPolity(array &$polity): void
    {
        Log::warning("FRAGMENTATION: Empire {$polity['name']} has collapsed into smaller units.");
        $polity['territory_size'] = 1;
        $polity['cohesion'] = 0.8;
        $polity['is_empire'] = false;
        // In a real system, we would spawn new polities here
    }
}

