<?php

namespace App\Modules\Narrative\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Intelligence\Models\LegendaryAgent;
use Illuminate\Http\JsonResponse;

class LoomCharacterController extends Controller
{
    /**
     * Tương đương: GET /api/loom/v1/narrative/characters/{character_id}
     * Trả về profile tâm lý và tiểu sử để The Psychologist agent phân tích.
     */
    public function show(Request $request, string $characterId): JsonResponse
    {
        $agent = LegendaryAgent::with(['universe.world', 'alignment'])->find($characterId);

        if (!$agent) {
            return response()->json(['error' => 'Character not found'], 404);
        }

        $profile = [
            'id' => $agent->id,
            'name' => $agent->name,
            'archetype' => $agent->archetype,
            'universe_id' => $agent->universe_id,
            'is_transcendental' => (bool)$agent->is_transcendental,
            'is_isekai' => (bool)$agent->is_isekai,
            
            // Tâm lý học & Nhân cách (Input cho The Psychologist)
            'psychology' => [
                'virtues' => $agent->virtue_matrix ?? [],
                'vices' => $agent->vice_matrix ?? [],
                'alignment' => $agent->alignment ? $agent->alignment->name : 'Vô thần',
                'heresy_score' => $agent->heresy_score,
                'fate_tags' => $agent->fate_tags ?? []
            ],
            
            // Tình trạng vật lý & Nền tảng sức mạnh
            'stats' => [
                'power_level' => $agent->power_level,
                'influence_score' => $agent->influence_score,
                'soul_metadata' => $agent->soul_metadata ?? []
            ],
            
            'visual_dna' => $agent->visual_dna ?? []
        ];

        return response()->json($profile);
    }
}

