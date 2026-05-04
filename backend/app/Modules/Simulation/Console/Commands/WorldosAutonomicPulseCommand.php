<?php

namespace App\Modules\Simulation\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Simulation\Services\Society\AutonomicWorkerService;

class WorldosAutonomicPulseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worldos:autonomic-pulse {--ticks=1 : Number of ticks per universe} {--prune : Prune weak universes before pulsing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch simulation advance jobs for all autonomic worlds (Pruning & Branching integrated)';

    /**
     * Execute the console command.
     */
    public function handle(AutonomicWorkerService $service): int
    {
        $ticks = (int) $this->option('ticks');
        $shouldPrune = (bool) $this->option('prune');

        $this->info("Initiating modular autonomic pulse queueing...");
        
        $dispatched = $service->pulseAllAutonomicWorlds($ticks, $shouldPrune);
        
        $this->info("Success: Dispatched $dispatched simulation jobs to the queue.");
        return 0;
    }
}
