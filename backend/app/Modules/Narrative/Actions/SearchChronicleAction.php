<?php

namespace App\Modules\Narrative\Actions;

use App\Modules\Intelligence\Services\AI\VectorSearchService;

class SearchChronicleAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected VectorSearchService $vectorSearch
    ) {}

    public function execute(int $universeId, string $query): array
    {
        if (empty(trim($query))) {
            return [
                'ok' => true,
                'results' => []
            ];
        }

        $results = $this->vectorSearch->search($universeId, $query, 10);

        return [
            'ok' => true,
            'query' => $query,
            'results' => $results->toArray()
        ];
    }
}
