<?php

namespace App\Modules\Intelligence\Services\Dashboard;

use App\Modules\Intelligence\Services\Morphogenesis\EvolutionaryOperator;
use App\Modules\Simulation\Models\CivilizationAttractor;

class ArchetypeMetricsService
{
    /**
     * Get evolution tree and archetype populations.
     */
    public function getEvolutionMetrics(): array
    {
        // We query the DB for real simulation events reflecting evolution and archetypes.
        $recentMemories = \App\Modules\Intelligence\Models\AiMemory::where('category', 'episode')
            ->latest()
            ->limit(20)
            ->get();
            
        $nodes = [];
        $links = [];
        $winRates = [];
        $seenArchetypes = [];

        foreach ($recentMemories as $mem) {
            $content = json_decode($mem->content, true) ?? [];
            if (isset($content['winner_archetype'])) {
                $winner = $content['winner_archetype'];
                if (!isset($seenArchetypes[$winner])) {
                    $seenArchetypes[$winner] = 0;
                }
                $seenArchetypes[$winner]++;
            }
        }

        $idx = 0;
        foreach ($seenArchetypes as $arch => $count) {
            $nodes[] = ['id' => $arch, 'label' => $arch, 'group' => 1];
            $winRates[] = ['name' => $arch, 'rate' => $count];
            if ($idx > 0) {
                // Link to previous to show a sequence
                $prev = array_keys($seenArchetypes)[$idx - 1];
                $links[] = ['source' => $prev, 'target' => $arch, 'value' => 1];
            }
            $idx++;
        }

        if (empty($nodes)) {
            // Fallback default if simulation is brand new
            $nodes[] = ['id' => 'BaseSpecies', 'label' => 'Base Species', 'group' => 1];
            $winRates[] = ['name' => 'BaseSpecies', 'rate' => 1];
        }

        return [
            'nodes' => $nodes,
            'links' => $links,
            'win_rates' => $winRates
        ];
    }
}

