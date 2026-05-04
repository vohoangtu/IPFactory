<?php

namespace App\Modules\Intelligence\Contracts;

interface LlmDriverInterface
{
    /**
     * Send a chat request to the LLM.
     *
     * @param  array  $messages  List of messages (role, content)
     * @param  array  $options   Additional options (temperature, max_tokens, etc.)
     * @return string|null
     */
    public function chat(array $messages, array $options = []): ?string;

    /**
     * Send a completion request to the LLM (standalone prompt).
     *
     * @param  string  $prompt
     * @param  array   $options
     * @return string|null
     */
    public function generate(string $prompt, array $options = []): ?string;

    /**
     * Expose resolved runtime metadata for monitoring/logging.
     *
     * @return array<string, mixed>
     */
    public function metadata(): array;
}
