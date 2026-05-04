<?php

namespace Tests\Unit\Intelligence;

use App\Modules\Intelligence\Services\AI\Drivers\OpenAiDriver;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiDriverTest extends TestCase
{
    public function test_chat_throws_on_http_errors_so_pool_can_apply_cooldown(): void
    {
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'message' => 'Rate limit reached',
                ],
            ], 429),
        ]);

        $driver = new OpenAiDriver(
            'https://api.openai.com/v1/chat/completions',
            'sk-test',
            'gpt-4o'
        );

        $this->expectException(RequestException::class);

        $driver->chat([
            ['role' => 'user', 'content' => 'hello'],
        ]);
    }
}
