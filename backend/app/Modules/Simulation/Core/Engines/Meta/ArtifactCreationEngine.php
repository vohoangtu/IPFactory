<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Intelligence\Models\Actor;
use App\Modules\Intelligence\Models\ActorEvent;
use App\Modules\Narrative\Models\Artifact;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Core\Support\SimulationRandom;

/**
 * ArtifactCreationEngine — Phase 3.
 * When actor has high creativity + cognition and rolled an artifact-eligible action,
 * with probability create an artifact record and Chronicle (artifact_created).
 */
class ArtifactCreationEngine
{
    public function tryCreate(Actor $actor, Universe $universe, int $tick, string $action, SimulationRandom $rng): ?Artifact
    {
        $config = config('worldos.artifact', []);
        $actionToType = $config['action_to_type'] ?? ['write' => 'book', 'create_religion' => 'religion', 'build' => 'architecture'];
        $artifactType = $actionToType[$action] ?? 'book';

        $capabilities = $actor->capabilities ?? [];
        $creativity = (float) ($capabilities['creativity'] ?? 0);
        $creativityThreshold = (float) ($config['creativity_threshold'] ?? 0.4);
        if ($creativity < $creativityThreshold) {
            return null;
        }

        $traits = $actor->traits ?? [];
        $cognition = 0.0;
        foreach ([7, 8, 9, 10] as $i) {
            $cognition += (float) ($traits[$i] ?? 0);
        }
        $cognition /= 4.0;
        $cognitionThreshold = (float) ($config['cognition_threshold'] ?? 0.35);
        if ($cognition < $cognitionThreshold) {
            return null;
        }

        $probability = (float) ($config['create_probability'] ?? 0.25);
        if ($rng->nextFloat() >= $probability) {
            return null;
        }

        $state = (array) ($universe->state_vector ?? []);
        $culture = (string) ($state['dominant_culture'] ?? $state['ideology'] ?? 'unknown');
        $theme = $this->themeForType($artifactType, $actor->archetype ?? 'philosopher');

        $impactScore = round(0.3 + 0.5 * $creativity + 0.2 * $cognition, 4);
        $impactScore = min(1.0, max(0.1, $impactScore));

        $artifact = Artifact::create([
            'universe_id' => $universe->id,
            'creator_actor_id' => $actor->id,
            'institution_id' => null,
            'artifact_type' => $artifactType,
            'title' => $theme . ' (T' . $tick . ')',
            'theme' => $theme,
            'culture' => $culture,
            'tick_created' => $tick,
            'impact_score' => $impactScore,
            'metadata' => ['action' => $action, 'archetype' => $actor->archetype],
        ]);

        $influence = (float) (($actor->metrics ?? [])['influence'] ?? 0.5);
        $importance = round($influence * $impactScore, 4);

        Chronicle::create([
            'universe_id' => $universe->id,
            'actor_id' => $actor->id,
            'from_tick' => $tick,
            'to_tick' => $tick,
            'type' => 'artifact_created',
            'content' => null,
            'importance' => $importance,
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => "Artifact created: {$artifact->artifact_type} by {$actor->name}.",
                'artifact_id' => $artifact->id,
                'title' => $artifact->title,
            ],
        ]);

        ActorEvent::create([
            'actor_id' => $actor->id,
            'tick' => $tick,
            'event_type' => 'artifact_created',
            'context' => ['artifact_id' => $artifact->id, 'artifact_type' => $artifactType],
        ]);

        return $artifact;
    }

    private function themeForType(string $type, string $archetype): string
    {
        $themes = [
            'book' => 'Treatise on ' . $archetype,
            'religion' => 'Doctrine of the ' . $archetype,
            'architecture' => 'Monument of the Age',
            'poem' => 'Epic of the People',
            'painting' => 'Vision of the World',
            'law' => 'Code of Justice',
            'theory' => 'Theory of Nature',
            'music' => 'Harmony of the Spheres',
        ];
        return $themes[$type] ?? 'Creation';
    }
}

