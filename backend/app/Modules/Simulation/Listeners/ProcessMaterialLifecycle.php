<?php

namespace App\Modules\Simulation\Listeners;

use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Events\UniverseSimulationPulsed;
use App\Modules\World\Services\MaterialReactionEngine;
use App\Modules\Simulation\Core\Engines\Physics\MaterialEvolutionEngine;
use App\Modules\Simulation\Core\Engines\Meta\OmegaEngine;
use App\Modules\Simulation\Core\Engines\Meta\AscensionEngine;
use App\Modules\Simulation\Repositories\UniverseRepository;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessMaterialLifecycle implements ShouldQueue
{
    public function __construct(
        protected MaterialReactionEngine $reactionEngine,
        protected MaterialEvolutionEngine $materialEvolution,
        protected OmegaEngine $omegaEngine,
        protected AscensionEngine $ascensionEngine,
        protected UniverseRepository $universeRepository
    ) {}

    public function handle(UniverseSimulationPulsed $event): void
    {
        $universe = $event->universe;
        $snapshot = $event->snapshot;

        $context = $this->buildMaterialContext($snapshot);

        // Omega States & Ascension (§49, §50)
        $this->omegaEngine->checkOmegaStatus($universe, $context);
        $this->ascensionEngine->processAscension($universe, $context);

        // Persist Chronicle records for material_unlocked events emitted by MaterialEvolutionEngine
        $engineEvents = $event->engineEvents ?? [];
        foreach ($engineEvents as $ev) {
            if (($ev['type'] ?? '') !== 'material_unlocked') {
                continue;
            }
            Chronicle::firstOrCreate(
                [
                    'universe_id' => $ev['universe_id'],
                    'type'        => 'material_transition',
                    'from_tick'   => $ev['tick'],
                    'content'     => "Dân cư tại vùng {$ev['zone_name']} đã làm chủ kỹ thuật chế tác " . ucfirst($ev['material']) . ".",
                ],
                [
                    'to_tick'     => $ev['tick'],
                    'importance'  => 0.45,
                    'raw_payload' => [
                        'zone_id'   => $ev['zone_id'],
                        'material'  => $ev['material'],
                        'tech_band' => $ev['tech_band'],
                    ],
                ]
            );
        }
    }

    protected function applyDeltas($universe, $deltas): void
    {
            $vec = $universe->state_vector ?? [];
            $vec['entropy'] = ($vec['entropy'] ?? 0.0) + ($deltas['entropy'] ?? 0.0);
            $vec['stability_index'] = ($vec['stability_index'] ?? 0.0) + ($deltas['order'] ?? 0.0);
            $vec['innovation'] = ($vec['innovation'] ?? 0.0) + ($deltas['innovation'] ?? 0.0);
            $vec['growth'] = ($vec['growth'] ?? 0.0) + ($deltas['growth'] ?? 0.0);
            $vec['trauma'] = ($vec['trauma'] ?? 0.0) + ($deltas['trauma'] ?? 0.0);

            // Clamp
            $vec['entropy'] = max(0.0, min(1.0, (float)$vec['entropy']));
            $vec['stability_index'] = max(0.0, min(1.0, (float)$vec['stability_index']));
            
            $this->universeRepository->update($universe->id, ['state_vector' => $vec]);
    }

    protected function buildMaterialContext($snapshot): array
    {
        $metrics = $snapshot->metrics ?? [];
        return array_merge($metrics ?? [], [
            'entropy' => (float)($snapshot->entropy ?? 0),
            'order' => (float)($snapshot->stability_index ?? 0),
            'innovation' => $metrics['innovation'] ?? 0,
            'growth' => $metrics['growth'] ?? 0,
            'trauma' => $metrics['trauma'] ?? 0,
            'scars' => ($snapshot->state_vector ?? [])['scars'] ?? [],
        ]);
    }
}


