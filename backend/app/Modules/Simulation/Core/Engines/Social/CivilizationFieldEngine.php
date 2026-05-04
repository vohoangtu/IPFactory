<?php
namespace App\Modules\Simulation\Core\Engines\Social;
use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
class CivilizationFieldEngine implements EngineInterface {
    use DefaultSimulationEnginePhase;
    public function name(): string { return 'civilization_field.stub'; }
    public function phase(): string { return 'social'; }
    public function priority(): int { return 51; }
    public function tickRate(): int { return 1; }
    public function handle(WorldState $state, TickContext $ctx): EngineResult { return EngineResult::empty(); }
    public function runWithState(WorldState $state, int $tick): void {}
}
