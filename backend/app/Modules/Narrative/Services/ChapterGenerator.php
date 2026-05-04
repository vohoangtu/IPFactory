<?php

namespace App\Modules\Narrative\Services;

use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Narrative\Models\NarrativeSeries;
use App\Modules\Narrative\Models\SerialChapter;
use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\Log;

class ChapterGenerator
{
    public function __construct(
        private \App\Modules\Intelligence\Services\AI\AiGateway $aiGateway
    ) {}

    /**
     * Generate a new chapter for an active series in a universe.
     */
    public function generateForUniverse(Universe $universe, ?int $seriesId = null): ?SerialChapter
    {
        $series = $seriesId ? NarrativeSeries::find($seriesId) : $this->getActiveSeries($universe);
        if (!$series) {
            $series = $this->createNewSeries($universe);
        }

        // 1. Get recent woven chronicles that aren't in a chapter yet
        $lastChapter = $series->chapters()->orderByDesc('chapter_number')->first();
        $startTick = $lastChapter ? $lastChapter->end_tick + 1 : 0;
        $endTick = $universe->current_tick;

        $chronicles = Chronicle::where('universe_id', $universe->id)
            ->where('tick', '>=', $startTick)
            ->where('tick', '<=', $endTick)
            ->whereNotNull('content') // Must be woven
            ->orderBy('tick')
            ->get();

        if ($chronicles->count() < 3) {
            Log::info("ChapterGenerator: Not enough woven chronicles to form a chapter for Universe #{$universe->id} (count: {$chronicles->count()})");
            return null;
        }

        // 2. Synthesize chapter content using AI
        $chapterData = $this->synthesizeChapter($universe, $series, $chronicles);
        if (!$chapterData) {
            return null;
        }

        // 3. Create SerialChapter
        $chapterNumber = ($lastChapter?->chapter_number ?? 0) + 1;
        
        return SerialChapter::create([
            'narrative_series_id' => $series->id,
            'chapter_number' => $chapterNumber,
            'title' => $chapterData['title'] ?? "Chương {$chapterNumber}: " . ($universe->name ?? 'Vô Danh'),
            'summary' => $chapterData['summary'] ?? 'Một chương mới trong biên niên sử.',
            'content' => $chapterData['full_text'] ?? '',
            'start_tick' => $chronicles->first()->tick,
            'end_tick' => $chronicles->last()->tick,
            'metadata' => [
                'chronicle_ids' => $chronicles->pluck('id')->toArray(),
                'theme' => $chapterData['theme'] ?? 'general',
            ],
        ]);
    }

    private function getActiveSeries(Universe $universe): ?NarrativeSeries
    {
        return NarrativeSeries::where('universe_id', $universe->id)
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->first();
    }

    private function createNewSeries(Universe $universe): NarrativeSeries
    {
        return NarrativeSeries::create([
            'universe_id' => $universe->id,
            'title' => "Biên Niên Sử: " . ($universe->name ?? "Vũ Trụ {$universe->id}"),
            'description' => "Dòng thời gian chính thức được tổng hợp từ các biến số thực tại.",
            'status' => 'active',
            'metadata' => ['type' => 'main_chronicle'],
        ]);
    }

    private function synthesizeChapter(Universe $universe, NarrativeSeries $series, $chronicles): ?array
    {
        $eventsText = "";
        foreach ($chronicles as $c) {
            $eventsText .= "- Tick {$c->tick} [{$c->type}]: {$c->content}\n";
        }

        $prompt = "Bạn là Người Dệt Chuyện (The Weaver) của WorldOS.\n"
            . "Dưới đây là danh sách các sự kiện (chronicles) thô vừa xảy ra trong Vũ trụ '{$universe->name}':\n\n"
            . $eventsText . "\n"
            . "Hãy thực hiện các bước sau:\n"
            . "1. Đặt một tiêu đề chương (title) ngắn gọn, gợi cảm và mang tính sử thi.\n"
            . "2. Viết một tóm tắt (summary) ngắn gọn (2-3 câu) về diễn biến chính.\n"
            . "3. Viết một đoạn văn bản chương (full_text) hoàn chỉnh (khoảng 300-500 chữ), kết nối các sự kiện trên thành một mạch truyện mượt mà. Giữ nguyên các mốc thời gian (Tick) nếu cần thiết.\n"
            . "4. Xác định chủ đề chính (theme) của chương này.\n\n"
            . "Định dạng kết quả trả về duy nhất JSON với các key: title, summary, full_text, theme. Ngôn ngữ: Tiếng Việt.";

        $response = $this->aiGateway->feature('narrative')->chat([
            ['role' => 'system', 'content' => 'You are the Master Weaver of WorldOS. Return only valid JSON.'],
            ['role' => 'user', 'content' => $prompt],
        ], [
            'temperature' => 0.7,
            'json' => true,
            'timeout' => 150
        ]);

        if (!$response) return null;

        return json_decode($response, true);
    }
}
