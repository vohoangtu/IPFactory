<?php

namespace App\Modules\Simulation\Services\Core;

use App\Contracts\SimulationEngineClientInterface;

/**
 * Stub wrapper around the HTTP simulation engine client.
 * Replaced by SimulationEngineClientInterface — kept for backward compat with VectorizedActorStage.
 */
class FfiActorEngine
{
    public function __construct(
        protected SimulationEngineClientInterface $client
    ) {}

    public function processActorsSoa(
        int $tick, array $ids, array $zoneIds, array $hunger, array $energy,
        array $fear, array $trauma, array $heroicTypes, array $lineageIds, array $memes,
        array $traitsMatrix = [], array $behaviorStates = [], array $behaviorGraphs = [],
        array $archetypes = [], array $socialGraph = [], array $activeSagas = [],
        array $factionIds = [], array $factionLoyalty = []
    ): array {
        return $this->client->processActorsSoa(
            $tick, $ids, $zoneIds, $hunger, $energy, $fear, $trauma,
            $heroicTypes, $lineageIds, $memes, $traitsMatrix, $behaviorStates,
            $behaviorGraphs, $archetypes, $socialGraph, $activeSagas, $factionIds, $factionLoyalty
        );
    }

    public function processFieldsV7(
        array $fields, array $neighborCounts, array $neighborOffsets,
        array $neighbors, float $diffusionRate, float $preservationRate
    ): array {
        return $this->client->processFieldsV7($fields, $neighborCounts, $neighborOffsets, $neighbors, $diffusionRate, $preservationRate);
    }

    public function calculateVocationAlignment(array $actorIds, array $vocationIds, array $skillProfiles): array
    {
        return [];
    }

    public function getCombinedGravity(array $actorIds, array $posX, array $posY, array $masses): array
    {
        return [];
    }
}
