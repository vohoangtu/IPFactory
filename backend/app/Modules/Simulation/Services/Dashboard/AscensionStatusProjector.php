<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Services\Dashboard;

use App\Modules\Simulation\Core\Runtime\State\WorldState;

class AscensionStatusProjector
{
    public function project(WorldState $state, int $universeId): array
    {
        $fields = $state->getFields();
        $entropy = $state->getEntropy();
        $pressures = $state->getPressures();

        $filters = [
            ['id' => 'bio_entropy', 'name' => 'Entropy Sinh Học', 'status' => $entropy < 0.3 ? 'PASSED' : 'ACTIVE', 'progress' => min(1.0, 1.0 - $entropy)],
            ['id' => 'tech_singularity', 'name' => 'Kỳ Dị Công Nghệ', 'status' => ($fields['knowledge'] ?? 0) > 0.9 ? 'DANGER' : 'ACTIVE', 'progress' => $fields['knowledge'] ?? 0],
            ['id' => 'meaning_void', 'name' => 'Hư Vô Ý Nghĩa', 'status' => ($fields['meaning'] ?? 0) < 0.2 ? 'FAILED' : 'ACTIVE', 'progress' => $fields['meaning'] ?? 0.5],
            ['id' => 'causal_debt', 'name' => 'Nợ Nhân Quả', 'status' => ($pressures['collapse_pressure'] ?? 0) > 0.7 ? 'WARNING' : 'ACTIVE', 'progress' => 1.0 - ($pressures['collapse_pressure'] ?? 0)],
            ['id' => 'ascension_threshold', 'name' => 'Ngưỡng Cửa Thăng Hoa', 'status' => ($pressures['ascension_pressure'] ?? 0) > 0.8 ? 'OPEN' : 'LOCKED', 'progress' => $pressures['ascension_pressure'] ?? 0],
        ];

        return [
            'universe_id' => $universeId,
            'singularity_probability' => round(($fields['knowledge'] ?? 0.1) * (1 - ($pressures['stability'] ?? 0.5)), 4),
            'filters' => $filters,
        ];
    }
}
