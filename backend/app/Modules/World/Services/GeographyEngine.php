<?php

declare(strict_types=1);

namespace App\Modules\World\Services;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * World layer (Physical): geography engine — terrain, climate zones, natural disasters.
 *
 * Operates at ecosystem-level tick rate (every N ticks) to simulate:
 * - Terrain drift (plate tectonics, erosion) over long time scales.
 * - Climate zone evolution (temperature bands, precipitation).
 * - Natural disaster probability (earthquakes, volcanic eruptions, floods).
 *
 * State is stored in the world state vector under 'geography'.
 */
final class GeographyEngine implements SimulationEngine
{
    use DefaultSimulationEnginePhase;

    /** Only recalculate geography every 24 ticks (major epoch-scale changes). */
    private const GEOGRAPHY_TICK_INTERVAL = 24;

    /** Climate zone definitions with temperature and precipitation ranges. */
    private const CLIMATE_ZONES = [
        'polar'          => ['temp_min' => -50, 'temp_max' => 0,   'precip_min' => 0,   'precip_max' => 300],
        'subpolar'       => ['temp_min' => -10, 'temp_max' => 10,  'precip_min' => 100, 'precip_max' => 500],
        'temperate'      => ['temp_min' => 0,   'temp_max' => 25,  'precip_min' => 300, 'precip_max' => 1200],
        'subtropical'    => ['temp_min' => 15,  'temp_max' => 35,  'precip_min' => 200, 'precip_max' => 800],
        'tropical'       => ['temp_min' => 20,  'temp_max' => 40,  'precip_min' => 800, 'precip_max' => 3000],
        'arid'           => ['temp_min' => 10,  'temp_max' => 45,  'precip_min' => 0,   'precip_max' => 200],
        'mediterranean'  => ['temp_min' => 5,   'temp_max' => 30,  'precip_min' => 300, 'precip_max' => 800],
    ];

    /** Base disaster probabilities per climate zone (annualized, scaled to tick interval). */
    private const DISASTER_BASE_PROBABILITY = [
        'earthquake' => 0.0001,
        'volcano'    => 0.00005,
        'flood'      => 0.001,
        'drought'    => 0.002,
        'wildfire'   => 0.0015,
    ];

    public function phase(): string
    {
        return 'physical';
    }

    public function name(): string
    {
        return 'geography';
    }

    public function priority(): int
    {
        return 0; // Runs first in the physical phase pipeline.
    }

    public function tickRate(): int
    {
        return self::GEOGRAPHY_TICK_INTERVAL;
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $tick = $ctx->getTick();
        $seed = $ctx->getSeed();

        // Only recalculate on interval-aligned ticks; skip with empty result otherwise.
        if ($tick % self::GEOGRAPHY_TICK_INTERVAL !== 0) {
            return EngineResult::empty();
        }

        $geography = $state->get('geography', []);
        $seed = $this->deriveSeed($seed, $tick);

        // 1. Evolve terrain: drift landmasses, apply erosion.
        $terrain = $this->evolveTerrain($geography['terrain'] ?? $this->generateInitialTerrain($seed), $seed, $tick);

        // 2. Update climate zones based on terrain and long-term trends.
        $climate = $this->updateClimateZones(
            $geography['climate_zones'] ?? $this->generateInitialClimate($terrain, $seed),
            $terrain,
            $tick
        );

        // 3. Compute natural disaster events for this interval.
        $disasters = $this->computeDisasters($climate, $seed, $tick);

        // 4. Apply terrain feedback: disasters reshape terrain.
        $terrain = $this->applyDisasterTerrainEffects($terrain, $disasters);

        $geographyState = [
            'tick'          => $tick,
            'terrain'       => $terrain,
            'climate_zones' => $climate,
            'disasters'     => $disasters,
            'continent_count' => $terrain['continent_count'] ?? 0,
        ];

        return new EngineResult(
            events: $disasters ? $this->buildDisasterEvents($disasters, $tick) : [],
            stateChanges: ['geography' => $geographyState],
            metrics: [
                'continent_count'         => $terrain['continent_count'] ?? 0,
                'total_landmass'          => $terrain['total_landmass'] ?? 0,
                'disaster_count'           => count($disasters),
                'dominant_climate_zone'    => $this->dominantZone($climate),
                'avg_temperature'          => $this->averageTemperature($climate),
                'total_precipitation'      => $this->totalPrecipitation($climate),
            ],
        );
    }

