<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Services\AI\Concerns;

use App\Modules\Intelligence\Models\AiLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Shared AI audit logging + error-code resolution logic.
 *
 * Used by AiDriverProxy (in-process LLM calls) and LoomIntentClient
 * (remote Python service calls) to persist a consistent AiLog record and
 * detect transient provider errors (401/429) for key rotation.
 */
trait LogsAiCalls
{
    private static ?bool $aiLogsHasModelColumnCache = null;

    /**
     * Persist an AiLog entry. Gracefully falls back if the `model` column is
     * missing on older schemas.
     */
    protected function recordAiLog(array $payload): void
    {
        try {
            if (!$this->aiLogsHasModelColumn()) {
                unset($payload['model']);
            }

            AiLog::create($payload);
        } catch (\Throwable $e) {
            if (!$this->shouldRetryWithoutModel($e, $payload)) {
                Log::warning('Failed to record AI log: ' . $e->getMessage());
                return;
            }

            unset($payload['model']);
            self::$aiLogsHasModelColumnCache = false;

            try {
                AiLog::create($payload);
            } catch (\Throwable $retry) {
                Log::warning('Failed to record AI log (retry): ' . $retry->getMessage());
            }
        }
    }

    protected function aiLogsHasModelColumn(): bool
    {
        if (self::$aiLogsHasModelColumnCache === null) {
            try {
                self::$aiLogsHasModelColumnCache = Schema::hasColumn('ai_logs', 'model');
            } catch (\Throwable) {
                self::$aiLogsHasModelColumnCache = false;
            }
        }

        return self::$aiLogsHasModelColumnCache;
    }

    protected function shouldRetryWithoutModel(\Throwable $e, array $payload): bool
    {
        if (!array_key_exists('model', $payload)) {
            return false;
        }

        $message = strtolower($e->getMessage());

        return str_contains($message, 'column "model"')
            || str_contains($message, "column 'model'")
            || str_contains($message, 'undefined column')
            || str_contains($message, 'sqlstate[42703]');
    }

    /**
     * Resolve a provider error code from a thrown exception (401, 429, other).
     */
    protected function resolveErrorCodeFromThrowable(?\Throwable $e = null): ?int
    {
        if (!$e) {
            return null;
        }

        $errorCode = null;

        if ($e instanceof \Illuminate\Http\Client\RequestException) {
            $errorCode = $e->response?->status() ?? $e->getCode();
        } elseif (method_exists($e, 'getCode')) {
            $errorCode = (int) $e->getCode();
        }

        $message = strtolower($e->getMessage());

        if ($errorCode === 401
            || str_contains($message, '401')
            || str_contains($message, 'unauthorized')
            || str_contains($message, 'invalid api key')
            || str_contains($message, 'incorrect api key')
            || str_contains($message, 'token expired')) {
            return 401;
        }

        if ($errorCode === 429
            || str_contains($message, '429')
            || str_contains($message, 'rate limit')) {
            return 429;
        }

        return $errorCode ?: null;
    }

    /**
     * Resolve a provider error code from an HTTP failure response body.
     */
    protected function resolveErrorCodeFromHttpFailure(int $status, string $body): ?int
    {
        $message = strtolower($body);

        if ($status === 401
            || str_contains($message, '401')
            || str_contains($message, 'unauthorized')
            || str_contains($message, 'invalid api key')
            || str_contains($message, 'incorrect api key')
            || str_contains($message, 'token expired')) {
            return 401;
        }

        if ($status === 429
            || str_contains($message, '429')
            || str_contains($message, 'rate limit')) {
            return 429;
        }

        return null;
    }
}
