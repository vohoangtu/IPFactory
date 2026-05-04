<?php

namespace App\Modules\Psychology\Dsl;

use Illuminate\Support\Facades\Cache;

/**
 * BehaviorDslLoader – loads and caches the psychology behaviors.json DSL.
 *
 * Returns the parsed DSL array on each call, cached for performance.
 */
final class BehaviorDslLoader
{
    private const CACHE_KEY = 'psychology.dsl.behaviors';
    private const CACHE_TTL = 300; // seconds

    public function __construct(
        private readonly string $dslPath = '',
    ) {}

    /**
     * Load and return the full DSL array.
     *
     * @return array{behaviors: array, goals: array, event_meanings: array}
     */
    public function load(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $path = $this->resolvePath();
            if (!file_exists($path)) {
                return $this->fallbackDsl();
            }
            $json = file_get_contents($path);
            $data = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->fallbackDsl();
            }
            return $data;
        });
    }

    /**
     * @return array{name: string, base_score: string, tags: string[]}[]
     */
    public function behaviors(): array
    {
        return $this->load()['behaviors'] ?? [];
    }

    /**
     * @return array{type: string, priority: string, influences: array}[]
     */
    public function goals(): array
    {
        return $this->load()['goals'] ?? [];
    }

    /**
     * @return array<string, array{type: string, valence: float, intensity: float, certainty: float, tags: string[]}>
     */
    public function eventMeanings(): array
    {
        return $this->load()['event_meanings'] ?? [];
    }

    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function resolvePath(): string
    {
        if ($this->dslPath !== '') {
            return $this->dslPath;
        }
        return base_path('resources/worldos_psychology/behaviors.json');
    }

    private function fallbackDsl(): array
    {
        return [
            'behaviors' => [
                ['name' => 'withdraw',   'base_score' => 'fear * 0.6 + stress * 0.3',   'tags' => ['passive']],
                ['name' => 'cooperate',  'base_score' => 'trust * 0.7 + joy * 0.3',     'tags' => ['social']],
                ['name' => 'resist',     'base_score' => 'anger * 0.5 - fear * 0.3',    'tags' => ['active']],
                ['name' => 'passive',    'base_score' => 'stress * 0.2',                'tags' => ['passive']],
            ],
            'goals' => [],
            'event_meanings' => [],
        ];
    }
}
