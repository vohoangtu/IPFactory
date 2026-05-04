<?php

namespace App\Contracts;

/**
 * LLM client for narrative generation (chronicle, snippet).
 * Implementations: OpenAINarrativeService, or local/compatible API adapter.
 */
interface LlmNarrativeClientInterface
{
    /**
     * Generate text from prompt. Returns null if unavailable or error.
     */
    public function generate(string $prompt, array $options = []): ?string;

    /**
     * Whether this client is configured and ready to use.
     */
    public function isAvailable(): bool;
}
