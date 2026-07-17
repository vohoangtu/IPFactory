<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Simulation\Services\Meta\UniverseRuntimeService;
use App\Modules\WorldOS\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SimulationAdvanceRouteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Không có token -> 401. Route tồn tại nhưng auth:sanctum middleware chặn
     * trước khi tới controller, nên không cần DB / mock service ở case này.
     */
    public function test_advance_without_token_returns_401(): void
    {
        $this->postJson('/api/worldos/simulation/advance', [
            'universe_id' => 5,
            'ticks' => 3,
        ])->assertStatus(401);
    }

    /**
     * Ghi chú hạ tầng: `User::factory()->create()` (như brief đề xuất) crash với
     * "Class App\User not found" — Factory::modelName() guess sai namespace vì
     * User thật nằm ở App\Modules\WorldOS\Models\User, không phải App\Models\User
     * (xem UserFactory@newModel / Factory::modelName()). Toàn bộ 20 test hiện có
     * dùng User::factory()->create() trong repo đều FAIL vì lý do này (pre-existing
     * test-rot, ngoài phạm vi task 2b) — đã verify bằng cách chạy trực tiếp.
     *
     * Workaround (không đụng UserFactory dùng chung): dựng User bằng `new User(...)`
     * + save() thủ công, bỏ qua Factory::modelName() hoàn toàn. Đã verify cách này
     * hoạt động với Sanctum::actingAs trước khi dùng cho case (b)/(c) dưới đây.
     */
    private function actingAsUser(): User
    {
        $user = new User([
            'name' => 'Advance Route Test User',
            'email' => 'advance-route-test@example.com',
            'password' => bcrypt('secret'),
        ]);
        $user->save();

        Sanctum::actingAs($user, ['*']);

        return $user;
    }

    public function test_advance_with_auth_delegates_to_universe_runtime_service(): void
    {
        $this->actingAsUser();

        $this->mock(UniverseRuntimeService::class)
            ->shouldReceive('advance')
            ->once()
            ->with(5, 3)
            ->andReturn(['tick' => 8]);

        $response = $this->postJson('/api/worldos/simulation/advance', [
            'universe_id' => 5,
            'ticks' => 3,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.tick', 8);
    }

    public function test_advance_without_universe_id_returns_422(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/worldos/simulation/advance', [
            'ticks' => 3,
        ])->assertStatus(422);
    }

    /**
     * Khoa bien tren cua `ticks`: FE (`TickAdvancePanel`) cho phep toi 1000 (kem
     * confirm dialog khi >100), BE phai dong bo max:1000 — 1001 phai bi tu choi
     * o tang validation, khong duoc lot xuong goi UniverseRuntimeService.
     */
    public function test_advance_with_ticks_over_1000_returns_422(): void
    {
        $this->actingAsUser();

        $this->mock(UniverseRuntimeService::class)
            ->shouldNotReceive('advance');

        $this->postJson('/api/worldos/simulation/advance', [
            'universe_id' => 5,
            'ticks' => 1001,
        ])->assertStatus(422);
    }
}
