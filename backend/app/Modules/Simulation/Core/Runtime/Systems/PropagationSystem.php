<?php

namespace App\Modules\Simulation\Core\Runtime\Systems;

use App\Modules\Simulation\Core\Runtime\Contracts\WorldSystemInterface;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Runtime\Causality\ImpactReport;
use App\Modules\Simulation\Core\Runtime\WorldKernel;

/**
 * 6️⃣ Idea Propagation Rule: Ideas spread between actors.
 */
class PropagationSystem implements WorldSystemInterface
{
    public function update(array $context, int $tick): ?ImpactReport
    {
        $report = new ImpactReport('PropagationSystem', WorldKernel::PHASE_MIND, WorldKernel::RULE_DIFFUSION);
        $state = $context['state'];
        $actors = $state['actors'] ?? [];
        $ideas = $state['ideas'] ?? [];
        $impacts = $context['impacts'] ?? []; // Past impacts collected in this tick

        // 1. Rumor Spreading (Simple Zone-based Diffusion)
        $this->spreadRumors($actors, $report);

        // 2. SCAR Propagation (Translate World Scars to Agent Psychology)
        $this->propagateScars($actors, $impacts, $report);

        // 3. Cultural Idea Drift (Original Logic)
        foreach ($ideas as $idea) {
            $idea->appeal *= 1.001; // Natural cultural drift
            
            if ($idea->appeal > 0.9) {
                $idea->spreadRate += 0.01;

                // V81 Semantic Reporting
                $report->log('idea', $idea->id, 'surged_in_popularity', 'world', 'culture', 0.01, 1.0, [
                    'appeal' => $idea->appeal
                ]);
            }
        }

        return $report->hasImpacts() ? $report : null;
    }

    private function spreadRumors(array $actors, ImpactReport $report): void
    {
        // Simple logic: if anyone has a high "fear", they spread it to others in the same (x,y).
        $rumorsByZone = [];
        foreach ($actors as $actor) {
            if ($actor->psychology->get('fear') > 0.7) {
                $rumorsByZone["{$actor->x}_{$actor->y}"] = true;
            }
        }

        foreach ($actors as $actor) {
            $zoneKey = "{$actor->x}_{$actor->y}";
            if (isset($rumorsByZone[$zoneKey])) {
                if ($actor->psychology->get('fear') < 0.7) {
                    $actor->psychology->applyDelta(['fear' => 0.05, 'trust' => -0.02]);
                }
            }
        }
    }

    private function propagateScars(array $actors, array $impacts, ImpactReport $report): void
    {
        // Translate EVENT_SCAR impacts to agent psychology
        foreach ($impacts as $impact) {
            if (($impact['event_type'] ?? '') === 'EVENT_SCAR' || ($impact['action'] ?? '') === 'depleted_by_scarcity') {
                $x = $impact['location']['x'] ?? null;
                $y = $impact['location']['y'] ?? null;

                if ($x !== null && $y !== null) {
                    foreach ($actors as $actor) {
                        // Radius effect (e.g., within 3 units)
                        $dist = sqrt(pow($actor->x - $x, 2) + pow($actor->y - $y, 2));
                        if ($dist < 3.0) {
                            $fearDelta = 0.1 / ($dist + 1.0);
                            $actor->psychology->applyDelta(['fear' => $fearDelta, 'trust' => -$fearDelta/2]);
                        }
                    }
                }
            }
        }
    }
}
