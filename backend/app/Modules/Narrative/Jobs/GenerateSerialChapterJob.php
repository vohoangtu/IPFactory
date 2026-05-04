<?php

namespace App\Modules\Narrative\Jobs;

use App\Modules\Narrative\Models\NarrativeSeries;
use App\Modules\Narrative\Services\SerialStoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job sinh chapter tự động cho NarrativeSeries khi được dispatch.
 * Dùng queue để tránh blocking request.
 */
class GenerateSerialChapterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120; // LLM call có thể lâu
    public int $tries = 2;

    public function __construct(
        public readonly int $seriesId
    ) {}

    public function handle(SerialStoryService $service): void
    {
        $series = NarrativeSeries::find($this->seriesId);
        if (!$series) {
            Log::warning("GenerateSerialChapterJob: Series #{$this->seriesId} không tìm thấy.");
            return;
        }

        if ($series->status !== 'active') {
            Log::info("GenerateSerialChapterJob: Series #{$this->seriesId} không active, bỏ qua.");
            return;
        }

        try {
            $chapter = $service->generateNextChapter($series);
            Log::info("GenerateSerialChapterJob: Chapter #{$chapter->chapter_index} đã sinh cho Series #{$this->seriesId}");
        } catch (\Throwable $e) {
            Log::error("GenerateSerialChapterJob: Lỗi sinh chapter cho Series #{$this->seriesId}: " . $e->getMessage());
            throw $e;
        }
    }
}

