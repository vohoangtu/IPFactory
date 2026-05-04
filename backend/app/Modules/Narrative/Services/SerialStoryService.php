<?php

namespace App\Modules\Narrative\Services;

use App\Modules\Narrative\Models\MythScar;
use App\Modules\Narrative\Models\NarrativeSeries;
use App\Modules\Narrative\Models\SerialChapter;
use App\Modules\Narrative\Models\StoryBible;
use Illuminate\Support\Facades\Log;

class SerialStoryService
{
    public function __construct(
        protected NarrativeAiService $narrativeAi,
        protected StoryBibleService $storyBibleService,
        protected NarrativeLoomService $narrativeLoomService
    ) {}

    public function createSeries(array $data): NarrativeSeries
    {
        $series = NarrativeSeries::create([
            'universe_id' => $data['universe_id'],
            'saga_id' => $data['saga_id'] ?? null,
            'title' => $data['title'],
            'genre_key' => $data['genre_key'] ?? 'wuxia',
            'status' => 'active',
            'config' => $data['config'] ?? [],
        ]);

        StoryBible::create(['series_id' => $series->id]);

        Log::info("IP Factory: Created Series #{$series->id} - '{$series->title}'");

        return $series->load(['universe', 'bible']);
    }

    public function generateNextChapter(NarrativeSeries $series): SerialChapter
    {
        $universe = $series->universe;
        if (!$universe) {
            throw new \RuntimeException("Series #{$series->id} has no Universe.");
        }

        $latestSnapshot = $universe->snapshots()->orderByDesc('tick')->first();
        $toTick = $latestSnapshot?->tick ?? 0;

        $lastChapter = $series->chapters()->orderByDesc('chapter_index')->first();
        $fromTick = $lastChapter ? ($lastChapter->tick_end ?? 0) + 1 : 0;
        $chapterIndex = $lastChapter ? $lastChapter->chapter_index + 1 : 1;

        if ($toTick < $fromTick) {
            throw new \RuntimeException('Not enough new ticks to generate the next chapter.');
        }

        Log::info("IP Factory: Triggering NarrativeLoom for Series #{$series->id} (Ticks: {$fromTick}-{$toTick})");

        try {
            $result = $this->narrativeLoomService->weave((int) $universe->world_id, $fromTick, $toTick);

            if (($result['ok'] ?? true) === false || isset($result['error'])) {
                throw new \RuntimeException("NarrativeLoom failed: " . ($result['error'] ?? 'unknown error'));
            }

            $content = $result['final_prose'] ?? 'Khong the tong hop noi dung chuong truyen.';

            $chronicle = \App\Modules\Narrative\Models\Chronicle::create([
                'universe_id' => $universe->id,
                'from_tick' => $fromTick,
                'to_tick' => $toTick,
                'content' => $content,
                'raw_payload' => json_encode(['source' => 'narrative_loom', 'loom_result' => $result]),
            ]);

            \App\Modules\Narrative\Models\Narrative::create([
                'universe_id' => $universe->id,
                'tick_born' => $fromTick,
                'story' => $content,
                'virality' => 1.0,
                'distortion' => 0.1,
                'is_active' => true,
                'news_headline' => $result['news_headline'] ?? 'Chapter Update: ' . ($series->title ?? 'New Saga'),
                'news_slogan' => $result['news_slogan'] ?? 'A new era blooms...',
                'vfx_config' => $result['vfx_config'] ?? [],
                'tags' => ['series_chapter', 'multiverse_broadcast'],
            ]);
        } catch (\Throwable $e) {
            Log::error('IP Factory: NarrativeLoom Error: ' . $e->getMessage());

            $chronicle = $this->narrativeAi->generateChronicle(
                $universe->id,
                $fromTick,
                $toTick,
                'serial_chapter'
            );
            $content = $chronicle?->content ?? 'Khong co noi dung.';
        }

        $chapterTitle = $this->generateChapterTitle($series, $chapterIndex, $content);

        $chapter = \App\Modules\Narrative\Models\SerialChapter::create([
            'series_id' => $series->id,
            'chronicle_id' => $chronicle?->id,
            'book_index' => $series->current_book_index,
            'chapter_index' => $chapterIndex,
            'title' => $chapterTitle,
            'content' => $content,
            'tick_start' => $fromTick,
            'tick_end' => $toTick,
            'needs_review' => true,
        ]);

        $series->increment('total_chapters_generated');

        Log::info("IP Factory: Chapter #{$chapterIndex} generated for Series #{$series->id}");

        return $chapter;
    }

    public function canonizeChapter(SerialChapter $chapter): SerialChapter
    {
        if ($chapter->isCanonized()) {
            throw new \RuntimeException("Chapter #{$chapter->id} is already canonized.");
        }

        $chapter->update([
            'canonized_at' => now(),
            'needs_review' => false,
        ]);

        $series = $chapter->series;

        try {
            $series->bible ?? StoryBible::create(['series_id' => $series->id]);
            $this->storyBibleService->updateFromChapter($chapter, $series);
        } catch (\Throwable $e) {
            Log::warning("IP Factory: StoryBible update failed for Chapter #{$chapter->id}: " . $e->getMessage());
        }

        try {
            $latestSnapshot = $series->universe->snapshots()->orderByDesc('tick')->first();
            $zones = ($latestSnapshot?->state_vector ?? [])['zones'] ?? [];
            $zoneId = is_array($zones) && isset($zones[0]['id']) ? (string) $zones[0]['id'] : 'root';
            MythScar::create([
                'universe_id' => $series->universe_id,
                'zone_id' => $zoneId,
                'name' => "Canon: {$chapter->title}",
                'description' => "Chapter Canonized - Book {$chapter->book_index}, Ch {$chapter->chapter_index}. Ticks: {$chapter->tick_start}-{$chapter->tick_end}",
                'severity' => 0.3,
                'decay_rate' => 0.01,
                'created_at_tick' => $latestSnapshot?->tick ?? $chapter->tick_end ?? 0,
            ]);
        } catch (\Throwable $e) {
            Log::warning('IP Factory: MythScar creation failed: ' . $e->getMessage());
        }

        Log::info("IP Factory: Chapter #{$chapter->id} canonized.");

        return $chapter->fresh();
    }

    protected function generateChapterTitle(NarrativeSeries $series, int $index, string $content): string
    {
        $words = array_slice(explode(' ', strip_tags($content)), 0, 8);
        $snippet = implode(' ', $words);
        return "Chuong {$index}: " . rtrim($snippet, '.,;:') . '...';
    }
}
