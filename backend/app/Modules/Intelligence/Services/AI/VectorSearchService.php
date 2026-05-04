<?php

namespace App\Modules\Intelligence\Services\AI;

use Illuminate\Support\Facades\DB;

class VectorSearchService
{
    /**
     * Dimensions for the vector space.
     */
    protected int $dimensions = 384;

    /**
     * Vectorize a string using a Hashing Vectorizer approach.
     * This allows semantic-like search without external LLM dependencies.
     */
    public function vectorize(string $text): array
    {
        $text = strtolower($text);
        $words = preg_split('/\W+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        if (empty($words)) {
            return array_fill(0, $this->dimensions, 0.0);
        }

        $vector = array_fill(0, $this->dimensions, 0.0);

        foreach ($words as $word) {
            $wordVector = $this->getWordVector($word);
            for ($i = 0; $i < $this->dimensions; $i++) {
                $vector[$i] += $wordVector[$i];
            }
        }

        // L2 Normalization
        $norm = sqrt(array_sum(array_map(fn($x) => $x * $x, $vector)));
        if ($norm > 0) {
            $vector = array_map(fn($x) => $x / $norm, $vector);
        }

        return $vector;
    }

    /**
     * Get a deterministic pseudorandom vector for a word using hashing.
     */
    protected function getWordVector(string $word): array
    {
        $v = [];
        // Use multiple hashes or seeds to spread the word across dimensions
        for ($i = 0; $i < $this->dimensions; $i++) {
             // Basic hashing-based weight generation
             $hash = md5($word . "_" . $i);
             // Use part of the hash to generate a value between -1 and 1
             $val = (hexdec(substr($hash, 0, 4)) / 32768.0) - 1.0;
             $v[] = $val;
        }
        return $v;
    }

    /**
     * Perform semantic search using Cosine Similarity via pgvector.
     */
    public function search(int $universeId, string $query, int $limit = 5): \Illuminate\Support\Collection
    {
        $vector = $this->vectorize($query);
        $vectorString = '[' . implode(',', $vector) . ']';

        return DB::table('chronicles')
            ->where('universe_id', $universeId)
            ->select('id', 'from_tick', 'to_tick', 'type', 'content')
            // Cosine distance <=> (1 - cosine similarity)
            ->selectRaw('1 - (embedding <=> ?) as similarity', [$vectorString])
            ->whereNotNull('embedding')
            ->orderByRaw('embedding <=> ?', [$vectorString])
            ->limit($limit)
            ->get();
    }
}
