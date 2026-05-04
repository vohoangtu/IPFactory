<?php

namespace App\Modules\Intelligence\Services\Analysis;

use App\Modules\Simulation\Models\CivilizationAttractor;
use App\Modules\Intelligence\Models\AiMemory;

/**
 * Phase 3 Self-Reflection Engine.
 * Automated pipeline: Pattern Discovery -> Rule Extraction -> Attractor Persistence.
 */
class SelfReflectionEngine
{
    public function __construct(
        private PatternAnalyzer $analyzer,
        private RuleExtractor $extractor
    ) {}

    /**
     * Perform self-reflection on a batch of memories.
     */
    public function reflect(array $memories): int
    {
        $discovered = 0;
        
        // 1. Convert memories to flattened histories
        $histories = array_map(function (AiMemory $mem) {
            return json_decode($mem->content, true);
        }, $memories);

        // 2. Discover Patterns
        $patterns = $this->analyzer->findInvariants($histories);

        // 3. Extract Rules
        $rules = $this->extractor->extractRules($patterns);

        // 4. Persist as Attractors (Meta-Learning)
        foreach ($rules as $ruleData) {
            // Check if similar rule exists to prevent duplicates
            if (!$this->alreadyExists($ruleData['name'])) {
                CivilizationAttractor::create($ruleData);
                $discovered++;
            }
        }

        return $discovered;
    }

    private function alreadyExists(string $name): bool
    {
        return CivilizationAttractor::where('name', $name)->exists();
    }
}

