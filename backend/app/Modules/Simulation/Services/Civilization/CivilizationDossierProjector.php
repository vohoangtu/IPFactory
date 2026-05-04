<?php

namespace App\Modules\Simulation\Services\Civilization;

use App\Modules\World\Models\Religion;
use App\Modules\World\Models\Universe;

class CivilizationDossierProjector
{
    public function __construct(
        protected EraDetector $eraDetector
    ) {}

    /**
     * @param array<string, mixed> $state
     * @param array<string, mixed> $materialIdentity
     * @param array<string, mixed> $cultureIdentity
     * @return array<string, mixed>
     */
    public function project(
        Universe $universe,
        array $state,
        array $materialIdentity = [],
        array $cultureIdentity = [],
        ?Religion $dominantReligion = null,
    ): array {
        $civilization = (array) ($state['civilization'] ?? []);
        $settlements = array_values(array_filter((array) ($civilization['settlements'] ?? [])));
        $zones = array_values(array_filter((array) ($state['zones'] ?? []), fn ($zone) => is_array($zone)));

        $politics = (array) ($civilization['politics'] ?? []);
        $market = (array) data_get($civilization, 'economy.market', []);

        return [
            'identity' => [
                'civilization_name' => $universe->name ?: "Universe {$universe->id}",
                'governance_type' => (string) ($politics['governance_type'] ?? 'proto-social'),
                'phase' => (string) ($civilization['phase'] ?? $civilization['long_cycle']['phase'] ?? 'EMERGENCE'),
                'primary_material' => (string) ($materialIdentity['primary_material'] ?? 'unknown'),
                'primary_livelihood' => (string) ($materialIdentity['primary_livelihood'] ?? 'unknown'),
                'dominant_culture_group' => (string) ($cultureIdentity['dominant_group'] ?? 'ungrouped'),
                'dominant_religion' => $dominantReligion ? $dominantReligion->name : null,
                'era' => $this->eraDetector->detect($universe, $materialIdentity, $cultureIdentity),
            ],
            'governance' => [
                'stability' => round((float) ($politics['stability'] ?? 0), 4),
                'legitimacy' => round((float) ($politics['legitimacy'] ?? 0), 4),
                'elite_power' => round((float) ($politics['elite_power'] ?? 0), 4),
                'total_population' => (int) ($politics['total_population'] ?? data_get($civilization, 'demographic.total_population', 0)),
            ],
            'economy' => [
                'prosperity_index' => round((float) ($civilization['prosperity_index'] ?? data_get($civilization, 'long_cycle.prosperity_index', 0)), 4),
                'prosperity_trend' => round((float) ($civilization['prosperity_trend'] ?? data_get($civilization, 'long_cycle.prosperity_trend', 0)), 4),
                'food_price' => round((float) ($market['food_price'] ?? 0), 4),
                'market_surplus' => round((float) ($market['surplus'] ?? 0), 4),
                'resource_biases' => $materialIdentity['resource_biases'] ?? [],
            ],
            'settlement_pattern' => [
                'count' => count($settlements),
                'styles' => $materialIdentity['settlement_styles'] ?? [],
                'sample' => $this->summarizeSettlements($settlements),
            ],
            'core_regions' => $this->projectZoneProfiles($zones),
            'belief_order' => [
                'dominant_memes' => $cultureIdentity['dominant_memes'] ?? [],
                'average_cohesion' => round((float) ($cultureIdentity['average_cohesion'] ?? 0), 4),
                'religion_followers' => (int) ($dominantReligion?->followers ?? 0),
                'holy_sites' => $dominantReligion?->holy_sites ?? [],
            ],
        ];
    }

    /**
     * @param array<int, mixed> $settlements
     * @return array<int, array<string, mixed>>
     */
    protected function summarizeSettlements(array $settlements): array
    {
        $rows = [];
        foreach (array_slice($settlements, 0, 6) as $settlement) {
            if (is_array($settlement)) {
                $rows[] = [
                    'name' => $settlement['name'] ?? 'Unnamed settlement',
                    'zone_id' => $settlement['zone_id'] ?? null,
                    'kind' => $settlement['kind'] ?? $settlement['type'] ?? 'settlement',
                ];
                continue;
            }

            $rows[] = [
                'name' => (string) $settlement,
                'zone_id' => null,
                'kind' => 'settlement',
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, array<string, mixed>> $zones
     * @return array<int, array<string, mixed>>
     */
    protected function projectZoneProfiles(array $zones): array
    {
        $profiles = [];

        foreach ($zones as $zone) {
            $zoneState = (array) ($zone['state'] ?? []);
            $material = (array) ($zoneState['material_profile'] ?? []);
            $culture = (array) ($zoneState['culture_profile'] ?? []);

            // Heuristic Fallbacks for legacy/un-simulated snapshots
            if ($material === []) {
                $material = app(MaterialIdentityProjector::class)->deriveHeuristicProfile($zone);
            }
            if ($culture === []) {
                $culture = app(CultureIdentityProjector::class)->deriveHeuristicProfile($zone);
            }

            $profiles[] = [
                'zone_id' => $zone['id'] ?? null,
                'name' => $zone['name'] ?? ('Zone ' . ($zone['id'] ?? '?')),
                'stress' => round((float) ($zoneState['stress'] ?? 0), 4),
                'dominant_material' => $material['dominant_material'] ?? 'unknown',
                'livelihood' => $material['livelihood'] ?? 'unknown',
                'settlement_style' => $material['settlement_style'] ?? 'unknown',
                'climate_signature' => $material['climate_signature'] ?? 'unknown',
                'culture_group' => $culture['dominant_group'] ?? 'ungrouped',
                'cohesion' => round((float) ($culture['cohesion'] ?? 0), 4),
                'cultural_artifacts' => $culture['cultural_artifacts'] ?? [],
            ];
        }

        usort($profiles, fn (array $left, array $right) => ($right['cohesion'] <=> $left['cohesion']) ?: ($left['stress'] <=> $right['stress']));

        return array_slice($profiles, 0, 8);
    }
}
