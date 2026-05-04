<?php
namespace App\Modules\Simulation\Core\Engines\Physics;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;

/**
 * RealityAnchorEngine — Thiết lập hằng số vật lý nền.
 *
 * Chạy mỗi tick nhưng chỉ tính lại khi chưa có hoặc axiom thay đổi.
 * Output: state_vector.reality_constants
 */
class RealityAnchorEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    public function name(): string { return 'reality_anchor'; }
    public function phase(): string { return 'physical'; }
    public function priority(): int { return 0; }
    public function tickRate(): int { return 1; }
    public function isParallelSafe(): bool { return true; }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $result = new EngineResult();
        $existing = $state->get('reality_constants');

        // Chỉ khởi tạo khi chưa có
        if (!empty($existing) && is_array($existing)) {
            return $result;
        }

        $axiom = $state->get('world_config.axiom', []);
        $seed  = $ctx->getSeed();

        // Seeded deterministic constants
        $hash = crc32("reality_{$seed}");
        $norm = abs($hash) / 2147483647; // 0..1

        $constants = [
            'gravity'                => (float) ($axiom['gravity'] ?? 1.0),
            'time_scale'             => (float) ($axiom['time_scale'] ?? 1.0),
            'dimensional_stability'  => max(0.1, min(1.0, 0.7 + $norm * 0.3)),
            'causality_strength'     => max(0.5, min(1.0, 0.8 + $norm * 0.2)),
            'entropy_tendency'       => max(0.01, min(0.1, 0.03 + $norm * 0.07)),
        ];

        $result->stateChanges[] = ['reality_constants' => $constants];
        return $result;
    }
}
