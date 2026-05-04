<?php

namespace App\Modules\Narrative\Services;

use App\Modules\Narrative\Dto\NarrativeMeaning;
use Illuminate\Support\Facades\Log;

/**
 * SignalExtractor: Parses LLM output to extract actionable simulation signals.
 * Enforces a structured format for AI feedback loops.
 */
class SignalExtractor
{
    /**
     * @param string $llmResponse Raw text from LLM (expected to contain JSON)
     * @return NarrativeMeaning
     */
    public function extract(string $llmResponse): NarrativeMeaning
    {
        // Try to extract JSON between backticks if present
        if (preg_match('/```json(.*?)```/s', $llmResponse, $matches)) {
            $json = trim($matches[1]);
        } else {
            $json = trim($llmResponse);
        }

        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning("SignalExtractor: Invalid JSON received from LLM. Falling back to default meaning.");
            return new NarrativeMeaning(
                summary: $llmResponse,
                tension: 'medium',
                direction: 'stagnation'
            );
        }

        return NarrativeMeaning::fromArray($decoded);
    }
}