    /**
     * Generate initial terrain from a seed: N continents with random shapes.
     */
    private function generateInitialTerrain(int $seed): array
    {
        $rng = $this->makeRandomizer($seed);
        $continentCount = $rng->getInt(2, 7);
        $continents = [];

        for ($i = 0; $i < $continentCount; $i++) {
            $continents[] = [
                'id'       => "continent_{$i}",
                'name'     => "Continent-" . chr(65 + $i), // A, B, C...
                'size'     => round($rng->getInt(100000, 50000000) / 1000000, 2), // km² in millions
                'latitude' => round($rng->getInt(-900, 900) / 10, 1),              // -90 to 90
                'longitude'=> round($rng->getInt(-1800, 1800) / 10, 1),            // -180 to 180
                'elevation'=> round($rng->getInt(0, 8848) / 1000, 2),             // km
                'biome_count' => $rng->getInt(3, 12),
            ];
        }

        $totalLandmass = array_sum(array_column($continents, 'size'));

        return [
            'generated_at'   => 0,
            'continent_count' => $continentCount,
            'continents'      => $continents,
            'total_landmass'  => round($totalLandmass, 2),
            'ocean_coverage'  => round(100 - ($totalLandmass / 510) * 100, 1), // Earth surface = 510M km²
        ];
    }

    /**
     * Evolve terrain over time: slight drift, erosion reduces elevation.
     */
    private function evolveTerrain(array $terrain, int $seed, int $tick): array
    {
        $rng = $this->makeRandomizer($seed + $tick);

        $continents = $terrain['continents'] ?? [];
        foreach ($continents as &$c) {
            // Continental drift: shift latitude/longitude by tiny amounts.
            $c['latitude']  = round($c['latitude']  + ($rng->getInt(-2, 2) / 10), 1);
            $c['longitude'] = round($c['longitude'] + ($rng->getInt(-2, 2) / 10), 1);

            // Erosion: elevation decreases slightly over time (natural + rainfall).
            $erosionRate = 0.001 * (1 + ($c['latitude'] < 20 && $c['latitude'] > -20 ? 2 : 1)); // tropics erode faster
            $c['elevation'] = round(max(0, $c['elevation'] - $erosionRate), 2);

            // Occasional tectonic uplift.
            if ($rng->getInt(0, 100) < 2) {
                $c['elevation'] = round($c['elevation'] + ($rng->getInt(1, 20) / 100), 2);
            }

            // Biome count evolves with climate.
            $c['biome_count'] = max(1, $c['biome_count'] + $rng->getInt(-1, 1));
        }
        unset($c);

        $terrain['continents']     = $continents;
        $terrain['continent_count'] = count($continents);
        $terrain['total_landmass']  = round(array_sum(array_column($continents, 'size')), 2);

        return $terrain;
    }

    /**
     * Generate initial climate zones mapping each latitude band to a climate type.
     */
    private function generateInitialClimate(array $terrain, int $seed): array
    {
        $rng = $this->makeRandomizer($seed);
        $zones = [];

        // Divide into 18 latitude bands (every 10 degrees).
        for ($lat = -90; $lat < 90; $lat += 10) {
            $zoneIndex = match (true) {
                abs($lat) >= 60 => 'polar',
                abs($lat) >= 40 => 'temperate',
                abs($lat) >= 20 => 'subtropical',
                default        => 'tropical',
            };

            // Vary precipitation randomly within the zone's range.
            $def    = self::CLIMATE_ZONES[$zoneIndex];
            $temp   = round($rng->getInt($def['temp_min'] * 10, $def['temp_max'] * 10) / 10, 1);
            $precip = round($rng->getInt($def['precip_min'], $def['precip_max']), 1);

            $zones[] = [
                'latitude_band'   => "{$lat}_" . ($lat + 10),
                'zone'            => $zoneIndex,
                'temperature_avg' => $temp,
                'precipitation'   => $precip,
                'seasonality'     => abs($lat) > 30 ? (abs($lat) > 60 ? 'extreme' : 'moderate') : 'mild',
            ];
        }

        return $zones;
    }

    /**
     * Update climate zones: temperatures may shift, precipitation patterns change.
     */
    private function updateClimateZones(array $zones, array $terrain, int $tick): array
    {
        $rng = $this->makeRandomizer($tick);
        $updated = [];

        foreach ($zones as $zone) {
            $def = self::CLIMATE_ZONES[$zone['zone']] ?? null;
            if ($def === null) {
                $updated[] = $zone;
                continue;
            }

            // Natural climate variability: slowly drift temperature and precipitation.
            $tempDrift   = ($rng->getInt(-20, 20) / 100);       // ±0.2°C per geo-tick
            $precipDrift = ($rng->getInt(-50, 50) / 10);        // ±5 mm per geo-tick

            $newTemp   = round(max($def['temp_min'], min($def['temp_max'], $zone['temperature_avg'] + $tempDrift)), 1);
            $newPrecip = round(max($def['precip_min'], min($def['precip_max'], $zone['precipitation'] + $precipDrift)), 1);

            // Long-term warming/cooling: slight trend over many ticks.
            $trendTemp = round($zone['temperature_avg'] + 0.01 * ($tick / self::GEOGRAPHY_TICK_INTERVAL), 1);

            $updated[] = [
                'latitude_band'   => $zone['latitude_band'],
                'zone'            => $zone['zone'],
                'temperature_avg' => ($newTemp + $trendTemp) / 2, // Blend natural variation with trend.
                'precipitation'   => $newPrecip,
                'seasonality'     => $zone['seasonality'],
            ];
        }

        return $updated;
    }

