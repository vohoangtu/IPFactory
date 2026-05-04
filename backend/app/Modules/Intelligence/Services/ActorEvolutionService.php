<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\Intelligence\Contracts\ActorRepositoryInterface;
use App\Modules\Intelligence\Actions\SpawnActorAction;
use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Services\NarrativeGeneratorService;
use App\Modules\Intelligence\Services\ArchetypeResolverService;

class ActorEvolutionService
{
    public function __construct(
        private ActorRepositoryInterface $actorRepository,
        private SpawnActorAction $spawnAction,
        private NarrativeGeneratorService $narrativeService,
        private ArchetypeResolverService $archetypeResolver,
        private \App\Modules\Intelligence\Actions\RunMicroCycleAction $runMicroCycleAction
    ) {}

    /**
     * Logic trích xuất từ HeroicActorService: Tạo life record cho actor.
     */
    public function recordLifeEvent(int $actorId, int $tick, array $config): string
    {
        $actor = $this->actorRepository->findById($actorId);
        if (!$actor || !$actor->isAlive) return "";

        try {
            $event = $this->narrativeService->generateLifeEvent(
                $actor->name,
                $actor->archetype,
                $actor->traits,
                ['genre' => $config['genre'] ?? 'wuxia', 'style' => $config['naming_style'] ?? 'asian_classic']
            );
            
            $actor->biography .= "\n- T{$tick}: {$event}";
            $actor->incrementInfluence(0.1);
            
            $this->actorRepository->save($actor);
            
            return $event;
        } catch (\Exception $e) {
            return "Trải qua một kiếp nạn nhân sinh.";
        }
    }

    /**
     * Evolve existing actors by orchestrating the Phase 6 Meta-Cycle
     */
    public function evolve(Universe $universe, int $tick): void
    {
        $actorEntities = $this->actorRepository->findByUniverse($universe->id);
        
        if (count($actorEntities) === 0) return;

        // Convert Entities to States
        $actorStates = [];
        $actorMap = [];
        foreach ($actorEntities as $entity) {
            $state = $entity->toState();
            $actorStates[] = $state;
            $actorMap[$entity->id] = $entity;
        }

        // Run Phase 6 Micro Cycle
        $worldAxiom = $universe->world?->axiom ?? [];
        $result = $this->runMicroCycleAction->handle($universe, $tick, $actorStates, $worldAxiom);
        
        $nextActorStates = $result['actors'];
        $updatedUniverse = $result['universe'];

        // Persist Actor States back
        foreach ($nextActorStates as $state) {
            $entity = $actorMap[$state->id];
            $entity->fromState($state);
            
            // Phase 3: Update Social Class based on influence
            $influence = (float)($entity->metrics['influence'] ?? 0.5);
            if ($influence > 2.0) {
                $entity->metrics['social_class'] = 'elite';
            } elseif ($influence < 0.1) {
                $entity->metrics['social_class'] = 'outcast';
            }

            // Random life record (chance 10% per pulse for active ones)
            if ($entity->isAlive && mt_rand(1, 100) > 90) {
                // Not ideal putting narrative call here but works for legacy flow mapping
                $this->recordLifeEvent($entity->id, $tick, []);
            }
            
            $this->actorRepository->save($entity);
        }

        // Save Universe Macro metrics
        $updatedUniverse->save();
    }

    public function generateRandomTraits(): array
    {
        $traits = [];
        $dimsCount = count(\App\Modules\Intelligence\Entities\ActorEntity::TRAIT_DIMENSIONS);
        for ($i = 0; $i < $dimsCount; $i++) {
            $traits[] = mt_rand(0, 100) / 100.0;
        }
        return $traits;
    }

    public function ensureMinimumPopulation(Universe $universe, ?int $min = null): void
    {
        $min = $min ?? (int) config('worldos.intelligence.actor_minimum_population', 5);
        if ($min <= 0) {
            return;
        }

        $count = $this->actorRepository->getActiveCount($universe->id);
        
        while ($count < $min) {
            $axiom = $universe->world?->axiom ?? [];
            $archetype = $this->archetypeResolver->resolve($axiom, $universe->entropy ?? 0.5, $universe->structural_coherence ?? 0.5);

            $this->spawnAction->doExecute([
                'universe_id' => $universe->id,
                'name' => "Nhân Vật " . mt_rand(100, 999),
                'archetype' => $archetype,
                'traits' => $this->generateRandomTraits(),
                'biography' => "Cảm ứng thiên địa, xuất thế giữa lúc năng lượng dao động mạnh.",
                'metrics' => $this->enrichInitialMetrics($universe, $axiom),
            ]);
            $count++;
        }
    }

    /**
     * Phase 3: Enrich initial actor metrics with social class, origin, and livelihood.
     */
    private function enrichInitialMetrics(Universe $universe, array $axiom): array
    {
        $metrics = ['influence' => 0.5];
        
        // 1. Origin and Livelihood from Zone
        $zones = $universe->world?->data['zones'] ?? [];
        if (!empty($zones)) {
            $randomZone = $zones[array_rand($zones)];
            $profile = $randomZone['state']['material_profile'] ?? [];
            
            $metrics['origin_zone_id'] = $randomZone['id'] ?? null;
            $metrics['livelihood'] = $profile['livelihood'] ?? 'unknown';
            $metrics['birth_material'] = $profile['dominant_material'] ?? 'none';
        }

        // 2. Initial Social Class (Start as Citizen or Serf)
        $metrics['social_class'] = mt_rand(1, 100) > 80 ? 'citizen' : 'serf';
        
        return $metrics;
    }
}

