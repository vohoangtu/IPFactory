<?php

namespace App\Modules\Intelligence\Services\AI;

use App\Modules\Intelligence\Models\AiMemory;
use Illuminate\Support\Facades\DB;

class MemoryService
{
    public function __construct(
        protected VectorSearchService $vectorizer,
        protected EpistemicService $epistemicService
    ) {}

    public function write(?int $universeId, string $scope, string $category, string $content, array $keywords = [], array $meta = []): AiMemory
    {
        // Apply distortion if noise is high and we have a universe context
        if ($universeId) {
            $universe = \App\Modules\World\Models\Universe::find($universeId);
            if ($universe) {
                $noise = $this->epistemicService->calculateNoise($universe, (float)(($universe->state_vector ?? [])['entropy'] ?? 0));
                if ($noise > 0.3) {
                    $content = "[SYSTEM PERCEPTION: " . $this->epistemicService->getClarityLabel($noise) . "]\n" . $content;
                    // Note: Full content distortion could be done here if needed
                }
            }
        }

        $contentHash = $this->hashContent($content);

        $existing = AiMemory::query()
            ->where('universe_id', $universeId)
            ->where('scope', $scope)
            ->where('category', $category)
            ->where('content_hash', $contentHash)
            ->first();
        if ($existing) {
            return $existing;
        }

        $vec = $this->vectorizer->vectorize($content);
        $expiresAt = $meta['expires_at'] ?? null;
        if ($expiresAt === null && isset($meta['ttl_days'])) {
            $expiresAt = now()->addDays((int) $meta['ttl_days']);
        }
        return AiMemory::create([
            'universe_id' => $universeId,
            'scope' => $scope,
            'category' => $category,
            'keywords' => implode(',', $keywords),
            'content' => $content,
            'embedding' => $vec,
            'embedding_model' => $meta['embedding_model'] ?? config('worldos.memory.embedding_model', 'hashing-384'),
            'embedding_version' => $meta['embedding_version'] ?? config('worldos.memory.embedding_version', 'v1'),
            'source' => $meta['source'] ?? null,
            'importance' => (int) ($meta['importance'] ?? 0),
            'expires_at' => $expiresAt,
            'content_hash' => $contentHash,
        ]);
    }

    /**
     * Find memories that resonate with the given content.
     * Returns a collection of resonating memories with their resonance scores.
     */
    public function findResonance(string $content, ?int $universeId, float $threshold = 0.75, int $limit = 3): array
    {
        $qvec = $this->vectorizer->vectorize($content);
        
        $builder = DB::table('ai_memories');
        if ($universeId !== null) {
            $builder->where(function ($q) use ($universeId) {
                $q->where('universe_id', $universeId)->orWhereNull('universe_id');
            });
        }

        // Only search memories that haven't expired
        $builder->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });

        // We use a candidate pool to avoid calculating cosine for everything in DB (if not using VectorDB)
        $candidates = $builder->orderByDesc('importance')
            ->orderByDesc('created_at')
            ->limit(200)
            ->get(['id', 'content', 'embedding', 'importance', 'category', 'tick']);

        $resonances = [];
        foreach ($candidates as $candidate) {
            $emb = is_string($candidate->embedding) ? json_decode($candidate->embedding, true) : $candidate->embedding;
            if (!is_array($emb)) continue;

            $similarity = $this->cosine($qvec, $emb);
            
            if ($similarity >= $threshold) {
                $resonances[] = [
                    'id' => $candidate->id,
                    'content' => $candidate->content,
                    'score' => $similarity,
                    'importance' => $candidate->importance,
                    'category' => $candidate->category,
                    'original_tick' => $candidate->tick ?? 0
                ];
            }
        }

        // Sort by resonance score
        usort($resonances, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($resonances, 0, $limit);
    }

    public function search(string $query, ?int $universeId = null, int $limit = 5, array $filters = []): array
    {
        $driver = (string) config('worldos.memory.driver', 'db_json');
        if ($driver !== 'db_json') {
            $driver = 'db_json';
        }

        return $this->searchDbJson($query, $universeId, $limit, $filters);
    }

    protected function searchDbJson(string $query, ?int $universeId, int $limit, array $filters): array
    {
        $qvec = $this->vectorizer->vectorize($query);

        $maxCandidates = (int) config('worldos.memory.max_candidates', 500);
        if ($maxCandidates < 1) {
            $maxCandidates = 1;
        }

        $builder = DB::table('ai_memories');

        if ($universeId !== null) {
            $builder->where(function ($q) use ($universeId) {
                $q->where('universe_id', $universeId)->orWhereNull('universe_id');
            });
        }

        $builder->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });

        if (isset($filters['scope'])) {
            $builder->where('scope', (string) $filters['scope']);
        }
        if (isset($filters['category'])) {
            $builder->where('category', (string) $filters['category']);
        }

        $builder->orderByDesc('importance')->orderByDesc('created_at')->limit($maxCandidates);
        $rows = $builder->get(['id', 'content', 'embedding']);

        $scored = [];
        foreach ($rows as $row) {
            $emb = is_string($row->embedding) ? json_decode($row->embedding, true) : $row->embedding;
            if (!is_array($emb)) {
                $emb = [];
            }
            $score = $this->cosine($qvec, $emb);
            $scored[] = ['id' => $row->id, 'content' => $row->content, 'score' => $score];
        }

        usort($scored, function ($a, $b) {
            if ($a['score'] === $b['score']) return 0;
            return $a['score'] < $b['score'] ? 1 : -1;
        });

        $top = array_slice($scored, 0, $limit);
        return array_map(fn ($x) => $x['content'], $top);
    }

    protected function cosine(array $a, array $b): float
    {
        $n = min(count($a), count($b));
        if ($n === 0) return 0.0;
        $dot = 0.0;
        $na = 0.0;
        $nb = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $dot += $a[$i] * $b[$i];
            $na += $a[$i] * $a[$i];
            $nb += $b[$i] * $b[$i];
        }
        $den = sqrt($na) * sqrt($nb);
        if ($den == 0.0) return 0.0;
        return $dot / $den;
    }

    protected function hashContent(string $content): string
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $content)));
        return sha1($normalized);
    }
}

