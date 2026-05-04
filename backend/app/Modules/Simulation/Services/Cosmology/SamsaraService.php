<?php

namespace App\Modules\Simulation\Services\Cosmology;

use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Intelligence\Models\LegendaryAgent;
use App\Modules\World\Models\Universe;

class SamsaraService
{
    /**
     * Get the transmigration path for a specific legendary agent.
     */
    public function getPath(int $agentId): array
    {
        $agent = LegendaryAgent::findOrFail($agentId);
        
        // Find all isekai events related to this agent name
        // Since original_agent_id might change between universes but name stays
        $events = Chronicle::where('raw_payload->agent_name', $agent->name)
            ->whereIn('type', ['isekai_departure', 'isekai_arrival'])
            ->orderBy('created_at', 'asc')
            ->get();

        $path = [];
        foreach ($events as $event) {
            $universe = Universe::find($event->universe_id);
            $path[] = [
                'tick' => $event->from_tick,
                'universe_id' => $event->universe_id,
                'universe_name' => $universe?->name ?? "Unknown Universe #{$event->universe_id}",
                'type' => $event->type, // isekai_departure or isekai_arrival
                'payload' => $event->raw_payload,
                'timestamp' => $event->created_at?->toIso8601String(),
            ];
        }

        return [
            'agent' => [
                'id' => $agent->id,
                'name' => $agent->name,
                'archetype' => $agent->archetype,
                'is_isekai' => $agent->is_isekai,
                'is_transcendental' => $agent->is_transcendental,
            ],
            'path' => $path
        ];
    }
}

