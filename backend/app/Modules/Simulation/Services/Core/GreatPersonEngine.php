<?php

namespace App\Modules\Simulation\Services\Core;

use App\Modules\Simulation\Models\BranchEvent;
use App\Modules\Institutions\Models\InstitutionalEntity;
use App\Modules\Institutions\Models\SupremeEntity as SupremeEntityModel;
use App\Modules\World\Models\Universe;
use App\Modules\Institutions\Actions\SpawnSupremeEntityAction;
use App\Modules\Institutions\Entities\SupremeEntity;
use Illuminate\Support\Facades\Log;

/**
 * Great Person Engine (Phase H): evaluates conditions and spawns SupremeEntity
 * (great person) when universe state meets thresholds. Uses existing SpawnSupremeEntityAction.
 */
class GreatPersonEngine
{
    private const ENTITY_TYPES = [
        'prophet' => ['domain' => 'Truyền đạo', 'prefix' => 'Tiên tri'],
        'general' => ['domain' => 'Binh nghiệp', 'prefix' => 'Đại tướng'],
        'sage' => ['domain' => 'Minh triết', 'prefix' => 'Hiền nhân'],
        'builder' => ['domain' => 'Kiến tạo', 'prefix' => 'Kiến trúc sư'],
        'scholar' => ['domain' => 'Học thuật', 'prefix' => 'Đại học giả'],
    ];

    public function __construct(
        protected SpawnSupremeEntityAction $spawnAction
    ) {}

    /**
     * Evaluate whether the universe is eligible to spawn a great person.
     *
     * @return array{eligible: bool, reason: string, entropy: float, institution_count: int, last_supreme_tick: int|null}
     */
    public function evaluateCandidates(Universe $universe, int $tick): array
    {
        $entropyMin = (float) config('worldos.great_person.entropy_min', 0.3);
        $entropyMax = (float) config('worldos.great_person.entropy_max', 0.75);
        $minInstitutions = (int) config('worldos.great_person.min_institutions', 1);
        $cooldownTicks = (int) config('worldos.great_person.cooldown_ticks', 500);

        $entropy = (float) ($universe->entropy ?? ($universe->state_vector['entropy'] ?? 0.5));
        $institutionCount = InstitutionalEntity::where('universe_id', $universe->id)
            ->whereNull('collapsed_at_tick')
            ->count();

        $lastSupreme = BranchEvent::where('universe_id', $universe->id)
            ->where('event_type', 'supreme_emergence')
            ->orderByDesc('from_tick')
            ->first();
        $lastTick = $lastSupreme ? (int) $lastSupreme->from_tick : null;
        $ticksSince = $lastTick === null ? PHP_INT_MAX : $tick - $lastTick;

        $populationProxy = $this->getPopulationProxy($universe);
        $heroesPerPop = (int) config('worldos.great_person.heroes_per_population', 100000);
        $effectivePop = $populationProxy * 10_000_000;
        $maxHeroes = max(1, (int) floor($effectivePop / max(1, $heroesPerPop)));
        $currentHeroes = SupremeEntityModel::where('universe_id', $universe->id)
            ->where('entity_type', 'like', 'great_person_%')
            ->count();
        $underHeroCap = $currentHeroes < $maxHeroes;

        $eligible = $entropy >= $entropyMin && $entropy <= $entropyMax
            && $institutionCount >= $minInstitutions
            && $ticksSince >= $cooldownTicks
            && $underHeroCap;

        $reason = ! $eligible
            ? ($entropy < $entropyMin ? 'entropy_low' : ($entropy > $entropyMax ? 'entropy_high' : ($institutionCount < $minInstitutions ? 'few_institutions' : ($ticksSince < $cooldownTicks ? 'cooldown' : 'hero_cap'))))
            : 'eligible';

        return [
            'eligible' => $eligible,
            'reason' => $reason,
            'entropy' => $entropy,
            'institution_count' => $institutionCount,
            'last_supreme_tick' => $lastTick,
        ];
    }

    /**
     * If conditions are met, spawn one SupremeEntity and return it; otherwise null.
     */
    public function spawnIfEligible(Universe $universe, int $tick): ?SupremeEntity
    {
        $eval = $this->evaluateCandidates($universe, $tick);
        if (! ($eval['eligible'] ?? false)) {
            return null;
        }

        $typeKey = array_rand(self::ENTITY_TYPES);
        $config = self::ENTITY_TYPES[$typeKey];
        $name = $config['prefix'] . ' ' . ($universe->name ?? 'Vô danh') . '-' . $tick;

        try {
            $entity = $this->spawnAction->handle($universe, $tick, [
                'name' => $name,
                'entity_type' => 'great_person_' . $typeKey,
                'domain' => $config['domain'],
                'description' => "Vĩ nhân xuất hiện trong giai đoạn biến chuyển. {$config['domain']}.",
                'power_level' => 0.6,
                'alignment' => ['entropy' => $eval['entropy'], 'institutions' => $eval['institution_count']],
            ], null);

            Log::info("GreatPersonEngine: spawned SupremeEntity #{$entity->id} for Universe #{$universe->id} at tick {$tick}");
            return $entity;
        } catch (\Throwable $e) {
            Log::error("GreatPersonEngine: spawn failed: " . $e->getMessage());
            return null;
        }
    }

    private function getPopulationProxy(Universe $universe): float
    {
        $vec = $universe->state_vector;
        if (! is_array($vec) || empty($vec['zones'])) {
            return 0.01;
        }
        $sum = 0.0;
        foreach ($vec['zones'] as $zone) {
            $state = $zone['state'] ?? [];
            $sum += (float) ($state['population_proxy'] ?? 0);
        }
        return $sum > 0 ? $sum : 0.01;
    }
}

