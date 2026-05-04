<?php
namespace App\Modules\Simulation\Core\Engines\Social;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;

class CultureEngine implements EngineInterface {
    use DefaultSimulationEnginePhase;
    public function name(): string { return 'CultureEngine'; }
    public function phase(): string { return 'culture'; }
    public function priority(): int { return 35; }
    public function tickRate(): int { return 1; }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $this->runWithState($state, $ctx->getTick());
        return EngineResult::empty();
    }

    public function runWithState(WorldState $state, int $tick): void
    {
        app(\App\Modules\Intelligence\Services\CultureEngine::class)->runWithState($state, $tick);

        $zones = $state->getZones();
        $actors = array_values(array_filter($state->getActorEntities(), fn ($actor) => $actor->isAlive));

        if ($zones === [] || $actors === []) {
            return;
        }

        $actorsByZone = [];
        foreach ($actors as $actor) {
            $zoneId = (int) ($actor->zone_id ?? -1);
            if ($zoneId < 0) {
                continue;
            }

            $actorsByZone[$zoneId][] = $actor;
        }

        $updated = false;

        foreach ($zones as &$zone) {
            $zoneId = (int) ($zone['id'] ?? -1);
            $zoneActors = $actorsByZone[$zoneId] ?? [];

            if ($zoneActors === []) {
                continue;
            }

            $profile = $this->buildZoneCultureProfile($zoneActors, $zone);
            $zone['state'] ??= [];

            if (($zone['state']['culture_profile'] ?? null) !== $profile) {
                $zone['state']['culture_profile'] = $profile;
                $zone['state']['culture_group'] = $profile['dominant_group'];
                $updated = true;
            }
        }

        unset($zone);

        if ($updated) {
            $state->setZones($zones);
            Log::debug("SimulationCore CultureEngine: tick {$tick}, updated zone culture profiles");
        }
    }

    /**
     * @param array<int, object> $actors
     * @param array<string, mixed> $zone
     * @return array<string, mixed>
     */
    private function buildZoneCultureProfile(array $actors, array $zone): array
    {
        $memeTotals = array_fill_keys(\App\Modules\Intelligence\Services\CultureEngine::MEME_DIMENSIONS, 0.0);
        $groupCounts = [];

        foreach ($actors as $actor) {
            $culture = \App\Modules\Intelligence\Services\CultureEngine::getCultureForActor((array) ($actor->metrics ?? []));
            foreach ($culture as $dimension => $value) {
                $memeTotals[$dimension] += (float) $value;
            }

            $group = (string) (($actor->metrics['culture_group'] ?? '') ?: 'ungrouped');
            $groupCounts[$group] = ($groupCounts[$group] ?? 0) + 1;

            // Phase 3: Ensure actor metrics carry the culture group for tracking
            if (($actor->metrics['culture_group'] ?? null) !== $group) {
                $actor->metrics['culture_group'] = $group;
            }
        }

        $count = max(1, count($actors));
        $averages = [];
        foreach ($memeTotals as $dimension => $total) {
            $averages[$dimension] = round($total / $count, 4);
        }

        arsort($averages);
        arsort($groupCounts);

        $profile = [
            'dominant_group' => (string) (array_key_first($groupCounts) ?? 'ungrouped'),
            'group_diversity' => count($groupCounts),
            'dominant_memes' => array_slice($averages, 0, 3, true),
            'meme_signature' => $averages,
            'cohesion' => round(($groupCounts[array_key_first($groupCounts)] ?? 0) / $count, 4),
        ];

        // Phase 2: Material-to-Culture Synthesis
        $profile['cultural_artifacts'] = $this->deriveCulturalArtifacts($actors, $profile, $averages, $zone);

        return $profile;
    }

    private function deriveCulturalArtifacts(array $actors, array $profile, array $memes, array $zone): array
    {
        $dominantMeme = array_key_first($memes);
        $cohesion = $profile['cohesion'] ?? 0.5;
        
        $zoneProfile = $zone['state']['material_profile'] ?? [];

        $livelihood = $zoneProfile['livelihood'] ?? 'foraging';
        $construction = $zoneProfile['construction_style'] ?? 'timber';

        $artifacts = [];

        // 1. Aesthetics from Material
        $artifacts['aesthetics'] = match($construction) {
            'stonework' => 'Megalithic & Geometric',
            'timber_frame' => 'Organic & Carved',
            'earth_and_reed' => 'Textured & Minimalist',
            default => 'Functionalist'
        };

        // 2. Rituals from Livelihood + Memes
        $artifacts['rituals'] = match($livelihood) {
            'fishing' => 'Tide Ceremonies',
            'mining' => 'Deep Earth Offerings',
            'farming' => 'Harvest Calendars',
            'pastoral' => 'Migration Blessings',
            default => 'Nature Veneration'
        };

        // 3. Taboos from Cohesion + Dominant Meme
        if ($cohesion > 0.7) {
            $artifacts['taboo'] = ($memes['ritual_rigidity'] ?? 0.5) > 0.6 ? 'Heresy of Separation' : 'Waste of Common Resources';
        } else {
            $artifacts['taboo'] = 'Infringement of Personal Autonomy';
        }

        return $artifacts;
    }
}
