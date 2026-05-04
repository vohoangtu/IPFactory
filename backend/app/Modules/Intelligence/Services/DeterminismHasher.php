<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;

class DeterminismHasher
{
    /**
     * @param array<int, object> $actorStates
     */
    public function hash(array $actorStates, Universe $universe): string
    {
        $payload = [
            'u' => [
                'entropy' => $universe->entropy,
                'level' => $universe->level,
                'coherence' => $universe->structural_coherence,
            ],
            'a' => array_map(fn($a) => [
                'id' => $a->id,
                'traits' => $a->traits,
                'metrics' => $a->metrics,
                'arch' => $a->archetype,
                'alive' => $a->isAlive,
            ], $actorStates),
        ];

        usort($payload['a'], fn($a, $b) => $a['id'] <=> $b['id']);
        ksort($payload['u']);
        ksort($payload['a']);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
}
