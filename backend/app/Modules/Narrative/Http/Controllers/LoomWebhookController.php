<?php

declare(strict_types=1);

namespace App\Modules\Narrative\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Models\Narrative;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * LoomWebhookController — nhận kết quả từ Narrative Loom service sau khi pipeline hoàn tất.
 *
 * Route: POST /api/worldos/narrative-loom/webhook (internal — Loom service only)
 *
 * Payload (từ chronicle_task.py):
 * {
 *   type: "pipeline_done",
 *   task_id: string,
 *   world_id: int,
 *   tick_start: int,
 *   tick_end: int,
 *   final_prose: string|null,
 *   news_headline: string|null,
 *   news_slogan: string|null,
 *   storyboard: object|null,
 *   vfx_config: object|null,
 *   historical_outline: string|null,
 *   completed_agents: string[],
 * }
 */
class LoomWebhookController extends Controller
{
    /**
     * Xử lý callback từ Narrative Loom sau khi pipeline hoàn tất.
     * Persist final_prose, news_headline, animation_script vào Chronicle và Narrative table.
     */
    public function receive(Request $request): JsonResponse
    {
        $type = $request->input('type');

        if ($type !== 'pipeline_done') {
            // Bỏ qua các event type khác (pipeline_error, v.v.)
            Log::debug('[LoomWebhook] Ignored event', ['type' => $type]);
            return response()->json(['ok' => true, 'action' => 'ignored']);
        }

        $worldId   = (int) $request->input('world_id');
        $taskId    = $request->input('task_id', '');
        $tickStart = (int) $request->input('tick_start', 0);
        $tickEnd   = (int) $request->input('tick_end', 0);

        $finalProse     = $request->input('final_prose');
        $newsHeadline   = $request->input('news_headline');
        $newsSlogan     = $request->input('news_slogan');
        $animationScript = $request->input('vfx_config');
        $storyboard     = $request->input('storyboard');

        Log::info('[LoomWebhook] pipeline_done received', [
            'task_id'      => $taskId,
            'world_id'     => $worldId,
            'tick_start'   => $tickStart,
            'tick_end'     => $tickEnd,
            'has_prose'    => !empty($finalProse),
            'has_headline' => !empty($newsHeadline),
            'has_vfx'      => !empty($animationScript),
        ]);

        // 1. Tìm Universe thuộc world_id này
        $universe = Universe::where('world_id', $worldId)->latest()->first();

        if (!$universe) {
            Log::warning('[LoomWebhook] Universe not found for world_id', ['world_id' => $worldId]);
            return response()->json(['ok' => false, 'error' => 'universe_not_found'], 404);
        }

        // 2. Upsert Chronicle — tìm chronicle trùng tick range hoặc tạo mới
        $chronicle = Chronicle::updateOrCreate(
            [
                'universe_id' => $universe->id,
                'from_tick'   => $tickStart,
                'to_tick'     => $tickEnd,
                'type'        => 'loom_chronicle',
            ],
            [
                'content'          => $finalProse,
                'animation_script' => $animationScript,
                'importance'       => 0.8,
                'raw_payload'      => [
                    'task_id'          => $taskId,
                    'news_headline'    => $newsHeadline,
                    'news_slogan'      => $newsSlogan,
                    'storyboard'       => $storyboard,
                    'completed_agents' => $request->input('completed_agents', []),
                ],
            ]
        );

        // 3. Persist vào Narrative table (news_headline → virality proxy)
        //    Narrative chứa các câu chuyện ngắn với độ viral — dùng news_headline làm story
        if (!empty($newsHeadline)) {
            Narrative::updateOrCreate(
                [
                    'universe_id' => $universe->id,
                    'tick_created' => $tickEnd,
                ],
                [
                    'story'     => $newsHeadline . ($newsSlogan ? " — {$newsSlogan}" : ''),
                    'virality'  => 0.7,
                    'is_active' => true,
                ]
            );
        }

        Log::info('[LoomWebhook] Persisted chronicle and narrative', [
            'chronicle_id' => $chronicle->id,
            'universe_id'  => $universe->id,
        ]);

        return response()->json([
            'ok'           => true,
            'chronicle_id' => $chronicle->id,
            'universe_id'  => $universe->id,
        ]);
    }
}
