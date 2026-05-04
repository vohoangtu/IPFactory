<?php

namespace App\Modules\Simulation\Services\Society;

use App\Contracts\UniverseEvaluatorInterface;
use App\Contracts\UniverseSimilarityServiceInterface;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Narrative\Models\Myth;
use App\Modules\World\Models\School;
use App\Modules\Simulation\Services\Core\MetricsExtractor;
use Illuminate\Support\Facades\Log;

/**
 * Autonomic Evolution Engine (AEE): decides universe lifecycle (continue / fork / archive / mutate / merge / promote).
 * doc §13: merge when similarity > 0.92; promote when civilization milestone.
 */
class AutonomicEvolutionEngine implements UniverseEvaluatorInterface
{
    public function __construct(
        protected MetricsExtractor $metrics,
        protected ?UniverseSimilarityServiceInterface $similarityService = null
    ) {}

    /**
     * Evaluate snapshot and return recommendation (IP-score, fork/continue/archive/mutate, mutation suggestion).
     */
    public function evaluate(UniverseSnapshot $snapshot): array
    {
        $m = $this->metrics->extract($snapshot);
        $entropy = (float) ($m['entropy'] ?? 0.5);
        $stability = (float) ($m['stability_index'] ?? 0.5);
        $complexity = (float) ($m['complexity'] ?? 0);

        $archiveThreshold = (float) config('worldos.autonomic.archive_entropy_threshold', 0.99);
        $forkMin = (float) config('worldos.autonomic.fork_entropy_min', 0.5);
        $stagnationThreshold = (float) config('worldos.autonomic.stagnation_threshold', 0.1);

        $ip_score = $stability * (1 - $entropy * 0.5);
        $novelty = $this->computeNovelty($snapshot);

        $mergeThreshold = (float) config('worldos.autonomic.merge_similarity_threshold', 0.92);
        $promoteComplexity = (float) config('worldos.autonomic.promote_milestone_complexity', 0);
        $promoteCivCount = (int) config('worldos.autonomic.promote_milestone_civ_count', 0);

        $recommendation = 'continue';
        $mergeCandidateUniverseId = null;

        $minTicksBeforeMerge = (int) config('worldos.autonomic.min_ticks_before_merge', 50);
        $tick = (int) ($snapshot->tick ?? 0);

        if ($this->similarityService !== null && $tick >= $minTicksBeforeMerge) {
            $candidate = $this->similarityService->getMergeCandidate($snapshot);
            if ($candidate !== null && ($candidate['similarity'] ?? 0) >= $mergeThreshold) {
                $recommendation = 'merge';
                $mergeCandidateUniverseId = $candidate['universe_id'] ?? null;
            }
        }

        if ($recommendation === 'continue') {
            $minTicksBeforeArchive = (int) config('worldos.autonomic.min_ticks_before_archive', 150);
            $forkGracePeriod = (int) config('worldos.autonomic.fork_grace_period_ticks', 50);
            $tick = (int) ($snapshot->tick ?? 0);
            if ($entropy >= $forkMin) {
                $universe = $snapshot->universe;
                $hasChildren = $universe && $universe->childUniverses()->exists();
                if (!$hasChildren) {
                    $recommendation = 'fork';
                } elseif ($entropy >= $archiveThreshold && $tick >= $minTicksBeforeArchive) {
                    $recommendation = 'archive';
                }
            } elseif ($entropy >= $archiveThreshold && $tick >= $minTicksBeforeArchive) {
                $universe = $snapshot->universe;
                $inGracePeriod = $universe && $universe->forked_at_tick !== null
                    && ($tick - (int) $universe->forked_at_tick) < $forkGracePeriod;
                if (!$inGracePeriod) {
                    $recommendation = 'archive';
                }
            } elseif ($promoteComplexity > 0 && $complexity >= $promoteComplexity) {
                if ($this->isQualitativelyReady($snapshot)) {
                    $recommendation = 'promote';
                    Log::info("AEE: promote (complexity milestone) Universe " . ($snapshot->universe_id ?? '?') . " at tick {$snapshot->tick}, complexity={$complexity}");
                } else {
                    Log::debug("AEE: skipped promote (complexity reached but qualitative depth insufficient) Universe " . ($snapshot->universe_id ?? '?'));
                }
            } elseif ($promoteCivCount > 0 && ($m['civilization_count'] ?? 0) >= $promoteCivCount) {
                if ($this->isQualitativelyReady($snapshot)) {
                    $recommendation = 'promote';
                    Log::info("AEE: promote (civ count milestone) Universe " . ($snapshot->universe_id ?? '?') . " at tick {$snapshot->tick}");
                } else {
                    Log::debug("AEE: skipped promote (civ count reached but qualitative depth insufficient) Universe " . ($snapshot->universe_id ?? '?'));
                }
            } elseif ($novelty < $stagnationThreshold) {
                $recommendation = 'mutate';
                Log::info("AEE: mutate (stub) suggested for Universe " . ($snapshot->universe_id ?? '?') . " at tick {$snapshot->tick}, novelty={$novelty}");
            }
        }

        $mutation_suggestion = null;
        if ($entropy >= 0.7) {
            $mutation_suggestion = ['suggest_reduce_entropy' => true];
        } elseif ($entropy >= 0.5 && $entropy < 0.6 && $recommendation === 'continue') {
            $mutation_suggestion = ['add_scar' => 'entropy_crisis_scar'];
        }

        $meta = [
            'entropy' => $entropy,
            'stability' => $stability,
            'novelty' => $novelty,
            'reason' => "AEE at Tick {$snapshot->tick}: entropy={$entropy}, novelty={$novelty}",
        ];
        if ($mergeCandidateUniverseId !== null) {
            $meta['merge_candidate_universe_id'] = $mergeCandidateUniverseId;
        }

        return [
            'ip_score' => round($ip_score, 4),
            'recommendation' => $recommendation,
            'mutation_suggestion' => $mutation_suggestion,
            'meta' => $meta,
        ];
    }

    /**
     * Check if the universe has enough qualitative depth (Myths, Schools) to promote.
     */
    protected function isQualitativelyReady(UniverseSnapshot $snapshot): bool
    {
        $universeId = $snapshot->universe_id;
        if (!$universeId) return false;

        $mythCount = Myth::where('universe_id', $universeId)->count();
        $schoolCount = School::where('universe_id', $universeId)->count();

        $minMyths = (int) config('worldos.autonomic.promote_min_myths', 2);
        $minSchools = (int) config('worldos.autonomic.promote_min_schools', 1);

        return $mythCount >= $minMyths || $schoolCount >= $minSchools;
    }

    /**
     * Simple novelty from state_vector (distance from "empty" state). Stub: 0.5 when no fields.
     */
    protected function computeNovelty(UniverseSnapshot $snapshot): float
    {
        $vec = (array) ($snapshot->state_vector ?? []);
        $fields = (array) ($vec['fields'] ?? []);
        if (empty($fields)) {
            return 0.5;
        }
        $fieldNames = ['survival', 'power', 'wealth', 'knowledge', 'meaning'];
        $sum = 0.0;
        $n = 0;
        foreach ($fieldNames as $f) {
            $v = (float) ($fields[$f] ?? 0.5);
            $sum += ($v - 0.5) ** 2;
            $n++;
        }
        $distance = $n > 0 ? sqrt($sum / $n) : 0.5;
        return min(1.0, $distance);
    }
}


