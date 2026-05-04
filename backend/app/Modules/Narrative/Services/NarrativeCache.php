<?php

namespace App\Modules\Narrative\Services;

use App\Modules\Intelligence\Services\AI\EpistemicService;
use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\Cache;

/**
 * NarrativeCache: Manages the short-term perception of reality.
 * Implements "Self-Decaying Truth" for narratives.
 * Integrates with EpistemicService to filter facts through the "Mist of History".
 */
class NarrativeCache
{
    public function __construct(
        protected EpistemicService $epistemicService
    ) {}

    /**
     * Store a narrative segment with a specific TTL.
     */
    public function put(int $universeId, string $key, mixed $content, int $ttlSeconds = 3600): void
    {
        $cacheKey = $this->getCacheKey($universeId, $key);
        Cache::put($cacheKey, [
            'content' => $content,
            'timestamp' => now()->timestamp,
            'universe_id' => $universeId
        ], $ttlSeconds);
    }

    /**
     * Retrieve a narrative segment, filtered through Epistemic noise.
     * What is retrieved is "Perceived Truth", not "Canonical Truth".
     */
    public function get(Universe $universe, string $key): mixed
    {
        $cacheKey = $this->getCacheKey($universe->id, $key);
        $data = Cache::get($cacheKey);

        if (!$data) {
            return null;
        }

        // Calculate current noise for this universe
        $noise = $this->epistemicService->calculateNoise($universe, (float)(($universe->state_vector ?? [])['entropy'] ?? 0.5));

        // If noise is low, return content as is
        if ($noise < 0.1) {
            return $data['content'];
        }

        // Apply distortion to the content based on current instability
        return $this->applyEpistemicFilter($universe, $data['content'], $noise);
    }

    /**
     * T7/T8 Philosophy: Distort reality based on entropy and temporal decay.
     */
    protected function applyEpistemicFilter(Universe $universe, mixed $content, float $noise): mixed
    {
        if (is_string($content)) {
            return $this->epistemicService->distort($universe, ['text' => $content], $noise)['text'];
        }

        if (is_array($content)) {
            return $this->epistemicService->distort($universe, $content, $noise);
        }

        return $content;
    }

    protected function getCacheKey(int $universeId, string $key): string
    {
        return "worldos:narrative:{$universeId}:{$key}";
    }

    /**
     * Clear perception for a universe (used for reset or major collapse).
     */
    public function clear(int $universeId): void
    {
        // Note: Actual mass deletion depends on Cache driver settings.
    }
}
