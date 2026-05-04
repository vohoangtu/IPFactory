<?php

namespace App\Modules\Institutions\Services;

use App\Modules\Institutions\Models\SupremeEntity as SupremeEntityModel;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Institutions\Contracts\SupremeEntityRepositoryInterface;
use App\Modules\Institutions\Actions\SpawnSupremeEntityAction;
use App\Modules\Institutions\Actions\AscendHeroAction;
use App\Modules\Intelligence\Services\EcosystemMetricsService;

class SupremeEntityEvolutionService
{
    public function __construct(
        private SupremeEntityRepositoryInterface $supremeEntityRepository,
        private SpawnSupremeEntityAction $spawnAction,
        private AscendHeroAction $ascendHeroAction,
        private EcosystemMetricsService $ecosystemMetricsService
    ) {}

    public function processPulse(Universe $universe, UniverseSnapshot $snapshot): void
    {
        $metrics = $snapshot->metrics ?? [];

        // Ensure total_population for emergence complexity when not set (Eval does not fill it before ProcessInstitutionalFramework)
        if (!isset($metrics['total_population'])) {
            try {
                $eco = $this->ecosystemMetricsService->forUniverse($universe);
                $metrics['total_population'] = $eco['total_population'];
                $snapshot->metrics = array_merge($snapshot->metrics ?? [], ['total_population' => $eco['total_population']]);
            } catch (\Throwable $e) {
                // Fallback: getPopulationForComplexity will use state_vector or actor count
            }
        }

        // 1. Natural Emergence
        $this->evaluateNaturalEmergence($universe, $snapshot, $metrics);

        // 2. Ascended Heroes
        $this->ascendHeroAction->handle($universe, $snapshot);

        // 3. Impact on World
        $this->applyCosmicImpact($universe, $snapshot, $metrics);
    }

    protected function evaluateNaturalEmergence(Universe $universe, UniverseSnapshot $snapshot, array $metrics): void
    {
        $tick = (int) $snapshot->tick;

        // Guards: max global entities
        $maxEntities = (int) config('worldos.emergence.max_global_entities', 5);
        $activeCount = SupremeEntityModel::where('universe_id', $universe->id)->where('status', 'active')->count();
        if ($activeCount >= $maxEntities) {
            return;
        }

        // Guards: cooldown since last spawn
        $cooldown = (int) config('worldos.emergence.min_ticks_between_entities', 200);
        $lastSpawnTick = SupremeEntityModel::where('universe_id', $universe->id)->max('ascended_at_tick');
        if ($lastSpawnTick !== null && ($tick - $lastSpawnTick) < $cooldown) {
            return;
        }

        $energy = max(0.0, min(1.0, (float) ($metrics['energy_level'] ?? 0.5)));
        $entropy = max(0.0, min(1.0, (float) ($snapshot->entropy ?? 0.5)));
        $spirituality = max(0.0, min(1.0, (float) (($metrics['ethos'] ?? [])['spirituality'] ?? 0.5)));

        $population = $this->getPopulationForComplexity($snapshot, $metrics, $universe);
        $cap = (int) config('worldos.emergence.complexity_population_cap', 1_000_000);
        $complexity = max(0.0, min(1.0, $population / max(1, $cap)));

        $ticksPerYear = (int) config('worldos.emergence.ticks_per_year', 12);
        $tickDurationFactor = $ticksPerYear > 0 ? 1.0 / $ticksPerYear : 1.0;
        $scale = (float) config('worldos.emergence.scale', 0.02);
        $maxP = (float) config('worldos.emergence.max_probability', 0.02);

        // World Will: entropy_factor peak at semi-chaotic
        $optimalWorldWill = (float) config('worldos.emergence.optimal_entropy_world_will', 0.4);
        $entropyFactorWorldWill = 1.0 - abs($entropy - $optimalWorldWill);
        $pWorldWill = $energy * $complexity * $entropyFactorWorldWill * $spirituality * $scale * $tickDurationFactor;
        $pWorldWill = min($pWorldWill, $maxP);
        if ($pWorldWill > 0 && (mt_rand() / mt_getrandmax()) < $pWorldWill) {
            $this->spawnAction->handle($universe, $tick, [
                'name' => 'Thiên Ý Nguyên Thủy',
                'entity_type' => 'world_will',
                'domain' => 'Trật Tự Tuyệt Đối',
                'power_level' => 1.0,
                'alignment' => ['spirituality' => 0.9, 'hardtech' => 0.1, 'entropy' => 0.0, 'energy_level' => 1.0]
            ]);
            return;
        }

        // Outer God: entropy_factor peak at high entropy
        $optimalOuterGod = (float) config('worldos.emergence.optimal_entropy_outer_god', 0.85);
        $entropyFactorOuterGod = 1.0 - abs($entropy - $optimalOuterGod);
        $pOuterGod = $energy * $complexity * $entropyFactorOuterGod * (1.0 - $spirituality) * $scale * $tickDurationFactor;
        $pOuterGod = min($pOuterGod, $maxP);
        if ($pOuterGod > 0 && (mt_rand() / mt_getrandmax()) < $pOuterGod) {
            $this->spawnAction->handle($universe, $tick, [
                'name' => 'Thực Thể Viễn Cổ',
                'entity_type' => 'outer_god',
                'domain' => 'Hỗn Độn Vô Hạn',
                'power_level' => 1.5,
                'alignment' => ['spirituality' => 0.5, 'hardtech' => 0.5, 'entropy' => 1.0, 'energy_level' => 0.9]
            ]);
        }
    }

