<?php

namespace App\Modules\Simulation\Jobs;

use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Services\UniverseHistoryGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Generate "Complete History of Universe #X" and save to universe_histories. Offline / on-demand.
 */
class GenerateUniverseHistoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(
        public readonly int $universeId,
        public readonly ?int $fromTick = null,
        public readonly ?int $toTick = null
    ) {}

    public function handle(UniverseHistoryGenerator $generator): void
    {
        $universe = Universe::find($this->universeId);
        if (!$universe) {
            Log::warning("GenerateUniverseHistoryJob: Universe #{$this->universeId} not found.");
            return;
        }
        $generator->generate($universe, $this->fromTick, $this->toTick);
    }
}

