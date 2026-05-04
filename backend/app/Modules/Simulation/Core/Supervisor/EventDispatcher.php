<?php

namespace App\Modules\Simulation\Core\Supervisor;

use App\Modules\World\Models\Universe;

use App\Contracts\Repositories\UniverseRepositoryInterface;
use App\Modules\Simulation\Events\UniverseSimulationPulsed;
use App\Modules\Simulation\Entities\UniverseEntity;
use App\Modules\Simulation\Entities\SnapshotEntity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Dispatches UniverseSimulationPulsed and updates universe (current_tick, fitness_score, structural_coherence).
 */
final class EventDispatcher
{
    public function __construct(
        private readonly \App\Modules\Simulation\Contracts\UniverseRepositoryInterface $universeRepository,
    ) {}

    public function dispatchPulsed(UniverseEntity $universe, SnapshotEntity $snapshot, array $engineResponse, int $ticks, float $tickDurationMsPerTick, array $engineEvents = []): void
    {
        // Vẫn cần Model cho Event (UniverseSimulationPulsed) nếu Event chưa refactor
        $universeModel = \App\Modules\World\Models\Universe::find($universe->id);
        $snapshotModel = \App\Modules\Simulation\Models\UniverseSnapshot::find($snapshot->id);

        if ($universeModel && $snapshotModel) {
            event(new \App\Modules\Simulation\Events\UniverseSimulationPulsed(
                $universeModel,
                $snapshotModel,
                array_merge($engineResponse, ['_ticks' => $ticks]),
                $engineEvents
            ));
        }

        Cache::put("worldos.tick_duration_ms.{$universe->id}", $tickDurationMsPerTick, now()->addHours(1));

        Log::info('Simulation: advance completed', [
            'universe_id' => $universe->id,
            'ticks' => $ticks,
            'tick' => $snapshot->tick,
            'entropy' => $snapshot->entropy,
            'tick_duration_ms' => round($tickDurationMsPerTick, 2),
        ]);

        // Cập nhật Entity thông qua logic domain
        $universe->currentTick = (int) ($engineResponse['snapshot']['tick'] ?? $snapshot->tick);
        
        $universe->structuralCoherence = min(1.0, $universe->structuralCoherence + ($universe->observerBonus ?? 0));
        
        if ($universe->currentTick % 10 === 0) {
            $universe->fitnessScore = app(\App\Modules\Simulation\Services\Ecology\KernelMutationService::class)->calculateFitness(\App\Modules\World\Models\Universe::find($universe->id));
        }

        $this->universeRepository->save($universe);
    }
}



