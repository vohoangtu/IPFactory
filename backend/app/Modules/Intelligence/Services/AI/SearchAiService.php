<?php

namespace App\Modules\Intelligence\Services\AI;

/**
 * Search AI: evolutionary search on macro parameters (mutate batch simulations for interestingness).
 * Stub: returns placeholder; full impl would run batch with mutated params and score.
 */
class SearchAiService
{
    /**
     * Suggest macro parameter mutations for next batch to maximize "interestingness".
     *
     * @param  array  $currentParams  Current world/universe macro params
     * @return array{mutations: array, score_hint: float}
     */
    public function suggestMutations(array $currentParams): array
    {
        return [
            'mutations' => [
                'entropy_decay_rate' => ($currentParams['entropy_decay_rate'] ?? 0.02) + 0.005,
            ],
            'score_hint' => 0.0,
        ];
    }
}
