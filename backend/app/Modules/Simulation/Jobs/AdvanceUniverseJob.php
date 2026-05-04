<?php

namespace App\Modules\Simulation\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Modules\Simulation\Actions\AdvanceSimulationAction;

class AdvanceUniverseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $universeId,
        public readonly int $ticks = 1
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AdvanceSimulationAction $action): void
    {
        $action->execute($this->universeId, $this->ticks);
    }
}

