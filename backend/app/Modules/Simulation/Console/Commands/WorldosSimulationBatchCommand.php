<?php

namespace App\Modules\Simulation\Console\Commands;

use App\Modules\Simulation\Actions\AdvanceSimulationAction;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Services\Core\SimulationMetricsLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Deep Sim Phase 2.1: Run advance for N ticks, log metrics every M ticks to file (batch run for calibration).
 */
class WorldosSimulationBatchCommand extends Command
{
    protected $signature = 'worldos:simulation-batch
                            {universe : Universe ID}
                            {--ticks=10000 : Total ticks to advance}
                            {--chunk=100 : Advance this many ticks per step}
                            {--log-every=100 : Log metrics every N ticks (after each chunk that reaches this)}
                            {--fast : Skip heavy event listeners for faster batch processing}
                            {--output= : Path to JSON or CSV output file}';

    protected $description = 'Deep Sim Phase 2: Batch advance and log metrics for calibration (variance, healthy band)';

    /**
     * Heavy listeners to disable in --fast mode.
     * These are synchronous listeners that significantly slow down batch processing.
     */
    private const FAST_MODE_DISABLED_LISTENERS = [
        \App\Modules\Simulation\Listeners\SynchronizeNarrativeHistory::class,
        \App\Modules\SocialGraph\Listeners\SyncToGraph::class,
        \App\Modules\Simulation\Listeners\PublishSimulationAdvancedToKafka::class,
        \App\Modules\Simulation\Listeners\ProcessStrategicTopology::class,
        \App\Modules\Simulation\Listeners\StagnationDetectorListener::class,
        \App\Modules\Simulation\Listeners\RecordMaterialIdentityTransition::class,
        \App\Modules\Simulation\Listeners\EvaluateEvolutionaryLeaps::class,
        \App\Modules\Institutions\Listeners\ProcessInstitutionalFramework::class,
    ];

    public function handle(AdvanceSimulationAction $advance, SimulationMetricsLogger $logger): int
    {
        $universeId = (int) $this->argument('universe');
        $totalTicks = max(1, (int) $this->option('ticks'));
        $chunk = max(1, (int) $this->option('chunk'));
        $logEvery = max(1, (int) $this->option('log-every'));
        $outputPath = $this->option('output');
        $fastMode = (bool) $this->option('fast');

        $universe = Universe::find($universeId);
        if (! $universe) {
            $this->error("Universe {$universeId} not found.");
            return 1;
        }

        if ($universe->status === 'archived' || $universe->status === 'halted') {
            $this->warn("Universe {$universeId} status is {$universe->status}. Advance may be skipped.");
        }

        if (empty($outputPath)) {
            $outputPath = storage_path('logs/simulation_metrics_' . $universeId . '_' . now()->format('Y-m-d_His') . '.json');
        }
        $isJson = str_ends_with(strtolower($outputPath), '.json');
        $rows = [];

        // Fast mode: disable heavy synchronous listeners
        if ($fastMode) {
            $this->warn('Fast mode enabled: disabling all event listeners for maximum batch speed.');
            $dispatcher = app(\Illuminate\Contracts\Events\Dispatcher::class);
            $dispatcher->forget(\App\Modules\Simulation\Events\UniverseSimulationPulsed::class);
            // Only keep UpdateCivilizationMetrics for essential state tracking
            $dispatcher->listen(
                \App\Modules\Simulation\Events\UniverseSimulationPulsed::class,
                \App\Modules\Simulation\Listeners\UpdateCivilizationMetrics::class
            );
        }

        $this->info("Batch: universe={$universeId} total_ticks={$totalTicks} chunk={$chunk} log_every={$logEvery}" . ($fastMode ? ' [FAST]' : '') . " output={$outputPath}");

        $remaining = $totalTicks;
        $lastLoggedTick = null;
        $startTime = microtime(true);

        while ($remaining > 0) {
            $toRun = min($chunk, $remaining);
            $result = $advance->execute($universeId, $toRun);

            if (! ($result['ok'] ?? false)) {
                $this->error('Advance failed: ' . ($result['error_message'] ?? 'unknown'));
                Log::warning('worldos:simulation-batch advance failed', ['result' => $result]);
                break;
            }

            $universe->refresh();
            $currentTick = (int) $universe->current_tick;

            $snap = UniverseSnapshot::where('universe_id', $universeId)->orderByDesc('tick')->first();
            if ($snap && ($lastLoggedTick === null || $currentTick - $lastLoggedTick >= $logEvery)) {
                $m = $logger->fromSnapshot($snap);
                $elapsed = round((microtime(true) - $startTime), 1);
                $ticksCompleted = $totalTicks - $remaining;
                $ticksPerSec = $ticksCompleted > 0 ? round($ticksCompleted / max(1, $elapsed), 2) : 0;
                $row = array_merge(['tick' => $currentTick], $m);
                $rows[] = $row;
                $lastLoggedTick = $currentTick;
                $this->line(sprintf(
                    '  tick=%d mean_p=%.4f var_p=%.4f collapse_rate=%.4f [%.1fs elapsed, %.2f ticks/s]',
                    $currentTick,
                    $m['mean_pressure'],
                    $m['variance_pressure'],
                    $m['collapse_rate'],
                    $elapsed,
                    $ticksPerSec
                ));
            }

            $remaining -= $toRun;
        }

        $totalElapsed = round((microtime(true) - $startTime), 1);
        $completedTicks = $totalTicks - $remaining;

        if (empty($rows)) {
            $this->warn('No metrics rows collected (no snapshots or log-every too large).');
            $this->info("Completed {$completedTicks}/{$totalTicks} ticks in {$totalElapsed}s");
            return 0;
        }

        $dir = dirname($outputPath);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if ($isJson) {
            file_put_contents($outputPath, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $fp = fopen($outputPath, 'w');
            if ($fp) {
                fputcsv($fp, array_keys($rows[0]));
                foreach ($rows as $r) {
                    fputcsv($fp, $r);
                }
                fclose($fp);
            }
        }

        $this->info("Wrote " . count($rows) . " rows to {$outputPath}");
        $this->info("Completed {$completedTicks}/{$totalTicks} ticks in {$totalElapsed}s (" . round($completedTicks / max(1, $totalElapsed), 2) . " ticks/s)");
        return 0;
    }
}
