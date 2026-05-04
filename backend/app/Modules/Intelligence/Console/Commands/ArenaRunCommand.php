<?php

namespace App\Modules\Intelligence\Console\Commands;

use App\Modules\Simulation\Models\ArenaBatch;
use App\Modules\Simulation\Models\CivilizationPolicy;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseDecisionModel;
use App\Modules\Intelligence\Domain\Policy\DecisionModel;
use App\Modules\Intelligence\Domain\Policy\FitnessScore;
use App\Modules\Intelligence\Services\FitnessEvaluator;
use App\Modules\Intelligence\Services\PolicyMutator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * Manages the full Civilizational Evolution Arena.
 * Spawns parallel universe simulations, evaluates fitness, and evolves the next generation.
 *
 * Usage:
 *   php artisan arena:run --universes=10 --ticks=2000 --generation=1 [--policy=uuid]
 */
class ArenaRunCommand extends Command
{
    protected $signature = 'arena:run
        {--universes=10    : Number of parallel universes to simulate}
        {--ticks=2000      : Simulation ticks per universe}
        {--generation=1    : Starting generation number}
        {--policy=         : Seed policy UUID (optional, leaves blank to create new)}';

    protected $description = 'Run a Civilizational Evolution Arena batch with parallel universe simulation';

    public function __construct(
        private readonly FitnessEvaluator $fitnessEvaluator,
        private readonly PolicyMutator    $policyMutator,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $universeCount = (int) $this->option('universes');
        $ticks         = (int) $this->option('ticks');
        $generation    = (int) $this->option('generation');
        $seedPolicyId  = $this->option('policy');

        $this->info("🌌 WorldOS Civilizational Evolution Arena — Generation {$generation}");
        $this->info("   Universes: {$universeCount} | Ticks each: {$ticks}");
        $this->line('');

        // 1. Create Arena Batch
        $batch = ArenaBatch::create([
            'id'                => (string) Str::uuid(),
            'generation'        => $generation,
            'universe_count'    => $universeCount,
            'ticks_per_universe' => $ticks,
            'status'            => 'running',
            'started_at'        => now(),
        ]);

        // 2. Create or load seed policy
        $policy = $seedPolicyId
            ? CivilizationPolicy::findOrFail($seedPolicyId)
            : CivilizationPolicy::create([
                'id'               => (string) Str::uuid(),
                'generation'       => $generation,
                'arena_batch_id'   => $batch->id,
                'survival_priority' => 1.0,
                'stability_priority' => 0.6,
                'diversity_priority' => 0.4,
            ]);

        // 3. Provision universes
        $universeIds = [];
        for ($i = 0; $i < $universeCount; $i++) {
            $universe = Universe::create([
                'name'           => "Arena-Gen{$generation}-U{$i}",
                'policy_id'      => $policy->id,
                'arena_batch_id' => $batch->id,
                'random_seed'    => mt_rand(1, PHP_INT_MAX),
                'arena_status'   => 'pending',
                'state_vector'   => ['entropy' => 0.3, 'stability_index' => 0.7, 'metrics' => []],
            ]);

            // Seed actors for new universe
            $this->seedActors($universe);

            // Create initial decision model from policy defaults
            $dm = DecisionModel::defaultLinear(
                (string) Str::uuid(),
                $policy->id,
                $universe->id,
                $generation
            );
            $this->persistDecisionModel($dm, $universe->id);

            $universeIds[] = $universe->id;
        }

        // 4. Spawn parallel simulation processes
        $this->line("⚡ Spawning {$universeCount} simulation processes...");
        $processes = [];
        foreach ($universeIds as $uid) {
            $proc = new Process([
                PHP_BINARY,
                base_path('artisan'),
                'arena:simulate',
                "--universe={$uid}",
                "--ticks={$ticks}",
            ]);
            $proc->start();
            $processes[$uid] = $proc;
        }

        // 5. Monitor and wait
        $this->withProgressBar($universeIds, function ($uid) use ($processes) {
            $processes[$uid]->wait();
        });
        $this->newLine(2);

        // 6. Update batch status
        $batch->update(['status' => 'evaluating']);

        // 7. Evaluate fitness for all completed universes
        $this->line('📊 Evaluating fitness...');
        $fitnesses = [];
        foreach ($universeIds as $uid) {
            $universe = Universe::find($uid);
            if (!$universe || $universe->arena_status !== 'completed') {
                $this->warn("  Universe {$uid} did not complete.");
                continue;
            }

            $score = $this->fitnessEvaluator->compute($universe);
            $universe->update([/* stored in fitness_snapshots by arena:simulate */]);
            $fitnesses[$uid] = $score->total();

            $this->line(sprintf(
                '  🌍 %s → fitness: %.4f (survival=%.2f, stability=%.2f, diversity=%.2f)',
                substr($uid, 0, 8),
                $score->total(),
                $score->survivalScore,
                $score->stabilityScore,
                $score->diversityScore,
            ));
        }

        // 8. Select elite (top 30%)
        arsort($fitnesses);
        $eliteCount   = max(1, (int) ceil($universeCount * 0.3));
        $eliteUids    = array_slice(array_keys($fitnesses), 0, $eliteCount);
        $bestFitness  = current($fitnesses);

        $policy->update(['fitness_score' => $bestFitness]);
        $batch->update(['status' => 'completed', 'completed_at' => now()]);

        $this->info("✅ Arena complete. Best fitness: {$bestFitness}");
        $this->line("   Elite universes: " . implode(', ', array_map(fn($u) => substr($u, 0, 8), $eliteUids)));

        return Command::SUCCESS;
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function seedActors(Universe $universe): void
    {
        // Seed a minimal set of actors using the existing ActorSeeder logic
        \Illuminate\Support\Facades\Artisan::call('db:seed', [
            '--class' => 'ActorSeeder',
            '--force' => true,
        ]);
    }

    private function persistDecisionModel(DecisionModel $dm, int $universeId): void
    {
        UniverseDecisionModel::create([
            'id'                => $dm->id,
            'universe_id'       => $universeId,
            'policy_id'         => $dm->policyId,
            'model_type'        => $dm->modelType->value,
            'weight_vector'     => $dm->weightVector,
            'interaction_matrix' => $dm->interactionMatrix,
            'threshold_vector'  => $dm->thresholdVector,
            'context_weights'   => $dm->contextWeights,
            'generation'        => $dm->generation,
        ]);
    }
}

