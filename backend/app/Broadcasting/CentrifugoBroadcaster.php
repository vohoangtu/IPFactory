<?php

declare(strict_types=1);

namespace App\Broadcasting;

use App\Modules\World\Models\Universe;
use Firebase\JWT\JWT;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Centrifugo WebSocket broadcaster with dead-letter-queue resilience.
 *
 * Failed publishes are stored in a Redis-backed dead letter queue and retried
 * on the next successful broadcast cycle (up to 3 retry attempts).
 */
class CentrifugoBroadcaster extends Broadcaster
{
    private const DEAD_LETTER_KEY = 'centrifugo:dead_letter_queue';
    private const DEAD_LETTER_LOCK_KEY = 'centrifugo:dead_letter_lock';
    private const DEAD_LETTER_LOCK_TTL_SECONDS = 5;
    private const MAX_DEAD_LETTER_RETRIES = 3;
    private const DEAD_LETTER_TTL_SECONDS = 3600;
    private const DEAD_LETTER_MAX_SIZE = 100;
    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $channel
     * @return mixed
     */
    public function auth($request)
    {
        $channel = $request->input('channel', '');

        // Public channels: allow all authenticated users (or anonymous)
        if (str_starts_with($channel, 'public:')) {
            return true;
        }

        // Universe-specific channels: verify the universe exists and is accessible
        if (preg_match('/^universes:(\d+)(?::(narrative|anomaly|autopoiesis))?$/', $channel, $matches)) {
            $universeId = (int) $matches[1];

            return Cache::remember(
                "centrifugo:auth:universe:{$universeId}",
                60,
                fn () => Universe::where('id', $universeId)
                    ->whereIn('status', ['active', 'paused'])
                    ->exists()
            );
        }

        // Kênh hệ thống: soundtrack toàn cục (SoundtrackChanged) — read-only, cho phép mọi client đã kết nối.
        if ($channel === 'global_universe') {
            return true;
        }

        // Kênh task tường thuật: narrative:{worldId}:{taskId} — publish từ narrative-loom, client chỉ nghe.
        if (preg_match('/^narrative:\d+:[A-Za-z0-9_-]+$/', $channel)) {
            return true;
        }

        // Unknown channel pattern: deny
        return false;
    }

    /**
     * Return the valid authentication response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $result
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
    {
        return $result;
    }

    /**
     * Generate a connection token for Centrifugo.
     * Useful for Frontend to connect securely via WebSocket.
     *
     * @param string $userId
     * @param int $exp
     * @return string
     */
    public function generateToken(string $userId, int $exp = 86400): string
    {
        $secret = config('centrifugo.secret');
        if (empty($secret)) {
            throw new \RuntimeException("Centrifugo secret not configured.");
        }

        $payload = [
            'sub' => $userId,
            'exp' => time() + $exp,
            'iat' => time(),
        ];

        return JWT::encode($payload, $secret, 'HS256');
    }

    /**
     * Broadcast the given event.
     *
     * @param  array  $channels
     * @param  string  $event
     * @param  array  $payload
     * @return void
     *
     * @throws \Illuminate\Broadcasting\BroadcastException
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $url = rtrim(config('centrifugo.url'), '/') . '/api';
        $apiKey = config('centrifugo.api_key');

        if (empty($url) || empty($apiKey)) {
            return;
        }

        // Centrifugo rejects publish with empty data — skip if nothing to send
        if (empty($payload)) {
            return;
        }

        // Flush pending dead letter messages before this broadcast.
        $this->flushDeadLetterQueue($url, $apiKey);

        // Build newline-delimited JSON body for batch publishing
        $body = "";
        foreach ($this->formatChannels($channels) as $channel) {
            $params = ['channel' => $channel, 'data' => $payload];

            $body .= json_encode([
                'method' => 'publish',
                'params' => $params,
            ]) . "\n";
        }

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withHeaders([
            'X-API-Key' => $apiKey,
            'X-Centrifugo-Error-Mode' => 'any',
        ])->withBody($body, 'application/json')->post($url);

        if ($response->failed()) {
            $errorMsg = $response->json('error.message') ?? $response->body();

            // Enqueue failed payload for later retry.
            $this->enqueueDeadLetter($channels, $event, $payload);

            throw new BroadcastException(
                "Centrifugo broadcast error: {$errorMsg} (payload enqueued to dead letter queue)"
            );
        }
    }

    /**
     * Enqueue a failed broadcast for later retry.
     *
     * Wrapped in Cache::lock to make read-modify-write atomic across processes.
     * Without the lock, concurrent broadcast() calls could lose newly-enqueued
     * entries: thread A reads [X], thread B reads [X], both write [X, …] — the
     * last writer wins and one of the new entries is silently dropped.
     */
    private function enqueueDeadLetter(array $channels, string $event, array $payload): void
    {
        $entry = [
            'channels' => $channels,
            'event' => $event,
            'payload' => $payload,
            'retries' => 0,
            'queued_at' => now()->toISOString(),
        ];

        try {
            Cache::lock(self::DEAD_LETTER_LOCK_KEY, self::DEAD_LETTER_LOCK_TTL_SECONDS)->block(
                self::DEAD_LETTER_LOCK_TTL_SECONDS,
                function () use ($entry) {
                    $queue = Cache::get(self::DEAD_LETTER_KEY, []);
                    if (count($queue) >= self::DEAD_LETTER_MAX_SIZE) {
                        // Drop oldest entry if queue is full to prevent unbounded growth.
                        array_shift($queue);
                    }
                    $queue[] = $entry;
                    Cache::put(self::DEAD_LETTER_KEY, $queue, self::DEAD_LETTER_TTL_SECONDS);

                    Log::warning('Centrifugo: broadcast enqueued to dead letter', [
                        'channels' => $entry['channels'],
                        'event' => $entry['event'],
                        'queue_size' => count($queue),
                    ]);
                }
            );
        } catch (\Throwable $e) {
            Log::error('Centrifugo: failed to enqueue dead letter: ' . $e->getMessage());
        }
    }

