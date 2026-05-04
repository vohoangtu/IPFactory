<?php

namespace App\Modules\Narrative\Services;

use App\Contracts\LlmNarrativeClientInterface;
use App\Modules\Intelligence\Services\AI\AiGateway;
use Illuminate\Support\Facades\Log;

/**
 * GatewayNarrativeService: Implementation of LlmNarrativeClientInterface using the unified AiGateway.
 * This allows Narrative module to benefit from Audit Logging and Multi-driver support.
 */
class GatewayNarrativeService implements LlmNarrativeClientInterface
{
    public function __construct(
        protected AiGateway $aiGateway
    ) {}

    /**
     * @return bool
     */
    public function isAvailable(): bool
    {
        try {
            return $this->aiGateway->getActiveKeyForFeature('narrative') !== null;
        } catch (\Throwable $e) {
            Log::debug('GatewayNarrativeService::isAvailable check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate narrative using the AI Gateway.
     * 
     * @param string $prompt
     * @param array $options
     * @return string|null
     */
    public function generate(string $prompt, array $options = []): ?string
    {
        try {
            // Support Era-specific personas
            if (isset($options['persona'])) {
                $prompt = "Context/Persona: {$options['persona']}\n\nUser Request: {$prompt}";
            }

            // Forward to AiGateway with 'narrative' feature context
            return $this->aiGateway->feature('narrative')->generate($prompt, $options);
        } catch (\Throwable $e) {
            Log::error("GatewayNarrativeService: Generation failed: " . $e->getMessage());
            return null;
        }
    }
}
