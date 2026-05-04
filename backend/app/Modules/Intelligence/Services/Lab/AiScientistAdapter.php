<?php

namespace App\Modules\Intelligence\Services\Lab;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Layer 10: AI Scientist Adapter.
 * Integrates with the local LM Studio model to analyze data and propose theories.
 */
class AiScientistAdapter
{
    public function __construct(
        private readonly \App\Modules\Intelligence\Services\AI\AiGateway $aiGateway
    )
    {
    }

    /**
     * Ask the LLM to formulate a theory based on discovered laws and raw data.
     * 
     * @param array $laws Rules from UniversalLawDiscovery
     * @param array $context Additional simulation context
     */
    public function formulateTheory(array $laws, array $context): ?string
    {
        $prompt = "You are the AI Scientist overseeing a multiverse simulation. Based on the following data, formulate a concise, academic theory about civilization dynamics.\n";
        $prompt .= "Discovered Laws:\n" . json_encode($laws, JSON_PRETTY_PRINT) . "\n";
        $prompt .= "Context:\n" . json_encode($context, JSON_PRETTY_PRINT) . "\n\n";
        $prompt .= "Theory: ";

        try {
            // Sử dụng AiGateway để tự động chọn driver (Local hoặc Pool)
            // 'analytical' feature có thể được cấu hình trong AiConfigManager
            return $this->aiGateway->feature('analytical')->chat([
                ['role' => 'system', 'content' => 'You are a brilliant sociologist, physicist, and AI scientist observing simulated civilizations.'],
                ['role' => 'user', 'content' => $prompt]
            ], [
                'temperature' => 0.7,
                'max_tokens' => 500,
            ]);

        } catch (\Exception $e) {
            Log::error("AI Scientist Adapter failed: " . $e->getMessage());
        }

        return null; // Fallback
    }
}
