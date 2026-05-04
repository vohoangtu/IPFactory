<?php

namespace App\Modules\Simulation\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\World\Models\Multiverse;
use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Actions\AdvanceSimulationAction;
use App\Modules\Simulation\Services\Core\ImplicitOrchestratorService;
use App\Modules\Narrative\Services\NarrativeAiService;

class RunDemoScenario extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worldos:demo-scenario';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs a standard demo scenario: Genesis -> Stability -> Crisis -> Fork';

    /**
     * Execute the console command.
     */
    public function handle(AdvanceSimulationAction $action, ImplicitOrchestratorService $orchestrator, NarrativeAiService $narrative, \App\Modules\Narrative\Actions\InjectCrisisAction $crisisAction)
    {
        $this->info("--- Starting WorldOS V6 Demo Scenario ---");

        // Ensure Multiverse exists
        $multiverse = Multiverse::firstOrCreate(
            ['slug' => 'default'],
            ['name' => 'Default Multiverse']
        );

        // 1. Setup World
        $this->info("[1/4] Genesis: Creating World...");
        $world = World::updateOrCreate(
            ['slug' => 'demo-world'],
            [
                'name' => 'Demo World', 
                'multiverse_id' => $multiverse->id,
                'global_tick' => 0,
                'axiom' => ['meta_edicts' => []],
                'world_seed' => ['seed' => 12345],
                'origin' => 'generic',
                'current_genre' => 'fantasy',
                'base_genre' => 'fantasy',
                'active_genre_weights' => [],
                'is_autonomic' => false,
                'is_chaotic' => false,
                'snapshot_interval' => 10,
            ]
        );

        // Initialize Universe (Remove Saga, fix parameters)
        $universe = $orchestrator->spawnUniverse($world, null, null);
        $this->info("      Created Universe ID: {$universe->id}");

        // 2. Stable Era
        $this->info("[2/4] The Golden Age: Running 10 stable ticks...");
        $bar = $this->output->createProgressBar(10);
        $bar->start();
        for ($i = 0; $i < 10; $i++) {
            $action->execute($universe->id, 1);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        // 3. The Crisis
        $this->info("[3/4] The Crisis: Injecting High Entropy...");
        $universe->refresh();
        $injectedCount = $crisisAction->execute($universe, 0.85, 0.95);
        
        $this->info("      Entropy set to 0.85 (Global) / 0.95 (Zones). Affected {$injectedCount} zones.");
        $this->info("      System destabilized.");

        $this->info("      Running 5 ticks to trigger Decision Engine...");
        for ($i = 0; $i < 5; $i++) {
            $res = $action->execute($universe->id, 1);
            $snap = $res['snapshot'] ?? [];
            $e = $snap['entropy'] ?? 'N/A';
            $this->line("      Tick +1 | Entropy: {$e}");
        }

        // 4. The Fork
        $this->info("[4/4] The Aftermath: Checking for Forks...");
        $forks = \App\Modules\World\Models\Universe::where('parent_universe_id', $universe->id)->get();
        
        if ($forks->count() > 0) {
            $this->info("SUCCESS: System forked! Created " . $forks->count() . " new universe(s).");
            foreach ($forks as $fork) {
                $this->line("      - Fork ID: {$fork->id} (Parent: {$universe->id})");
            }
        } else {
            $this->warn("WARNING: System did not fork. Check DecisionEngine logic.");
        }

        // [5/5] Narrative Generation
        $this->info("[5/5] Narrative: Generating Chronicle for the Crisis...");
        // Generate for the last 5 ticks (crisis period)
        $chronicle = $narrative->generateChronicle($universe->id, 11, 15, 'chronicle');
        if ($chronicle) {
            $this->info("Chronicle: " . $chronicle->content);
        } else {
            $this->warn("Could not generate chronicle.");
        }

        $this->info("--- Demo Scenario Complete ---");
        $this->info("Visit the Dashboard to see the Multiverse Graph and Chronicles.");
    }
}


