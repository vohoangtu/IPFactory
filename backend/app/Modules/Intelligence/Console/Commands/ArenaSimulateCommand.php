<?php

namespace App\Modules\Intelligence\Console\Commands;

use App\Modules\Simulation\Models\FitnessSnapshot;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseDecisionModel;
use App\Modules\Intelligence\Domain\Policy\DecisionModel;
use App\Modules\Intelligence\Domain\Policy\ModelType;
use App\Modules\Intelligence\Domain\Policy\UniverseContext;
use App\Modules\Intelligence\Services\AgentAutonomyService;
use App\Modules\Intelligence\Services\FitnessEvaluator;
use App\Modules\Intelligence\Services\PolicyMutator;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Simulates a single universe inside the Arena.
 * Spawned as a child process by ArenaRunCommand — exits after completing N ticks.
 *
 * Usage:
 *   php artisan arena:simulate --universe=uuid --ticks=2000
 */
class ArenaSimulateCommand extends Command
{
    protected $signature = 'arena:simulate
        {--universe=  : UUID of universe to simulate}
        {--ticks=2000 : Number of ticks to run}';

    protected $description = '[Arena internal] Simulate a single universe. Called by arena:run.';

    public function __construct(
        private readonly AgentAutonomyService $autonomy,
        private readonly FitnessEvaluator     $fitness,
        private readonly PolicyMutator        $mutator,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $universeId = $this->option('universe');
        $totalTicks = (int) $this->option('ticks');

        $universe = Universe::find($universeId);
        if (!$universe) {
            $this->error("Universe {$universeId} not found.");
            return Command::FAILURE;
        }

        // Claim the universe (optimistic lock)
        $claimed = Universe::where('id', $universeId)
            ->where('arena_status', 'pending')
            ->update(['arena_status' => 'running']);

        if (!$claimed) {
            $this->warn("Universe {$universeId} already taken.");
            return Command::SUCCESS;
        }

        // Load decision model for this universe
        $dmRow = UniverseDecisionModel::where('universe_id', $universeId)
            ->orderByDesc('generation')
            ->first();

        $decisionModel = $dmRow ? $this->hydrateModel($dmRow, $universe->id) : null;

        // Seed RNG — fully deterministic
        mt_srand($universe->random_seed);

        $this->line("🔵 Simulating universe {$universeId} for {$totalTicks} ticks...");

        for ($tick = 1; $tick <= $totalTicks; $tick++) {
            // Refresh universe state from DB every 50 ticks (batch writes)
            if ($tick % 50 === 1) {
                $universe->refresh();
            }

            $this->autonomy->processWithModel($universe, $tick, $decisionModel);

            // Persist fitness snapshot every 100 ticks
            if ($tick % 100 === 0) {
                $this->recordSnapshot($universe, $tick);
            }
        }

        // Final snapshot
        $this->recordSnapshot($universe, $totalTicks);

        $universe->update(['arena_status' => 'completed']);

        $this->info("✅ Universe {$universeId} completed {$totalTicks} ticks.");

        return Command::SUCCESS;
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function hydrateModel($row, int $universeId): DecisionModel
    {
        return new DecisionModel(
            id:                $row->id,
            policyId:          $row->policy_id,
            universeId:        $universeId,
            modelType:         ModelType::from($row->model_type),
            weightVector:      $row->weight_vector ?? [],
            interactionMatrix: $row->interaction_matrix ?? [],
            thresholdVector:   $row->threshold_vector ?? [],
            contextWeights:    $row->context_weights ?? [],
            generation:        $row->generation,
        );
    }

    private function recordSnapshot(Universe $universe, int $tick): void
    {
        $universe->refresh();
        $score = $this->fitness->compute($universe);

        FitnessSnapshot::create([
            'universe_id'       => $universe->id,
            'arena_batch_id'    => $universe->arena_batch_id,
            'tick'              => $tick,
            'survival_score'    => $score->survivalScore,
            'stability_score'   => $score->stabilityScore,
            'diversity_score'   => $score->diversityScore,
            'complexity_penalty' => $this->mutator->complexityPenalty(
                $this->hydrateModelForUniverse($universe->id)
            ),
            'fitness_total'     => $score->total(),
            'measured_at'       => now(),
        ]);
    }

    private function hydrateModelForUniverse(int $universeId): DecisionModel
    {
        $row = UniverseDecisionModel::where('universe_id', $universeId)
            ->orderByDesc('generation')
            ->first();

        if (!$row) {
            return DecisionModel::defaultLinear(
                (string) Str::uuid(),
                '',
                $universeId,
                1
            );
        }

        return $this->hydrateModel($row, $universeId);
    }
}

