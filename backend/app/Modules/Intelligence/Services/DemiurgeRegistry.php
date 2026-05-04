<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Demiurge;
use Illuminate\Support\Facades\Log;

/**
 * DemiurgeRegistry: Manages the 'Pantheon' of autonomous AI rivals (§V14).
 * These entities compete to shape the multiverse based on their Divine Intentions.
 */
class DemiurgeRegistry
{
    /**
     * Initial seed for the Pantheon.
     */
    public function seedPantheon(): void
    {
        $pantheon = [
            [
                'name' => 'Aethelgard the Eternal',
                'intention_type' => 'order',
                'config' => [
                    'description' => 'Seeker of absolute stability and crystalline structure.',
                    'target_sci' => 0.95,
                    'target_entropy' => 0.05,
                    'edict_style' => 'authoritarian',
                ]
            ],
            [
                'name' => 'Khaos-Null',
                'intention_type' => 'chaos',
                'config' => [
                    'description' => 'The void that hungers for dissolution and creative destruction.',
                    'target_sci' => 0.2,
                    'target_entropy' => 0.8,
                    'edict_style' => 'unpredictable',
                ]
            ],
            [
                'name' => 'The Weaver of Fates',
                'intention_type' => 'sovereignty',
                'config' => [
                    'description' => 'Ensures that legends rise and history follows epic trajectories.',
                    'target_sci' => 0.6,
                    'target_entropy' => 0.4,
                    'edict_style' => 'mythic',
                ]
            ]
        ];

        foreach ($pantheon as $data) {
            Demiurge::firstOrCreate(['name' => $data['name']], $data);
        }

        Log::info("MYTHOS: The Pantheon has been seeded with " . count($pantheon) . " Demiurges.");
    }

    /**
     * Get all active rivals.
     */
    public function getActiveRivals()
    {
        return Demiurge::where('is_active', true)->get();
    }
}

