<?php

namespace App\Modules\Intelligence\Services\AI;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Services\AI\AnalyticalAiService;
use App\Modules\Intelligence\Services\AI\TheorySynthesisService;
use App\Modules\Intelligence\Services\Lab\MaterialSynthesisService;
use App\Modules\World\Services\MaterialService;
use Illuminate\Support\Facades\Log;

/**
 * World Advisor Service: The "Strategist" AI Agent.
 * Synthesizes analysis from all AI sub-services and provides structured recommendations.
 * Implements the Observer-Actor model from WorldOS AI Neuro System spec.
 */
class WorldAdvisorService
{
    public function __construct(
        protected AnalyticalAiService $analyticalAi,
        protected TheorySynthesisService $theorySynthesis,
        protected MaterialSynthesisService $materialSynthesis,
        protected MaterialService $materialService,
        protected MemoryService $memory
    ) {}

    /**
     * Run a full advisory analysis cycle on a universe.
     */
    public function advise(Universe $universe): array
    {
        $results = [];

        // 1. Pattern Analysis: check cross-universe health
        $allUniverseIds = Universe::where('world_id', $universe->world_id)
            ->where('status', '!=', 'archived')

            ->pluck('id')
            ->toArray();

        $analysisResult = $this->analyticalAi->analyze($allUniverseIds, 50);
        $results['pattern_analysis'] = $analysisResult;

        // 2. Narrative Analysis: generate human-readable advisory text
        $advisoryText = $this->analyticalAi->generateNarrativeAnalysis([
            'universe_id' => $universe->id,
            'patterns'    => $analysisResult['patterns'],
            'suggestion'  => $analysisResult['suggestion'],
        ]);
        $results['advisory_text'] = $advisoryText;

        // 3. Store in Long-Term Memory for future reference
        try {
            $this->memory->write(
                $universe->id,
                'advisor',
                'pattern_analysis',
                $advisoryText,
                ['entropy', 'stability', 'advisor'],
                ['importance' => 5, 'ttl_days' => 30]
            );
        } catch (\Throwable $e) {
            Log::warning('WorldAdvisorService: failed to write to memory: ' . $e->getMessage());
        }

        // 4. Theory Synthesis: propose a new Axiom if conditions are right
        try {
            $axiom = $this->theorySynthesis->synthesizeTheory($universe);
            $results['discovered_axiom'] = $axiom?->only(['axiom_key', 'description', 'confidence']);
        } catch (\Throwable $e) {
            Log::warning('WorldAdvisorService: TheorySynthesis failed: ' . $e->getMessage());
            $results['discovered_axiom'] = null;
        }

        // 5. Material Synthesis: suggest a new emergent material
        try {
            $latest   = $universe->snapshots()->orderByDesc('tick')->first();
            $scars    = (array) (($latest?->state_vector ?? [])['scars'] ?? []);
            $matNames = \App\Modules\Simulation\Models\MaterialInstance::where('universe_id', $universe->id)
                ->where('lifecycle', 'active')
                ->with('material')
                ->get()
                ->pluck('material.name')
                ->toArray();

            if (!empty($matNames)) {
                $newMaterialData = $this->materialSynthesis->synthesize($universe, $matNames, $scars);
                if ($newMaterialData) {
                    $parent = \App\Modules\World\Models\Material::where('name', $newMaterialData['parent_material_name'] ?? '')->first();
                    $newMaterial = $this->materialService->injectSynthesizedMaterial($newMaterialData, $parent);
                    $results['synthesized_material'] = [
                        'id' => $newMaterial->id,
                        'name' => $newMaterial->name,
                        'ontology' => $newMaterial->ontology,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('WorldAdvisorService: MaterialSynthesis failed: ' . $e->getMessage());
            $results['synthesized_material'] = null;
        }

        Log::info("WorldAdvisorService: advisory cycle completed for Universe {$universe->id}.", ['results' => array_keys($results)]);
        return $results;
    }
}

