<?php

namespace App\Modules\Narrative\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Narrative\Models\NarrativeSeries;
use App\Modules\Narrative\Models\SerialChapter;
use Illuminate\Http\JsonResponse;

class PublicSeriesController extends Controller
{
    /**
     * GET /api/public/series/{slug}
     * Thông tin series đã published (không cần auth).
     */
    public function show(string $slug): JsonResponse
    {
        $series = NarrativeSeries::published()
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'id' => $series->id,
            'title' => $series->title,
            'slug' => $series->slug,
            'description' => $series->description,
            'genre_key' => $series->genre_key,
            'published_at' => $series->published_at?->toIso8601String(),
        ]);
    }

    /**
     * GET /api/public/series/{slug}/chapters
     * Danh sách chapters đã canonized (không trả content).
     */
    public function chapters(string $slug): JsonResponse
    {
        $series = NarrativeSeries::published()
            ->where('slug', $slug)
            ->firstOrFail();

        $chapters = $series->chapters()
            ->whereNotNull('canonized_at')
            ->orderBy('book_index')
            ->orderBy('chapter_index')
            ->get(['id', 'series_id', 'title', 'book_index', 'chapter_index']);

        return response()->json($chapters);
    }

    /**
     * GET /api/public/series/{slug}/chapters/{chapter}
     * Nội dung một chapter đã canonized.
     */
    public function chapter(string $slug, SerialChapter $chapter): JsonResponse
    {
        $series = NarrativeSeries::published()
            ->where('slug', $slug)
            ->firstOrFail();

        if ($chapter->series_id !== $series->id || !$chapter->isCanonized()) {
            abort(404);
        }

        return response()->json([
            'id' => $chapter->id,
            'series_id' => $chapter->series_id,
            'book_index' => $chapter->book_index,
            'chapter_index' => $chapter->chapter_index,
            'title' => $chapter->title,
            'content' => $chapter->content,
        ]);
    }

    /**
     * GET /api/public/series/{slug}/bible
     * StoryBible của series (characters, locations, lore).
     */
    public function bible(string $slug): JsonResponse
    {
        $series = NarrativeSeries::published()
            ->where('slug', $slug)
            ->with('bible')
            ->firstOrFail();

        $bible = $series->bible;
        if (!$bible) {
            return response()->json([
                'characters' => [],
                'locations' => [],
                'lore' => [],
            ]);
        }

        return response()->json([
            'characters' => $bible->characters ?? [],
            'locations' => $bible->locations ?? [],
            'lore' => $bible->lore ?? [],
        ]);
    }
}