    /**
     * Compute natural disasters probabilistically based on climate and terrain.
     *
     * @return array<int, array{type: string, latitude: float, longitude: float, severity: float}>
     */
    private function computeDisasters(array $climateZones, int $seed, int $tick): array
    {
        $rng = $this->makeRandomizer($seed + $tick);
        $disasters = [];
        $intervalFactor = self::GEOGRAPHY_TICK_INTERVAL; // Scale annual probabilities to interval.

        foreach ($climateZones as $zone) {
            $lat = (float) explode('_', $zone['latitude_band'])[0] + 5; // midpoint of band

            foreach (self::DISASTER_BASE_PROBABILITY as $type => $baseProb) {
                $scaledProb = $baseProb * $intervalFactor;

                // Modifiers based on climate zone.
                $scaledProb *= match ($type) {
                    'earthquake' => 1.0,  // Equal everywhere.
                    'volcano'    => abs($lat) > 40 ? 1.5 : 1.0, // More likely at high latitudes (simplified plate-boundary heuristic).
                    'flood'      => $zone['precipitation'] > 1000 ? 2.0 : 1.0,
                    'drought'    => $zone['precipitation'] < 200 ? 3.0 : 1.0,
                    'wildfire'   => $zone['temperature_avg'] > 25 && $zone['precipitation'] < 500 ? 2.5 : 1.0,
                    default      => 1.0,
                };

                if (($rng->getInt(0, 10000) / 10000) < $scaledProb) {
                    $disasters[] = [
                        'type'      => $type,
                        'latitude'  => round($lat + ($rng->getInt(-50, 50) / 10), 1),
                        'longitude' => round(($rng->getInt(-1800, 1800) / 10), 1),
                        'severity'  => round($rng->getInt(10, 100) / 100, 2), // 0.1 to 1.0
                    ];
                }
            }
        }

        return $disasters;
    }

    /**
     * Disasters reshape terrain: volcanoes create new land, earthquakes lower elevation.
     */
    private function applyDisasterTerrainEffects(array $terrain, array $disasters): array
    {
        if (empty($disasters)) {
            return $terrain;
        }

        $continents = $terrain['continents'] ?? [];
        foreach ($continents as &$c) {
            foreach ($disasters as $d) {
                // Proximity check: disaster affects continent if within ~15 degrees.
                if (abs($c['latitude'] - $d['latitude']) < 15 && abs($c['longitude'] - $d['longitude']) < 15) {
                    if ($d['type'] === 'volcano') {
                        $c['elevation'] = round($c['elevation'] + ($d['severity'] * 0.05), 2);
                    } elseif (in_array($d['type'], ['earthquake', 'flood'])) {
                        $c['elevation'] = round(max(0, $c['elevation'] - ($d['severity'] * 0.03)), 2);
                    }
                }
            }
        }
        unset($c);

        $terrain['continents'] = $continents;
        return $terrain;
    }

    /**
     * Build simulation events from disaster occurrences.
     */
    private function buildDisasterEvents(array $disasters, int $tick): array
    {
        return array_map(fn (array $d) => (object) [
            'type'      => 'geography.' . $d['type'],
            'tick'      => $tick,
            'payload'   => $d,
            'severity'  => $d['severity'],
        ], $disasters);
    }

    private function deriveSeed(int $seed, int $tick): int
    {
        return (int) crc32("{$seed}:{$tick}:geography");
    }

    /**
     * Build a hermetic Randomizer seeded by the given integer.
     *
     * The engine state is encapsulated inside the Randomizer instance, so two
     * ticks running concurrently (Octane workers, parallel FPM, async jobs)
     * cannot bleed random values into each other the way mt_srand() does.
     * Each call gets a fresh deterministic stream that is reproducible given
     * the same seed.
     */
    private function makeRandomizer(int $seed): Randomizer
    {
        return new Randomizer(new Mt19937($seed));
    }

    private function dominantZone(array $climateZones): string
    {
        $counts = [];
        foreach ($climateZones as $z) {
            $key = $z['zone'];
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
        return (string) (array_search(max($counts), $counts, true) ?: 'unknown');
    }

    private function averageTemperature(array $climateZones): float
    {
        if (empty($climateZones)) return 0.0;
        return round(array_sum(array_column($climateZones, 'temperature_avg')) / count($climateZones), 2);
    }

    private function totalPrecipitation(array $climateZones): float
    {
        return round(array_sum(array_column($climateZones, 'precipitation')), 1);
    }
}

