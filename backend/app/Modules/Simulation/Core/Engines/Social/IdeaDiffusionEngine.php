<?php
namespace App\Modules\Simulation\Core\Engines\Social;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Events\WorldEvent;
use App\Modules\Simulation\Core\Events\WorldEventType;
use function config;


/**
 * IdeaDiffusionEngine — Lan truyền ý tưởng/meme giữa zones.
 *
 * Ideas spread from zones with high knowledge to neighbors.
 * Institutional amplification (religion → religion ideas, academy → science).
 */
class IdeaDiffusionEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    public function name(): string { return 'idea_diffusion'; }
    public function phase(): string { return 'social'; }
    public function priority(): int { return 5; }
    public function tickRate(): int { return 1; }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $result = new EngineResult();
        $tick   = $ctx->getTick();

        if ($tick % 20 !== 0) { return $result; }

        $zones = $state->getZones();
        $seed  = $ctx->getSeed();
        $transmissionRate = (float) config('worldos.idea_diffusion.transmission_rate', 0.15);
        $mutationRate     = (float) config('worldos.idea_diffusion.mutation_rate', 0.05);

        $knowledgeCore = (float) $state->get('knowledge_core', 0.1);
        $techLevel     = (float) $state->get('tech_level', 0.1);

        $updatedZones = [];
        $totalIdeas   = 0;

        foreach ($zones as $idx => $zone) {
            $s = $zone['state'] ?? [];
            $population  = (float) ($s['population'] ?? 0);
            $infraLevel  = (float) ($s['infrastructure_level'] ?? 0.1);
            $ideaPool    = (array) ($s['idea_pool'] ?? []);

            // Idea generation: proportional to population + knowledge + infrastructure
            $generationChance = ($population * 0.01 + $knowledgeCore * 0.1 + $infraLevel * 0.05);
            $genHash = abs(crc32("idea_gen_{$seed}_{$idx}_{$tick}")) / 2147483647;

            if ($genHash < $generationChance && count($ideaPool) < 10) {
                $ideaTypes = ['agriculture', 'metalworking', 'writing', 'law', 'philosophy', 'mathematics', 'medicine', 'navigation'];
                $ideaIdx = abs(crc32("idea_type_{$seed}_{$idx}_{$tick}")) % count($ideaTypes);
                $newIdea = $ideaTypes[$ideaIdx];

                if (!in_array($newIdea, $ideaPool, true)) {
                    $ideaPool[] = $newIdea;

                    $result->events[] = WorldEvent::create(
                        type: WorldEventType::TECHNOLOGY_INVENTED,
                        universeId: $ctx->getUniverseId(),
                        tick: $tick,
                        payload: ['idea' => $newIdea, 'zone_id' => $zone['id'] ?? $idx],
                        impactScore: 0.4
                    );
                }
            }

            // Diffusion: inherit ideas from neighbors (simplified: previous and next zone)
            foreach ([-1, 1] as $offset) {
                $neighborIdx = $idx + $offset;
                if (!isset($zones[$neighborIdx])) continue;

                $neighborIdeas = (array) ($zones[$neighborIdx]['state']['idea_pool'] ?? []);
                foreach ($neighborIdeas as $idea) {
                    if (in_array($idea, $ideaPool, true)) continue;

                    $spreadHash = abs(crc32("spread_{$seed}_{$idx}_{$idea}_{$tick}")) / 2147483647;
                    if ($spreadHash < $transmissionRate) {
                        // Mutation: idea changes slightly
                        $mutHash = abs(crc32("mut_{$seed}_{$idx}_{$idea}_{$tick}")) / 2147483647;
                        if ($mutHash < $mutationRate) {
                            $idea = 'evolved_' . $idea;
                        }
                        $ideaPool[] = $idea;
                    }
                }
            }

            $totalIdeas += count($ideaPool);
            $s['idea_pool'] = array_unique($ideaPool);
            $s['dominant_ideas'] = array_slice($s['idea_pool'], 0, 3);
            $zone['state'] = $s;
            $updatedZones[] = $zone;
        }

        if (!empty($updatedZones)) {
            $result->stateChanges['zones'] = $updatedZones;
            $result->stateChanges['culture'] = [
                'idea_count' => $totalIdeas,
            ];
        }

        return $result;
    }
}
