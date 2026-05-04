<?php

namespace App\Modules\Simulation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Simulation\Services\Cosmology\AxiomRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RuleSetLibraryController extends Controller
{
    private AxiomRegistry $axiomRegistry;

    public function __construct(AxiomRegistry $axiomRegistry)
    {
        $this->axiomRegistry = $axiomRegistry;
    }

    /**
     * Get all ruleset tiers and their associated axioms.
     */
    public function index(): JsonResponse
    {
        // Fetch tiers from DB
        $tiers = DB::table('ruleset_tiers')
            ->orderBy('level')
            ->get();

        // Fetch ruleset definitions grouped by tier
        $definitions = DB::table('ruleset_definitions')
            ->get()
            ->groupBy('tier');

        // Fetch combination rules
        $combinations = DB::table('ruleset_combine_rules')
            ->get()
            ->map(fn($c) => [
                'ruleset_a' => $c->ruleset_a,
                'ruleset_b' => $c->ruleset_b,
                'result_vocation' => $c->result_vocation,
                'description' => $c->description,
            ]);

        // Map tiers to include axioms and rulesets
        $library = $tiers->map(function ($tier) use ($definitions) {
            $tierLevel = (int)$tier->level;
            
            return [
                'level' => $tierLevel,
                'name' => $tier->name,
                'label' => $tier->label,
                'status' => $tierLevel <= 1 ? 'Active' : 'Locked', // Basic tier and Tier 1 active by default for demo
                'axioms' => $this->axiomRegistry->getByMaxTier($tierLevel),
                'rulesets' => $definitions->get($tierLevel, collect())->map(fn($d) => [
                    'id' => $d->id,
                    'name' => $d->name,
                    'tags' => json_decode($d->tags),
                ]),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'tiers' => $library,
                'combinations' => $combinations
            ]
        ]);
    }
}
