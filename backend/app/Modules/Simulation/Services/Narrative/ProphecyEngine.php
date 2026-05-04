<?php

namespace App\Modules\Simulation\Services\Narrative;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Narrative\Models\Chronicle;
use Illuminate\Support\Facades\Log;

/**
 * Prophecy Engine: Projects potential future paths based on current metrics.
 * Part of Phase 3: The Engine of Emergence.
 */
class ProphecyEngine
{
    public function generateProphecy(Universe $universe, int $tick): array
    {
        $snapshot = $universe->snapshots()->where('tick', '<=', $tick)->orderByDesc('tick')->first();
        if (!$snapshot) return ['ok' => false, 'error' => 'No snapshot found'];

        $metrics = is_string($snapshot->metrics) ? json_decode($snapshot->metrics, true) : ($snapshot->metrics ?? []);
        
        $stability = $metrics['stability'] ?? 1.0;
        $entropy = $metrics['entropy'] ?? 0.0;
        $techLevel = $metrics['tech_level'] ?? 0.0;

        $prophecies = [];

        // Logic for branching prophecies
        if ($entropy > 0.7 && $stability < 0.4) {
            $prophecies[] = [
                'type' => 'cataclysm',
                'title' => 'Lời Nguyền Hỗn Loạn (The Chaos Omen)',
                'probability' => 0.65,
                'description' => 'Sự mất cân bằng năng lượng đang đẩy thực tại đến điểm sụp đổ.'
            ];
        }

        if ($techLevel > 0.6 && $stability > 0.6) {
            $prophecies[] = [
                'type' => 'ascension',
                'title' => 'Khải Huyền Thăng Hoa (The Ascension Revelation)',
                'probability' => 0.45,
                'description' => 'Sự trỗi dậy của trí tuệ nhân tạo hoặc thần tính kỹ thuật số.'
            ];
        }

        if (empty($prophecies)) {
            $prophecies[] = [
                'type' => 'stagnation',
                'title' => 'Dòng Thời Gian Tĩnh Lặng (The Silent Timeline)',
                'probability' => 0.9,
                'description' => 'Mọi thứ tiếp tục vận hành trong sự lặp lại vô tận.'
            ];
        }

        // Log the prophecy for narrative consumption
        foreach ($prophecies as $p) {
            Log::info("PROPHECY_GENERATED: [{$p['title']}] Prob={$p['probability']}");
            
            Chronicle::create([
                'universe_id' => $universe->id,
                'from_tick' => $tick,
                'to_tick' => $tick + 100, // Projected window
                'type' => 'prophecy',
                'raw_payload' => $p,
            ]);
        }

        return [
            'ok' => true,
            'prophecies' => $prophecies
        ];
    }
}
