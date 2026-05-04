<?php

namespace App\Modules\Simulation\Core\Supervisor\Handlers;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Supervisor\Contracts\PostSnapshotHandlerInterface;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use App\Modules\Simulation\Services\Core\SelfImprovingSimulationService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

final class SelfImprovingPostSnapshotHandler implements PostSnapshotHandlerInterface
{
    public function __construct(
        private readonly SelfImprovingSimulationService $selfImprovingService,
        private readonly RuleVmService $ruleVmService,
    ) {}

    public function handle(Universe $universe, UniverseSnapshot $snapshot): void
    {
        if (! Config::get('worldos.self_improving.enabled', false)) {
            return;
        }
        $proposal = $this->selfImprovingService->proposeRule('simulation_tick');
        if ($proposal === null || empty($proposal['dsl'] ?? null)) {
            return;
        }
        $state = $this->ruleVmService->buildStateForVm($universe, $snapshot);
        $result = $this->selfImprovingService->sandboxTest($state, $proposal['dsl']);
        Log::debug('SelfImproving: sandbox test result', [
            'universe_id' => $universe->id,
            'tick' => $snapshot->tick,
            'ok' => $result['ok'],
            'outputs_count' => count($result['outputs'] ?? []),
        ]);
        if ($result['ok'] ?? false) {
            Event::dispatch(new \App\Modules\Simulation\Events\RuleProposed($universe->id, (int) $snapshot->tick, $proposal['dsl'], $result));
        }
    }
}






