<?php

declare(strict_types=1);

namespace App\Modules\Narrative\Jobs;

use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Narrative\Services\NarrativeEngine;
use App\Modules\Simulation\Contracts\UniverseRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * PulseNarrativeJob — Async wrapper cho NarrativeEngine::pulse().
 *
 * Thay thế việc gọi pulse() đồng bộ trong SimulationTickPipeline,
 * tránh việc LLM call blocking tick loop (mỗi call có thể mất 30s+).
 *
 * Queue: narrative
 */
class PulseNarrativeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Số lần retry tối đa (1 = không retry, tránh duplicate narrative).
     */
    public int $tries = 1;

    /**
     * Timeout 30 phút — đủ cho LLM call chậm nhất.
     */
    public int $timeout = 1800;

    public function __construct(
        private readonly int $universeId,
        private readonly int $snapshotId,
    ) {
        $this->onQueue('narrative');
    }

    public function handle(
        NarrativeEngine $narrativeEngine,
        UniverseRepositoryInterface $universeRepository
    ): void {
        $universeEntity = $universeRepository->findById($this->universeId);

        if (!$universeEntity) {
            Log::warning("[PulseNarrativeJob] Universe #{$this->universeId} not found — skipping pulse.");
            return;
        }

        $snapshot = UniverseSnapshot::find($this->snapshotId);

        if (!$snapshot) {
            Log::warning("[PulseNarrativeJob] Snapshot #{$this->snapshotId} not found — skipping pulse.");
            return;
        }

        Log::debug("[PulseNarrativeJob] Dispatching pulse for Universe #{$this->universeId} tick {$snapshot->tick}");

        $narrativeEngine->pulse($universeEntity, $snapshot);
    }

    /**
     * Xử lý khi Job thất bại sau tất cả các lần retry.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error(
            "[PulseNarrativeJob] Failed for Universe #{$this->universeId} Snapshot #{$this->snapshotId}: " .
            $exception->getMessage()
        );
    }
}
