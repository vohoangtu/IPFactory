<?php

namespace App\Modules\Intelligence\Services\AI\Drivers;

use App\Modules\Intelligence\Contracts\LlmDriverInterface;
use App\Modules\Intelligence\Services\AI\Drivers\Concerns\ResolvesChatResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiDriver implements LlmDriverInterface
{
    use ResolvesChatResponse;

    public function __construct(
        protected string $url,
        protected string $key,
        protected string $model
    ) {}

    public function chat(array $messages, array $options = []): ?string
    {
        try {
            $response = Http::timeout($options['timeout'] ?? 60)
                ->withToken($this->key)
                ->post($this->url, [
                    'model' => $this->model,
                    'messages' => $messages,
                    'temperature' => $options['temperature'] ?? 0.7,
                    'max_tokens' => $options['max_tokens'] ?? 2000,
                    'top_p' => $options['top_p'] ?? 1.0,
                ]);

            if (!$response->successful()) {
                $error = $response->body();
                Log::error("OpenAiDriver Error: " . $error);
                $response->throw();
            }

            $content = $this->extractTextFromResponse($response);

            if ($content === null) {
                Log::warning("OpenAiDriver empty content [{$this->model}]: " . substr($response->body(), 0, 200));
            }

            return $content;
        } catch (\Throwable $e) {
            Log::error("OpenAiDriver Exception: " . $e->getMessage());
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
