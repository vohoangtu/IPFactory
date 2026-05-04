<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Core\Runtime\State;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\World\Entities\ResourceEntity;
use App\Modules\Intelligence\Entities\IdeaEntity;
use Illuminate\Support\Facades\Log;

/**
 * StateLoader — Responsible for loading and reconstructing WorldState
 * from the database and optional cache for a simulation tick.
 *
 * Extracted from the load() path of the original StateManager to
 * satisfy the Single Responsibility Principle.
 */
class StateLoader
{
    public function __construct(
        protected \App\Modules\Intelligence\Contracts\ActorRepositoryInterface $actorRepository,
        protected \App\Modules\Institutions\Contracts\InstitutionalRepositoryInterface $institutionalRepository,
        protected \App\Modules\Intelligence\Services\EcosystemMetricsService $ecosystemMetrics,
        protected \App\Modules\Narrative\Services\OmenIntegrationService $omenService,
        protected \App\Contracts\UniverseSimilarityServiceInterface $similarityService,
    ) {
    }

    /**
     * Load and reconstruct the full WorldState for a universe.
     */
    public function load(Universe $universe, ?UniverseSnapshot $snapshot = null): WorldState
    {
        $data = (array) ($universe->state_vector ?? []);

        // Phase 70: Decompress holographic state if detected
        if (isset($data['_hologram'])) {
            Log::debug('StateLoader: Holographic compression detected during load.');
        }

        // Phase 42: Load real-time ecosystem metrics into state
        $data['ecosystem_metrics'] = $this->ecosystemMetrics->forUniverse($universe);

        // Phase 11: Sync tech_level from Universe level (int -> float 0.0-1.0 mapping)
        $data['tech_level'] = (float) ($universe->level ?? 1) / 10.0;

        $state = new WorldState($data);

        // Load active actors into the state pooled collection
        $entities = $this->actorRepository->findActiveByUniverse($universe->id);
        $state->setActorEntities($entities);

        // Phase 46: Load active institutions into the state pooled collection
        $institutions = $this->institutionalRepository->findActiveByUniverse($universe->id);
        $state->setInstitutionalEntities($institutions);

        // Phase 80: Load Resources and Ideas from state_vector (§World-Kernel)
        $resources = array_map(fn ($r) => ResourceEntity::fromArray($r), $data['resources'] ?? []);
        $state->setResourceEntities($resources);
        $ideas = array_map(fn ($i) => IdeaEntity::fromArray($i), $data['ideas'] ?? []);
        $state->setIdeaEntities($ideas);

        // Phase 47: Load historical weight (recent chronicles)
        $chronicles = \App\Modules\Narrative\Models\Chronicle::where('universe_id', $universe->id)
            ->orderByDesc('to_tick')
            ->limit(10)
            ->get()
            ->all();
        $state->setRecentChronicles($chronicles);

        // Phase 47: Fetch and store Reality Omen
        $omen = $this->omenService->getCurrentOmen($universe);
        $state->set('meta.omen', [
            'type'             => $omen['type'],
            'sci_modifier'     => (float) $omen['sci_modifier'],
            'entropy_modifier' => (float) $omen['entropy_modifier'],
            'description'      => $omen['description'],
        ]);

        // Phase 48: Load Supreme Entities (Causal Overlords)
        $supremes = \App\Modules\Institutions\Models\SupremeEntity::where('universe_id', $universe->id)->get()->all();
        $state->setSupremeEntities($supremes);

        // Phase 49: Quantum Observer State
        $state->set('meta.observation_load', (float) ($universe->observation_load ?? 0.0));
        $isObserved = $universe->last_observed_at
            && $universe->last_observed_at->diffInSeconds(\Illuminate\Support\Carbon::now()) < 30;
        $state->setIsObserved($isObserved);

        // Phase 56: Neighboring Realities Pool (Reality Bleeding)
        if ($snapshot) {
            $neighbors = $this->similarityService->getNeighbors($snapshot, 0.6);
            $state->setNeighboringRealities($neighbors);
        }

        return $state;
    }
}
