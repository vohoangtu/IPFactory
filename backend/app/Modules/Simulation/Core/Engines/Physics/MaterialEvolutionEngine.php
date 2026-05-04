<?php
namespace App\Modules\Simulation\Core\Engines\Physics;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;

/**
 * MaterialEvolutionEngine — Vật liệu tiến hóa theo tech_level.
 *
 * Chuyển đổi raw minerals thành processed materials dựa trên tech_level.
 * Output: zone.state.available_materials, zone.state.material_profile
 */
class MaterialEvolutionEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    public function name(): string { return 'material_evolution'; }
    public function phase(): string { return 'physics'; }
    public function priority(): int { return 4; }
    public function tickRate(): int { return 1; }
    public function isParallelSafe(): bool { return true; }

    /** Material tiers: mỗi tier cần tech_level tối thiểu để unlock. */
    private const MATERIAL_TIERS = [
        ['name' => 'stone',    'tech_min' => 0.0, 'mineral_factor' => 0.3],
        ['name' => 'copper',   'tech_min' => 0.1, 'mineral_factor' => 0.5],
        ['name' => 'bronze',   'tech_min' => 0.2, 'mineral_factor' => 0.6],
        ['name' => 'iron',     'tech_min' => 0.3, 'mineral_factor' => 0.7],
        ['name' => 'steel',    'tech_min' => 0.5, 'mineral_factor' => 0.8],
        ['name' => 'alloy',    'tech_min' => 0.7, 'mineral_factor' => 0.9],
        ['name' => 'advanced', 'tech_min' => 0.9, 'mineral_factor' => 1.0],
    ];

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $tick = $ctx->getTick();

        // Chạy mỗi 10 tick để dễ xác minh trong giai đoạn ổn định
        if ($tick % 10 !== 0) {
            return EngineResult::empty();
        }

        $zones     = $state->getZones();
        $techLevel = (float) $state->get('tech_level', 0.1);
        $universeId = (int) $state->get('universe_id');

        $updatedZones = [];
        $events = [];
        $hasChanged = false;

        Log::info("[MaterialEvolutionEngine] Universe {$universeId} Tick {$tick}, TechLevel: {$techLevel}");

        foreach ($zones as $idx => $zone) {
            $zoneState  = $zone['state'] ?? [];
            $minerals   = (float) ($zoneState['minerals'] ?? $zoneState['mineral_richness'] ?? 0.5);
            $materials  = $zoneState['available_materials'] ?? [];
            $unlockedThisTick = [];

            foreach (self::MATERIAL_TIERS as $tier) {
                if ($techLevel >= $tier['tech_min']) {
                    $amount = $minerals * $tier['mineral_factor'] * 10.0;
                    $rounded = round($amount, 2);

                    if (!isset($materials[$tier['name']])) {
                        $unlockedThisTick[] = $tier['name'];
                    }
                    $materials[$tier['name']] = $rounded;
                }
            }

            if (!empty($unlockedThisTick)) {
                Log::info("[MaterialEvolutionEngine] Zone {$idx} unlocked: " . implode(', ', $unlockedThisTick));

                foreach ($unlockedThisTick as $newMaterial) {
                    $events[] = [
                        'type' => 'material_unlocked',
                        'universe_id' => $universeId,
                        'zone_id' => $zone['id'] ?? $idx,
                        'zone_name' => $zone['name'] ?? "Zone {$idx}",
                        'material' => $newMaterial,
                        'tech_band' => $this->deriveTechBand($techLevel),
                        'tick' => $tick,
                    ];
                }
            }

            $materialProfile = $this->buildMaterialProfile($zoneState, $materials, $techLevel);

            if (($zoneState['available_materials'] ?? null) !== $materials
                || ($zoneState['material_profile'] ?? null) !== $materialProfile) {
                $zoneState['available_materials'] = $materials;
                $zoneState['material_profile'] = $materialProfile;
                $zone['state'] = $zoneState;
                $hasChanged = true;
            }
            $updatedZones[$idx] = $zone;
        }

        if ($hasChanged || !empty($events)) {
            Log::info("[MaterialEvolutionEngine] Updated zones: " . count($updatedZones) . ", events: " . count($events));
            return new EngineResult(['zones' => $updatedZones], $events);
        }

        return EngineResult::empty();
    }

    /**
     * Derive a lightweight material identity for each zone.
     * This becomes the seed layer for future livelihood, settlement, and culture synthesis.
     *
     * @param array<string, mixed> $zoneState
     * @param array<string, float|int> $materials
     * @return array<string, mixed>
     */
    private function buildMaterialProfile(array $zoneState, array $materials, float $techLevel): array
    {
        $temperature = (float) ($zoneState['temperature'] ?? 0.5);
        $rainfall = (float) ($zoneState['rainfall'] ?? 0.5);
        $terrainType = (string) ($zoneState['terrain_type'] ?? 'plains');
        $biome = (string) ($zoneState['biome'] ?? 'grassland');
        $minerals = (float) ($zoneState['minerals'] ?? $zoneState['mineral_richness'] ?? 0.5);
        $materialStress = (float) ($zoneState['material_stress'] ?? 0.0);

        arsort($materials);
        $dominantMaterial = (string) (array_key_first($materials) ?? 'stone');

        $waterAccess = $rainfall >= 0.65 ? 'abundant'
            : ($rainfall >= 0.35 ? 'seasonal' : 'scarce');

        $resourceBias = $minerals >= 0.7 ? 'extractive'
            : ($rainfall >= 0.6 ? 'agrarian' : ($terrainType === 'ocean' ? 'maritime' : 'mixed'));

        $livelihood = $this->deriveLivelihood($terrainType, $biome, $rainfall, $minerals);
        $settlementStyle = $this->deriveSettlementStyle($terrainType, $waterAccess, $resourceBias);
        $constructionStyle = $this->deriveConstructionStyle($dominantMaterial, $terrainType, $rainfall);

        return [
            'dominant_material' => $dominantMaterial,
            'water_access' => $waterAccess,
            'resource_bias' => $resourceBias,
            'livelihood' => $livelihood,
            'settlement_style' => $settlementStyle,
            'construction_style' => $constructionStyle,
            'tech_band' => $this->deriveTechBand($techLevel),
            'climate_signature' => $this->deriveClimateSignature($temperature, $rainfall, $biome),
            'stress_outlook' => $materialStress >= 0.7 ? 'fragile' : ($materialStress >= 0.4 ? 'strained' : 'stable'),
        ];
    }

    private function deriveLivelihood(string $terrainType, string $biome, float $rainfall, float $minerals): string
    {
        if ($terrainType === 'ocean') {
            return 'fishing';
        }

        if ($minerals >= 0.75 && in_array($terrainType, ['mountains', 'peaks', 'highlands'], true)) {
            return 'mining';
        }

        if ($rainfall >= 0.65 && in_array($biome, ['temperate_forest', 'tropical_forest', 'grassland'], true)) {
            return 'farming';
        }

        if ($biome === 'desert' || $rainfall < 0.25) {
            return 'pastoral';
        }

        return 'foraging';
    }

    private function deriveSettlementStyle(string $terrainType, string $waterAccess, string $resourceBias): string
    {
        if ($terrainType === 'ocean') {
            return 'coastal_harbor';
        }

        if ($resourceBias === 'extractive') {
            return 'mining_outpost';
        }

        if ($waterAccess === 'abundant') {
            return 'river_settlement';
        }

        if (in_array($terrainType, ['mountains', 'peaks', 'highlands'], true)) {
            return 'hill_fort';
        }

        return 'agrarian_village';
    }

    private function deriveConstructionStyle(string $dominantMaterial, string $terrainType, float $rainfall): string
    {
        if (in_array($terrainType, ['mountains', 'peaks'], true) || in_array($dominantMaterial, ['stone', 'iron', 'steel', 'alloy'], true)) {
            return 'stonework';
        }

        if ($rainfall >= 0.6) {
            return 'timber_frame';
        }

        return 'earth_and_reed';
    }

    private function deriveTechBand(float $techLevel): string
    {
        return match (true) {
            $techLevel >= 0.9 => 'advanced',
            $techLevel >= 0.7 => 'industrializing',
            $techLevel >= 0.5 => 'metallurgical',
            $techLevel >= 0.3 => 'iron_age',
            $techLevel >= 0.2 => 'bronze_age',
            $techLevel >= 0.1 => 'copper_age',
            default => 'stone_age',
        };
    }

    private function deriveClimateSignature(float $temperature, float $rainfall, string $biome): string
    {
        if ($biome === 'desert') {
            return 'arid';
        }

        if ($biome === 'tundra') {
            return 'frozen';
        }

        if ($temperature >= 0.65 && $rainfall >= 0.6) {
            return 'humid_tropical';
        }

        if ($temperature <= 0.35) {
            return 'cool';
        }

        if ($rainfall >= 0.55) {
            return 'temperate_wet';
        }

        return 'temperate_dry';
    }
}
