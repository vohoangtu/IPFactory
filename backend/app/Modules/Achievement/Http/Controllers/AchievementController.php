<?php

namespace App\Modules\Achievement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\World\Models\Universe;
use App\Modules\Achievement\Entities\Achievement;
use App\Modules\Achievement\Services\AchievementEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AchievementController extends Controller
{
    public function __construct(
        protected AchievementEngine $engine
    ) {}

    /**
     * List all achievements for a universe, including unlock/claim status.
     */
    public function index(Request $request, Universe $universe): JsonResponse
    {
        $user = Auth::user();
        $universe->load(['achievements', 'actors.achievements']);

        $claimedIds = \DB::table('user_claims')
            ->where('user_id', $user?->id)
            ->where('universe_id', $universe->id)
            ->pluck('achievement_id')
            ->toArray();

        $unlockedIds = $universe->achievements->pluck('id')->toArray();

        $achievements = Achievement::orderBy('sort_order')->get()->map(function (Achievement $a) use ($unlockedIds, $claimedIds) {
            return [
                'id' => $a->id,
                'code' => $a->code,
                'name' => $a->name,
                'description' => $a->description,
                'category' => $a->category,
                'icon' => $a->icon,
                'rarity' => $a->rarity,
                'unlocked' => in_array($a->id, $unlockedIds, true),
                'claimed' => in_array($a->id, $claimedIds, true),
            ];
        });

        return response()->json([
            'achievements' => $achievements,
            'stats' => [
                'total' => $achievements->count(),
                'unlocked' => count($unlockedIds),
                'claimed' => count($claimedIds),
            ],
        ]);
    }

    /**
     * Claim an achievement for the current user.
     */
    public function claim(Request $request, Universe $universe, Achievement $achievement): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Must be unlocked in this universe
        if (!$universe->achievements()->where('achievement_id', $achievement->id)->exists()) {
            return response()->json(['message' => 'Achievement not yet unlocked in this universe'], 403);
        }

        $exists = \DB::table('user_claims')
            ->where('user_id', $user->id)
            ->where('achievement_id', $achievement->id)
            ->where('universe_id', $universe->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Already claimed'], 409);
        }

        \DB::table('user_claims')->insert([
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
            'universe_id' => $universe->id,
            'claimed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Claimed successfully',
            'achievement' => [
                'id' => $achievement->id,
                'code' => $achievement->code,
                'name' => $achievement->name,
            ],
        ]);
    }

    /**
     * Trigger evaluation for a universe.
     */
    public function evaluate(Request $request, Universe $universe): JsonResponse
    {
        $unlocked = $this->engine->evaluateForUniverse($universe);

        foreach ($universe->actors as $actor) {
            $actorUnlocked = $this->engine->evaluateForActor($actor, $universe);
            $unlocked = array_merge($unlocked, $actorUnlocked);
        }

        return response()->json([
            'unlocked' => array_unique($unlocked),
            'message' => 'Evaluation complete',
        ]);
    }

    /**
     * Progression feed: timeline of unlockable moments.
     */
    public function progression(Request $request, Universe $universe): JsonResponse
    {
        $timeline = [];
        $tick = $universe->current_tick;

        // Epoch transitions
        $epochs = $universe->world?->epochs ?? [];
        foreach ($epochs as $epoch) {
            $timeline[] = [
                'tick' => $epoch['tick'] ?? 0,
                'type' => 'epoch',
                'label' => $epoch['name'] ?? 'Unknown Era',
                'description' => $epoch['description'] ?? '',
                'claimable' => false,
            ];
        }

        // Technologies
        try {
            foreach ($universe->technologies as $tech) {
                $timeline[] = [
                    'tick' => $tech->discovered_at_tick ?? 0,
                    'type' => 'discovery',
                    'label' => $tech->name,
                    'actor_id' => $tech->discoverer_actor_id ?? null,
                    'claimable' => true,
                ];
            }
        } catch (\Throwable $e) {
            // technologies relationship may not be defined on this universe
        }

        // World events (scars)
        try {
            foreach ($universe->worldEvents as $event) {
                $timeline[] = [
                    'tick' => $event->tick ?? 0,
                    'type' => 'scar',
                    'label' => $event->name,
                    'severity' => $event->severity ?? 'normal',
                    'claimable' => false,
                ];
            }
        } catch (\Throwable $e) {
            // worldEvents relationship may not be defined on this universe
        }

        // Celebrities / Hero actors
        try {
            foreach ($universe->actors()->whereNotNull('celebrity_data')->get() as $actor) {
                $timeline[] = [
                    'tick' => $actor->celebrity_data['tick'] ?? 0,
                    'type' => 'celebrity',
                    'label' => "Hero Rises: {$actor->name}",
                    'actor_id' => $actor->id,
                    'claimable' => true,
                ];
            }
        } catch (\Throwable $e) {
            // celebrity_data column may not exist in this schema
        }

        usort($timeline, fn($a, $b) => $a['tick'] <=> $b['tick']);

        try {
            $totalDiscoveries = $universe->technologies()->count();
        } catch (\Throwable $e) {
            $totalDiscoveries = 0;
        }

        try {
            $totalScars = $universe->worldEvents()->count();
        } catch (\Throwable $e) {
            $totalScars = 0;
        }

        try {
            $totalCelebrities = $universe->actors()->whereNotNull('celebrity_data')->count();
        } catch (\Throwable $e) {
            $totalCelebrities = 0;
        }

        return response()->json([
            'timeline' => $timeline,
            'stats' => [
                'total_discoveries' => $totalDiscoveries,
                'total_scars' => $totalScars,
                'total_celebrities' => $totalCelebrities,
                'current_epoch' => $universe->world?->current_epoch ?? 'Unknown',
            ],
        ]);
    }
}
