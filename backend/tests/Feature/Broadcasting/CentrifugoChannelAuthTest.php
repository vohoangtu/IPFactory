<?php

declare(strict_types=1);

namespace Tests\Feature\Broadcasting;

use App\Broadcasting\CentrifugoBroadcaster;
use App\Modules\World\Models\Universe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CentrifugoChannelAuthTest extends TestCase
{
    use RefreshDatabase;

    private function authFor(string $channel): mixed
    {
        $request = Request::create('/broadcasting/auth', 'POST', ['channel' => $channel]);

        return (new CentrifugoBroadcaster())->auth($request);
    }

    public function test_lens_channels_of_existing_universe_are_authorized(): void
    {
        $universe = Universe::factory()->create(['status' => 'active']);

        $this->assertTrue((bool) $this->authFor("universes:{$universe->id}"));
        $this->assertTrue((bool) $this->authFor("universes:{$universe->id}:narrative"));
        $this->assertTrue((bool) $this->authFor("universes:{$universe->id}:anomaly"));
        $this->assertTrue((bool) $this->authFor("universes:{$universe->id}:autopoiesis"));
    }

    public function test_public_prefix_is_always_authorized(): void
    {
        $this->assertTrue((bool) $this->authFor('public:universes'));
    }

    public function test_unknown_universe_and_legacy_dot_channels_are_denied(): void
    {
        $this->assertFalse((bool) $this->authFor('universes:999999'));
        $this->assertFalse((bool) $this->authFor('universes:1:unknown-suffix'));
        $this->assertFalse((bool) $this->authFor('universe.1.narrative'));
        $this->assertFalse((bool) $this->authFor('simulation.alerts'));
    }

    public function test_auth_allows_global_universe_channel(): void
    {
        $this->assertTrue((bool) $this->authFor('global_universe'));
    }

    public function test_auth_allows_narrative_task_channels(): void
    {
        $this->assertTrue((bool) $this->authFor('narrative:12:task-abc_123'));
        $this->assertFalse((bool) $this->authFor('narrative:abc'));
    }
}
