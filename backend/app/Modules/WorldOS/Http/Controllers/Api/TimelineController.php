<?php

namespace App\Modules\WorldOS\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Services\ChronicleSynthesisEngine;
use App\Modules\Narrative\Services\NarrativeAiService;
use App\Modules\Narrative\Services\UniverseHistoryGenerator;
use App\Modules\WorldOS\Http\Resources\TimelineEventResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class TimelineController extends Controller
{
    public function history(int $id, Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 50);
        $cacheKey = "worldos:timeline:{$id}:limit:{$limit}";

        return Cache::remember($cacheKey, 300, function () use ($id, $limit) {
            $facts = DB::table('historical_facts')
                ->where('universe_id', $id)
                ->orderByDesc('tick')
                ->limit($limit)
                ->get();

            return TimelineEventResource::collection($facts)
                ->additional([
                    'meta' => [
                        'by_type' => $facts->groupBy('type')->map(static fn ($group) => $group->count()),
                    ],
                ])
                ->response();
        });
    }

    public function getChronicles(string $id, Request $request): JsonResponse
    {
        $universeId = (int) $id;
        $universe = Universe::findOrFail($universeId);

        $fromTick = $request->input('from_tick');
        $toTick = $request->input('to_tick');

        if ($fromTick === null || $fromTick === '') {
            $first = $universe->snapshots()->orderBy('tick')->first();
            $latest = $universe->snapshots()->orderByDesc('tick')->first();
            $fromTick = $first ? (int) $first->tick : 0;
            $toTick = $toTick !== null && $toTick !== '' ? (int) $toTick : ($latest ? (int) $latest->tick : $fromTick);
        } else {
            $fromTick = (int) $fromTick;
            $latest = $universe->snapshots()->orderByDesc('tick')->first();
            $toTick = $toTick !== null && $toTick !== '' ? (int) $toTick : ($latest ? (int) $latest->tick : $fromTick);
        }

        // Fetch chronicles from database
        $chronicles = \App\Modules\Narrative\Models\Chronicle::where('universe_id', $universeId)
            ->where('from_tick', '>=', $fromTick)
            ->where('to_tick', '<=', $toTick)
            ->orderBy('from_tick')
            ->get();

        // Normalize format cho narrative-loom
        $events = $chronicles->map(function ($chronicle) {
            return [
                'tick' => (int) $chronicle->from_tick,
                'type' => $chronicle->type ?? 'chronicle',
                'raw_payload' => $chronicle->raw_payload ?? [],
                'content' => $chronicle->content ?? null,
                'importance' => (float) ($chronicle->importance ?? 0.0),
            ];
        })->toArray();

        return response()->json([
            'data' => [
                'from_tick' => (int) $fromTick,
                'to_tick' => (int) $toTick,
                'events' => $events,
            ],
        ]);
    }

    public function generateChronicle(string $id, Request $request, NarrativeAiService $narrativeAi, \App\Modules\Narrative\Services\NarrativeLoomService $loomService): JsonResponse
    {
        $universeId = (int) $id;
        $universe = Universe::findOrFail($universeId);

        $fromTick = $request->input('from_tick');
        $toTick = $request->input('to_tick');

        if ($fromTick === null || $fromTick === '') {
            $first = $universe->snapshots()->orderBy('tick')->first();
            $latest = $universe->snapshots()->orderByDesc('tick')->first();
            $fromTick = $first ? (int) $first->tick : 0;
            $toTick = $toTick !== null && $toTick !== '' ? (int) $toTick : ($latest ? (int) $latest->tick : $fromTick);
        } else {
            $fromTick = (int) $fromTick;
            $latest = $universe->snapshots()->orderByDesc('tick')->first();
            $toTick = $toTick !== null && $toTick !== '' ? (int) $toTick : ($latest ? (int) $latest->tick : $fromTick);
        }

        // Gọi NarrativeLoom service để submit task async
        $worldId = $universe->world_id ?? $universeId;
        $loomResult = $loomService->weave($worldId, (int) $fromTick, (int) $toTick);

        if (! $loomResult || ! isset($loomResult['task_id'])) {
            // Fallback về PHP service nếu Loom thất bại
            Log::warning('NarrativeLoom failed, falling back to PHP service', ['loom_result' => $loomResult]);
            $chronicle = $narrativeAi->generateChronicle($universeId, (int) $fromTick, (int) $toTick, 'chronicle');

            if (! $chronicle) {
                return response()->json(['message' => 'Khong the sinh su thi.'], 422);
            }

            // Broadcast narrative completion via Centrifugo
            try {
                $broadcaster = app(\App\Broadcasting\CentrifugoBroadcaster::class);
                $broadcaster->broadcast(
                    ["public:universes"],
                    'narrative.completed',
                    [
                        'type' => 'chronicle_generated',
                        'universe_id' => $universeId,
                        'chronicle_id' => $chronicle->id,
                    ]
                );
            } catch (\Throwable $e) {
                Log::warning('Failed to broadcast narrative completion: ' . $e->getMessage());
            }

            return response()->json(['data' => [
                'id' => $chronicle->id,
                'content' => $chronicle->content,
                'from_tick' => $chronicle->from_tick,
                'to_tick' => $chronicle->to_tick,
            ]]);
        }

        // Return task_id ngay lập tức (async)
        return response()->json(['data' => [
            'task_id' => $loomResult['task_id'],
            'channel' => $loomResult['channel'] ?? null,
            'world_id' => $worldId,
            'universe_id' => $universeId,
            'tick_start' => (int) $fromTick,
            'tick_end' => (int) $toTick,
            'status' => 'submitted',
        ]]);
    }

    public function loomWebhook(Request $request): JsonResponse
    {
        $data = $request->all();

        $type = $data['type'] ?? null;
        $taskId = $data['task_id'] ?? null;
        $worldId = $data['world_id'] ?? null;

        if ($type === 'pipeline_done' && $taskId) {
            // Extract outputs từ narrative-loom
            $finalProse = $data['final_prose'] ?? null;
            $newsHeadline = $data['news_headline'] ?? null;
            $newsSlogan = $data['news_slogan'] ?? null;
            $vfxConfig = $data['vfx_config'] ?? null;
            $fromTick = $data['tick_start'] ?? null;
            $toTick = $data['tick_end'] ?? null;

            // Tìm universe từ world_id
            $world = \App\Modules\World\Models\World::find($worldId);
            if (!$world) {
                Log::error('Loom webhook: world not found', ['world_id' => $worldId]);
                return response()->json(['error' => 'World not found'], 404);
            }

            $universe = $world->universes()->first();
            if (!$universe) {
                Log::error('Loom webhook: universe not found for world', ['world_id' => $worldId]);
                return response()->json(['error' => 'Universe not found'], 404);
            }

            // Tạo hoặc update chronicle
            $chronicle = \App\Modules\Narrative\Models\Chronicle::updateOrCreate(
                [
                    'universe_id' => $universe->id,
                    'from_tick' => $fromTick,
                    'to_tick' => $toTick,
                ],
                [
                    'content' => $finalProse,
                    'type' => 'narrative_loom',
                    'importance' => 0.8,
                    'raw_payload' => [
                        'task_id' => $taskId,
                        'news_headline' => $newsHeadline,
                        'news_slogan' => $newsSlogan,
                        'vfx_config' => $vfxConfig,
                    ],
                ]
            );

            // Broadcast narrative completion via Centrifugo
            try {
                $broadcaster = app(\App\Broadcasting\CentrifugoBroadcaster::class);
                $broadcaster->broadcast(
                    ["public:universes"],
                    'narrative.completed',
                    [
                        'type' => 'chronicle_generated',
                        'universe_id' => $universe->id,
                        'chronicle_id' => $chronicle->id,
                        'task_id' => $taskId,
                        'headline' => $newsHeadline,
                    ]
                );
            } catch (\Throwable $e) {
                Log::warning('Failed to broadcast narrative completion: ' . $e->getMessage());
            }

            Log::info('Loom webhook: chronicle updated', [
                'chronicle_id' => $chronicle->id,
                'task_id' => $taskId,
                'universe_id' => $universe->id,
            ]);

            return response()->json(['data' => [
                'chronicle_id' => $chronicle->id,
                'status' => 'completed',
            ]]);
        }

        return response()->json(['error' => 'Unsupported event type'], 400);
    }

    public function generateHistory(string $id, Request $request, UniverseHistoryGenerator $historian): JsonResponse
    {
        $universe = Universe::findOrFail((int) $id);
        $fromTick = $request->has('from_tick') ? (int) $request->input('from_tick') : null;
        $toTick = $request->has('to_tick') ? (int) $request->input('to_tick') : null;

        $history = $historian->generate($universe, $fromTick, $toTick);

        if (! $history) {
            return response()->json(['message' => 'Loi khi tao lich su.'], 422);
        }

        return response()->json(['data' => [
            'id' => $history->id,
            'content' => $history->full_text,
            'from_tick' => $history->from_tick,
            'to_tick' => $history->to_tick,
        ]]);
    }

    public function causalLinks(string $id, ChronicleSynthesisEngine $synthesisEngine): JsonResponse
    {
        $links = $synthesisEngine->synthesize(
            (int) $id,
            (int) request('from_tick', 0),
            (int) request('to_tick', 1000000)
        );

        return response()->json([
            'data' => [
                'universe_id' => (int) $id,
                'links' => $links,
            ],
        ]);
    }
}
