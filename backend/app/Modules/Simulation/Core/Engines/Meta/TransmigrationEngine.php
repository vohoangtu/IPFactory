<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Intelligence\Models\LegendaryAgent;
use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Services\Core\CheatGranterService;
use Illuminate\Support\Facades\Log;

/**
 * TransmigrationEngine: Triggers death and rebirth events (§V26).
 * Handles the extraction of an agent and injection into another universe (Isekai).
 */
class TransmigrationEngine
{
    public function __construct(protected CheatGranterService $cheatGranter) {}

    /**
     * Attempt an Isekai event for a random agent in this universe.
     */
    public function triggerIsekai(Universe $sourceUniverse): void
    {
        // Pick a random agent. Prefer those who are not already isekai'd.
        $agent = LegendaryAgent::where('universe_id', $sourceUniverse->id)
            ->where('is_isekai', false)
            ->inRandomOrder()
            ->first();

        if (!$agent) return;

        // Pick a target universe that is active and DIFFERENT from the source
        $targetUniverse = Universe::where('status', 'active')
            ->where('id', '!=', $sourceUniverse->id)
            ->inRandomOrder()
            ->first();

        // If no other universe exists, we can't Isekai
        if (!$targetUniverse) return;

        // Phase 1: Sudden Death (Extract from source)
        $this->extractAgentFromUniverse($sourceUniverse, $agent);

        // Phase 2: The Golden Finger (Grant cheat)
        $cheat = $this->cheatGranter->grantCheat($agent);
        
        // Update agent record
        $agent->universe_id = $targetUniverse->id;
        $agent->is_isekai = true;
        // The isekai'd agent is now permanently marked as transcendental so they survive local collapses
        $agent->is_transcendental = true; 
        $agent->save();

        // Phase 3: Rebirth (Inject into target)
        $this->injectAgentIntoUniverse($targetUniverse, $agent);

        // Chronicles for both worlds (Data-driven, narrative generation is deferred, §V27)
        Chronicle::create([
            'universe_id' => $sourceUniverse->id,
            'from_tick' => $sourceUniverse->current_tick,
            'to_tick' => $sourceUniverse->current_tick,
            'type' => 'isekai_departure',
            'raw_payload' => [
                'action' => 'death_by_anomaly',
                'agent_name' => $agent->name,
                'archetype' => $agent->archetype,
                'traits' => $agent->trait_vector,
            ]
        ]);

        Chronicle::create([
            'universe_id' => $targetUniverse->id,
            'from_tick' => $targetUniverse->current_tick,
            'to_tick' => $targetUniverse->current_tick,
            'type' => 'isekai_arrival',
            'raw_payload' => [
                'action' => 'rebirth_with_cheat',
                'agent_name' => $agent->name,
                'cheat_granted' => $cheat,
                'origin_universe_id' => $sourceUniverse->id,
            ]
        ]);

        Log::alert("ISEKAI: Agent [{$agent->name}] transmigrated from Univ #{$sourceUniverse->id} to Univ #{$targetUniverse->id} with Cheat [{$cheat}]");
    }

    protected function extractAgentFromUniverse(Universe $universe, LegendaryAgent $legend): void
    {
        $vec = $universe->state_vector ?? [];
        if (!isset($vec['zones'])) return;

        foreach ($vec['zones'] as &$z) {
            $agents = $z['state']['agents'] ?? [];
            foreach ($agents as $idx => $a) {
                if (($a['id'] ?? null) === $legend->original_agent_id) {
                    unset($agents[$idx]);
                    $z['state']['agents'] = array_values($agents); // reset array indexes
                    break 2;
                }
            }
        }
        $universe->state_vector = $vec;
        $universe->save();
    }

    protected function injectAgentIntoUniverse(Universe $universe, LegendaryAgent $legend): void
    {
        $vec = $universe->state_vector ?? [];
        if (!isset($vec['zones']) || empty($vec['zones'])) {
            // Failsafe empty zone
            $vec['zones'][] = ['id' => 'zone_0', 'name' => 'Khu vực hoang sơ', 'state' => ['agents' => []]];
        }

        // Pick random zone
        $prng = \App\Modules\Simulation\Services\Ecology\SimulationPRNG::forUniverse($universe);
        $zoneIdx = $prng->arrayRand($vec['zones']);
        
        // Reconstruct basic agent payload
        $agentData = [
            'id' => $legend->original_agent_id,
            'name' => $legend->name,
            'archetype' => $legend->archetype,
            'traits' => array_fill(0, 18, 0.6), // 17D + Longevity (index 17)
            'physic' => \App\Modules\Intelligence\Entities\ActorEntity::defaultPhysicVector(),
            'order' => 1.0,  // Reborn with high order initially
            'entropy' => 0.0,
            'fate_tags' => $legend->fate_tags,
            'is_isekai' => true
        ];

        // Ensure agents array exists
        if (!isset($vec['zones'][$zoneIdx]['state']['agents'])) {
            $vec['zones'][$zoneIdx]['state']['agents'] = [];
        }

        $vec['zones'][$zoneIdx]['state']['agents'][] = $agentData;
        $universe->state_vector = $vec;
        
        // Spiking entropy due to the intrusion of foreign matter
        $universe->entropy = min(1.0, $universe->entropy + 0.1);
        $universe->save();
    }
}


