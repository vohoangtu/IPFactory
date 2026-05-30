<?php

declare(strict_types=1);

namespace App\Broadcasting;

use App\Modules\World\Models\Universe;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Firebase\JWT\JWT;
use Illuminate\Broadcasting\BroadcastException;

/**
 * Centrifugo WebSocket broadcaster with dead-letter-queue resilience.
 *
 * Failed publishes are stored in a Redis-backed dead letter queue and retried
 * on the next successful broadcast cycle (up to 3 retry attempts).
 */
class CentrifugoBroadcaster extends Broadcaster
{
    private const DEAD_LETTER_KEY = 'centrifugo:dead_letter_queue';
    private const MAX_DEAD_LETTER_RETRIES = 3;
    private const DEAD_LETTER_TTL_SECONDS = 3600;
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
        if (preg_match('/^universes:(\d+)$/', $channel, $matches)) {
            $universeId = (int) $matches[1];

            return Cache::remember(
                "centrifugo:auth:universe:{$universeId}",
                60,
                fn () => Universe::where('id', $universeId)
                    ->whereIn('status', ['active', 'paused'])
                    ->exists()
            );
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
            $isBinary = isset($payload['tick']); // Nếu là nhịp đập vũ trụ, phát dạng nhị phân

            $params = ['channel' => $channel];
            if ($isBinary) {
                $params['data_base64'] = base64_encode(json_encode($payload));
            } else {
                $params['data'] = $payload;
            }

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
            $queue = Cache::get(self::DEAD_LETTER_KEY, []);
            if (count($queue) >= 100) {
                // Drop oldest entry if queue is full to prevent unbounded growth.
                array_shift($queue);
            }
            $queue[] = $entry;
            Cache::put(self::DEAD_LETTER_KEY, $queue, self::DEAD_LETTER_TTL_SECONDS);

            Log::warning('Centrifugo: broadcast enqueued to dead letter', [
                'channels' => $channels,
                'event' => $event,
                'queue_size' => count($queue),
            ]);
        } catch (\Throwable $e) {
            Log::error('Centrifugo: failed to enqueue dead letter: ' . $e->getMessage());
        }
    }

    /**
     * Flush pending dead letter messages, retrying up to MAX_DEAD_LETTER_RETRIES.
     *
     * This is called before each new broadcast so the queue self-heals
     * when connectivity recovers.
     */
    private function flushDeadLetterQueue(string $url, string $apiKey): void
    {
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

    /**
     * Get the current dead letter queue size (for monitoring).
     */
    public function getDeadLetterQueueSize(): int
    {
        return count(Cache::get(self::DEAD_LETTER_KEY, []));
    }
}
