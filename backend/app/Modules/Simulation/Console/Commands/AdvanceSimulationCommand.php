<?php

namespace App\Modules\Simulation\Console\Commands;

use App\Modules\Simulation\Services\Core\ImplicitOrchestratorService;
use Illuminate\Console\Command;
use App\Modules\World\Models\World;

class AdvanceSimulationCommand extends Command
{
    protected $signature = 'simulation:advance-v3
                            {--ticks=5 : Ticks per universe}
                            {--world= : World ID (optional, runs first autonomic world if not set)}';

    protected $description = 'Advance world universe(s) by N ticks (WorldOS V6)';

    public function handle(ImplicitOrchestratorService $orchestrator): int
    {
        $ticks = (int) $this->option('ticks');
        $worldId = (int) $this->option('world');

        $world = $worldId 
            ? World::find($worldId) 
            : World::where('is_autonomic', true)->first();

        if (!$world) {
            $this->error("World not found or no autonomic worlds available.");
            return 1;
        }

        $this->info("Running batch for world {$world->id} ({$world->name}), ticks={$ticks}");
        
        $results = $orchestrator->runBatch($world, $ticks);
        
        $rows = [];
        foreach ($results as $universeId => $r) {
            $snap = $r['snapshot'] ?? [];
            $rows[] = [
                $universeId,
                ($r['ok'] ?? false) ? 'yes' : 'no',
                $snap['tick'] ?? '-',
            ];
        }
        $this->table(['Universe ID', 'OK', 'Tick'], $rows);
        return 0;
    }
}
