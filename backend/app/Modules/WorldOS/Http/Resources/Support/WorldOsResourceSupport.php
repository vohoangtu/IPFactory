<?php

namespace App\Modules\WorldOS\Http\Resources\Support;

use App\Modules\Narrative\Models\MythScar;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;

final class WorldOsResourceSupport
{
    public static function normalizeUniverseStatus(?string $status): string
    {
        return match ($status) {
            'active' => 'active',
            'forked' => 'forked',
            'inactive', 'paused', 'archived' => 'paused',
            default => 'paused',
        };
    }

    public static function normalizeBranchStatus(?string $status): string
    {
        return match (self::normalizeUniverseStatus($status)) {
            'active' => 'stable',
            'forked' => 'volatile',
            default => 'observed',
        };
    }

    public static function chronicleType(?string $type): string
    {
        return match ($type) {
            'war', 'battle', 'collapse', 'conflict' => 'conflict',
            'revelation', 'research', 'innovation', 'discovery' => 'discovery',
            'regime', 'council', 'dynasty', 'institution' => 'institution',
            default => 'transition',
        };
    }

    public static function scarSeverity(float|int|string|null $severity): string
    {
        $value = is_numeric($severity) ? (float) $severity : 0.0;

        return match (true) {
            $value >= 0.75 => 'high',
            $value <= 0.3 => 'low',
            default => 'medium',
        };
    }

    public static function anomalyCount(Universe $universe): int
    {
        return (int) MythScar::query()
            ->where('universe_id', $universe->id)
            ->whereNull('resolved_at_tick')
            ->count();
    }

    public static function stabilityForUniverse(Universe $universe, ?UniverseSnapshot $latestSnapshot = null): float
    {
        $structuralCoherence = (float) ($universe->structural_coherence ?? 0);
        if ($structuralCoherence > 0) {
            return round($structuralCoherence * 100, 2);
        }

        return round((float) ($latestSnapshot?->stability_index ?? 0) * 100, 2);
    }

    public static function decodeList(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(
                static fn (mixed $item) => is_scalar($item) ? trim((string) $item) : null,
                $value
            )));
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return self::decodeList($decoded);
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    public static function toMetricArray(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    public static function numericMetricDeltas(array $from, array $to): array
    {
        $deltas = [];

        foreach (array_unique(array_merge(array_keys($from), array_keys($to))) as $key) {
            if (! is_numeric($from[$key] ?? null) || ! is_numeric($to[$key] ?? null)) {
                continue;
            }

            $deltas[$key] = round((float) $to[$key] - (float) $from[$key], 4);
        }

        ksort($deltas);

        return $deltas;
    }

    public static function getVfxConfigForEra(?string $era): array
    {
        $era = strtolower($era ?? 'genesis');

        $configs = [
            'paleo' => ['primary_color' => '#ff4500', 'distortion' => 0.8, 'particle_density' => 120, 'atmosphere_filter' => 'mist'],
            'medieval' => ['primary_color' => '#ffd700', 'distortion' => 0.15, 'particle_density' => 60, 'atmosphere_filter' => 'sepia'],
            'ancient_east' => ['primary_color' => '#059669', 'distortion' => 0.25, 'particle_density' => 80, 'atmosphere_filter' => 'mist'],
            'modern_war' => ['primary_color' => '#475569', 'distortion' => 0.45, 'particle_density' => 50, 'atmosphere_filter' => 'grain'],
            'modern' => ['primary_color' => '#0ea5e9', 'distortion' => 0.35, 'particle_density' => 40, 'atmosphere_filter' => 'none'],
            'sci_fi' => ['primary_color' => '#00f3ff', 'distortion' => 0.6, 'particle_density' => 150, 'atmosphere_filter' => 'glitch'],
            'post_apoc' => ['primary_color' => '#92400e', 'distortion' => 0.7, 'particle_density' => 100, 'atmosphere_filter' => 'dust'],
            'fantasy' => ['primary_color' => '#8b5cf6', 'distortion' => 0.4, 'particle_density' => 90, 'atmosphere_filter' => 'aurora'],
            'genesis' => ['primary_color' => '#8b5cf6', 'distortion' => 0.4, 'particle_density' => 80, 'atmosphere_filter' => 'none'],
        ];

        return $configs[$era] ?? $configs['genesis'];
    }
}
