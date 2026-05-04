<?php

namespace App\Modules\Simulation\Services\Meta;

use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use Illuminate\Support\Collection;

/**
 * Timeline Selection Engine (Phase C): selects "best" timelines for narrative extraction or display.
 * Scores universes by narrative interest: novelty, complexity, divergence, depth, tension.
 * Higher score = more interesting story potential.
 */
class TimelineSelectionEngine
{
    public function __construct() {}

    /**
     * Return top timelines for a world, ordered by narrative interest (highest first).
     *
     * @param  int|null  $limit  Max number to return; null = use config default_limit; 0 = no limit
     */
    public function selectBest(World $world, ?int $limit = null): Collection
    {
        $universes = Universe::where('world_id', $world->id)->get();
        
        if ($universes->isEmpty()) {
            return collect();
        }

        $limit = $limit ?? (int) config('worldos.timeline_selection.default_limit', 10);

        $scored = $universes->map(function (Universe $u) {
            return [
                'universe' => $u,
                'narrative_score' => $this->computeNarrativeScore($u),
            ];
        })->sortByDesc('narrative_score')->values();

        if ($limit > 0) {
            return $scored->take($limit)->pluck('universe');
        }

        return $scored->pluck('universe');
    }


    /**
     * Narrative interest score from state_vector, entropy, and progress.
     */
    protected function computeNarrativeScore(Universe $universe): float
    {
        $weights = config('worldos.timeline_selection.narrative_weights', [
            'novelty' => 0.25,
            'complexity' => 0.25,
            'divergence' => 0.20,
            'depth' => 0.15,
            'tension' => 0.15,
        ]);

        $vec = (array) ($universe->state_vector ?? []);
        $novelty = $this->noveltyFromState($vec);
        $complexity = $this->complexityFromState($vec);
        $divergence = $this->divergenceFromUniverse($universe);
        $depth = $this->depthFromUniverse($universe);
        $tension = $this->tensionFromEntropy((float) ($universe->entropy ?? $vec['entropy'] ?? 0.5));

        $score = ($weights['novelty'] ?? 0.25) * $novelty
            + ($weights['complexity'] ?? 0.25) * $complexity
            + ($weights['divergence'] ?? 0.20) * $divergence
            + ($weights['depth'] ?? 0.15) * $depth
            + ($weights['tension'] ?? 0.15) * $tension;

        return round(min(1.0, max(0.0, $score)), 4);
    }

    protected function noveltyFromState(array $vec): float
    {
        $fields = (array) ($vec['fields'] ?? []);
        if (empty($fields)) {
            return 0.5;
        }
        $names = ['survival', 'power', 'wealth', 'knowledge', 'meaning'];
        $sum = 0.0;
        $n = 0;
        foreach ($names as $f) {
            $v = (float) ($fields[$f] ?? 0.5);
            $sum += ($v - 0.5) ** 2;
            $n++;
        }
        return min(1.0, $n > 0 ? sqrt($sum / $n) : 0.5);
    }

    protected function complexityFromState(array $vec): float
    {
        $zones = (array) ($vec['zones'] ?? []);
        $civ = (array) ($vec['civilization'] ?? []);
        $settlements = (array) ($civ['settlements'] ?? []);
        $zoneScore = min(1.0, count($zones) / 12.0);
        $civScore = min(1.0, count($settlements) / 8.0);
        return min(1.0, $zoneScore * 0.5 + $civScore * 0.5);
    }

    protected function divergenceFromUniverse(Universe $universe): float
    {
        if (! $universe->parent_universe_id) {
            return 0.5;
        }
        $parent = Universe::find($universe->parent_universe_id);
        if (! $parent || ! $parent->state_vector) {
            return 0.5;
        }
        $vec = (array) $universe->state_vector;
        $parentVec = (array) $parent->state_vector;
        $fields1 = (array) ($vec['fields'] ?? []);
        $fields2 = (array) ($parentVec['fields'] ?? []);
        $names = ['survival', 'power', 'wealth', 'knowledge', 'meaning'];
        $dist = 0.0;
        $n = 0;
        foreach ($names as $f) {
            $dist += abs((float) ($fields1[$f] ?? 0.5) - (float) ($fields2[$f] ?? 0.5));
            $n++;
        }
        return $n > 0 ? min(1.0, $dist / $n) : 0.5;
    }

    protected function depthFromUniverse(Universe $universe): float
    {
        $tick = (int) ($universe->current_tick ?? 0);
        return min(1.0, $tick / 2000.0);
    }

    /**
     * Tension: entropy in "interesting" range (0.3–0.7) scores higher; too low or too high = less narrative tension.
     */
    protected function tensionFromEntropy(float $entropy): float
    {
        if ($entropy >= 0.3 && $entropy <= 0.7) {
            $mid = 0.5;
            return 1.0 - 2.0 * abs($entropy - $mid) / 0.4;
        }
        return max(0.0, 0.5 - abs($entropy - 0.5));
    }
}

