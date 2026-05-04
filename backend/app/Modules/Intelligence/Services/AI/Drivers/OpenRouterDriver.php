<?php

namespace App\Modules\Intelligence\Services\AI\Drivers;

use App\Modules\Intelligence\Contracts\LlmDriverInterface;
use App\Modules\Intelligence\Services\AI\Drivers\Concerns\ResolvesChatResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterDriver implements LlmDriverInterface
{
    use ResolvesChatResponse;

    public function __construct(
        protected string $url,
        protected string $key,
        protected string $model
    ) {}

    /**
     * Send a chat request to OpenRouter.
     */
    public function chat(array $messages, array $options = []): ?string
    {
        try {
            $response = Http::timeout($options['timeout'] ?? 60)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->key}",
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => config('app.url', 'https://worldos.ai'), // Recommended by OpenRouter
                    'X-Title' => 'WorldOS Intelligence Hub', // Recommended by OpenRouter
                ])
                ->post($this->url, [
                    'model' => $this->model,
                    'messages' => $messages,
                    'temperature' => (float) ($options['temperature'] ?? 0.7),
                    'max_tokens' => (int) ($options['max_tokens'] ?? 2000),
                    'top_p' => (float) ($options['top_p'] ?? 1.0),
                ]);

            if (!$response->successful()) {
                $error = $response->body();
                Log::error("OpenRouterDriver Error: " . $error);
                throw new \Exception("OpenRouter API Error: " . ($response->json('error.message') ?? $error));
            }

            $content = $this->extractTextFromResponse($response);

            if ($content === null) {
                Log::warning("OpenRouterDriver empty content [{$this->model}]: " . substr($response->body(), 0, 200));
            }

            return $content;
        } catch (\Throwable $e) {
            Log::error("OpenRouterDriver Exception: " . $e->getMessage());
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
