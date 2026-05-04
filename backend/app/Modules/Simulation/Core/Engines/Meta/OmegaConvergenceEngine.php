<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;
use App\Modules\World\Models\UniverseBridge;
use App\Modules\Simulation\Services\Meta\ConvergenceScoreService;
use App\Modules\Simulation\Core\Effects\WorldStateUpdateEffect;

/**
 * Phase 64/9: Omega Point Convergence Engine (V8+) 🏁⚛️
 * 
 * Điểm cuối của quá trình tiến hóa: Tất cả các dòng thời gian hợp nhất.
 * Khi độ tương đồng giữa các vũ trụ lân cận đạt ngưỡng tuyệt đối, 
 * thực tại sẽ bước vào trạng thái "Omega" - Thống nhất hoàn toàn.
 * Phase 9 Update: Uses proper Persistence via ConvergenceScoreService.
 */
class OmegaConvergenceEngine implements SimulationEngine
{
    use DefaultSimulationEnginePhase;

    public function __construct(
        private ?ConvergenceScoreService $convergenceScoreService = null
    ) {
    }

    public function name(): string
    {
        return 'omega_convergence';
    }

    public function phase(): string
    {
        return 'meta';
    }

    public function priority(): int
    {
        return 10;
    }

    public function tickRate(): int
    {
        return 5;
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $universeId = $ctx->getUniverseId();

        $bridges = UniverseBridge::with(['sourceUniverse', 'targetUniverse'])
            ->where('source_universe_id', $universeId)
            ->where('is_active', true)
            ->get();

        if ($bridges->isEmpty()) {
            return EngineResult::empty();
        }

        $service = $this->convergenceScoreService ?? app(ConvergenceScoreService::class);

        $totalResonance = 0.0;
        foreach ($bridges as $bridge) {
            $totalResonance += $service->computeAndSave($bridge, $ctx->getTick());
        }

        $avgResonance = $totalResonance / $bridges->count();
        $effects = [];
        
        // Ngưỡng hội tụ tuyệt đối
        if ($avgResonance > 0.98) {
            $progress = ($avgResonance - 0.98) / 0.02;
            $effects[] = new WorldStateUpdateEffect([
                'meta.omega_convergence_active' => true,
                'meta.omega_point_progress' => $progress,
            ]);
            
            Log::info("OmegaConvergenceEngine: Omega Point approaching! Convergence progress: {$progress}");
        }

        return new EngineResult([], $effects);
    }
}



