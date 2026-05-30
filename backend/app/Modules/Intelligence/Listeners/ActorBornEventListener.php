<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Listeners;

use App\Modules\Intelligence\Models\Actor;
use App\Modules\Simulation\Core\Events\ActorBornEvent;
use App\Modules\Intelligence\Services\ActorRegistry;
use Illuminate\Support\Facades\Log;

class ActorBornEventListener
{
    public function __construct(
        private readonly ActorRegistry $actorRegistry
    ) {}

    public function handle(ActorBornEvent $event): void
    {
        $payload = $event->payload;
        $childId = $payload['child_id'] ?? null;
        $universeId = $event->universeId;

        if ($childId === null) {
            return;
        }

        Log::info("Intelligence: Initializing newborn actor {$childId} in Universe {$universeId}.");

        // 1. Determine archetype: inherit from parent with mutation chance, or random fallback.
        $child = Actor::find($childId);
        if ($child === null) {
            return;
        }

        $archetype = $this->resolveArchetype($payload, $child);
        Log::debug("Intelligence: Actor {$childId} assigned archetype: {$archetype}.");

        // 2. Inherit beliefs from parents' traits.
        $this->inheritParentBeliefs($payload, $child, $universeId);
    }

    /**
     * Resolve the child's archetype:
     * - If a parent exists, inherit the parent's archetype with 10% mutation chance.
     * - Otherwise assign a weighted random archetype.
     */
    private function resolveArchetype(array $payload, Actor $child): string
    {
        $parentIds = [];
        if (isset($payload['parent_ids']) && is_array($payload['parent_ids'])) {
            $parentIds = $payload['parent_ids'];
        } elseif (isset($payload['parent_id'])) {
            $parentIds = [$payload['parent_id']];
        }

        $parents = ! empty($parentIds) ? Actor::whereIn('id', $parentIds)->get() : collect();
        $dominantParent = $parents->first();

        if ($dominantParent !== null && $dominantParent->archetype) {
            // 10% chance of random mutation.
            if (mt_rand(0, 100) < 10) {
                return $this->randomArchetype();
            }
            return $dominantParent->archetype;
        }

        return $this->randomArchetype();
    }

    /**
     * Inherit beliefs from parent actors.
     *
     * For each parent, copy their beliefs to the child unless the child
     * already has the same belief (dedup by name).
     */
    private function inheritParentBeliefs(array $payload, Actor $child, int $universeId): void
    {
        $parentIds = [];
        if (isset($payload['parent_ids']) && is_array($payload['parent_ids'])) {
            $parentIds = $payload['parent_ids'];
        } elseif (isset($payload['parent_id'])) {
            $parentIds = [$payload['parent_id']];
        }

        if (empty($parentIds)) {
            return;
        }

        try {
            $parents = Actor::whereIn('id', $parentIds)->get();
            $existingBeliefNames = $child->beliefs()->pluck('name')->toArray();
            $inheritedCount = 0;

            foreach ($parents as $parent) {
                $parentBeliefs = $parent->beliefs()->get();
                foreach ($parentBeliefs as $belief) {
                    if (! in_array($belief->name, $existingBeliefNames, true)) {
                        $child->beliefs()->attach($belief->id, ['alignment' => 0.5]);
                        $existingBeliefNames[] = $belief->name;
                        $inheritedCount++;
                    }
                }
            }

            if ($inheritedCount > 0) {
                Log::debug("Intelligence: Actor {$child->id} inherited {$inheritedCount} beliefs from parents.");
            }

            // Also inherit a lightweight trait-weight blend from the first parent's beliefs.
            if ($parents->isNotEmpty()) {
                $firstParentTraits = $parents->first()->traits;
                if (is_array($firstParentTraits) || is_string($firstParentTraits)) {
                    $traits = is_array($firstParentTraits) ? $firstParentTraits : (json_decode($firstParentTraits, true) ?? []);
                    if (! empty($traits)) {
                        $blended = [];
                        foreach ($traits as $trait => $value) {
                            $blended[$trait] = round((float) $value * 0.7 + (mt_rand(0, 50) / 100), 2);
                        }
                        $child->forceFill(['traits' => $blended])->save();
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Intelligence: Failed to inherit beliefs for actor {$child->id}: " . $e->getMessage());
        }
    }

    private function randomArchetype(): string
    {
        $archetypes = ['tribal_leader', 'villager', 'shaman', 'warrior', 'rogue_ai', 'artisan', 'merchant', 'nomad'];
        return $archetypes[array_rand($archetypes)];
    }
}
