<?php

namespace App\Modules\Simulation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use Illuminate\Http\JsonResponse;

/**
 * MultiverseMapController: Serves the high-level DAG of all active and collapsed universes (§V12).
 * This is the data source for the Architect's 'Multiverse View'.
 */
class MultiverseMapController extends Controller
{
    public function index(): JsonResponse
    {
        $universes = Universe::all(['id', 'name', 'parent_universe_id', 'multiverse_id', 'status', 'structural_coherence', 'entropy', 'state_vector']);

        $nodes = $universes->map(function($u) {
            return [
                'id' => $u->id,
                'label' => $u->name,
                'status' => $u->status,
                'multiverse_id' => $u->multiverse_id,
                'metrics' => [
                    'sci' => $u->structural_coherence,
                    'entropy' => $u->entropy,
                    'attractor' => $u->state_vector['active_attractor'] ?? 'unknown',
                ],
                'type' => $u->parent_universe_id ? 'branch' : 'origin'
            ];
        });

        // 1. Birth edges (Causal)
        $edges = $universes->whereNotNull('parent_universe_id')->map(function($u) {
            return [
                'from' => $u->parent_universe_id,
                'to' => $u->id,
                'type' => 'birth',
                'label' => 'Causal Birth'
            ];
        })->values()->toArray();

        // 2. Quantum Trade Routes (Economic Sync)
        $tradeEdges = [];
        $activeUniverses = $universes->where('status', 'active')->values();
        
        for ($i = 0; $i < $activeUniverses->count(); $i++) {
            for ($j = $i + 1; $j < $activeUniverses->count(); $j++) {
                $ua = $activeUniverses[$i];
                $ub = $activeUniverses[$j];

                if ($ua->multiverse_id === $ub->multiverse_id && abs($ua->entropy - $ub->entropy) <= 0.1) {
                    $tradeEdges[] = [
                        'from' => $ua->id,
                        'to' => $ub->id,
                        'type' => 'trade',
                        'label' => 'Quantum Trade',
                        'intensity' => 1 - abs($ua->entropy - $ub->entropy) // Higher intensity if more synced
                    ];
                }
            }
        }

        return response()->json([
            'nodes' => $nodes,
            'edges' => array_merge($edges, $tradeEdges),
        ]);
    }

    /**
     * Bloom public endpoint: hierarchical WorldOS → World → Universe structure.
     * Used by the Bloom UI (port 3001) for DAG visualization.
     */
    public function bloom(): JsonResponse
    {
        $worlds = World::with(['universes' => function ($q) {
            $q->select('id', 'world_id', 'name', 'status', 'structural_coherence', 'parent_universe_id', 'current_tick')
              ->orderBy('id');
        }])->select('id', 'name', 'origin', 'current_genre')->get();

        $worldsData = $worlds->map(function (World $w) {
            $universes = $w->universes->map(function (Universe $u) {
                return [
                    'id'               => (string) $u->id,
                    'label'            => $u->name,
                    'sub'              => 'Tick #' . $u->current_tick,
                    'status'           => $u->status ?? 'active',
                    'sci'              => (int) round($u->structural_coherence ?? 0),
                    'parentUniverseId' => $u->parent_universe_id ? (string) $u->parent_universe_id : null,
                    'saliency'         => (float) ($u->state_vector['meta']['time_saliency'] ?? 0),
                ];
            });

            $avgSci = $universes->avg('sci') ?? 0;

            return [
                'id'        => (string) $w->id,
                'label'     => $w->name,
                'genre'     => $w->current_genre ?? $w->origin ?? 'generic',
                'sci'       => (int) round($avgSci),
                'universes' => $universes->values(),
                'status'    => $w->universes->every(fn($u) => $u->status === 'collapsed') ? 'dead' : 'blooming',
            ];
        });

        return response()->json([
            'id'     => 'worldos',
            'label'  => 'The Multiverse',
            'sub'    => 'WorldOS — Simulation Active',
            'worlds' => $worldsData->values(),
        ]);
    }

    /**
     * Narrative Resonance: Returns active narratives and their spatial influence.
     * This "pollen" data allows Bloom UI to visualize story spreading across universes.
     */
    public function resonance(): JsonResponse
    {
        $activeNarratives = \App\Modules\Narrative\Models\Narrative::where('is_active', true)
            ->orderBy('virality', 'desc')
            ->limit(20)
            ->get();

        $resonance = $activeNarratives->map(function (\App\Modules\Narrative\Models\Narrative $n) {
            return [
                'id' => $n->id,
                'universe_id' => $n->universe_id,
                'headline' => $n->news_headline,
                'slogan' => $n->news_slogan,
                'story_snippet' => substr($n->story, 0, 150) . '...',
                'intensity' => $n->virality, // Bloom intensity
                'distortion' => $n->distortion, // Reality ripple effect
                'vfx' => $n->vfx_config,
                'tags' => $n->tags,
                'effects' => $n->field_effects,
                'origin_tick' => $n->tick_born,
            ];
        });

        return response()->json([
            'resonance_pollen' => $resonance,
            'global_narrative_entropy' => $activeNarratives->avg('distortion') ?? 0,
        ]);
    }
}

