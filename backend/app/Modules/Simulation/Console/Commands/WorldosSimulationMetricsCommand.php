<?php

namespace App\Modules\Simulation\Console\Commands;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Services\Core\SimulationMetricsLogger;
use Illuminate\Console\Command;

/**
 * Doc 21 §4c: Simulation observability metrics (mean/variance pressure, collapse rate, population variance, innovation).
 * Use to detect stagnation (variance_pressure → 0) or collapse (variance → 1).
 */
class WorldosSimulationMetricsCommand extends Command
{
    protected $signature = 'worldos:simulation-metrics
                            {universe? : Universe ID}
                            {--from-tick= : From tick (with --to-tick for range)}
                            {--to-tick= : To tick (with --from-tick for range)}
                            {--every=1 : Sample every N ticks when using range}
                            {--json : Output JSON}
                            {--latest : Only latest snapshot for each universe}';

    protected $description = 'Doc 21 §4c: Report simulation metrics (mean_pressure, variance_pressure, collapse_rate, population_variance, innovation_proxy)';

    public function handle(SimulationMetricsLogger $logger): int
    {
        $universeId = $this->argument('universe');
        $fromTick = $this->option('from-tick') !== null ? (int) $this->option('from-tick') : null;
        $toTick = $this->option('to-tick') !== null ? (int) $this->option('to-tick') : null;
        $every = max(1, (int) $this->option('every'));
        $asJson = (bool) $this->option('json');
        $latestOnly = (bool) $this->option('latest');

        $query = Universe::query();
        if ($universeId !== null && $universeId !== '') {
            $query->where('id', (int) $universeId);
        } else {
            $query->whereIn('status', ['active', 'running']);
        }
        $universes = $query->get();

        if ($universes->isEmpty()) {
            $this->warn('No universe(s) found.');
            return 1;
        }

        $all = [];
        foreach ($universes as $universe) {
            if ($fromTick !== null && $toTick !== null) {
                $range = $logger->fromSnapshotRange($universe->id, $fromTick, $toTick, $every);
                $all[(int) $universe->id] = array_values($range);
            } elseif ($latestOnly) {
                $snap = UniverseSnapshot::where('universe_id', $universe->id)->orderByDesc('tick')->first();
                if ($snap) {
                    $m = $logger->fromSnapshot($snap);
                    $all[(int) $universe->id] = [array_merge(['tick' => (int) $snap->tick], $m)];
                } else {
                    $all[(int) $universe->id] = [];
                }
            } else {
                $snap = UniverseSnapshot::where('universe_id', $universe->id)->orderByDesc('tick')->first();
                if ($snap) {
                    $m = $logger->fromSnapshot($snap);
                    $all[(int) $universe->id] = [array_merge(['tick' => (int) $snap->tick], $m)];
                } else {
                    $all[(int) $universe->id] = [];
                }
            }
        }

        if ($asJson) {
            $this->line(json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return 0;
        }

        foreach ($all as $uid => $rows) {
            $this->info("Universe {$uid}");
            if (empty($rows)) {
                $this->line('  (no snapshots)');
                continue;
            }
            foreach ($rows as $row) {
                $tick = $row['tick'] ?? '-';
                $this->line(sprintf(
                    '  tick=%s mean_pressure=%.4f variance_pressure=%.4f collapse_rate=%.4f population_variance=%.4f innovation_proxy=%.4f zones=%d collapse_count=%d',
                    $tick,
                    $row['mean_pressure'] ?? 0,
                    $row['variance_pressure'] ?? 0,
                    $row['collapse_rate'] ?? 0,
                    $row['population_variance'] ?? 0,
                    $row['innovation_proxy'] ?? 0,
                    $row['zone_count'] ?? 0,
                    $row['collapse_count'] ?? 0
                ));
            }
        }

        return 0;
    }
}


