<?php

namespace App\Modules\Narrative\Services;

use App\Modules\Narrative\Models\SerialChapter;
use App\Modules\Narrative\Models\NarrativeSeries;
use App\Modules\Narrative\Models\StoryBible;
use App\Modules\Intelligence\Services\AI\AnalyticalAiService;
use Illuminate\Support\Facades\Log;

class StoryBibleService
{
    public function __construct(
        protected AnalyticalAiService $ai
    ) {}

    /**
     * Cập nhật StoryBible từ nội dung chapter đã canonize.
     * Dùng LLM để extract nhân vật và địa điểm.
     */
    public function updateFromChapter(SerialChapter $chapter, NarrativeSeries $series): void
    {
        $bible = $series->bible;
        if (!$bible) {
            $bible = StoryBible::create(['series_id' => $series->id]);
        }

        // Gọi LLM để extract nhân vật từ chapter
        $extractedData = $this->extractEntitiesViaLlm($chapter->content, $series->genre_key);

        if ($extractedData) {
            // Upsert characters
            foreach ($extractedData['characters'] ?? [] as $char) {
                if (!empty($char['name'])) {
                    $char['first_appearance_tick'] = $char['first_appearance_tick'] ?? $chapter->tick_start;
                    $bible->upsertCharacter($char);
                }
            }

            // Upsert locations
            $locations = collect($bible->locations ?? []);
            foreach ($extractedData['locations'] ?? [] as $loc) {
                if (empty($loc['name'])) continue;
                $exists = $locations->contains(fn($l) => strcasecmp($l['name'] ?? '', $loc['name']) === 0);
                if (!$exists) {
                    $locations->push(array_merge($loc, ['first_appearance_tick' => $chapter->tick_start]));
                }
            }
            $bible->locations = $locations->values()->toArray();
            $bible->save();

            Log::info("StoryBible: Cập nhật cho Series #{$series->id} từ Chapter #{$chapter->id}");
        }
    }

    /**
     * Dùng AI để extract nhân vật và địa điểm từ chapter content.
     */
    protected function extractEntitiesViaLlm(string $content, string $genreKey): ?array
    {
        $snippet = mb_substr($content, 0, 1000);
        $prompt = "Đọc đoạn truyện sau (thể loại: {$genreKey}) và trích xuất:\n"
            . "1. Danh sách NHÂN VẬT (mỗi nhân vật cần: name, archetype, description ngắn 1 câu)\n"
            . "2. Danh sách ĐỊA ĐIỂM (mỗi địa điểm: name, description ngắn)\n"
            . "Trả về JSON format: {\"characters\": [...], \"locations\": [...]}\n\n"
            . "Đoạn truyện:\n{$snippet}";

        return $this->ai->generateStructuredProposal($prompt);
    }
}

