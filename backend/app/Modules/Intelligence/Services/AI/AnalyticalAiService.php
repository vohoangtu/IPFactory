<?php

namespace App\Modules\Intelligence\Services\AI;

use App\Modules\Simulation\Models\UniverseSnapshot;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Analytical AI: read feature vectors from many universes, clustering/mining for phase transition rules.
 */
class AnalyticalAiService
{
    public function __construct(
        protected AiGateway $aiGateway
    ) {}

    /**
     * Analyze snapshots across universes for collapse/phase-transition patterns.
     *
     * @param  array<int>  $universeIds
     * @return array{patterns: array, suggestion: string}
     */
    public function analyze(array $universeIds, int $limitPerUniverse = 100): array
    {
        $snapshots = UniverseSnapshot::whereIn('universe_id', $universeIds)
            ->orderByDesc('tick')
            ->limit($limitPerUniverse * count($universeIds))
            ->get(['universe_id', 'tick', 'entropy', 'stability_index', 'metrics']);

        $entropies = $snapshots->pluck('entropy')->filter()->values();
        $avgEntropy = $entropies->avg() ?? 0;
        $highEntropyCount = $entropies->filter(fn ($e) => $e >= 0.85)->count();

        return [
            'patterns' => [
                'avg_entropy' => round($avgEntropy, 4),
                'high_entropy_critical_count' => $highEntropyCount,
            ],
            'suggestion' => $highEntropyCount > 0
                ? 'Consider collapse threshold tuning or fork at criticality.'
                : 'No phase transition signals in sample.',
        ];
    }

    /**
     * Calculate cosine similarity between two state vectors.
     * Used by MultiverseSynthesisService to measure resonance.
     */
    public function calculateSimilarity(array $vec1, array $vec2): float
    {
        // Flatten any nested arrays to scalar values
        $a = $this->flattenToScalars($vec1);
        $b = $this->flattenToScalars($vec2);

        $keys = array_unique(array_merge(array_keys($a), array_keys($b)));
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($keys as $key) {
            $va = (float)($a[$key] ?? 0.0);
            $vb = (float)($b[$key] ?? 0.0);
            $dot  += $va * $vb;
            $normA += $va * $va;
            $normB += $vb * $vb;
        }

        $denom = sqrt($normA) * sqrt($normB);
        if ($denom == 0.0) return 0.0;

        return (float)($dot / $denom);
    }

    /**
     * Call LLM to generate a structured JSON proposal.
     * Used by TheorySynthesisService for Axiom Discovery.
     *
     * @return array{axiom_key: string, description: string, effect: string, confidence: float}|null
     */
    public function generateStructuredProposal(string $prompt): ?array
    {
        $responseText = $this->callLlm($prompt);
        if (!$responseText) return null;

        return $this->parseJsonResponse($responseText);
    }

    /**
     * Generate a narrative analysis summary from a context array.
     * Used by WorldAdvisorService to generate human-readable recommendations.
     */
    public function generateNarrativeAnalysis(array $context): string
    {
        $prompt = "Bạn là một AI chiến lược gia của WorldOS.\n" .
                  "Phân tích bối cảnh sau và đưa ra 1-2 khuyến nghị ngắn gọn bằng tiếng Việt:\n" .
                  json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return $this->callLlm($prompt) ?? 'Không thể phân tích: LLM không phản hồi.';
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    protected function callLlm(string $prompt): ?string
    {
        try {
            return $this->aiGateway->feature('analytical')->chat([
                ['role' => 'system', 'content' => 'You are an AI analyst for WorldOS simulation. Respond in JSON when asked.'],
                ['role' => 'user',   'content' => $prompt],
            ], [
                'temperature' => 0.3,
                'timeout' => 45,
            ]);
        } catch (\Throwable $e) {
            Log::warning('AnalyticalAiService: LLM call failed', ['error' => $e->getMessage()]);
            return null;
        }
    }


    protected function parseJsonResponse(string $text): ?array
    {
        // Try direct decode
        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Try extracting JSON block from markdown
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $text, $m)) {
            $decoded = json_decode(trim($m[1]), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        // Fallback – look for first {...}
        if (preg_match('/(\{.*\})/s', $text, $m)) {
            $decoded = json_decode(trim($m[1]), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    protected function flattenToScalars(array $arr, string $prefix = ''): array
    {
        $result = [];
        foreach ($arr as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : (string) $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenToScalars($value, $fullKey));
            } elseif (is_numeric($value)) {
                $result[$fullKey] = (float) $value;
            }
        }
        return $result;
    }
}

