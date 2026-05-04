<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Core\Runtime\State;

/**
 * WorldStateAccessors – Trait containing complex query/accessor methods for WorldState.
 *
 * Extracted from WorldState to reduce class size while maintaining backward compatibility.
 * These methods compute derived/aggregated values from the state data.
 */
trait WorldStateAccessors
{
    /**
     * Step 3: Field & Layer Harmonization (§Field Simulation Architecture)
     * Maps existing CFT fields to the 4 Layers of Reality.
     */
    public function getPhysicalLayer(): array
    {
        $fields = $this->getFields();
        return [
            'state' => [
                'ecosystem' => $this->getEcosystem(),
                'planetary' => $this->getPlanetary(),
                'cosmic' => $this->getCosmic(),
                'resources' => $this->getResourceEntities(),
                'zones' => $this->getZones(),
            ],
            'pressures' => [
                'survival_pressure' => (float)($fields['survival'] ?? 0.0),
                'resource_scarcity' => (float)(isset($fields['wealth']) ? (1 - $fields['wealth']) : 0.5),
                'natural_entropy' => (float)($fields['entropy'] ?? 0.0),
                'physical_fear' => (float)($fields['fear'] ?? 0.0),
            ]
        ];
    }

    public function getLifeLayer(): array
    {
        $fields = $this->getFields();
        return [
            'state' => [
                'actors' => $this->getActorEntities(),
                'zones' => $this->getZones(),
            ],
            'pressures' => [
                'metabolic_stress' => (float)($fields['survival'] ?? 0.0),
                'environmental_instability' => (float)($this->get('stability_index', 1.0)),
            ]
        ];
    }

    public function getSocialLayer(): array
    {
        $fields = $this->getFields();
        return [
            'state' => [
                'institutions' => $this->getInstitutionalEntities(),
                'civilizations' => $this->get('civilizations', []),
                'resources' => $this->getResourceEntities(), // Required by PowerSystem
                'actors' => $this->getActorEntities(), // Required by AllianceSystem
                'zones' => $this->getZones(),
            ],
            'pressures' => [
                'war_pressure' => (float)($fields['power'] ?? 0.0),
                'authority_intensity' => (float)($fields['authority'] ?? 0.0),
                'social_order' => (float)($fields['order'] ?? 1.0),
            ]
        ];
    }

    public function getNarrativeLayer(): array
    {
        $fields = $this->getFields();
        return [
            'state' => [
                'ideas' => $this->getIdeaEntities(),
                'myths' => $this->get('meta.active_myths', []),
                'narratives' => $this->get('meta.active_narratives', []),
                'chronicles' => $this->getRecentChronicles(),
                'persistent_myths' => $this->get('meta.persistent_myths', []),
            ],
            'pressures' => [
                'collective_meaning' => (float)($fields['meaning'] ?? 0.5),
                'knowledge_diffusion' => (float)($fields['knowledge'] ?? 0.0),
            ]
        ];
    }

    public function getMythicLayer(): array
    {
        $fields = $this->getFields();
        return [
            'state' => [
                'hyperspace' => $this->getHyperspaceVector(),
                'nested_realities' => $this->getNestedRealities(),
                'supreme_entities' => $this->getSupremeEntities(),
                'resources' => $this->getResourceEntities(), // Required by ConflictSystem
                'entropy' => $this->getEntropy(),
                'stability_index' => $this->getStabilityIndex(),
                'ideas' => $this->getIdeaEntities(), // Required by MythCreationSystem
            ],
            'pressures' => [
                'field_resonance' => (float)($fields['resonance'] ?? 0.0),
                'mandate_strength' => (float)($this->get('meta.mandate_of_heaven', 0.5)),
            ]
        ];
    }

