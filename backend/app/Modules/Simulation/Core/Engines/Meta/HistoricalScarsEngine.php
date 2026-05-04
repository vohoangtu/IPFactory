<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use Illuminate\Support\Facades\Log;
use function resource_path;
use function file_get_contents;
use function file_exists;

/**
 * Phase 51: Historical Scars Engine 📜🩸
 * 
 * Chuyển hóa các sử lục (Chronicles) thành các "Vết sẹo" lâu dài trong manifold.
 */
class HistoricalScarsEngine implements SimulationEngine
{
    use DefaultSimulationEnginePhase;

    public function __construct(
        protected RuleVmService $ruleVm
    ) {}

    public function name(): string
    {
        return 'historical_scars';
    }

    public function phase(): string
    {
        return 'meta';
    }

    public function priority(): int
    {
        return 8;
    }

    public function tickRate(): int
    {
        return 5;
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $tick = $ctx->getTick();

        // 1. Phân tích Chronicles để tạo Scars mới
        $this->generateScarsFromChronicles($state, $tick);

        // 2. Chạy DSL để cập nhật/phân rã Scars hiện có
        $this->processScarDynamics($state, $tick);

        return EngineResult::empty();
    }

    protected function generateScarsFromChronicles(WorldState $state, int $tick): void
    {
        $chronicles = $state->getRecentChronicles();
        if (empty($chronicles)) return;

        $scars = $state->getScars();

        foreach ($chronicles as $chronicle) {
            $type = $chronicle->type ?? 'unknown';
            $magnitude = $this->estimateMagnitude($chronicle);

            if ($magnitude <= 0) continue;

            // Append as StructuredScar-compatible format (numeric index, required fields for Rust engine)
            $scars[] = [
                'tick' => $tick,
                'category' => 'historical_scar_' . $type,
                'description' => 'Historical scar from chronicle: ' . $type,
                'actor_id' => null,
                'zone_id' => null,
                'caused_by_id' => null,
                'metadata' => array_merge(
                    ['magnitude' => $magnitude, 'source_type' => $type],
                    is_array($chronicle->raw_payload ?? null) ? $chronicle->raw_payload : []
                ),
            ];
        }

        $state->setScars(array_values($scars));
    }

    protected function processScarDynamics(WorldState $state, int $tick): void
    {
        $path = resource_path('worldos_rules/simulation/scars.dsl');
        if (!file_exists($path)) {
            Log::warning("HistoricalScarsEngine: scars.dsl not found at {$path}");
            return;
        }

        $dsl = file_get_contents($path);
        $this->ruleVm->evaluateAndApplyWithDsl($state, $dsl, $tick);
    }

    protected function estimateMagnitude($chronicle): float
    {
        // Logic đơn giản: đánh giá độ quan trọng của sự kiện
        $type = $chronicle->type ?? '';
        
        return match($type) {
            'war' => 2.0,
            'plague' => 1.5,
            'innovation' => 1.0,
            'famine' => 1.2,
            'natural_disaster' => 1.8,
            'causal_correction' => 3.0,
            default => 0.5
        };
    }
}



