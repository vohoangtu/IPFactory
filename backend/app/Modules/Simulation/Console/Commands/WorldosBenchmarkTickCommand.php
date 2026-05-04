<?php

namespace App\Modules\Simulation\Console\Commands;

use App\Modules\Simulation\Actions\AdvanceSimulationAction;
use App\Modules\World\Models\Universe;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Doc §24: Benchmark tick duration — advance N ticks and output tick_duration_ms from Cache.
 */
class WorldosBenchmarkTickCommand extends Command
{
    protected $signature = 'worldos:benchmark-tick
                            {universe : Universe ID}
                            {--ticks=1 : Number of ticks to advance}';

    protected $description = 'Doc §24: Advance universe by N ticks and report tick_duration_ms (from Cache)';

    public function handle(AdvanceSimulationAction $advance): int
    {
        $universeId = (int) $this->argument('universe');
        $ticks = (int) $this->option('ticks');
        $universe = Universe::find($universeId);
        if (! $universe || ! $universe->world) {
            $this->error("Universe {$universeId} not found or has no world.");
            return 1;
        }
        $result = $advance->execute($universeId, $ticks);
        if (! ($result['ok'] ?? false)) {
            $this->error($result['error_message'] ?? 'Advance failed.');
            return 1;
        }
        $key = "worldos.tick_duration_ms.{$universeId}";
        $ms = Cache::get($key);
        if ($ms !== null) {
            $this->line("tick_duration_ms: " . round($ms, 4));
        } else {
            $this->warn('tick_duration_ms not in Cache (advance may not have run or Cache cleared).');
        }
        return 0;
    }
}


