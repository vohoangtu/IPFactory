<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Core\Runtime\State;

use App\Modules\Simulation\Core\Contracts\StateCacheInterface;
use App\Modules\Simulation\Services\Core\HolographicCompressionService;
use Illuminate\Support\Facades\Log;

/**
 * StateCacheManager — Manages caching and holographic compression
 * of simulation state vectors.
 *
 * Extracted from the original StateManager to isolate cache and
 * compression concerns from load/save logic.
 */
class StateCacheManager
{
    public function __construct(
        protected StateCacheInterface $cache,
        protected HolographicCompressionService $compressionService,
    ) {
    }

    /**
     * Try to load a cached state vector for the given universe.
     *
     * @return array|null The cached state data, or null on miss.
     */
    public function loadCached(int $universeId): ?array
    {
        $data = $this->cache->get($universeId);

        if ($data !== null) {
            Log::debug('StateCacheManager: Cache hit for universe state.', [
                'universe_id' => $universeId,
            ]);
        }

        return $data;
    }

    /**
     * Store a state vector in the cache.
     */
    public function storeCached(int $universeId, array $data): void
    {
        $this->cache->put($universeId, $data);
    }

    /**
     * Invalidate the cache for a universe.
     */
    public function invalidate(int $universeId): void
    {
        $this->cache->forget($universeId);
    }

    /**
     * Decompress a holographic state vector if the marker is present.
     */
    public function decompressIfNeeded(array $data, array $originalData = []): array
    {
        if (isset($data['_hologram'])) {
            Log::debug('StateCacheManager: Decompressing holographic state.');

            return $this->compressionService->decompress($data, $originalData);
        }

        return $data;
    }

    /**
     * Apply holographic (delta) compression to a state vector.
     */
    public function compress(array $currentData, array $originalData): array
    {
        return $this->compressionService->compress($currentData, $originalData);
    }
}
