<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Console\Commands;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Actions\AdvanceSimulationAction;
use Illuminate\Console\Command;

/**
 * Stress test command — runs N ticks with progress reporting,
 * memory tracking, and a summary report.
 *
 * Usage:
 *   php artisan worldos:stress-test 1
 *   php artisan worldos:stress-test 1 --ticks=5000 --checkpoint=200
 */
class StressTestCommand extends Command
{
    protected $signature = 'worldos:stress-test
                            {universe : Universe ID}
                            {--ticks=100 : Number of ticks to run}
                            {--checkpoint=100 : Report progress every N ticks}';

    protected $description = 'Run N simulation ticks with progress reporting, memory tracking, and summary';

    public function handle(AdvanceSimulationAction $advance): int
    {
        $universeId = (int) $this->argument('universe');
        $totalTicks = (int) $this->option('ticks');
        $checkpoint = max(1, (int) $this->option('checkpoint'));

        $universe = Universe::find($universeId);
        if (! $universe) {
            $this->error("Universe {$universeId} not found.");

            return 1;
        }

        $this->info("Stress test: Universe {$universeId}, {$totalTicks} ticks, checkpoint every {$checkpoint}");
        $this->newLine();

        $memoryWarningMB = 256;
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        $tickDurations = [];
        $totalEvents = 0;
        $totalSkipped = 0;
        $errors = [];
        $ticksCompleted = 0;

        $bar = $this->output->createProgressBar($totalTicks);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');

        for ($i = 0; $i < $totalTicks; $i++) {
            $tickStart = microtime(true);

            $result = $advance->execute($universeId, 1);

            $tickMs = (microtime(true) - $tickStart) * 1000;
            $tickDurations[] = $tickMs;

            if (! ($result['ok'] ?? false)) {
                $errors[] = [
                    'tick' => $i + 1,
                    'error' => $result['error_message'] ?? 'unknown',
                ];
                $this->newLine();
                $this->error("Tick " . ($i + 1) . " failed: " . ($result['error_message'] ?? 'unknown'));
                break;
            }

            $ticksCompleted++;
            $bar->advance();

            // Checkpoint reporting
            if (($i + 1) % $checkpoint === 0) {
                $peakMB = memory_get_peak_usage(true) / 1048576;
                $avgMs = count($tickDurations) > 0
                    ? array_sum($tickDurations) / count($tickDurations)
                    : 0;

                $this->newLine();
                $this->line(sprintf(
                    '  Checkpoint %d/%d | Avg: %.1fms/tick | Peak memory: %.1fMB',
                    $i + 1,
                    $totalTicks,
                    $avgMs,
                    $peakMB,
                ));

                if ($peakMB > $memoryWarningMB) {
                    $this->warn("  WARNING: Peak memory ({$peakMB}MB) exceeds {$memoryWarningMB}MB threshold");
                }
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $totalTime = microtime(true) - $startTime;
        $peakMemoryMB = memory_get_peak_usage(true) / 1048576;
        $avgMs = count($tickDurations) > 0
            ? array_sum($tickDurations) / count($tickDurations)
            : 0;
        $maxMs = count($tickDurations) > 0
            ? max($tickDurations)
            : 0;
        $minMs = count($tickDurations) > 0
            ? min($tickDurations)
            : 0;

        $this->info('=== Stress Test Summary ===');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Ticks completed', $ticksCompleted . '/' . $totalTicks],
                ['Total time', sprintf('%.2fs', $totalTime)],
                ['Avg tick duration', sprintf('%.2fms', $avgMs)],
                ['Min tick duration', sprintf('%.2fms', $minMs)],
                ['Max tick duration', sprintf('%.2fms', $maxMs)],
                ['Peak memory', sprintf('%.1fMB', $peakMemoryMB)],
                ['Errors', (string) count($errors)],
            ],
        );

        if (! empty($errors)) {
            $this->error('Errors:');
            foreach ($errors as $err) {
                $this->line("  Tick {$err['tick']}: {$err['error']}");
            }

            return 1;
        }

        $this->info('Stress test completed successfully.');

        return 0;
    }
}
