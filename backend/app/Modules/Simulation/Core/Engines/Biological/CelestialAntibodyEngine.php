<?php

namespace App\Modules\Simulation\Core\Engines\Biological;

use App\Modules\Intelligence\Models\LegendaryAgent;
use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Models\Chronicle;
use Illuminate\Support\Facades\Log;

/**
 * CelestialAntibodyEngine: The Immutable Defense Mechanism (§V23).
 * Detects heresy and purges reality-breaking agents.
 */
class CelestialAntibodyEngine
{
    /**
     * Scan and purge high-heresy agents in a universe.
     */
    public function execute(Universe $universe): void
    {
        // Find agents who have accumulated too much heresy
        $heretics = LegendaryAgent::where('universe_id', $universe->id)
            ->where('heresy_score', '>=', 0.8)
            ->get();

        foreach ($heretics as $heretic) {
            $this->purgeHeretic($universe, $heretic);
        }
    }

    protected function purgeHeretic(Universe $universe, LegendaryAgent $heretic): void
    {
        Log::alert("CELSTIAL ANTIBODY: Detecting Heresy in Agent #{$heretic->id} [{$heretic->name}]. Initiating Purge.");

        // Stage 1: Soul Severing (Strip Transcendence)
        $heretic->is_transcendental = false;
        
        // Add Damned tag
        $tags = $heretic->fate_tags ?? [];
        if (!in_array("Damned_by_the_System", $tags)) {
            $tags[] = "Damned_by_the_System (Kẻ Bị Ruồng Bỏ)";
            $heretic->fate_tags = $tags;
        }

        $heretic->save();

        // Stage 2: Entity Deletion (Kill the agent in the simulation state)
        // We simulate a localized apocalypse (Zone Purge)
        $this->zoneAnnihilation($universe, $heretic);

        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => $universe->current_tick,
            'to_tick' => $universe->current_tick,
            'type' => 'divine_retribution',
            'raw_payload' => [
                'action' => 'antibody_purge',
                'agent_name' => $heretic->name,
                'agent_id' => $heretic->id,
                'heresy_score' => $heretic->heresy_score
            ],
        ]);
        
        // Reset heresy to prevent repeated triggers if they somehow survive in state (they shouldn't)
        $heretic->update(['heresy_score' => 0]); 
    }

    protected function zoneAnnihilation(Universe $universe, LegendaryAgent $heretic): void
    {
        $vec = $universe->state_vector ?? [];
        $zones = $vec['zones'] ?? [];
        $targetZoneIndex = null;
        $agentFound = false;

        // Find which zone the agent is in
        foreach ($zones as $idx => &$z) {
            $agents = $z['state']['agents'] ?? [];
            foreach ($agents as $agentIdx => $a) {
                if ($a['id'] === $heretic->original_agent_id) {
                    $targetZoneIndex = $idx;
                    $agentFound = true;
                    // Remove the agent from the array
                    unset($agents[$agentIdx]);
                    $z['state']['agents'] = array_values($agents);
                    break;
                }
            }
            if ($agentFound) break;
        }

        if ($agentFound && $targetZoneIndex !== null) {
            // Irradiate the zone
            $zones[$targetZoneIndex]['state']['radiation'] = ($zones[$targetZoneIndex]['state']['radiation'] ?? 0) + 50.0;
            $vec['zones'] = $zones;
            
            // System-level instability due to the purge
            $universe->entropy = min(1.0, $universe->entropy + 0.1);
            $universe->structural_coherence = max(0.0, $universe->structural_coherence - 0.1);
            
            $scars = $vec['scars'] ?? [];
            $scars[] = [
                'tick' => $universe->current_tick,
                'description' => "Traces of a Celestial Purge.",
                'intensity' => 0.3
            ];
            $vec['scars'] = $scars;

            $universe->state_vector = $vec;
            $universe->save();
            
            Log::info("PURGE EXTENSION: Localized radiation spike applied to Zone {$targetZoneIndex} to clean up Heretic data.");
        }
    }
}

