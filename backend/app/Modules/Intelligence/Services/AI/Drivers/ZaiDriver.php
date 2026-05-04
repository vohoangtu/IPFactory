<?php

namespace App\Modules\Intelligence\Services\AI\Drivers;

use App\Modules\Intelligence\Contracts\LlmDriverInterface;
use App\Modules\Intelligence\Services\AI\Drivers\Concerns\ResolvesChatResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZaiDriver implements LlmDriverInterface
{
    use ResolvesChatResponse;

    public function __construct(
        protected string $url,
        protected string $key,
        protected string $model
    ) {}

    public function chat(array $messages, array $options = []): ?string
    {
        $response = Http::timeout($options['timeout'] ?? 60)
            ->withToken($this->key)
            ->post($this->url, [
                'model' => $this->model,
                'messages' => $messages,
                'do_sample' => true,
                'temperature' => (float) ($options['temperature'] ?? 0.7),
                'top_p' => (float) ($options['top_p'] ?? 0.7),
                'max_tokens' => (int) ($options['max_tokens'] ?? 2000),
            ]);

        if (!$response->successful()) {
            $error = $response->body();
            Log::error("ZaiDriver Error: " . $error);
            throw new \Exception("AI Driver Error: " . ($response->json('error.message') ?? $error));
        }

        $content = $this->extractTextFromResponse($response);

        if ($content === null) {
            Log::warning("ZaiDriver empty content [{$this->model}]: " . substr($response->body(), 0, 200));
        }

        return $content;
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
