<?php

namespace App\Modules\Simulation\Core\Services;

use App\Modules\Simulation\Core\Entities\Agent;
use App\Modules\Simulation\Core\Events\ActorBornEvent;
use App\Modules\Simulation\Core\Events\ActorDiedEvent;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;

/**
 * Quản lý Sinh (Reproduction) và Tử (Death) của Agent.
 * Tuân thủ Event-Driven Pattern và 17D Trait System.
 */
class LifecycleService
{
    public function __construct(
        private readonly Dispatcher $events
    ) {}

    /**
     * Kiểm tra cái chết và bắn event nếu cần.
     */
    public function checkDeath(object $agent, int $universeId, int $tick): bool
    {
        if (!$agent->isAlive()) {
            $this->events->dispatch(new ActorDiedEvent($universeId, $tick, [
                'actor_id' => $agent->id,
                'location' => ['x' => $agent->x ?? 0, 'y' => $agent->y ?? 0]
            ]));
            return true;
        }
        return false;
    }

    /**
     * Kiểm tra lão hóa (Aging).
     */
    public function checkAging(Agent $agent, int $universeId, int $tick): bool
    {
        $ticksPerYear = (int) config('worldos.intelligence.ticks_per_year', 1);
        $ageYears = ($tick - $agent->birthTick) / max(1, $ticksPerYear);

        if ($ageYears >= $agent->lifeExpectancy) {
            $agent->die();
            return $this->checkDeath($agent, $universeId, $tick);
        }

        return false;
    }

    /**
     * Kiểm tra sinh tồn xác suất (Dựa trên Entropy và Fitness).
     */
    public function checkStochasticSurvival(
        Agent $agent, 
        int $universeId, 
        int $tick, 
        float $entropy, 
        float $fitness,
        float $riskModifier = 0.0
    ): bool {
        // Rng based on actor and tick
        $seed = $agent->id . $tick;
        mt_srand(crc32($seed));
        
        $roll = mt_rand(0, 1000) / 1000.0;
        
        // Base death probability increases with entropy and low fitness
        // Formula: P(Death) = (Entropy * 0.01) + (1.0 - Fitness) * 0.02 + riskModifier
        $deathProb = ($entropy * 0.01) + (max(0, 1.0 - $fitness) * 0.02) + $riskModifier;

        if ($roll < $deathProb) {
            $agent->die();
            return $this->checkDeath($agent, $universeId, $tick);
        }

        return false;
    }

    /**
     * Kiểm tra điều kiện sinh sản giữa 2 Agent.
     */
    public function canReproduce(Agent $p1, Agent $p2): bool
    {
        return $p1->x === $p2->x && $p1->y === $p2->y
            && $p1->isAlive() && $p2->isAlive()
            && $p1->health >= 50 && $p2->health >= 50
            && $p1->hunger <= 0.3 && $p2->hunger <= 0.3
            && $p1->energy >= 40 && $p2->energy >= 40;
    }

    /**
     * Thực hiện quá trình sinh sản.
     */
    public function tryReproduce(Agent $parent1, Agent $parent2, int $universeId, int $tick): ?Agent
    {
        if (!$this->canReproduce($parent1, $parent2)) {
            return null;
        }

        // 1. Áp dụng chi phí sinh sản
        $parent1->applyReproductionCost();
        $parent2->applyReproductionCost();

        // 2. Lai ghép gene 17D TraitVector
        $noise = (mt_rand(-10, 10) / 100.0);
        $childTraits = $parent1->traits->blend($parent2->traits, $noise);

        // 3. Tạo Agent con
        $child = new Agent(
            id: (string) Str::uuid(),
            health: 80.0,
            energy: 50.0,
            hunger: 0.3,
            birthTick: $tick, // Đã có thuộc tính này
            x: $parent1->x,
            y: $parent1->y,
            traits: $childTraits
        );

        // 4. Bắn event sinh nở
        $this->events->dispatch(new ActorBornEvent($universeId, $tick, [
            'child_id' => $child->id,
            'parent1_id' => $parent1->id,
            'parent2_id' => $parent2->id,
            'traits' => $childTraits->toArray()
        ]));

        return $child;
    }
}
