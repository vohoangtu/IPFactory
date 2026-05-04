<?php

namespace App\Modules\Simulation\Console\Commands;

use App\Modules\Simulation\Actions\AutonomicPulseAction;
use Illuminate\Console\Command;

class AutonomicPulseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worldos:pulse {--ticks=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a pulse of the Autonomic Evolution Engine (Linh Cơ)';

    /**
     * Execute the console command.
     */
    public function handle(AutonomicPulseAction $pulseAction): int
    {
        $ticks = (int) $this->option('ticks');
        $this->info("Starting Autonomic Pulse ({$ticks} ticks per universe)...");

        $results = $pulseAction->execute($ticks);

        $rows = [];
        foreach ($results as $id => $status) {
            $rows[] = [$id, $status];
        }

        $this->table(['Universe ID', 'Status'], $rows);
        $this->info('Pulse completed.');

        return 0;
    }
}

