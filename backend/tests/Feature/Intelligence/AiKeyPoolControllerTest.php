<?php

namespace Tests\Feature\Intelligence;

use App\Modules\Intelligence\Models\AiKeyPool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiKeyPoolControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_masks_encrypted_keys_in_pool_responses(): void
    {
        AiKeyPool::create([
            'provider' => 'openai',
            'label' => 'Primary OpenAI',
            'key_encrypted' => encrypt('sk-test-secret-1234'),
            'tier' => 'premium',
            'level' => 1,
            'is_free' => false,
            'usage_count' => 2,
            'status' => 'active',
            'metadata' => [
                'model' => 'gpt-4o',
                'url' => 'https://api.openai.com/v1/chat/completions',
            ],
        ]);

        $response = $this->getJson('/api/ai-key-pool');

        $response->assertOk();
        $response->assertJsonMissingPath('0.key_encrypted');
        $response->assertJsonPath('0.label', 'Primary OpenAI');
        $response->assertJsonPath('0.key_preview', '********1234');
    }

    public function test_store_returns_safe_payload_without_encrypted_key(): void
    {
        $response = $this->postJson('/api/ai-key-pool', [
            'provider' => 'gemini',
            'label' => 'Gemini Flash',
            'key' => 'gm-test-secret-5678',
            'tier' => 'free',
            'level' => 2,
            'model_group' => 'flash',
            'metadata' => [
                'model' => 'gemini-1.5-flash',
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonMissingPath('key_encrypted');
        $response->assertJsonPath('provider', 'gemini');
        $response->assertJsonPath('key_preview', '********5678');
    }
}
