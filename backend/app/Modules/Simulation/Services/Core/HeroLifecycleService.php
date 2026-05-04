<?php

namespace App\Modules\Simulation\Services\Core;

use App\Modules\Intelligence\Models\Actor;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\World\Models\Universe;

/**
 * HeroLifecycleService — Phase 7.
 * Updates hero_stage for actors linked to SupremeEntity: latent → awakening → rising → peak → legacy → myth.
 */
class HeroLifecycleService
{
    public function process(Universe $universe, int $tick): void
    {
        $config = config('worldos.hero_lifecycle', []);
        $influenceRising = (float) ($config['influence_rising'] ?? 30);
        $influencePeak = (float) ($config['influence_peak'] ?? 70);
        $mythTicksAfterDeath = (int) ($config['myth_ticks_after_death'] ?? 100);

        Actor::where('universe_id', $universe->id)
            ->whereHas('supremeEntity')
            ->get()
            ->each(function (Actor $actor) use ($tick, $influenceRising, $influencePeak, $mythTicksAfterDeath) {
                $stage = $actor->hero_stage ?? 'latent';
                $influence = (float) (($actor->metrics ?? [])['influence'] ?? 0);
                $deathTick = $actor->death_tick;

                if ($deathTick !== null) {
                    if ($tick >= $deathTick + $mythTicksAfterDeath && $stage !== 'myth') {
                        $actor->hero_stage = 'myth';
                        $actor->save();
                        $this->chronicleStage($actor->universe_id, $actor->id, $tick, 'hero_myth');
                    } elseif ($stage === 'peak' || $stage === 'rising' || $stage === 'awakening') {
                        $actor->hero_stage = 'legacy';
                        $actor->save();
                        $this->chronicleStage($actor->universe_id, $actor->id, $tick, 'hero_legacy');
                    }
                    return;
                }

                $newStage = $stage;
                if ($stage === 'latent') {
                    $hasArtifact = \App\Modules\Intelligence\Models\ActorEvent::where('actor_id', $actor->id)->where('event_type', 'artifact_created')->exists();
                    if ($hasArtifact) {
                        $newStage = 'awakening';
                        $this->chronicleStage($actor->universe_id, $actor->id, $tick, 'hero_awakening');
                    }
                } elseif ($stage === 'awakening' && $influence >= $influenceRising) {
                    $newStage = 'rising';
                    $this->chronicleStage($actor->universe_id, $actor->id, $tick, 'hero_rising');
                } elseif ($stage === 'rising' && $influence >= $influencePeak) {
                    $newStage = 'peak';
                    $this->chronicleStage($actor->universe_id, $actor->id, $tick, 'hero_peak');
                }

                if ($newStage !== $stage) {
                    $actor->hero_stage = $newStage;
                    $actor->save();
                }
            });
    }

    private function chronicleStage(int $universeId, int $actorId, int $tick, string $type): void
    {
        Chronicle::create([
            'universe_id' => $universeId,
            'actor_id' => $actorId,
            'from_tick' => $tick,
            'to_tick' => $tick,
            'type' => $type,
            'importance' => 0.5,
            'raw_payload' => ['action' => 'legacy_event', 'description' => "Hero stage: {$type}."],
        ]);
    }
}

