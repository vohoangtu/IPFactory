<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Bảo vệ hồi quy cho các bản vá bảo mật P0 (review 2026-06-13):
 *  - loom-key / webhook: endpoint internal phải có shared secret (X-Loom-Secret).
 *  - loom proxy: phải đăng nhập (auth:sanctum).
 */
class LoomSecurityTest extends TestCase
{
    public function test_loom_key_rejects_request_without_or_wrong_shared_secret(): void
    {
        config(['services.loom.shared_secret' => 'unit-test-secret']);

        $this->getJson('/api/ai-settings/loom-key')->assertStatus(401);
        $this->postJson('/api/ai-settings/loom-key')->assertStatus(401);
        $this->getJson('/api/ai-settings/loom-key', ['X-Loom-Secret' => 'wrong'])->assertStatus(401);
    }

    public function test_loom_key_passes_middleware_with_correct_secret(): void
    {
        config(['services.loom.shared_secret' => 'unit-test-secret']);

        // Middleware đi qua → controller chạy (có thể 200/500 tùy pool) nhưng KHÔNG bao giờ 401/503.
        $status = $this->getJson('/api/ai-settings/loom-key', ['X-Loom-Secret' => 'unit-test-secret'])->status();
        $this->assertNotContains($status, [401, 503]);
    }

    public function test_loom_endpoints_blocked_when_secret_unconfigured(): void
    {
        config(['services.loom.shared_secret' => null]);

        $this->getJson('/api/ai-settings/loom-key')->assertStatus(503);
    }

    public function test_webhook_rejects_request_without_shared_secret(): void
    {
        config(['services.loom.shared_secret' => 'unit-test-secret']);

        $this->postJson('/api/narrative-loom/webhook', ['type' => 'pipeline_done', 'world_id' => 1])
            ->assertStatus(401);
    }

    public function test_loom_proxy_requires_authentication(): void
    {
        $this->getJson('/api/loom/health')->assertStatus(401);
        $this->postJson('/api/loom/scribe-history')->assertStatus(401);
    }
}
