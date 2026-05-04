<?php

namespace App\Modules\Intelligence\Services\AI;

use App\Modules\Intelligence\Models\AiKeyPool;
use App\Modules\Intelligence\Contracts\LlmDriverInterface;
use App\Modules\Intelligence\Services\AI\Concerns\LogsAiCalls;

class AiDriverProxy implements LlmDriverInterface
{
    use LogsAiCalls;

    public function __construct(
        protected LlmDriverInterface $driver,
        protected string $driverName,
        protected string $feature,
        protected ?AiKeyPool $keyPoolEntry = null,
        protected array $defaultOptions = []
    ) {}

    public function chat(array $messages, array $options = []): ?string
    {
        $startTime = microtime(true);

        try {
            $options = $this->mergeDefaultOptions($options);
            $options['timeout'] = (int) ($options['timeout'] ?? 60);

            $response = $this->driver->chat($messages, $options);
            $response = $this->cleanResponse($response);
            $latency = (int) ((microtime(true) - $startTime) * 1000);

            if ($response === null || (is_string($response) && trim($response) === '')) {
                $this->logToDatabase($messages, null, $latency, 'error', 'AI driver returned an empty or null response.');
            } else {
                $this->logToDatabase($messages, $response, $latency, 'success');
                $this->reportUsage();
            }

            return $response;
        } catch (\Throwable $e) {
            $latency = (int) ((microtime(true) - $startTime) * 1000);
            $this->logToDatabase($messages, null, $latency, 'error', $e->getMessage());
            $this->reportUsage($e);
            throw $e;
        }
    }

    public function generate(string $prompt, array $options = []): ?string
    {
        $startTime = microtime(true);

        try {
            $options = $this->mergeDefaultOptions($options);
            $options['timeout'] = (int) ($options['timeout'] ?? 60);

            $response = $this->driver->generate($prompt, $options);
            $response = $this->cleanResponse($response);
            $latency = (int) ((microtime(true) - $startTime) * 1000);

            if ($response === null || (is_string($response) && trim($response) === '')) {
                $this->logToDatabase(['prompt' => $prompt], null, $latency, 'error', 'AI driver returned an empty or null response.');
            } else {
                $this->logToDatabase(['prompt' => $prompt], $response, $latency, 'success');
                $this->reportUsage();
            }

            return $response;
        } catch (\Throwable $e) {
            $latency = (int) ((microtime(true) - $startTime) * 1000);
            $this->logToDatabase(['prompt' => $prompt], null, $latency, 'error', $e->getMessage());
            $this->reportUsage($e);
            throw $e;
        }
    }

    public function metadata(): array
    {
        return array_merge(
            [
                'driver' => $this->driverName,
                'feature' => $this->feature,
            ],
            $this->driver->metadata()
        );
    }

    protected function logToDatabase(array $input, mixed $output, int $latency, string $status, ?string $error = null): void
    {
        $metadata = $this->metadata();
        $payload = [
            'feature' => $this->feature,
            'driver' => $this->driverName,
            'model' => $this->resolveModel($metadata, $input),
            'input' => $input,
            'output' => is_string($output) ? ['text' => $output] : $output,
            'latency_ms' => $latency,
            'status' => $status,
            'error_message' => $error,
        ];

        $this->recordAiLog($payload);
    }

    protected function cleanResponse(?string $response): ?string
    {
        if ($response === null) {
            return null;
        }

        if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/i', trim($response), $matches)) {
            return trim($matches[1]);
        }

        return $response;
    }

    protected function resolveModel(array $metadata, array $input): ?string
    {
        $candidates = [
            $metadata['model'] ?? null,
            $input['model'] ?? null,
            $input['model_name'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }

    protected function mergeDefaultOptions(array $options): array
    {
        foreach ($this->defaultOptions as $key => $value) {
            if (!array_key_exists($key, $options) && $value !== null && $value !== '') {
                $options[$key] = $value;
            }
        }

        return $options;
    }

    protected function reportUsage(?\Throwable $e = null): void
    {
        if (!$this->keyPoolEntry) {
            return;
        }

        app(\App\Modules\Intelligence\Actions\ReportKeyUsageAction::class)->handle(
            $this->keyPoolEntry,
            $this->resolveErrorCodeFromThrowable($e)
        );
    }
}
