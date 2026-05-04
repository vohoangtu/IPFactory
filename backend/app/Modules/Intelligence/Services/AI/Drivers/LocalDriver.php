<?php

namespace App\Modules\Intelligence\Services\AI\Drivers;

use App\Modules\Intelligence\Contracts\LlmDriverInterface;
use App\Modules\Intelligence\Services\AI\Drivers\Concerns\ResolvesChatResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocalDriver implements LlmDriverInterface
{
    use ResolvesChatResponse;

    public function __construct(
        protected string $url,
        protected string $model
    ) {}

    public function chat(array $messages, array $options = []): ?string
    {
        try {
            $timeout = $options['timeout'] ?? 60;
            $response = Http::connectTimeout(10)
                ->timeout($timeout)
                ->post($this->url, [
                    'model' => $this->model,
                    'messages' => $messages,
                    'temperature' => $options['temperature'] ?? 0.7,
                    'max_tokens' => $options['max_tokens'] ?? 512,
                ]);

            if (!$response->successful()) {
                Log::warning("LocalDriver HTTP {$response->status()}: " . substr($response->body(), 0, 200));
                $response->throw();
            }

            $content = $this->extractTextFromResponse($response);

            if ($content === null) {
                Log::warning("LocalDriver empty content [{$this->model}]: " . substr($response->body(), 0, 200));
            }

            return $content;
        } catch (\Throwable $e) {
            Log::warning("LocalDriver error [{$this->model}]: " . $e->getMessage());
            throw $e;
        }
    }

    public function generate(string $prompt, array $options = []): ?string
    {
        return $this->chat([
            ['role' => 'user', 'content' => $prompt]
        ], $options);
    }

    public function metadata(): array
    {
        return [
            'url' => $this->url,
            'model' => $this->model,
        ];
    }
}
