<?php

namespace Tests\Unit\Modules\Intelligence;

use Tests\TestCase;
use App\Modules\Intelligence\Models\AiKeyPool;
use App\Modules\Intelligence\Actions\RotateKeyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class RotateKeyActionTest extends TestCase
{
    use RefreshDatabase;

    protected RotateKeyAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new RotateKeyAction();
    }

    public function test_it_prioritizes_free_over_premium_when_any_is_requested()
    {
        // Tạo 1 key premium và 1 key free
        AiKeyPool::create([
            'provider' => 'openai',
            'label' => 'Premium Key',
            'key_encrypted' => 'sk-premium',
            'tier' => 'premium',
            'level' => 1,
            'status' => 'active',
        ]);

        AiKeyPool::create([
            'provider' => 'openai',
            'label' => 'Free Key',
            'key_encrypted' => 'sk-free',
            'tier' => 'free',
            'level' => 1,
            'status' => 'active',
        ]);

        $selectedKey = $this->action->handle('any');

        $this->assertEquals('free', $selectedKey->tier);
        $this->assertEquals('Free Key', $selectedKey->label);
    }

    public function test_it_uses_round_robin_within_the_same_tier()
    {
        // Tạo 2 key free với thời gian sử dụng khác nhau
        AiKeyPool::create([
            'provider' => 'openai',
            'label' => 'Free Key 1',
            'key_encrypted' => 'sk-free-1',
            'tier' => 'free',
            'last_used_at' => now()->subMinutes(10),
            'status' => 'active',
        ]);

        AiKeyPool::create([
            'provider' => 'openai',
            'label' => 'Free Key 2',
            'key_encrypted' => 'sk-free-2',
            'tier' => 'free',
            'last_used_at' => now()->subMinutes(20), // Thằng này cũ hơn -> nên được chọn
            'status' => 'active',
        ]);

        $selectedKey = $this->action->handle('free');

        $this->assertEquals('Free Key 2', $selectedKey->label);
    }

    public function test_it_skips_keys_in_cooldown()
    {
        AiKeyPool::create([
            'provider' => 'openai',
            'label' => 'Cooldown Key',
            'key_encrypted' => 'sk-cooldown',
            'tier' => 'free',
            'status' => 'cooldown',
            'cooldown_until' => now()->addHour(),
        ]);

        AiKeyPool::create([
            'provider' => 'openai',
            'label' => 'Active Key',
            'key_encrypted' => 'sk-active',
            'tier' => 'free',
            'status' => 'active',
        ]);

        $selectedKey = $this->action->handle('free');

        $this->assertEquals('Active Key', $selectedKey->label);
    }

    public function test_it_filters_by_provider_and_level()
    {
        AiKeyPool::create([
            'provider' => 'gemini',
            'label' => 'Gemini Premium',
            'key_encrypted' => 'sk-gemini',
            'tier' => 'premium',
            'level' => 2,
            'status' => 'active',
        ]);

        AiKeyPool::create([
            'provider' => 'openai',
            'label' => 'OpenAI Premium',
            'key_encrypted' => 'sk-openai',
            'tier' => 'premium',
            'level' => 1,
            'status' => 'active',
        ]);

        // Yêu cầu premium, nhưng lọc theo openai
        $selectedKey = $this->action->handle('premium', 'openai');

        $this->assertEquals('openai', $selectedKey->provider);
        $this->assertEquals(1, $selectedKey->level);
    }
}
