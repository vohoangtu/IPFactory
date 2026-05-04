<?php

namespace App\Modules\Simulation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\World\Models\Universe;
use Illuminate\Http\JsonResponse;

class UniverseAnomalyController extends Controller
{
    public function index(int $universeId): JsonResponse
    {
        // Fetch snapshots that meet anomaly criteria
        $snapAnomalies = UniverseSnapshot::where('universe_id', $universeId)
            ->where(function($query) {
                $query->where('entropy', '>', 0.9)
                      ->orWhere('stability_index', '<', 0.25)
                      ->orWhereRaw("CAST(metrics->>'material_stress' AS DECIMAL) > 0.7");
            })
            ->orderBy('tick', 'desc')
            ->limit(10)
            ->get();

        $anomalies = $snapAnomalies->map(function($s) {
            $stress = (float) ($s->metrics['material_stress'] ?? 0);
            $severity = 'INFO';
            $title = 'Biến động nhẹ (Minor Fluctuation)';
            $desc = 'Cấu trúc thực tại đang tự hiệu chỉnh.';

            if ($s->entropy > 0.95) {
                $severity = 'CRITICAL';
                $title = 'Cánh cửa Hư vô (Void Gate)';
                $desc = "Entropy đạt mức tới hạn (".round($s->entropy*100, 2)."%).";
            } elseif ($s->stability_index < 0.2) {
                $severity = 'CRITICAL';
                $title = 'Sụp đổ Định chế';
                $desc = 'Chỉ số ổn định thấp kỷ lục ('.round($s->stability_index, 4).').';
            } elseif ($stress > 0.8) {
                $severity = 'WARN';
                $title = 'Căng thẳng Vật chất';
                $desc = 'Áp lực hạ tầng vượt ngưỡng an toàn.';
            }

            return [
                'id' => "anom_{$s->id}",
                'title' => $title,
                'description' => $desc,
                'severity' => $severity,
                'tick' => $s->tick,
            ];
        });

        return response()->json($anomalies);
    }
}

