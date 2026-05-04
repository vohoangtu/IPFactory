<?php

namespace App\Modules\Narrative\Services;

use App\Contracts\LlmNarrativeClientInterface;
use App\Modules\Intelligence\Services\AI\AiGateway;
use Illuminate\Support\Facades\Log;

/**
 * Legacy compatibility adapter.
 * Narrative generation is now fully routed through AiGateway so pool/direct policy stays consistent.
 */
class OpenAINarrativeService implements LlmNarrativeClientInterface
{
    public function __construct(
        protected AiGateway $aiGateway
    ) {}

    public function isAvailable(): bool
    {
        try {
            return $this->aiGateway->getActiveKeyForFeature('narrative') !== null;
        } catch (\Throwable $e) {
            Log::debug('OpenAINarrativeService::isAvailable check failed: ' . $e->getMessage());
            return false;
        }
    }

    public function generate(string $prompt, array $options = []): ?string
    {
        $system = $options['system'] ?? 'Bạn là WorldOS, người kể chuyện về sự tiến hóa của vũ trụ. Phản hồi bằng tiếng Việt.';
        $temperature = isset($options['temperature']) ? (float) $options['temperature'] : 0.7;

        try {
            return $this->aiGateway->feature('narrative')->chat([
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $prompt],
            ], array_merge($options, [
                'temperature' => $temperature,
            ]));
        } catch (\Throwable $e) {
            Log::error('OpenAINarrativeService gateway error: ' . $e->getMessage());
            return null;
        }
    }
}
