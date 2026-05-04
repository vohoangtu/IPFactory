<?php

namespace App\Modules\Simulation\Console\Commands;

use App\Modules\Simulation\Actions\AutonomicPulseAction;
use Illuminate\Console\Command;

class WorldOSRunContinuousCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worldos:run-continuous {--ticks=1 : Ticks per pulse} {--sleep=2 : Seconds to sleep between pulses}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the WorldOS Autonomic Engine in a continuous loop.';

    /**
     * Execute the console command.
     */
    public function handle(AutonomicPulseAction $pulseAction): int
    {
        $ticks = (int) $this->option('ticks');
        $sleep = (int) $this->option('sleep');

        $this->info("Starting WorldOS Continuous Engine...");
        $this->info("- Ticks per pulse: {$ticks}");
        $this->info("- Sleep interval: {$sleep} seconds");
        $this->warn("Press Ctrl+C to stop.");

        while (true) {
            $this->info("\n[" . now()->format('Y-m-d H:i:s') . "] Executing pulse...");
            
            $results = $pulseAction->execute($ticks);
            
            $rows = [];
            foreach ($results as $id => $status) {
                $rows[] = [$id, $status];
            }
            if (!empty($rows)) {
                $this->table(['Universe ID', 'Status'], $rows);
            } else {
                $this->info("No active autonomic universes found. Waiting...");
            }

            sleep($sleep);
        }

        return 0; // Unreachable, but required by PHP signature
    }
}

