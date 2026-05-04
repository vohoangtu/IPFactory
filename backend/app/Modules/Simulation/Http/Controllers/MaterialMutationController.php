<?php

namespace App\Modules\Simulation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\World\Models\Material;
use App\Modules\Simulation\Models\MaterialInstance;
use App\Modules\World\Models\Universe;
use Illuminate\Http\Request;

class MaterialMutationController extends Controller
{
    /**
     * Get the Material Mutation DAG (nodes and edges) for a specific universe.
     * This will return ALL base materials, instances specific to this universe, and all mutations.
     */
    public function getDagData(int $universeId)
    {
        $universe = Universe::findOrFail($universeId);

        // Fetch all materials (this is our base dictionary)
        $materials = Material::select('id', 'name', 'slug', 'ontology', 'description')->get();


        // Fetch instances for this universe to see which ones are Active/Dormant in THIS universe
        $instances = MaterialInstance::where('universe_id', $universe->id)->get();
        
        // Map instance states by material_id
        $instanceStates = $instances->keyBy('material_id')->map(function ($inst) {
            return [
                'lifecycle' => $inst->lifecycle,
                'quantity' => $inst->quantity ?? 0,
            ];
        });

        // Build nodes
        $nodes = $materials->map(function ($mat) use ($instanceStates) {
            $state = $instanceStates->get($mat->id);
            return [
                'id' => (string) $mat->id,
                'position' => ['x' => 0, 'y' => 0], // Position will be handled by Dagre on frontend
                'data' => [
                    'label' => $mat->name,
                    'ontology' => $mat->ontology,
                    'culture' => $mat->ontology === 'institutional' ? 'Imperial' : ($mat->ontology === 'symbolic' ? 'Mystic' : 'Common'),
                    'lifecycle' => $state ? $state['lifecycle'] : 'dormant', // Default to dormant if not instanced
                    'description' => $mat->description,
                ],
                'type' => 'materialNode',
            ];
        });

        // Fetch all reactions (edges)
        $reactions = \App\Modules\Simulation\Models\MaterialReaction::all();
        $edges = [];
        
        foreach ($reactions as $reaction) {
            $inputs  = is_array($reaction->inputs)  ? array_keys($reaction->inputs)  : [];
            $outputs = is_array($reaction->outputs) ? array_keys($reaction->outputs) : [];

            // A Reaction (MRE) can have multiple inputs and multiple outputs.
            // For the DAG visualization, we draw an edge from EACH input to EACH output.
            foreach ($inputs as $inputSlug) {
                $inputModel = $materials->firstWhere('slug', $inputSlug);
                if (!$inputModel) continue;

                foreach ($outputs as $outputSlug) {
                    $outputModel = $materials->firstWhere('slug', $outputSlug);
                    if (!$outputModel) continue;

                    // Skip self-edges if they are catalyst-type (A + B -> A + C)
                    if ($inputModel->id === $outputModel->id) continue;

                    $edges[] = [
                        'id' => 'e-' . $reaction->id . '-' . $inputModel->id . '-' . $outputModel->id,
                        'source' => (string) $inputModel->id,
                        'target' => (string) $outputModel->id,
                        'label' => $reaction->slug, 
                        'animated' => true,
                    ];
                }
            }
        }

        return response()->json([
            'ok' => true,
            'nodes' => $nodes,
            'edges' => $edges,
        ]);
    }
}

