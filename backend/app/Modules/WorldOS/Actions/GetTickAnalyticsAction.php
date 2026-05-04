<?php

namespace App\Modules\WorldOS\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use Illuminate\Support\Facades\DB;

class GetTickAnalyticsAction
implements \App\Contracts\ActionInterface {
    public function handle(): array
    {
        // 1. Tổng số tick đã chạy
        $totalTicks = UniverseSnapshot::count();

        // 2. Thống kê theo từng Universe
        $perUniverse = Universe::select('id', 'name', 'current_tick', 'status')
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'ticks' => $u->current_tick,
                'status' => $u->status
            ]);

        // 3. Thời gian xử lý trung bình và Macro Ratio (lấy từ các snapshot gần nhất)
        $recentSnapshots = UniverseSnapshot::orderBy('id', 'desc')
            ->limit(100)
            ->get();

        $avgDuration = $recentSnapshots->avg(function($s) {
            return $s->metrics['tick_duration_ms'] ?? 0;
        }) ?? 0;

        $macroCount = $recentSnapshots->filter(function($s) {
            return $s->metrics['is_macro_tick'] ?? false;
        })->count();

        $macroRatio = count($recentSnapshots) > 0 ? ($macroCount / count($recentSnapshots)) : 0;

        // 4. Ước tính số tick trong 1 giờ qua
        $ticksLastHour = UniverseSnapshot::where('created_at', '>=', now()->subHour())->count();

        return [
            'total_ticks' => $totalTicks,
            'avg_duration_ms' => round($avgDuration, 2),
            'macro_ratio' => round($macroRatio, 4),
            'ticks_last_hour' => $ticksLastHour,
            'universes' => $perUniverse,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
