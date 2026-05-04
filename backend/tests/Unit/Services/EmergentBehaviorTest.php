<?php

namespace Tests\Unit\Services;

use App\Modules\Intelligence\Services\CivilizationAttractorEngine;
use App\Modules\Intelligence\Entities\Archetypes\Warlord;
use App\Modules\Intelligence\Entities\Archetypes\Technocrat;
use App\Modules\Intelligence\Entities\Archetypes\RogueAI;
use App\Modules\Intelligence\Entities\Archetypes\Archmage;
use App\Modules\Intelligence\Entities\Archetypes\VillageElder;
use App\Modules\Intelligence\Entities\Archetypes\TribalLeader;
use Tests\TestCase;

/**
 * Emergent Behavior Validation Tests.
 *
 * Kiểm tra xem hệ attractor thực sự sinh ra emergent behavior
 * chứ không phải hardcoded outcomes.
 */
class EmergentBehaviorTest extends TestCase
{
    private CivilizationAttractorEngine $engine;
    private array $allArchetypes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new CivilizationAttractorEngine();
        $this->allArchetypes = [
            new Warlord(),
            new Technocrat(),
            new RogueAI(),
            new Archmage(),
            new VillageElder(),
            new TribalLeader(),
        ];
    }

    /**
     * Helper: evaluate state, return name of highest-scored archetype.
     */
    private function winner(array $state, ?array $archetypes = null): string
    {
        $results = $this->engine->evaluate($state, $archetypes ?? $this->allArchetypes);
        return $results[0]['archetype']->getName();
    }

    /**
     * Helper: generate random state biased toward a profile.
     */
    private function randomState(string $profile): array
    {
        $base = array_fill_keys(CivilizationAttractorEngine::CANONICAL_DIMENSIONS, 0.0);

        switch ($profile) {
            case 'chaotic':
                // High militarism, high chaos, low stability, high trauma
                $base['militarism'] = $this->randRange(0.6, 1.0);
                $base['chaos'] = $this->randRange(0.6, 1.0);
                $base['stability'] = $this->randRange(0.0, 0.3);
                $base['trauma'] = $this->randRange(0.4, 1.0);
                $base['technology'] = $this->randRange(0.0, 0.5);
                $base['tradition'] = $this->randRange(0.0, 0.5);
                $base['culture'] = $this->randRange(0.1, 0.5);
                $base['spirituality'] = $this->randRange(0.0, 0.4);
                $base['knowledge'] = $this->randRange(0.1, 0.4);
                break;

            case 'stable_tech':
                // High technology, high stability, high knowledge
                $base['technology'] = $this->randRange(0.7, 1.0);
                $base['stability'] = $this->randRange(0.6, 1.0);
                $base['knowledge'] = $this->randRange(0.5, 0.9);
                $base['chaos'] = $this->randRange(0.0, 0.3);
                $base['militarism'] = $this->randRange(0.0, 0.3);
                $base['tradition'] = $this->randRange(0.0, 0.4);
                $base['culture'] = $this->randRange(0.2, 0.5);
                $base['spirituality'] = $this->randRange(0.0, 0.3);
                $base['trauma'] = $this->randRange(0.0, 0.2);
                break;

            case 'traditional':
                // High tradition, high culture, moderate stability
                $base['tradition'] = $this->randRange(0.7, 1.0);
                $base['culture'] = $this->randRange(0.6, 1.0);
                $base['stability'] = $this->randRange(0.4, 0.8);
                $base['chaos'] = $this->randRange(0.0, 0.3);
                $base['technology'] = $this->randRange(0.0, 0.4);
                $base['militarism'] = $this->randRange(0.1, 0.4);
                $base['spirituality'] = $this->randRange(0.3, 0.7);
                $base['knowledge'] = $this->randRange(0.2, 0.5);
                $base['trauma'] = $this->randRange(0.0, 0.2);
                break;
        }

        return $base;
    }

    private function randRange(float $min, float $max): float
    {
        return $min + (mt_rand(0, 10000) / 10000.0) * ($max - $min);
    }

    // ═══════════════════════════════════════════════════════════
    // TEST 1: Parameter Perturbation (1000 simulations clustering)
    // ═══════════════════════════════════════════════════════════

    public function test_chaotic_states_cluster_toward_warlord(): void
    {
        $warlordWins = 0;
        $runs = 1000;

        for ($i = 0; $i < $runs; $i++) {
            $state = $this->randomState('chaotic');
            if ($this->winner($state) === 'Warlord') {
                $warlordWins++;
            }
        }

        $ratio = $warlordWins / $runs;
        // Warlord should dominate chaotic states (>50%)
        $this->assertGreaterThan(0.50, $ratio,
            "Warlord won only {$ratio}% of chaotic states — expected >50% (actual: {$warlordWins}/{$runs})");
    }

    public function test_stable_tech_states_cluster_toward_technocrat(): void
    {
        $techWins = 0;
        $runs = 1000;

        for ($i = 0; $i < $runs; $i++) {
            $state = $this->randomState('stable_tech');
            if ($this->winner($state) === 'Technocrat') {
                $techWins++;
            }
        }

        $ratio = $techWins / $runs;
        $this->assertGreaterThan(0.50, $ratio,
            "Technocrat won only {$ratio}% of stable-tech states — expected >50% (actual: {$techWins}/{$runs})");
    }

    public function test_traditional_states_cluster_toward_village_elder(): void
    {
        $elderWins = 0;
        $runs = 1000;

        for ($i = 0; $i < $runs; $i++) {
            $state = $this->randomState('traditional');
            if ($this->winner($state) === 'VillageElder') {
                $elderWins++;
            }
        }

        $ratio = $elderWins / $runs;
        $this->assertGreaterThan(0.40, $ratio,
            "VillageElder won only {$ratio}% of traditional states — expected >40% (actual: {$elderWins}/{$runs})");
    }

    // ═══════════════════════════════════════════════════════════
    // TEST 2: Actor Removal (Ecosystem Resilience)
    // ═══════════════════════════════════════════════════════════

    public function test_removing_warlord_ecosystem_redistributes(): void
    {
        // Without Warlord, chaotic states should be won by other archetypes
        $withoutWarlord = array_filter($this->allArchetypes, fn($a) => $a->getName() !== 'Warlord');
        $withoutWarlord = array_values($withoutWarlord);

        $winners = [];
        $runs = 500;

        for ($i = 0; $i < $runs; $i++) {
            $state = $this->randomState('chaotic');
            $name = $this->winner($state, $withoutWarlord);
            $winners[$name] = ($winners[$name] ?? 0) + 1;
        }

        // System should still function — at least 2 different archetypes should win
        $this->assertGreaterThanOrEqual(2, count($winners),
            "Only " . count($winners) . " archetype(s) won without Warlord — ecosystem not diverse enough");

        // TribalLeader or RogueAI should pick up some of the chaotic states
        $this->assertArrayHasKey('TribalLeader', $winners,
            "TribalLeader should absorb some chaotic states when Warlord removed. Winners: " . json_encode($winners));
    }

    public function test_removing_technocrat_ecosystem_redistributes(): void
    {
        $withoutTechnocrat = array_filter($this->allArchetypes, fn($a) => $a->getName() !== 'Technocrat');
        $withoutTechnocrat = array_values($withoutTechnocrat);

        $winners = [];
        $runs = 500;

        for ($i = 0; $i < $runs; $i++) {
            $state = $this->randomState('stable_tech');
            $name = $this->winner($state, $withoutTechnocrat);
            $winners[$name] = ($winners[$name] ?? 0) + 1;
        }

        $this->assertGreaterThanOrEqual(2, count($winners),
            "Only " . count($winners) . " archetype(s) won without Technocrat. Winners: " . json_encode($winners));
    }

    // ═══════════════════════════════════════════════════════════
    // TEST 3: Phase Boundary Detection (Regime Transition)
    // ═══════════════════════════════════════════════════════════

    public function test_phase_transition_stability_axis(): void
    {
        // Sweep stability from 0.0 → 1.0.
        // Keep secondary dimensions relatively neutral so multiple archetypes
        // can compete. Chaos tracks (1-stability) weakly, and
        // technology/knowledge provide a Technocrat baseline.
        $transitionFound = false;
        $prevWinner = null;
        $transitions = [];
        $regimes = [];

        for ($s = 0; $s <= 100; $s++) {
            $stability = $s / 100.0;
            $state = [
                'stability'    => $stability,
                'chaos'        => (1.0 - $stability) * 0.6,   // weaker coupling
                'militarism'   => 0.4,
                'technology'   => 0.6,                         // gives Technocrat baseline
                'knowledge'    => 0.5,                         // gives Technocrat baseline
                'tradition'    => 0.5,
                'culture'      => 0.5,
                'spirituality' => 0.3,
                'trauma'       => (1.0 - $stability) * 0.3,   // weaker coupling
                'ai_dependency'=> 0.1,
            ];

            $winner = $this->winner($state);
            $regimes[$s] = $winner;

            if ($prevWinner !== null && $winner !== $prevWinner) {
                $transitionFound = true;
                $transitions[] = ['at' => $stability, 'from' => $prevWinner, 'to' => $winner];
            }
            $prevWinner = $winner;
        }

        fwrite(STDERR, "\n  Stability phase transitions: " . json_encode($transitions) . "\n");
        fwrite(STDERR, "  Regimes sample: s=0→{$regimes[0]}, s=25→{$regimes[25]}, s=50→{$regimes[50]}, s=75→{$regimes[75]}, s=100→{$regimes[100]}\n");

        $this->assertTrue($transitionFound,
            "No phase transition found on stability axis 0→1. All states won by: {$prevWinner}");
    }

    public function test_phase_transition_technology_axis(): void
    {
        $transitionFound = false;
        $prevWinner = null;

        for ($t = 0; $t <= 100; $t++) {
            $tech = $t / 100.0;
            $state = [
                'technology'   => $tech,
                'stability'    => 0.6,
                'chaos'        => 0.3,
                'militarism'   => 0.4,
                'knowledge'    => $tech * 0.8, // knowledge correlates with tech
                'tradition'    => 1.0 - $tech, // tradition inversely correlated
                'culture'      => 0.5,
                'spirituality' => 0.3,
                'trauma'       => 0.1,
                'ai_dependency'=> $tech * 0.3,
            ];

            $winner = $this->winner($state);

            if ($prevWinner !== null && $winner !== $prevWinner) {
                $transitionFound = true;
            }
            $prevWinner = $winner;
        }

        $this->assertTrue($transitionFound,
            "No phase transition found on technology axis 0→1. All states won by: {$prevWinner}");
    }

    // ═══════════════════════════════════════════════════════════
    // TEST 4: Edge Case — Tech Dictatorship Scenario
    // ═══════════════════════════════════════════════════════════

    public function test_high_tech_low_stability_high_inequality_produces_interesting_outcome(): void
    {
        $state = [
            'technology'    => 0.9,
            'stability'     => 0.2,
            'inequality'    => 0.8,
            'chaos'         => 0.7,
            'militarism'    => 0.3,
            'knowledge'     => 0.7,
            'tradition'     => 0.1,
            'culture'       => 0.3,
            'spirituality'  => 0.1,
            'ai_dependency' => 0.5,
            'trauma'        => 0.4,
        ];

        $results = $this->engine->evaluate($state, $this->allArchetypes);

        // Winner should NOT be a simple choice — multiple archetypes should score close
        $topScore = $results[0]['score'];
        $secondScore = $results[1]['score'];
        $topName = $results[0]['archetype']->getName();
        $secondName = $results[1]['archetype']->getName();

        // In this unstable-tech scenario, RogueAI should be highly competitive
        $rogueScore = null;
        foreach ($results as $r) {
            if ($r['archetype']->getName() === 'RogueAI') {
                $rogueScore = $r['score'];
                break;
            }
        }

        // RogueAI thrives in high-tech chaos (tech=0.8, chaos=0.9, stability=-0.7, ai=0.6)
        $this->assertNotNull($rogueScore);
        $this->assertGreaterThan(0.5, $rogueScore,
            "RogueAI score ({$rogueScore}) should be significant in high-tech chaotic world");

        // The gap between #1 and #2 should be relatively tight (< 0.5)
        // showing genuine competition, not domination
        $gap = $topScore - $secondScore;
        $this->assertLessThan(0.5, $gap,
            "Gap between #{$topName}({$topScore}) and #{$secondName}({$secondScore}) is {$gap} — too dominant, lacks competition");

        // Log detailed scores for inspection
        $scoreMap = [];
        foreach ($results as $r) {
            $scoreMap[$r['archetype']->getName()] = round($r['score'], 3);
        }
        fwrite(STDERR, "\n  Edge case scores: " . json_encode($scoreMap) . "\n");
    }

    // ═══════════════════════════════════════════════════════════
    // BONUS: Basin Size Measurement
    // ═══════════════════════════════════════════════════════════

    public function test_attractor_basin_sizes_are_balanced(): void
    {
        $wins = [];
        $runs = 3000;

        for ($i = 0; $i < $runs; $i++) {
            // Fully random state: uniform [0,1] on all dimensions
            $state = [];
            foreach (CivilizationAttractorEngine::CANONICAL_DIMENSIONS as $dim) {
                $state[$dim] = mt_rand(0, 10000) / 10000.0;
            }

            $name = $this->winner($state);
            $wins[$name] = ($wins[$name] ?? 0) + 1;
        }

        // At least 3 different archetypes should have non-trivial basin (> 5%)
        $significantBasins = 0;
        $basinReport = [];
        foreach ($wins as $name => $count) {
            $pct = round($count / $runs * 100, 1);
            $basinReport[$name] = "{$pct}%";
            if ($pct > 5) {
                $significantBasins++;
            }
        }

        fwrite(STDERR, "\n  Basin sizes: " . json_encode($basinReport) . "\n");

        $this->assertGreaterThanOrEqual(3, $significantBasins,
            "Only {$significantBasins} archetype(s) have >5% basin. Basins: " . json_encode($basinReport));

        // No single archetype should dominate >60% of random space
        foreach ($wins as $name => $count) {
            $this->assertLessThan($runs * 0.60, $count,
                "{$name} dominates {$basinReport[$name]} of random space — too dominant");
        }
    }
}
