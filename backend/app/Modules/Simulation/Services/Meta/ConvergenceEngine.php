<?php

namespace App\Modules\Simulation\Services\Meta;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseInteraction;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Actions\MergeUniverseAction;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use function config;
use function app;
use function now;
use function abs;
use function is_array;

/**
 * Convergence Engine: Orchestrates the merging of multiverse timelines.
 * Driven by alignment resonance and stability thresholds.
 */
class ConvergenceEngine
{
    public function __construct(
        protected RuleVmService $ruleVm,
        protected ?MergeUniverseAction $mergeAction = null
    ) {}

    /**
     * Process a universe to check for convergence opportunities.
     */
    public function process(Universe $universe, int $tick): void
    {
        $multiverseId = $universe->multiverse_id;
        if (!$multiverseId) return;

        // 1. Identify high-resonance partners
        // Criteria: Same World, status active, and high resonance recorded in interactions
        $partners = UniverseInteraction::where('interaction_type', 'resonance')
            ->where(function($q) use ($universe) {
                $q->where('universe_a_id', $universe->id)
                  ->orWhere('universe_b_id', $universe->id);
            })
            ->where('created_at', '>=', now()->subHours(24))
            ->get()
            ->map(function($interaction) use ($universe) {
                return $interaction->universe_a_id === $universe->id 
                    ? $interaction->universe_b_id 
                    : $interaction->universe_a_id;
            })->unique();

        foreach ($partners as $partnerId) {
            $partner = Universe::find($partnerId);
            if (!$partner || $partner->status !== 'active') continue;

            if ($this->shouldConverge($universe, $partner)) {
                $this->triggerConvergence($universe, $partner, $tick);
            }
        }

        // 2. Check for Omega Point (Global Multiverse Collapse)
        $this->checkOmegaPoint($universe, $tick);
    }

    /**
     * Convergence occurs if two universes have very similar alignments and high IP scores.
     */
    protected function shouldConverge(Universe $a, Universe $b): bool
    {
        // Fetch latest snapshots for metrics
        $snapA = $a->snapshots()->orderByDesc('tick')->first();
        $snapB = $b->snapshots()->orderByDesc('tick')->first();

        if (!$snapA || !$snapB) return false;

        $alignA = ($snapA->metrics ?? [])['alignment'] ?? null;
        $alignB = ($snapB->metrics ?? [])['alignment'] ?? null;

        if (!$alignA || !$alignB || !is_array($alignA) || !is_array($alignB)) return false;

        // Prepare State for DSL
        $vmState = [
            'diff_spirituality' => abs(($alignA['spirituality'] ?? 0) - ($alignB['spirituality'] ?? 0)),
            'diff_hardtech' => abs(($alignA['hardtech'] ?? 0) - ($alignB['hardtech'] ?? 0)),
            'diff_entropy' => abs(($alignA['entropy'] ?? 0) - ($alignB['entropy'] ?? 0)),
            'entropy' => (float) ($a->state_vector['entropy'] ?? 0.5), // required for the same DSL file context
        ];

        $dslPath = 'legend/fate_bifurcation';
        $tempState = \App\Modules\Simulation\Core\Runtime\State\WorldState::fromArray(array_merge(
            ['universe_id' => $a->id],
            $vmState
        ));
        $this->ruleVm->evaluateAndApplyWithDsl($tempState, $dslPath, 0);
        return (bool) $tempState->get('should_merge', false);
    }

    protected function triggerConvergence(Universe $a, Universe $b, int $tick): void
    {
        if (!$this->mergeAction) return;

        // Only trigger if no pending merges exist
        $exists = UniverseInteraction::where('interaction_type', 'convergence_initiated')
            ->where(function($q) use ($a, $b) {
                $q->where('universe_a_id', $a->id)->where('universe_b_id', $b->id);
            })->exists();

        if ($exists) return;

        UniverseInteraction::create([
            'universe_a_id' => $a->id,
            'universe_b_id' => $b->id,
            'interaction_type' => 'convergence_initiated',
            'payload' => ['tick' => $tick]
        ]);

        Chronicle::create([
            'universe_id' => $a->id,
            'from_tick' => $tick,
            'to_tick' => $tick,
            'type' => 'convergence_event',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => "SỰ HỘI TỤ ĐA VŨ TRỤ: Ranh giới giữa thế giới này và {$b->id} đang sụp đổ. Hai dòng thời gian bắt đầu giao thoa để tìm về một thực tại thống nhất."
            ],
        ]);

        // Execute merge (this will create a new Prime Universe)
        $this->mergeAction->execute($a, $b, $tick);
    }

    protected function checkOmegaPoint(Universe $universe, int $tick): void
    {
        $minTicksBeforeArchive = (int) config('worldos.autonomic.min_ticks_before_archive', 150);
        if ($tick < $minTicksBeforeArchive) {
            return;
        }

        $forkGracePeriod = (int) config('worldos.autonomic.fork_grace_period_ticks', 50);
        if ($universe->forked_at_tick !== null && ($tick - (int) $universe->forked_at_tick) < $forkGracePeriod) {
            return;
        }

        $vec = $universe->state_vector ?? [];
        $entropy = (float)($vec['entropy'] ?? 0.0);

        // Evaluate DSL for Omega Point
        $vmState = ['entropy' => $entropy];
        $dslPath = 'legend/fate_bifurcation';
        $tempState = \App\Modules\Simulation\Core\Runtime\State\WorldState::fromArray(array_merge(
            ['universe_id' => $universe->id],
            $vmState
        ));
        $this->ruleVm->evaluateAndApplyWithDsl($tempState, $dslPath, $tick);
        $isOmega = (bool) $tempState->get('is_omega_point', false);

        if ($isOmega) {
            Chronicle::create([
                'universe_id' => $universe->id,
                'from_tick' => $tick,
                'to_tick' => $tick,
                'type' => 'omega_point',
                'raw_payload' => [
                    'action' => 'legacy_event',
                    'description' => "ĐIỂM OMEGA: Vũ trụ đã chạm tới giới hạn tuyệt đối của sự tồn tại. Mọi cấu trúc vật chất và ý thức dần tan biến vào Hư vô vĩnh hằng."
                ],
            ]);
            $universe->update(['status' => 'archived']);
        }
    }
}






