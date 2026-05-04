<?php

namespace App\Modules\Intelligence\Services\Dashboard;

use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\World\Models\Universe;
use App\Modules\Institutions\Models\InstitutionalEntity;
use App\Modules\Intelligence\Services\AI\EpistemicService;

class StateMetricsService
{
    public function __construct(
        protected EpistemicService $epistemic
    ) {}

    /**
     * Get macro metrics across all active universes or a specific one.
     */
    public function getMacroState(?int $universeId = null): array
    {
        $query = UniverseSnapshot::latest('tick');
        if ($universeId) {
            $query->where('universe_id', $universeId);
        }

        $latest = $query->first();

        if (!$latest) {
            return [
                'tech' => 0.0,
                'stability' => 0.0,
                'coercion' => 0.0,
                'entropy' => 0.0,
                'sci' => 0.0,
                'militarism' => 0.0,
                'spirituality' => 0.0,
                'institutional' => 0.0,
                'noise' => 0.0,
                'clarity' => 'Canonical',
                'legacy' => 0.0,
                'innovation' => 0.5,
                'winner' => 'Unknown',
                'tick' => 0,
            ];
        }

        $sv = $latest->state_vector;
        if (is_string($sv)) {
            $sv = json_decode($sv, true) ?? [];
        }
        $metrics = $latest->metrics ?? [];
        $civilization = $sv['civilization'] ?? [];
        $politics = $civilization['politics'] ?? [];
        $meta = $sv['meta'] ?? [];
        $legacy = $sv['legacy'] ?? [];

        // 1. Militarism
        $war = $politics['war'] ?? [];
        $militarism = (float) ($war['intensity'] ?? 0.0);

        // 2. Spirituality
        $mythCount = count($meta['active_myths'] ?? []);
        $meaningCount = count($meta['meaning_systems'] ?? []);
        $spirituality = min(1.0, ($mythCount * 0.25) + ($meaningCount * 0.1));

        // 3. Institutional
        $instCount = \App\Modules\Institutions\Models\InstitutionalEntity::where('universe_id', $latest->universe_id)
            ->whereNull('collapsed_at_tick')
            ->count();
        $institutional = min(1.0, $instCount / 10.0);

        // 4. Epistemic Clarity
        $universe = Universe::find($latest->universe_id);
        $noise = $universe ? $this->epistemic->calculateNoise($universe, (float)$latest->entropy) : 0.0;
        $clarity = $this->epistemic->getClarityLabel($noise);

        // 5. Legacy & Innovation
        $knowledgeFloor = (float)($legacy['knowledge_floor'] ?? 0.0);
        $cultureFloor = (float)($legacy['culture_floor'] ?? 0.0);
        $legacyWeight = min(1.0, ($knowledgeFloor + $cultureFloor) * 1.5);
        
        $stagnation = (float)($metrics['stagnation_score'] ?? 0.0);
        $innovation = max(0.0, 1.0 - $stagnation);

        return [
            'tech' => (float) ($metrics['knowledge_core'] ?? ($sv['knowledge_core'] ?? ($sv['knowledge'] ?? 0.0))),
            'stability' => (float) ($latest->stability_index ?? 0.0),
            'coercion' => (float) ($sv['coercion'] ?? 0.0),
            'entropy' => (float) ($latest->entropy ?? 0.0),
            'sci' => (float) ($metrics['sci'] ?? ($latest->sci ?? 0.0)),
            'militarism' => $militarism,
            'spirituality' => $spirituality,
            'institutional' => $institutional,
            'noise' => $noise,
            'clarity' => $clarity,
            'legacy' => $legacyWeight,
            'innovation' => $innovation,
            'winner' => $metrics['winner_archetype'] ?? 'Unknown',
            'tick' => $latest->tick,
        ];
    }
}

