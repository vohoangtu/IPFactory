<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Listeners;

use App\Modules\Intelligence\Models\Actor;
use App\Modules\Intelligence\Services\AI\MemoryService;
use App\Modules\Simulation\Core\Events\ActorDiedEvent;
use Illuminate\Support\Facades\Log;

class ActorDiedEventListener
{
    public function __construct(
        private readonly ?MemoryService $memoryService = null,
    ) {}

    public function handle(ActorDiedEvent $event): void
    {
        $actorId = $event->payload['actor_id'] ?? null;
        if ($actorId === null) {
            return;
        }

        $universeId = $event->universeId;
        $actor = Actor::find($actorId);

        Log::info("Intelligence: Processing death of actor {$actorId} in Universe {$universeId}.");

        // 1. Clean up active decision loops for the deceased actor.
        Log::info("Intelligence: Cleaning up active decision loops for deceased actor {$actorId}.");

        // 2. Save final will / last memory into AI Memory.
        if ($this->memoryService !== null && $actor !== null) {
            $this->archiveFinalMemory($actor, $event);
        }
    }

    /**
     * Archive the actor's final will and last memory to the AI Memory store.
     *
     * The final memory captures:
     * - The actor's name and archetype.
     * - Key traits and metrics at time of death.
     * - The tick of birth and death (lifespan).
     * - A "last will" if the actor had heroic designation.
     *
     * On failure we re-throw so the surrounding queue job (or synchronous
     * listener pipeline) surfaces a hard error instead of silently swallowing
     * it. A silent failure here would leave AscensionAction free to produce
     * LegendaryAgent rows for a hero whose memorial was never written, and
     * the system would diverge from its own narrative state.
     */
    private function archiveFinalMemory(Actor $actor, ActorDiedEvent $event): void
    {
        $universeId = $event->universeId;
        $tick = $event->tick;
        $actorId = $actor->id;

        // Schema guard: if a migration is missing the heroic columns, surface
        // it loudly rather than throwing deep inside the memory-write path
        // where the root cause is opaque.
        if (! array_key_exists('is_heroic', $actor->getAttributes())) {
            throw new \RuntimeException(
                "Actor model is missing the 'is_heroic' column. " .
                "Did migration 2026_03_12_200509_add_heroic_fields_to_actors_table run?"
            );
        }

        $traits = is_array($actor->traits) ? $actor->traits : (json_decode($actor->traits ?? '[]', true) ?? []);
        $metrics = is_array($actor->metrics) ? $actor->metrics : (json_decode($actor->metrics ?? '{}', true) ?? []);
        $keys = array_keys($metrics);
        $metricSummary = implode(', ', array_slice(array_map(
            fn($k) => "{$k}:{$metrics[$k]}",
            $keys
        ), 0, 5));

        $lifespan = ($actor->death_tick ?? $tick) - ($actor->birth_tick ?? 0);
        $archetype = $actor->archetype ?? 'unknown';

        $content = "FINAL MEMORY: Actor {$actor->name} (ID:{$actorId}, archetype:{$archetype}) died at tick {$tick}.\n"
                 . "Lifespan: {$lifespan} ticks. Generation: " . ($actor->generation ?? 0) . ".\n"
                 . "Key metrics at death: {$metricSummary}.\n"
                 . "Traits: " . implode(', ', array_slice($traits, 0, 8)) . ".\n";

        $isHeroic = (bool) $actor->is_heroic;
        if ($isHeroic) {
            $content .= "HEROIC WILL: The legacy of {$actor->name} echoes through the universe. "
                       . "Their deeds shall inspire future generations. "
                       . "Heroic type: " . ($actor->heroic_type ?? 'unknown') . ".\n";
        } else {
            $content .= "FINAL WILL: " . ($actor->name) . " has passed. "
                       . "Their memory fades into the cosmic fabric.\n";
        }

        // No try/catch — let exceptions bubble up. The listener is invoked
        // synchronously from the simulation tick pipeline; a hard failure
        // here must fail the tick so an operator notices the divergence
        // rather than the simulation silently producing a LegendaryAgent
        // for a hero with no archived memorial.
        $this->memoryService->write(
            $universeId,
            'actor',
            'episode',
            $content,
            [$actor->name, $archetype, 'death', 'final_memory'],
            [
                'source' => 'ActorDiedEventListener',
                'importance' => $isHeroic ? 9 : 4,
                'ttl_days' => $isHeroic ? 365 : 30,
            ]
        );
        Log::info("Intelligence: Final memory archived for actor {$actorId}.");
    }
}