    /**
     * Flush pending dead letter messages, retrying up to MAX_DEAD_LETTER_RETRIES.
     *
     * This is called before each new broadcast so the queue self-heals
     * when connectivity recovers.
     *
     * Read-modify-write of the queue is wrapped in Cache::lock so concurrent
     * flushes + enqueues from other broadcast() invocations cannot clobber
     * each other (TOCTOU race on the shared Redis hash).
     */
    private function flushDeadLetterQueue(string $url, string $apiKey): void
    {
        try {
            Cache::lock(self::DEAD_LETTER_LOCK_KEY, self::DEAD_LETTER_LOCK_TTL_SECONDS)->block(
                self::DEAD_LETTER_LOCK_TTL_SECONDS,
                function () use ($url, $apiKey) {
                    $queue = Cache::get(self::DEAD_LETTER_KEY, []);

                    if (empty($queue)) {
                        return;
                    }

                    $remaining = [];
                    $flushed = 0;
                    $dropped = 0;

                    foreach ($queue as $entry) {
                        $retries = (int) ($entry['retries'] ?? 0);

                        if ($retries >= self::MAX_DEAD_LETTER_RETRIES) {
                            $dropped++;
                            Log::warning('Centrifugo: dead letter discarded after max retries', [
                                'channels' => $entry['channels'] ?? [],
                                'event' => $entry['event'] ?? 'unknown',
                                'retries' => $retries,
                            ]);
                            continue;
                        }

                        try {
                            $body = '';
                            foreach ($entry['channels'] as $channel) {
                                $body .= json_encode([
                                    'method' => 'publish',
                                    'params' => [
                                        'channel' => $channel,
                                        'data' => $entry['payload'],
                                    ],
                                ]) . "\n";
                            }

                            $response = Http::withHeaders([
                                'X-API-Key' => $apiKey,
                            ])->withBody($body, 'application/json')->post($url);

                            if ($response->failed()) {
                                $entry['retries'] = $retries + 1;
                                $remaining[] = $entry;
                            } else {
                                $flushed++;
                            }
                        } catch (\Throwable $e) {
                            $entry['retries'] = $retries + 1;
                            $remaining[] = $entry;
                        }
                    }

                    if ($flushed > 0 || $dropped > 0) {
                        Log::info('Centrifugo: dead letter flush complete', [
                            'flushed' => $flushed,
                            'dropped' => $dropped,
                            'remaining' => count($remaining),
                        ]);
                    }

                    Cache::put(
                        self::DEAD_LETTER_KEY,
                        $remaining,
                        $remaining ? self::DEAD_LETTER_TTL_SECONDS : 1 // expire immediately if empty
                    );
                }
            );
        } catch (\Throwable $e) {
            // If we can't acquire the lock or anything else fails, leave the
            // queue intact so the next broadcast can retry. We do not want a
            // transient lock failure to lose pending messages.
            Log::warning('Centrifugo: dead letter flush skipped due to lock failure: ' . $e->getMessage());
        }
    }

    /**
     * Get the current dead letter queue size (for monitoring).
     */
    public function getDeadLetterQueueSize(): int
    {
        return count(Cache::get(self::DEAD_LETTER_KEY, []));
    }
}
