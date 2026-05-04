<?php

namespace App\Modules\Intelligence\Services\Dashboard;

use App\Modules\Intelligence\Models\AiMemory;
use App\Modules\Simulation\Models\CivilizationAttractor;

class AttractorMetricsService
{
    /**
     * Get basin sizes and active strange/dark attractors.
     */
    public function getAttractorMap(): array
    {
        // 1. Calculate Basins from actual universe history (Phase Space occupancy)
        // Basin size is proportional to how many ticks a specific archetype holds dominance
        $snapshots = \Illuminate\Support\Facades\DB::table('universe_snapshots')
            ->select('metrics->winner_archetype as winner_archetype', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->whereNotNull('metrics->winner_archetype')
            ->groupBy('metrics->winner_archetype')
            ->orderByDesc('count')
            ->limit(8)
            ->get();

        $total = $snapshots->sum('count');
        $basins = [];

        if ($total > 0) {
            foreach ($snapshots as $snap) {
                $basins[] = [
                    'name' => $snap->winner_archetype,
                    'value' => round(($snap->count / $total) * 100, 1)
                ];
            }
        } else {
            // Fallback for empty simulation
            $basins[] = ['name' => 'Undifferentiated', 'value' => 100];
        }

        // 2. Add Persistent Rules (Dark/Strange from DB)
        $rules = CivilizationAttractor::all()->map(function($a) {
            return [
                'id' => $a->id,
                'name' => $a->name,
                'is_dark' => str_contains(strtolower($a->name), 'dark') || str_contains(strtolower($a->description ?? ''), 'trap'),
            ];
        })->toArray();

        return [
            'basins' => $basins,
            'active_rules' => $rules,
        ];
    }
}

