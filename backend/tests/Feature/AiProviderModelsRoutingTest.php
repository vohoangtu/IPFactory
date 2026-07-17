<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class AiProviderModelsRoutingTest extends TestCase
{
    public function test_export_is_not_shadowed_by_show_and_requires_auth(): void
    {
        // Trước fix: 'export' khớp route public {id} → show('export') → 404 (hoặc 500).
        // Sau fix: khớp route export (protected) → 401 vì thiếu token.
        $this->getJson('/api/ai-provider-models/export')->assertStatus(401);
    }
}
