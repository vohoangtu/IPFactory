<?php

namespace App\Modules\Simulation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Narrative\Models\NarrativeSeries;
use App\Modules\Narrative\Models\SerialChapter;
use App\Modules\Narrative\Services\SerialStoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IpFactoryController extends Controller
{
    public function __construct(
        protected SerialStoryService $serialService
    ) {}

    /**
     * GET /api/ip-factory/series
     * Danh sách tất cả NarrativeSeries
     */
    public function index(): JsonResponse
    {
        $series = NarrativeSeries::with(['universe', 'bible'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($series);
    }

    /**
     * POST /api/ip-factory/series
     * Tạo một NarrativeSeries mới
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'universe_id' => 'required|integer|exists:universes,id',
            'saga_id'     => 'nullable|integer|exists:sagas,id',
            'title'       => 'required|string|max:255',
            'genre_key'   => 'nullable|string|max:64',
            'config'      => 'nullable|array',
        ]);

        $series = $this->serialService->createSeries($validated);

        return response()->json($series, 201);
    }

    /**
     * GET /api/ip-factory/series/{series}
     * Chi tiết một NarrativeSeries
     */
    public function show(NarrativeSeries $series): JsonResponse
    {
        $series->load(['universe', 'saga', 'bible', 'chapters' => fn($q) => $q->orderBy('chapter_index')]);
        return response()->json($series);
    }

    /**
     * GET /api/ip-factory/series/{series}/chapters
     * Danh sách chapters của Series
     */
    public function chapters(NarrativeSeries $series): JsonResponse
    {
        $chapters = $series->chapters()
            ->orderBy('book_index')
            ->orderBy('chapter_index')
            ->get();

        return response()->json($chapters);
    }

    /**
     * POST /api/ip-factory/series/{series}/generate-chapter
     * Sinh chapter tiếp theo tự động. Mặc định dispatch queue Job.
     * Thêm ?sync=true để sinh ngay lập tức (dùng cho test).
     */
    public function generateChapter(Request $request, NarrativeSeries $series): JsonResponse
    {
        $sync = $request->boolean('sync', false);

        if ($sync) {
            try {
                $chapter = $this->serialService->generateNextChapter($series);
                return response()->json($chapter, 201);
            } catch (\RuntimeException $e) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
        }

        // Dispatch queue Job (default)
        \App\Modules\Narrative\Jobs\GenerateSerialChapterJob::dispatch($series->id);

        return response()->json([
            'message' => 'Chapter generation queued. Check back shortly.',
            'series_id' => $series->id,
        ], 202);
    }

    /**
     * POST /api/ip-factory/series/{series}/chapters/{chapter}/canonize
     * Canonize (phê duyệt) một chapter
     */
    public function canonize(NarrativeSeries $series, SerialChapter $chapter): JsonResponse
    {
        if ($chapter->series_id !== $series->id) {
            return response()->json(['error' => 'Chapter không thuộc Series này.'], 403);
        }

        try {
            $chapter = $this->serialService->canonizeChapter($chapter);
            return response()->json($chapter);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /api/ip-factory/series/{series}/bible
     * Lấy StoryBible của Series
     */
    public function bible(NarrativeSeries $series): JsonResponse
    {
        $bible = $series->bible;
        if (!$bible) {
            return response()->json(['error' => 'Chưa có StoryBible cho Series này.'], 404);
        }
        return response()->json($bible);
    }

    /**
     * PUT /api/ip-factory/series/{series}/publish
     * Xuất bản bộ truyện: set slug, description, published_at.
     */
    public function publish(Request $request, NarrativeSeries $series): JsonResponse
    {
        $validated = $request->validate([
            'slug' => 'required|string|max:255|unique:narrative_series,slug,' . $series->id,
            'description' => 'nullable|string|max:5000',
        ]);

        $slug = \Illuminate\Support\Str::slug($validated['slug']);
        if ($slug === '') {
            return response()->json(['error' => 'Slug không hợp lệ.'], 422);
        }

        $series->update([
            'slug' => $slug,
            'description' => $validated['description'] ?? $series->description,
            'published_at' => now(),
        ]);

        return response()->json($series->fresh());
    }
}


