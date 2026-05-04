<?php

namespace App\Modules\Simulation\Core\Engines\Environment;

use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Geography\Services\EnvironmentTickService;
use App\Modules\Geography\ValueObjects\Tile;
use App\Modules\Geography\ValueObjects\Weather;
use Illuminate\Support\Facades\Log;

class GeographyEngine implements EngineInterface
{
    /**
     * Map data per universe. 
     * In Phase 1, we use a simple grid of Tiles, indexed by "x,y".
     * @var array<int, array{map: array<string, Tile>, resources: array<string, array>, weather: Weather}>
     */
    private static array $persistentState = [];

    public function __construct(
        private readonly EnvironmentTickService $tickService
    ) {}

    /**
     * Cho phép các hệ thống khác (như ActionHandler) truy cập và sửa đổi trực tiếp tài nguyên.
     */
    public static function &getPersistentState(int $universeId): ?array
    {
        if (!isset(self::$persistentState[$universeId])) {
            $null = null;
            return $null;
        }
        return self::$persistentState[$universeId];
    }

    /**
     * Cho phép Sandbox hoặc công cụ bên ngoài inject trạng thái map/tài nguyên.
     */
    public static function injectPersistentState(int $universeId, array $state): void
    {
        self::$persistentState[$universeId] = $state;
    }

    public function name(): string
    {
        return 'GeographyEngine';
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $result = new EngineResult();
        $universeId = (int) $state->get('universe_id', 1);
        $tick = $ctx->getTick();

        // 1. Load or Initialize Map
        if (!isset(self::$persistentState[$universeId])) {
            $this->initializeUniverseMap($universeId);
            $result->events[] = \App\Modules\Simulation\Core\Events\WorldEvent::create(
                type: 'geography_initialized',
                universeId: $universeId,
                tick: $tick,
                payload: []
            );
        }

        $mapData = &self::$persistentState[$universeId];
        
        $totalDepleted = 0;
        $totalRegenerated = 0.0;
        
        // 2. Process Tick for each Tile's resources
        // Note: For large maps this needs chunking. This is a naive implementation for Phase 1.
        foreach ($mapData['map'] as $coord => $tile) {
            $tileResources = $mapData['resources'][$coord] ?? [];
            if (empty($tileResources)) {
                continue;
            }

            [$updatedResources, $nextWeather] = $this->tickService->tickEnvironment(
                $tileResources, 
                $mapData['weather'], 
                $tick
            );

            // Track changes for metrics
            $totalDepleted += (count($tileResources) - count($updatedResources));
            if (isset($updatedResources) && count($updatedResources) > 0) {
                // Approximate growth metric
                $totalRegenerated += 1.0; 
            }

            $mapData['resources'][$coord] = $updatedResources;
            $mapData['weather'] = $nextWeather; // Weather is currently global for the map
        }

        // 3. Update WorldState Output
        $state->set('geography.weather', $mapData['weather']->toArray());
        
        // Export just sample coordinates to avoid huge payload
        $sampleCoord = '0,0';
        if (isset($mapData['map'][$sampleCoord])) {
            $state->set('geography.sample_tile', $mapData['map'][$sampleCoord]->toArray());
            $state->set('geography.sample_resources', array_map(fn($r) => $r->toArray(), $mapData['resources'][$sampleCoord] ?? []));
        }

        $result->metrics['geography'] = [
            'weather_type'      => $mapData['weather']->type,
            'weather_intensity' => $mapData['weather']->intensity,
            'resources_depleted'=> $totalDepleted,
            'total_tiles'       => count($mapData['map']),
        ];

        return $result;
    }

    /**
     * Khởi tạo bản đồ 3x3 mặc định cho vũ trụ mới.
     */
    private function initializeUniverseMap(int $universeId): void
    {
        $map = [];
        $resources = [];
        
        $weather = new Weather(Weather::TYPE_CLEAR, 20, 0.5); // Thời tiết ban đầu

        // Auto-generate a 3x3 grid
        $biomes = [Tile::BIOME_PLAINS, Tile::BIOME_FOREST, Tile::BIOME_MOUNTAIN, Tile::BIOME_DESERT, Tile::BIOME_WATER];

        for ($x = -1; $x <= 1; $x++) {
            for ($y = -1; $y <= 1; $y++) {
                $coord = "$x,$y";
                // Random biome, but 0,0 is always Plains and flat
                $biome = ($x === 0 && $y === 0) ? Tile::BIOME_PLAINS : $biomes[array_rand($biomes)];
                $elevation = ($biome === Tile::BIOME_MOUNTAIN) ? 'high' : 'flat';
                
                $tile = new Tile($x, $y, $biome, $elevation);
                $map[$coord] = $tile;
                
                // Spawn resources
                $resources[$coord] = $this->tickService->initializeZoneResources($tile);
            }
        }

        self::$persistentState[$universeId] = [
            'map'       => $map,
            'resources' => $resources,
            'weather'   => $weather,
        ];
    }
}

