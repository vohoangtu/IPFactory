<?php

namespace App\Modules\Simulation\Core\Runtime\Systems;

use App\Modules\Simulation\Core\Runtime\Contracts\WorldSystemInterface;
use App\Modules\Simulation\Core\Runtime\Causality\ImpactReport;
use App\Modules\Simulation\Core\Runtime\WorldKernel;
use App\Modules\Simulation\Core\Services\LifecycleService;

class SurvivalSystem implements WorldSystemInterface
{
    public function __construct(
        private readonly LifecycleService $lifecycleService
    ) {}

    public function update(array $context, int $tick): ?ImpactReport
    {
        $report = new ImpactReport('SurvivalSystem', WorldKernel::PHASE_LIFE, WorldKernel::RULE_METABOLISM);
        $actors = $context['state']['actors'] ?? [];
        $resources = $context['state']['resources'] ?? [];
        $universeId = (int) ($context['state']['universe_id'] ?? 0);
        
        // Map resources by zone for fast access (§V9 Realignment)
        $resourceByZone = [];
        foreach ($resources as $res) {
            $resourceByZone[$res->zone_id] = $res;
        }

        foreach ($actors as $actor) {
            if (!$actor->isAlive) continue;

            // 1. Biological Tick (Hunger increment + Energy decay)
            $hungerDelta = 0.05 + ($tick % 10 === 0 ? 0.02 : 0); // Fluctuating metabolism
            $actor->hunger = min(1.0, ($actor->hunger ?? 0.0) + $hungerDelta);
            
            // 2. Resource Consumption (Search for food in current zone)
            $zoneId = $actor->zone_id;
            $zoneResources = $resourceByZone[$zoneId] ?? null;
            
            $consumed = false;
            if ($zoneResources && $zoneResources->quantity > 0) {
                // Decision: Hunger > 0.4 triggers intensive search
                if ($actor->hunger > 0.4) {
                    $consumeAmount = min(0.3, $actor->hunger, $zoneResources->quantity);
                    $actor->hunger -= $consumeAmount;
                    $zoneResources->quantity -= $consumeAmount;
                    $consumed = true;
                    
                    $report->log('actor', $actor->id, 'consumed_resource', 'zone', $zoneId, $consumeAmount, 1.0, [
                        'new_hunger' => $actor->hunger,
                        'remaining_resources' => $zoneResources->quantity
                    ]);
                }
            }

            // 3. Consequence: Starvation & Exhaustion
            if (!$consumed && $actor->hunger > 0.8) {
                // If hungry and no food found, energy decays rapidly
                $energy = (float)($actor->metrics['energy'] ?? $actor->energy ?? 100);
                $energy -= 15; // Heavy penalty for starvation
                
                $metrics = $actor->metrics;
                $metrics['energy'] = max(0, $energy);
                $actor->metrics = $metrics;
                $actor->energy = $metrics['energy']; // Sync for safety
                
                if ($energy <= 0) {
                    $actor->isAlive = false;
                    $actor->biography .= " [STARVED TO DEATH IN ZONE $zoneId AT TICK $tick]";
                    
                    $report->log('system', 'metabolism', 'death', 'actor', $actor->id, 1.0, 1.0, [
                        'reason' => 'starvation',
                        'zone_id' => $zoneId
                    ]);
                    
                    \Illuminate\Support\Facades\Log::info("SurvivalSystem: Actor {$actor->name} ({$actor->id}) starved in Zone {$zoneId}");
                }
            }

            // Hard Kill if resources are absolute zero in zone (The 11th Rule: Selection Pressure)
            if ($zoneResources && $zoneResources->quantity <= 0 && $actor->hunger > 0.9) {
                $actor->isAlive = false;
                $actor->biography .= " [PERISHED DUE TO TOTAL RESOURCE EXHAUSTION AT TICK $tick]";
                $report->log('system', 'scarcity', 'extinction', 'actor', $actor->id, 1.0, 1.0);
            }
        }

        return $report->hasImpacts() ? $report : null;
    }
}
