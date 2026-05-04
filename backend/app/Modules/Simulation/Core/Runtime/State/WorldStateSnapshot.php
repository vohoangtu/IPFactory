<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Core\Runtime\State;

/**
 * WorldStateSnapshot – Handles snapshot/serialization operations for WorldState.
 *
 * Extracted from WorldState to separate serialization concerns.
 */
class WorldStateSnapshot
{
    /**
     * Create a WorldState from an array.
     */
    public static function fromArray(array $data): WorldState
    {
        return new WorldState($data);
    }

    /**
     * Serialize a WorldState to an array.
     *
     * V9: Enforce Single Source of Truth for Agent Visibility
     */
    public static function toArray(WorldState $state): array
    {
        $data = $state->getData();

        // V9: Enforce Single Source of Truth for Agent Visibility
        if (!empty($state->getActorEntities())) {
            $alive = array_filter($state->getActorEntities(), fn ($a) => $a->isAlive);

            // Limit actor_table size for snapshot performance, but maintain real count
            $data['agents'] = array_map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'hunger' => round($a->hunger ?? 0.0, 2),
                'energy' => round((float)($a->metrics['energy'] ?? $a->energy ?? 100), 2),
                'zone_id' => $a->zone_id,
                'is_alive' => $a->isAlive,
            ], array_slice($alive, 0, 50));

            $data['total_population'] = count($alive);
        }

        return $data;
    }

    /**
     * Phase 4: Create an immutable snapshot copy of the current state for rollback purposes.
     * Use before a risky resolution step so state can be restored on failure.
     */
    public static function snapshot(WorldState $state): WorldState
    {
        $copy = new WorldState(
            data: $state->getData(),
            neighboring_realities: $state->neighboring_realities,
            legacy_data: $state->legacy_data,
            hyperspace_vector: $state->hyperspace_vector,
            nested_realities: $state->nested_realities
        );
        $copy->setActorEntities($state->getActorEntities());
        $copy->setInstitutionalEntities($state->getInstitutionalEntities());
        $copy->setResourceEntities($state->getResourceEntities());
        $copy->setIdeaEntities($state->getIdeaEntities());
        $copy->setRecentChronicles($state->getRecentChronicles());
        $copy->setSupremeEntities($state->getSupremeEntities());
        $copy->setIsObserved($state->isObserved());
        return $copy;
    }

    /**
     * Phase 4: Restore internal state from a previously taken snapshot.
     */
    public static function restoreFrom(WorldState $target, WorldState $snapshot): void
    {
        $target->setData($snapshot->getData());
        $target->neighboring_realities  = $snapshot->neighboring_realities;
        $target->legacy_data            = $snapshot->legacy_data;
        $target->hyperspace_vector      = $snapshot->hyperspace_vector;
        $target->nested_realities       = $snapshot->nested_realities;
        $target->setActorEntities($snapshot->getActorEntities());
        $target->setInstitutionalEntities($snapshot->getInstitutionalEntities());
        $target->setResourceEntities($snapshot->getResourceEntities());
        $target->setIdeaEntities($snapshot->getIdeaEntities());
        $target->setRecentChronicles($snapshot->getRecentChronicles());
        $target->setSupremeEntities($snapshot->getSupremeEntities());
        $target->setIsObserved($snapshot->isObserved());
    }

    /**
     * Compute the difference between current data and an original context array.
     * Returns a flat array of 'dot.key' => value for scalar changes,
     * and special handling for zones/agents if needed.
     */
    public static function getDiff(WorldState $state, array $originalData): array
    {
        $diff = [];
        $currentData = $state->getData();

        // 1. Check top-level numeric fields (entropy, stability, etc.)
        $monitoredFields = ['entropy', 'stability_index', 'civilizationComplexity', 'logic_density'];
        foreach ($monitoredFields as $field) {
            $old = (float)($originalData[$field] ?? 0);
            $new = (float)($currentData[$field] ?? 0);
            if (abs($old - $new) > 1e-6) {
                $diff[$field] = $new;
            }
        }

        // 2. Check Global Fields (CFT)
        $oldFields = $originalData['fields'] ?? [];
        $newFields = $currentData['fields'] ?? [];
        foreach ($newFields as $key => $val) {
            $oldVal = $oldFields[$key] ?? null;
            if ($oldVal !== $val) {
                $diff["fields.{$key}"] = $val;
            }
        }

        // 3. Check Zones (Macro-resource changes)
        // Note: Full zone diff might be heavy, but we need it for LivingWorldEngine
        if (isset($currentData['zones'])) {
            $diff['zones'] = $currentData['zones'];
        }

        return $diff;
    }
}
