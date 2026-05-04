<?php

namespace App\Modules\Simulation\Console\Commands;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Repositories\UniverseSnapshotRepository;
use App\Modules\Simulation\Services\Cosmology\RealityCalibrationService;
use Illuminate\Console\Command;

/**
 * Doc §33: Compare simulation metrics with calibration benchmarks and output suggested adjustments.
 * Does not apply changes; for logging/review only.
 */
class WorldosCalibrationCheckCommand extends Command
{
    protected $signature = 'worldos:calibration-check
                            {--universe= : Universe ID to take latest snapshot metrics from}';

    protected $description = 'Doc §33: Compare simulation metrics to benchmarks and suggest adjustments (no apply)';

    public function handle(
        RealityCalibrationService $calibration,
        UniverseSnapshotRepository $snapshots
    ): int {
        $universeId = $this->option('universe');
        $metrics = [];

        if ($universeId !== null) {
            $universe = Universe::find((int) $universeId);
            if (! $universe) {
                $this->error("Universe {$universeId} not found.");
                return 1;
            }
            $latest = $snapshots->getLatest($universe->id);
            if ($latest) {
                $sv = is_array($latest->state_vector) ? $latest->state_vector : (json_decode($latest->state_vector ?? '{}', true) ?? []);
                $metrics['entropy'] = $latest->entropy ?? ($sv['entropy'] ?? null);
                $metrics['stability_index'] = $latest->stability_index ?? ($sv['stability_index'] ?? null);
                $metrics['tick'] = $latest->tick;
                if (is_array($latest->metrics)) {
                    foreach ($latest->metrics as $k => $v) {
                        if (is_scalar($v)) {
                            $metrics[$k] = $v;
                        }
                    }
                }
            }
        }

        $deltas = $calibration->compareWithBenchmarks($metrics);
        if (empty($deltas)) {
            $this->info('No benchmark keys matched current metrics. Add calibration_benchmarks or run with --universe=<id>.');
            return 0;
        }

        $suggestions = $calibration->suggestAdjustments($deltas);
        $this->table(
            ['key', 'actual', 'target', 'delta', 'suggested_direction', 'suggested_factor'],
            array_map(fn ($s) => [
                $s['key'],
                $s['actual'],
                $s['target'],
                $s['delta'],
                $s['suggested_direction'],
                $s['suggested_factor'],
            ], $suggestions)
        );
        return 0;
    }
}

