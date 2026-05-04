<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Redis-backed Circuit Breaker.
 *
 * States:
 *   CLOSED   → Normal operation, calls pass through
 *   OPEN     → Calls fail fast, no HTTP requests made
 *   HALF_OPEN → One probe call allowed to test recovery
 *
 * After $failureThreshold consecutive failures, circuit opens for $cooldownSeconds.
 */
final class CircuitBreaker
{
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';

    public function __construct(
        private readonly string $service,
        private readonly int $failureThreshold = 3,
        private readonly int $cooldownSeconds = 60,
    ) {}

    /**
     * Check if a call should be allowed.
     */
    public function isAvailable(): bool
    {
        $state = $this->getState();

        if ($state === self::STATE_CLOSED) {
            return true;
        }

        if ($state === self::STATE_OPEN) {
            $openedAt = (int) Cache::get($this->key('opened_at'), 0);
            if (time() - $openedAt >= $this->cooldownSeconds) {
                // Transition to half-open: allow one probe
                $this->setState(self::STATE_HALF_OPEN);
                Log::info("CircuitBreaker [{$this->service}]: transitioning to HALF_OPEN");

                return true;
            }

            return false;
        }

        // HALF_OPEN: allow one probe
        return true;
    }

    /**
     * Record a successful call.
     */
    public function recordSuccess(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_HALF_OPEN) {
            Log::info("CircuitBreaker [{$this->service}]: probe succeeded, closing circuit");
        }

        // Reset to closed state
        Cache::put($this->key('failures'), 0, 3600);
        $this->setState(self::STATE_CLOSED);
    }

    /**
     * Record a failed call.
     */
    public function recordFailure(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_HALF_OPEN) {
            // Probe failed — reopen circuit
            $this->openCircuit();
            Log::warning("CircuitBreaker [{$this->service}]: probe failed, reopening circuit");

            return;
        }

        $failures = (int) Cache::get($this->key('failures'), 0);
        $failures++;
        Cache::put($this->key('failures'), $failures, 3600);

        if ($failures >= $this->failureThreshold) {
            $this->openCircuit();
            Log::warning("CircuitBreaker [{$this->service}]: threshold reached ({$failures}/{$this->failureThreshold}), opening circuit");
        }
    }

    /**
     * Get the current circuit state.
     */
    public function getState(): string
    {
        return Cache::get($this->key('state'), self::STATE_CLOSED);
    }

    /**
     * Get consecutive failure count.
     */
    public function getFailureCount(): int
    {
        return (int) Cache::get($this->key('failures'), 0);
    }

    private function openCircuit(): void
    {
        $this->setState(self::STATE_OPEN);
        Cache::put($this->key('opened_at'), time(), $this->cooldownSeconds + 60);
        Cache::put($this->key('failures'), 0, 3600);
    }

    private function setState(string $state): void
    {
        Cache::put($this->key('state'), $state, 3600);
    }

    private function key(string $suffix): string
    {
        return "circuit_breaker:{$this->service}:{$suffix}";
    }
}
