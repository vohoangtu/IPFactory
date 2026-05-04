<?php

namespace App\Modules\Narrative\Console\Commands;

use App\Modules\Simulation\Jobs\GenerateUniverseHistoryJob;
use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Services\UniverseHistoryGenerator;
use Illuminate\Console\Command;

class NarrativeHistoryBookCommand extends Command
{
    protected $signature = 'narrative:history-book
                            {universe_id : Universe ID}
                            {--from= : Start tick (optional)}
                            {--to= : End tick (optional)}
                            {--queue : Dispatch job to queue instead of running synchronously}';

    protected $description = 'Generate Complete History of Universe (chronicles + eras + civilizations + religions + legends) and save to universe_histories. Offline/on-demand.';

    public function handle(UniverseHistoryGenerator $generator): int
    {
        $universeId = (int) $this->argument('universe_id');
        $universe = Universe::find($universeId);
        if (!$universe) {
            $this->error("Universe #{$universeId} not found.");
            return 1;
        }

        $fromTick = $this->option('from') !== null ? (int) $this->option('from') : null;
        $toTick = $this->option('to') !== null ? (int) $this->option('to') : null;

        if ($this->option('queue')) {
            GenerateUniverseHistoryJob::dispatch($universeId, $fromTick, $toTick);
            $this->info("Dispatched GenerateUniverseHistoryJob for universe #{$universeId}.");
            return 0;
        }

        $history = $generator->generate($universe, $fromTick, $toTick);
        if ($history) {
            $this->info("Generated universe history #{$history->id} (" . strlen($history->full_text) . " chars).");
            return 0;
        }

        $this->warn("No history generated (no context or LLM unavailable).");
        return 0;
    }
}