    /**
     * V9: Sync internal Agent entities to their respective Zones.
     * Use this to rebuild zone.agents[] from the global actorEntities source.
     */
    public function syncAgentsToZones(): void
    {
        $zones = $this->getZones();
        $agents = $this->getActorEntities();

        $actorTable = [
            'ids' => [],
            'zone_ids' => [],
            'hunger' => [],
            'energy' => [],
            'fear' => [],
            'traits_mask' => [],
            'memes_mask' => [],
            'heroic_type' => [],
            'lineage_id' => [],
            'current_node' => [],
        ];

        // Group alive agents by zone
        $agentGroups = [];
        foreach ($agents as $agent) {
            if ($agent->isAlive) {
                $e = (float)($agent->metrics['energy'] ?? $agent->energy ?? 100);
                $h = (float)($agent->hunger ?? 0.0);

                $agentGroups[$agent->zone_id][] = [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'hunger' => $h,
                    'energy' => $e,
                ];

                // Build SOA actor_table
                $actorTable['ids'][] = (int)$agent->id;
                $actorTable['zone_ids'][] = (int)$agent->zone_id;
                $actorTable['hunger'][] = $h;
                $actorTable['energy'][] = $e;
                $actorTable['fear'][] = (float)($agent->fear ?? 0.0);
                $actorTable['traits_mask'][] = (int)($agent->metrics['traits_mask'] ?? 0);
                $actorTable['memes_mask'][] = (int)($agent->metrics['memes_mask'] ?? 0);
                $actorTable['heroic_type'][] = (int)($agent->metrics['heroic_type'] ?? 0);
                $actorTable['lineage_id'][] = (int)($agent->metrics['lineage_id'] ?? 0);
                $actorTable['current_node'][] = (int)($agent->metrics['behavior_state'] ?? 0);
            }
        }

        // Update zone structures
        foreach ($zones as &$zone) {
            $zoneId = $zone['id'];
            $zone['agents'] = $agentGroups[$zoneId] ?? [];
            $zone['population_proxy'] = count($zone['agents']);
        }

        $this->setZones($zones);
        $this->set('actor_table', $actorTable);
        $this->set('population_proxy', count($actorTable['ids']));

        \Illuminate\Support\Facades\Log::debug("WorldState: Synced " . count($actorTable['ids']) . " agents into actor_table.");
    }

    /**
     * Phase 65: Project higher-dimensional state into 3D dashboard compatible fields.
     */
    public function projectTo3D(): void
    {
        if (empty($this->hyperspace_vector)) {
            return;
        }

        // Logic dự phóng từ 11D/22D xuống các trường 3D cơ bản (Power, Knowledge...)
        // Giả sử các chiều từ 0-9 của hyperspace tương ứng với các trường trong CFT.
        $dimensionMap = ['survival', 'power', 'wealth', 'knowledge', 'meaning', 'authority', 'fear', 'order', 'entropy', 'resonance'];

        $fields = $this->getFields();
        foreach ($dimensionMap as $index => $fieldName) {
            if (isset($this->hyperspace_vector[$index])) {
                $fields[$fieldName] = ($fields[$fieldName] ?? 0.0) * 0.7 + $this->hyperspace_vector[$index] * 0.3;
            }
        }
        $this->setFields($fields);
    }

    /**
     * Get zone pressures from a zone array.
     */
    public static function getZonePressures(array $zone): array
    {
        $state = $zone['state'] ?? [];
        $pressures = $state['pressures'] ?? [];

        return [
            'war_pressure' => (float) ($pressures['war'] ?? ($state['war_pressure'] ?? 0)),
            'economic_pressure' => (float) ($pressures['economic'] ?? ($state['economic_pressure'] ?? 0)),
            'religious_pressure' => (float) ($pressures['religious'] ?? ($state['religious_pressure'] ?? 0)),
            'migration_pressure' => (float) ($pressures['migration'] ?? ($state['migration_pressure'] ?? 0)),
            'innovation_pressure' => (float) ($pressures['innovation'] ?? ($state['innovation_pressure'] ?? 0)),
        ];
    }

    public static function defaultZonePressureKeys(): array
    {
        return [
            'war_pressure' => 0.0,
            'economic_pressure' => 0.0,
            'religious_pressure' => 0.0,
            'migration_pressure' => 0.0,
            'innovation_pressure' => 0.0,
        ];
    }

    public static function defaultZonePopulationKeys(): array
    {
        return [
            'population_proxy' => 0.5,
        ];
    }

    public static function getZonePopulationProxy(array $zone): float
    {
        $state = $zone['state'] ?? [];
        return (float) ($state['population_proxy'] ?? 0.5);
    }
}