    /**
     * Population for world complexity (emergence threshold). From metrics, state_vector, or actor count.
     */
    protected function getPopulationForComplexity(UniverseSnapshot $snapshot, array $metrics, Universe $universe): float
    {
        if (isset($metrics['total_population'])) {
            return (float) $metrics['total_population'];
        }
        $state = $snapshot->state_vector ?? [];
        if (is_array($state) && isset($state['population'])) {
            $p = $state['population'];
            return is_numeric($p) ? (float) $p : 0.0;
        }
        if (isset($state['civilization']['total_population'])) {
            return (float) $state['civilization']['total_population'];
        }
        return (float) \App\Modules\Intelligence\Models\Actor::where('universe_id', $universe->id)->where('is_alive', true)->count();
    }

    protected function applyCosmicImpact(Universe $universe, UniverseSnapshot $snapshot, array &$metrics): void
    {
        $entities = $this->supremeEntityRepository->findByUniverse($universe->id);
        $activeEntities = array_filter($entities, fn($e) => $e->status === 'active');

        if (empty($activeEntities)) return;

        $ethos = $metrics['ethos'] ?? [];
        $state = [
            'spirituality' => (float) ($ethos['spirituality'] ?? 0.5),
            'hardtech' => (float) ($ethos['openness'] ?? 0.5),
            'entropy' => (float) ($snapshot->entropy ?? 0.5),
            'energy_level' => (float) ($metrics['energy_level'] ?? 0.5),
        ];

        foreach ($activeEntities as $entity) {
            $alignment = $entity->alignment;
            $dt = 0.02 * log(1.0 + $entity->powerLevel);
            $dt = max(0.0, min(0.15, $dt));

            foreach (['spirituality', 'hardtech', 'entropy', 'energy_level'] as $dim) {
                if (isset($alignment[$dim])) {
                    $state[$dim] += ($alignment[$dim] - $state[$dim]) * $dt;
                }
            }
        }

        // Update snapshot (in-memory only; Eval will persist)
        // ethos = ideology (spirituality, rationality/hardtech); metrics = physics (energy_level, entropy).
        $ethos['spirituality'] = max(0.0, min(1.0, $state['spirituality']));
        $ethos['openness'] = max(0.0, min(1.0, $state['hardtech']));
        $ethos['rationality'] = max(0.0, min(1.0, $state['hardtech'])); // same as openness for compatibility
        $metrics['ethos'] = $ethos;
        $metrics['energy_level'] = max(0.0, min(1.0, $state['energy_level']));
        
        $snapshot->entropy = max(0.0, min(1.0, $state['entropy']));
        $snapshot->metrics = $metrics;
    }
}

