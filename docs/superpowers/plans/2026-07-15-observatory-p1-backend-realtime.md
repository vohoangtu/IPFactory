# Observatory Plan 1 — Backend Realtime Core Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Chuẩn hóa toàn bộ kênh Centrifugo về một quy ước duy nhất, bọc mọi broadcast trong `WorldEventEnvelope`, persist các sự kiện tường thuật vào `world_events`, và mở endpoint `observatory/feed` hợp nhất events + chronicles theo tick.

**Architecture:** Envelope là hợp đồng duy nhất giữa backend và frontend mới: mọi Event class broadcast qua trait `EmitsWorldEvent` (memoized envelope), một listener persist envelope của các sự kiện tường thuật vào bảng `world_events` sẵn có, và `GetObservatoryFeedAction` UNION `world_events` + `chronicles` thành một feed phân trang theo tick. Kênh chuẩn: `universes:{id}` (+ hậu tố `:narrative`/`:anomaly`/`:autopoiesis`), giữ nguyên `public:universes`, `global_universe`, `loom:system:status`, `narrative:{worldId}:{taskId}`.

**Tech Stack:** PHP 8.3 + Laravel 13 (Modular Monolith), PHPUnit (SQLite `:memory:`), Centrifugo HTTP API.

## Global Constraints

- **KHÔNG BAO GIỜ chạy `composer`/`npm`/`cargo` trên host.** Mọi lệnh chạy qua `docker compose -f deployment/docker-compose.prod.yml exec backend ...` (viết tắt dưới đây: `DC exec backend`).
- PHP giữ `^8.3` trong `composer.json`.
- File PHP MỚI: PSR-12 + `declare(strict_types=1)`. File sửa: giữ style hiện có, không thêm `strict_types` vào file cũ.
- Baseline test hiện tại: Unit `~166 pass / 3 fail / 92 skip` — 3 fail là pre-existing (`MeaningEngineTest` ×2, `IntelligenceExplosionTest` ×1), KHÔNG tính là regression.
- Controller mỏng → delegate cho Action (`implements \App\Contracts\ActionInterface` — marker interface, module WorldOS dùng method `handle()`).
- Không import Service/Action/Repository chéo module (guardrail `tests/Unit/Architecture/ModuleBoundaryTest.php` sẽ fail). Import Event class và Model chéo module được phép.
- Trả lời người dùng bằng tiếng Việt. Kết thúc phiên: cập nhật `.dev_status.md`.

## Quy ước kênh & envelope (hợp đồng cho Plan 2 FE)

| Kênh | Event (broadcastAs = envelope.type) |
|---|---|
| `universes:{id}` | `universe.pulsed` (UniversePulsed), `epoch.transitioned` (EpochTransitioned — phát thêm cả `public:universes`), `simulation.event` (SimulationEventStreamReceived) |
| `universes:{id}:narrative` | `artifact.discovered`, `celebrity.emerged`, `history.shifted`, `chronicle.generated` (TimelineController) |
| `universes:{id}:anomaly` | `anomaly.detected` |
| `universes:{id}:autopoiesis` | `autopoiesis.mutation` |
| `public:universes` | `pulsed` (UniverseSimulationPulsed), `epoch.transitioned`, `chronicle.generated` |
| `global_universe` | `SoundtrackChanged` — **giữ nguyên, không đụng** |

Envelope JSON (mọi payload broadcast):
```json
{"id": "uuid", "type": "epoch.transitioned", "tick": 120, "universe_id": 5,
 "world_id": 3, "severity": "info|notable|critical", "occurred_at": "ISO8601", "payload": {}}
```

Sự kiện được persist vào `world_events` (6 loại tường thuật): `epoch.transitioned`, `anomaly.detected`, `autopoiesis.mutation`, `artifact.discovered`, `celebrity.emerged`, `history.shifted`. KHÔNG persist pulse (đã có `universe_snapshots`).

`PowerSystemTransitionTriggered` không bao giờ được dispatch (dead code) — **bỏ qua trong plan này**, xóa ở Plan 4.

---

### Task 1: WorldEventEnvelope + trait EmitsWorldEvent

**Files:**
- Create: `backend/app/Support/Broadcasting/WorldEventEnvelope.php`
- Create: `backend/app/Support/Broadcasting/WorldEventBroadcast.php`
- Create: `backend/app/Support/Broadcasting/EmitsWorldEvent.php`
- Test: `backend/tests/Unit/Broadcasting/WorldEventEnvelopeTest.php`

**Interfaces:**
- Consumes: không có (task nền).
- Produces: `WorldEventEnvelope::__construct(string $type, int $tick, int $universeId, ?int $worldId = null, string $severity = 'info', array $payload = [], ?string $id = null, ?string $occurredAt = null)`, `toArray(): array`; interface `WorldEventBroadcast { envelope(): WorldEventEnvelope; }`; trait `EmitsWorldEvent` cung cấp `envelope()` memoized + `broadcastWith()`, yêu cầu class định nghĩa `protected function toEnvelope(): WorldEventEnvelope`.

- [ ] **Step 1: Viết test fail**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Broadcasting;

use App\Support\Broadcasting\WorldEventEnvelope;
use PHPUnit\Framework\TestCase;

class WorldEventEnvelopeTest extends TestCase
{
    public function test_to_array_has_all_envelope_keys(): void
    {
        $envelope = new WorldEventEnvelope(
            type: 'epoch.transitioned',
            tick: 120,
            universeId: 5,
            worldId: 3,
            severity: 'notable',
            payload: ['old' => 'Bronze', 'new' => 'Iron'],
        );

        $data = $envelope->toArray();

        $this->assertSame(
            ['id', 'type', 'tick', 'universe_id', 'world_id', 'severity', 'occurred_at', 'payload'],
            array_keys($data)
        );
        $this->assertSame('epoch.transitioned', $data['type']);
        $this->assertSame(120, $data['tick']);
        $this->assertSame(5, $data['universe_id']);
        $this->assertSame(3, $data['world_id']);
        $this->assertSame('notable', $data['severity']);
        $this->assertSame(['old' => 'Bronze', 'new' => 'Iron'], $data['payload']);
        $this->assertNotEmpty($data['id']);
        $this->assertNotEmpty($data['occurred_at']);
    }

    public function test_id_is_stable_across_calls_and_defaults_apply(): void
    {
        $envelope = new WorldEventEnvelope(type: 'anomaly.detected', tick: 1, universeId: 9);

        $this->assertSame($envelope->toArray()['id'], $envelope->toArray()['id']);
        $this->assertSame('info', $envelope->toArray()['severity']);
        $this->assertNull($envelope->toArray()['world_id']);
        $this->assertSame([], $envelope->toArray()['payload']);
    }
}
```

Lưu ý: test này extends `PHPUnit\Framework\TestCase` thuần (không boot Laravel) — `now()` cần app; vì vậy trong `WorldEventEnvelope` dùng `\date(DATE_ATOM)` thay vì `now()`.

- [ ] **Step 2: Chạy test, xác nhận fail**

Run: `docker compose -f deployment/docker-compose.prod.yml exec backend php artisan test --filter=WorldEventEnvelopeTest`
Expected: FAIL — `Class "App\Support\Broadcasting\WorldEventEnvelope" not found`

- [ ] **Step 3: Implement 3 file**

`backend/app/Support/Broadcasting/WorldEventEnvelope.php`:

```php
<?php

declare(strict_types=1);

namespace App\Support\Broadcasting;

use Illuminate\Support\Str;

/**
 * Phong bì sự kiện thống nhất — hợp đồng broadcast duy nhất giữa backend
 * và frontend Observatory. Mọi event realtime đều bọc payload trong cấu trúc này.
 */
final class WorldEventEnvelope
{
    public readonly string $id;

    public readonly string $occurredAt;

    public function __construct(
        public readonly string $type,
        public readonly int $tick,
        public readonly int $universeId,
        public readonly ?int $worldId = null,
        public readonly string $severity = 'info',
        public readonly array $payload = [],
        ?string $id = null,
        ?string $occurredAt = null,
    ) {
        $this->id = $id ?? (string) Str::uuid();
        $this->occurredAt = $occurredAt ?? date(DATE_ATOM);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'tick' => $this->tick,
            'universe_id' => $this->universeId,
            'world_id' => $this->worldId,
            'severity' => $this->severity,
            'occurred_at' => $this->occurredAt,
            'payload' => $this->payload,
        ];
    }
}
```

`backend/app/Support/Broadcasting/WorldEventBroadcast.php`:

```php
<?php

declare(strict_types=1);

namespace App\Support\Broadcasting;

interface WorldEventBroadcast
{
    public function envelope(): WorldEventEnvelope;
}
```

`backend/app/Support/Broadcasting/EmitsWorldEvent.php`:

```php
<?php

declare(strict_types=1);

namespace App\Support\Broadcasting;

trait EmitsWorldEvent
{
    private ?WorldEventEnvelope $worldEventEnvelope = null;

    abstract protected function toEnvelope(): WorldEventEnvelope;

    public function envelope(): WorldEventEnvelope
    {
        return $this->worldEventEnvelope ??= $this->toEnvelope();
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return $this->envelope()->toArray();
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận pass**

Run: `docker compose -f deployment/docker-compose.prod.yml exec backend php artisan test --filter=WorldEventEnvelopeTest`
Expected: PASS (2 tests)

- [ ] **Step 5: Commit**

```bash
git add backend/app/Support/Broadcasting/ backend/tests/Unit/Broadcasting/
git commit -m "feat(be): WorldEventEnvelope — phong bì sự kiện broadcast thống nhất"
```

---

### Task 2: CentrifugoBroadcaster — mở rộng auth regex + bỏ quirk base64

**Files:**
- Modify: `backend/app/Broadcasting/CentrifugoBroadcaster.php:46` (auth regex) và `:127-135` (base64 quirk)
- Test: `backend/tests/Feature/Broadcasting/CentrifugoChannelAuthTest.php`

**Interfaces:**
- Consumes: không có.
- Produces: auth chấp nhận `public:*`, `universes:{id}`, `universes:{id}:narrative|anomaly|autopoiesis`; publish luôn dùng `data` (JSON thuần, không còn `data_base64`).

- [ ] **Step 1: Viết test fail**

```php
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
}
```

- [ ] **Step 2: Chạy test, xác nhận fail**

Run: `docker compose -f deployment/docker-compose.prod.yml exec backend php artisan test --filter=CentrifugoChannelAuthTest`
Expected: FAIL — `test_lens_channels_...` fail vì regex hiện tại `/^universes:(\d+)$/` từ chối các hậu tố `:narrative` v.v.

- [ ] **Step 3: Sửa broadcaster**

Trong `auth()` thay regex (dòng 46):

```php
// TRƯỚC:
if (preg_match('/^universes:(\d+)$/', $channel, $matches)) {
// SAU:
if (preg_match('/^universes:(\d+)(?::(narrative|anomaly|autopoiesis))?$/', $channel, $matches)) {
```

Trong `broadcast()` bỏ quirk base64 (dòng 127-135) — envelope mới luôn có key `tick` nên quirk này sẽ base64-hóa toàn bộ; frontend mới cần JSON thuần:

```php
// TRƯỚC:
$isBinary = isset($payload['tick']); // Nếu là nhịp đập vũ trụ, phát dạng nhị phân

$params = ['channel' => $channel];
if ($isBinary) {
    $params['data_base64'] = base64_encode(json_encode($payload));
} else {
    $params['data'] = $payload;
}
// SAU:
$params = ['channel' => $channel, 'data' => $payload];
```

- [ ] **Step 4: Chạy test, xác nhận pass**

Run: `docker compose -f deployment/docker-compose.prod.yml exec backend php artisan test --filter=CentrifugoChannelAuthTest`
Expected: PASS (3 tests)

- [ ] **Step 5: Commit**

```bash
git add backend/app/Broadcasting/CentrifugoBroadcaster.php backend/tests/Feature/Broadcasting/
git commit -m "feat(be): auth kênh lens universes:{id}:* + bỏ quirk data_base64"
```

---

### Task 3: Chuẩn hóa 3 sự kiện narrative → `universes:{id}:narrative` + envelope

**Files:**
- Modify: `backend/app/Modules/World/Events/ArtifactDiscovered.php`
- Modify: `backend/app/Modules/SocialGraph/Events/CelebrityEmerged.php`
- Modify: `backend/app/Modules/Narrative/Events/HistoricalEpochShifted.php`
- Test: `backend/tests/Feature/Broadcasting/WorldEventBroadcastContractTest.php` (tạo mới ở task này, các task sau bổ sung)

**Interfaces:**
- Consumes: Task 1 (`EmitsWorldEvent`, `WorldEventEnvelope`, `WorldEventBroadcast`).
- Produces: 3 event phát trên `universes:{id}:narrative` với `broadcastAs()` = `artifact.discovered` / `celebrity.emerged` / `history.shifted`; payload = envelope. Constructor của cả 3 GIỮ NGUYÊN (call site `RawGenerationPostSnapshotHandler.php` không đổi).

- [ ] **Step 1: Viết test fail**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Broadcasting;

use App\Modules\Narrative\Events\HistoricalEpochShifted;
use App\Modules\SocialGraph\Events\CelebrityEmerged;
use App\Modules\World\Events\ArtifactDiscovered;
use Tests\TestCase;

/**
 * Hợp đồng kênh + envelope cho mọi broadcast event.
 * Chặn tái diễn lệch quy ước dấu chấm / hai chấm.
 */
class WorldEventBroadcastContractTest extends TestCase
{
    /** @return string[] */
    private function channelNames(object $event): array
    {
        $channels = $event->broadcastOn();

        return array_map(fn ($c) => (string) $c, is_array($channels) ? $channels : [$channels]);
    }

    private function assertEnvelope(array $data, string $type, int $tick, int $universeId): void
    {
        $this->assertSame(
            ['id', 'type', 'tick', 'universe_id', 'world_id', 'severity', 'occurred_at', 'payload'],
            array_keys($data)
        );
        $this->assertSame($type, $data['type']);
        $this->assertSame($tick, $data['tick']);
        $this->assertSame($universeId, $data['universe_id']);
    }

    public function test_artifact_discovered_contract(): void
    {
        $event = new ArtifactDiscovered(universeId: 5, tick: 42, zoneId: 1, artifactId: 7, mass: 1.5, knowledgeEncoded: 0.8);

        $this->assertSame(['universes:5:narrative'], $this->channelNames($event));
        $this->assertSame('artifact.discovered', $event->broadcastAs());
        $this->assertEnvelope($event->broadcastWith(), 'artifact.discovered', 42, 5);
        $this->assertSame(7, $event->broadcastWith()['payload']['artifact_id']);
    }

    public function test_celebrity_emerged_contract(): void
    {
        $event = new CelebrityEmerged(universeId: 5, tick: 42, zoneId: 1, agentId: 9, fame: 0.9, vocation: 'bard');

        $this->assertSame(['universes:5:narrative'], $this->channelNames($event));
        $this->assertSame('celebrity.emerged', $event->broadcastAs());
        $this->assertEnvelope($event->broadcastWith(), 'celebrity.emerged', 42, 5);
        $this->assertSame('bard', $event->broadcastWith()['payload']['vocation']);
    }

    public function test_historical_epoch_shifted_contract(): void
    {
        $event = new HistoricalEpochShifted(universeId: 5, tick: 42, zoneId: 1, eventType: 'war', impactScore: 0.7, triggerData: ['a' => 1]);

        $this->assertSame(['universes:5:narrative'], $this->channelNames($event));
        $this->assertSame('history.shifted', $event->broadcastAs());
        $this->assertEnvelope($event->broadcastWith(), 'history.shifted', 42, 5);
        $this->assertSame('notable', $event->broadcastWith()['severity']);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận fail**

Run: `docker compose -f deployment/docker-compose.prod.yml exec backend php artisan test --filter=WorldEventBroadcastContractTest`
Expected: FAIL — kênh hiện tại là `universe.5.narrative`, `broadcastAs` là `ArtifactDiscovered`…

- [ ] **Step 3: Sửa 3 event class**

`ArtifactDiscovered.php` — thay toàn bộ phần method (giữ constructor):

```php
use App\Support\Broadcasting\EmitsWorldEvent;
use App\Support\Broadcasting\WorldEventBroadcast;
use App\Support\Broadcasting\WorldEventEnvelope;

class ArtifactDiscovered implements ShouldBroadcast, WorldEventBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels, EmitsWorldEvent;

    // ... constructor giữ nguyên ...

    public function broadcastOn(): array
    {
        return [new Channel("universes:{$this->universeId}:narrative")];
    }

    public function broadcastAs(): string
    {
        return 'artifact.discovered';
    }

    protected function toEnvelope(): WorldEventEnvelope
    {
        return new WorldEventEnvelope(
            type: 'artifact.discovered',
            tick: $this->tick,
            universeId: $this->universeId,
            severity: 'notable',
            payload: [
                'zone_id' => $this->zoneId,
                'artifact_id' => $this->artifactId,
                'mass' => $this->mass,
                'knowledge_encoded' => $this->knowledgeEncoded,
            ],
        );
    }
}
```

`CelebrityEmerged.php` — tương tự: kênh `universes:{$this->universeId}:narrative`, `broadcastAs` = `'celebrity.emerged'`, envelope type `celebrity.emerged`, severity `'notable'`, payload:

```php
payload: [
    'zone_id' => $this->zoneId,
    'agent_id' => $this->agentId,
    'fame' => $this->fame,
    'vocation' => $this->vocation,
],
```

`HistoricalEpochShifted.php` — tương tự: `broadcastAs` = `'history.shifted'`, envelope type `history.shifted`, severity `'notable'`, payload:

```php
payload: [
    'zone_id' => $this->zoneId,
    'event_type' => $this->eventType,
    'impact_score' => $this->impactScore,
    'trigger_data' => $this->triggerData,
],
```

- [ ] **Step 4: Chạy test contract + test hồi quy handler dispatch**

Run: `docker compose -f deployment/docker-compose.prod.yml exec backend php artisan test --filter=WorldEventBroadcastContractTest`
Expected: PASS (3 tests)

Run: `docker compose -f deployment/docker-compose.prod.yml exec backend php artisan test --filter=RawGenerationPostSnapshotHandlerTest`
Expected: PASS (test hiện có assert dispatch 3 event này — constructor không đổi nên phải xanh)

- [ ] **Step 5: Commit**

```bash
git add backend/app/Modules/World/Events/ backend/app/Modules/SocialGraph/Events/ backend/app/Modules/Narrative/Events/ backend/tests/Feature/Broadcasting/
git commit -m "feat(be): chuẩn hóa 3 sự kiện narrative về universes:{id}:narrative + envelope"
```

---

### Task 4: Chuẩn hóa AnomalyDetected + AutopoiesisMutationApplied

**Files:**
- Modify: `backend/app/Modules/Simulation/Events/AnomalyDetected.php`
- Modify: `backend/app/Modules/Simulation/Events/AutopoiesisMutationApplied.php`
- Test: `backend/tests/Feature/Broadcasting/WorldEventBroadcastContractTest.php` (bổ sung)

**Interfaces:**
- Consumes: Task 1.
- Produces: `anomaly.detected` trên `universes:{id}:anomaly` (bỏ kênh `simulation.alerts`); `autopoiesis.mutation` trên `universes:{id}:autopoiesis`. Constructor cả 2 GIỮ NGUYÊN.

- [ ] **Step 1: Bổ sung test fail vào `WorldEventBroadcastContractTest`**

```php
use App\Modules\Simulation\Events\AnomalyDetected;
use App\Modules\Simulation\Events\AutopoiesisMutationApplied;
use App\Modules\World\Models\Universe;
use Illuminate\Foundation\Testing\RefreshDatabase;
// thêm `use RefreshDatabase;` vào class (AnomalyDetected cần Universe model)

public function test_anomaly_detected_contract(): void
{
    $universe = Universe::factory()->create(['current_tick' => 77]);
    $event = new AnomalyDetected($universe, [
        'title' => 'Entropy spike',
        'description' => 'Entropy vượt ngưỡng',
        'severity' => 'medium',
    ]);

    $this->assertSame(["universes:{$universe->id}:anomaly"], $this->channelNames($event));
    $this->assertSame('anomaly.detected', $event->broadcastAs());
    $data = $event->broadcastWith();
    $this->assertEnvelope($data, 'anomaly.detected', 77, $universe->id);
    $this->assertSame('notable', $data['severity']); // medium → notable
    $this->assertSame('Entropy spike', $data['payload']['title']);
}

public function test_autopoiesis_mutation_contract(): void
{
    $event = new AutopoiesisMutationApplied(universeId: 5, payload: ['tick' => 12, 'rule' => 'gravity_v2']);

    $this->assertSame(['universes:5:autopoiesis'], $this->channelNames($event));
    $this->assertSame('autopoiesis.mutation', $event->broadcastAs());
    $data = $event->broadcastWith();
    $this->assertEnvelope($data, 'autopoiesis.mutation', 12, 5);
    $this->assertSame('gravity_v2', $data['payload']['rule']);
}
```

- [ ] **Step 2: Chạy test, xác nhận 2 test mới fail**

Run: `docker compose -f deployment/docker-compose.prod.yml exec backend php artisan test --filter=WorldEventBroadcastContractTest`
Expected: 3 pass (task 3), 2 FAIL mới

- [ ] **Step 3: Sửa 2 event class**

`AnomalyDetected.php` (giữ constructor; xóa `broadcastWith()` cũ):

```php
use App\Support\Broadcasting\EmitsWorldEvent;
use App\Support\Broadcasting\WorldEventBroadcast;
use App\Support\Broadcasting\WorldEventEnvelope;

class AnomalyDetected implements ShouldBroadcast, WorldEventBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels, EmitsWorldEvent;

    // ... constructor giữ nguyên ...

    public function broadcastOn(): array
    {
        return [new Channel("universes:{$this->universe->id}:anomaly")];
    }

    public function broadcastAs(): string
    {
        return 'anomaly.detected';
    }

    protected function toEnvelope(): WorldEventEnvelope
    {
        return new WorldEventEnvelope(
            type: 'anomaly.detected',
            tick: (int) $this->universe->current_tick,
            universeId: (int) $this->universe->id,
            worldId: $this->universe->world_id !== null ? (int) $this->universe->world_id : null,
            severity: match ($this->anomaly['severity'] ?? null) {
                'low' => 'info',
                'medium' => 'notable',
                default => 'critical',
            },
            payload: [
                'title' => $this->anomaly['title'] ?? null,
                'description' => $this->anomaly['description'] ?? null,
                'raw_severity' => $this->anomaly['severity'] ?? null,
            ],
        );
    }
}
```

`AutopoiesisMutationApplied.php` (giữ constructor; xóa `broadcastWith()` cũ — trait cung cấp):

```php
use App\Support\Broadcasting\EmitsWorldEvent;
use App\Support\Broadcasting\WorldEventBroadcast;
use App\Support\Broadcasting\WorldEventEnvelope;

class AutopoiesisMutationApplied implements ShouldBroadcast, WorldEventBroadcast
{
    use Dispatchable, SerializesModels, EmitsWorldEvent;

    // ... constructor giữ nguyên ...

    public function broadcastOn(): array
    {
        return [new Channel("universes:{$this->universeId}:autopoiesis")];
    }

    public function broadcastAs(): string
    {
        return 'autopoiesis.mutation';
    }

    protected function toEnvelope(): WorldEventEnvelope
    {
        return new WorldEventEnvelope(
            type: 'autopoiesis.mutation',
            tick: (int) ($this->payload['tick'] ?? 0),
            universeId: $this->universeId,
            severity: 'notable',
            payload: $this->payload,
        );
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận pass**

Run: `docker compose -f deployment/docker-compose.prod.yml exec backend php artisan test --filter=WorldEventBroadcastContractTest`
Expected: PASS (5 tests)

- [ ] **Step 5: Commit**

```bash
git add backend/app/Modules/Simulation/Events/AnomalyDetected.php backend/app/Modules/Simulation/Events/AutopoiesisMutationApplied.php backend/tests/Feature/Broadcasting/
git commit -m "feat(be): chuẩn hóa anomaly + autopoiesis về kênh lens + envelope"
```

---

### Task 5: Chuẩn hóa các sự kiện pulse/epoch/stream

**Files:**
- Modify: `backend/app/Modules/Simulation/Events/UniversePulsed.php`
- Modify: `backend/app/Modules/Simulation/Events/EpochTransitioned.php`
- Modify: `backend/app/Modules/Simulation/Events/UniverseSimulationPulsed.php`
- Modify: `backend/app/Modules/Simulation/Events/SimulationEventStreamReceived.php`
- Test: `backend/tests/Feature/Broadcasting/WorldEventBroadcastContractTest.php` (bổ sung)

**Interfaces:**
- Consumes: Task 1.
- Produces: `universe.pulsed` trên `universes:{id}` (bỏ kênh `worlds.{id}`); `epoch.transitioned` trên `universes:{id}` + `public:universes`; `pulsed` (UniverseSimulationPulsed) giữ `public:universes` nhưng payload slim envelope; `simulation.event` trên `universes:{id}`. Mọi constructor GIỮ NGUYÊN.

- [ ] **Step 1: Bổ sung test fail**

```php
use App\Modules\Simulation\Events\EpochTransitioned;
use App\Modules\Simulation\Events\SimulationEventStreamReceived;
use App\Modules\Simulation\Events\UniversePulsed;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\World\Models\Epoch;

public function test_universe_pulsed_contract(): void
{
    $universe = Universe::factory()->create();
    $snapshot = (new UniverseSnapshot())->forceFill([
        'tick' => 8, 'entropy' => 0.42, 'stability_index' => 0.9, 'metrics' => ['population' => 10],
    ]);
    $event = new UniversePulsed($universe, $snapshot);

    $this->assertSame(["universes:{$universe->id}"], $this->channelNames($event));
    $this->assertSame('universe.pulsed', $event->broadcastAs());
    $data = $event->broadcastWith();
    $this->assertEnvelope($data, 'universe.pulsed', 8, $universe->id);
    $this->assertSame(0.42, $data['payload']['entropy']);
}

public function test_epoch_transitioned_contract(): void
{
    $universe = Universe::factory()->create();
    $old = (new Epoch())->forceFill(['id' => 1, 'name' => 'Bronze']);
    $new = (new Epoch())->forceFill(['id' => 2, 'name' => 'Iron']);
    $event = new EpochTransitioned($universe, $old, $new, 100);

    $this->assertSame(["universes:{$universe->id}", 'public:universes'], $this->channelNames($event));
    $this->assertSame('epoch.transitioned', $event->broadcastAs());
    $data = $event->broadcastWith();
    $this->assertEnvelope($data, 'epoch.transitioned', 100, $universe->id);
    $this->assertSame('notable', $data['severity']);
    $this->assertSame('Iron', $data['payload']['new_epoch']['name']);
}

public function test_simulation_event_stream_received_contract(): void
{
    $event = new SimulationEventStreamReceived(universeId: 5, tick: 3, type: 'engine.custom', payload: ['x' => 1], occurredAt: '2026-07-15T00:00:00+00:00');

    $this->assertSame(['universes:5'], $this->channelNames($event));
    $this->assertSame('simulation.event', $event->broadcastAs());
    $data = $event->broadcastWith();
    $this->assertEnvelope($data, 'simulation.event', 3, 5);
    $this->assertSame('engine.custom', $data['payload']['stream_type']);
}
```

- [ ] **Step 2: Chạy test, xác nhận 3 test mới fail**

Run: `docker compose -f deployment/docker-compose.prod.yml exec backend php artisan test --filter=WorldEventBroadcastContractTest`
Expected: 5 pass, 3 FAIL mới

- [ ] **Step 3: Sửa 4 event class** (mẫu như Task 3-4: thêm `implements WorldEventBroadcast`, `use EmitsWorldEvent`, xóa `broadcastWith()` cũ nếu có)

`UniversePulsed.php`:

```php
public function broadcastOn(): array
{
    return [new Channel("universes:{$this->universe->id}")];
}

public function broadcastAs(): string
{
    return 'universe.pulsed';
}

protected function toEnvelope(): WorldEventEnvelope
{
    return new WorldEventEnvelope(
        type: 'universe.pulsed',
        tick: (int) $this->snapshot->tick,
        universeId: (int) $this->universe->id,
        worldId: $this->universe->world_id !== null ? (int) $this->universe->world_id : null,
        payload: [
            'entropy' => $this->snapshot->entropy,
            'stability_index' => $this->snapshot->stability_index,
            'metrics' => $this->snapshot->metrics,
        ],
    );
}
```

`EpochTransitioned.php` (thêm `use Illuminate\Broadcasting\Channel;`):

```php
public function broadcastOn(): array
{
    return [new Channel("universes:{$this->universe->id}"), 'public:universes'];
}

// broadcastAs() giữ nguyên 'epoch.transitioned'

protected function toEnvelope(): WorldEventEnvelope
{
    return new WorldEventEnvelope(
        type: 'epoch.transitioned',
        tick: $this->tick,
        universeId: (int) $this->universe->id,
        worldId: $this->universe->world_id !== null ? (int) $this->universe->world_id : null,
        severity: 'notable',
        payload: [
            'old_epoch' => ['id' => $this->oldEpoch->id, 'name' => $this->oldEpoch->name],
            'new_epoch' => ['id' => $this->newEpoch->id, 'name' => $this->newEpoch->name],
        ],
    );
}
```

`UniverseSimulationPulsed.php` — giữ kênh `['public:universes']`, giữ `broadcastAs()` = `'pulsed'`, giữ `broadcastWhen()`; thêm envelope slim (hiện event này serialize NGUYÊN universe + snapshot + engineResponse — rất nặng):

```php
protected function toEnvelope(): WorldEventEnvelope
{
    return new WorldEventEnvelope(
        type: 'pulsed',
        tick: (int) $this->snapshot->tick,
        universeId: (int) $this->universe->id,
        worldId: $this->universe->world_id !== null ? (int) $this->universe->world_id : null,
        payload: [
            'entropy' => $this->snapshot->entropy,
            'stability_index' => $this->snapshot->stability_index,
            'engine_events_count' => count($this->engineEvents),
        ],
    );
}
```

`SimulationEventStreamReceived.php`:

```php
public function broadcastOn(): array
{
    return [new Channel("universes:{$this->universeId}")];
}

public function broadcastAs(): string
{
    return 'simulation.event';
}

protected function toEnvelope(): WorldEventEnvelope
{
    return new WorldEventEnvelope(
        type: 'simulation.event',
        tick: $this->tick,
        universeId: $this->universeId,
        payload: ['stream_type' => $this->type, 'data' => $this->payload],
        occurredAt: $this->occurredAt,
    );
}
```

- [ ] **Step 4: Chạy test contract + hồi quy Kafka/pulse**

Run: `docker compose -f deployment/docker-compose.prod.yml exec backend php artisan test --filter=WorldEventBroadcastContractTest`
Expected: PASS (8 tests)

Run: `docker compose -f deployment/docker-compose.prod.yml exec backend php artisan test --filter="KafkaEventStreamTest|SimulationPulseOrderTest|CollectiveUnconsciousRuntimeIntegrationTest"`
Expected: PASS (các test này chỉ assert dispatch, constructor không đổi)

- [ ] **Step 5: Commit**

```bash
git add backend/app/Modules/Simulation/Events/ backend/tests/Feature/Broadcasting/
git commit -m "feat(be): chuẩn hóa pulse/epoch/stream về universes:{id} + envelope"
```

---

### Task 6: Listener persist sự kiện tường thuật vào `world_events`

**Files:**
- Create: `backend/app/Modules/WorldOS/Listeners/PersistWorldEventBroadcast.php`
- Modify: `backend/app/Modules/WorldOS/Providers/WorldOSServiceProvider.php` (đăng ký trong `boot()`)
- Test: `backend/tests/Feature/Observatory/PersistWorldEventBroadcastTest.php`

**Interfaces:**
- Consumes: `WorldEventBroadcast::envelope()` (Task 1), các event Task 3-5.
- Produces: mỗi dispatch của 6 event tường thuật → 1 row `world_events` `{id: envelope.id, universe_id, tick, type, payload: json {severity, world_id, occurred_at, data}}`. Feed (Task 8) đọc đúng shape json này.

- [ ] **Step 1: Viết test fail**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Observatory;

use App\Modules\Narrative\Events\HistoricalEpochShifted;
use App\Modules\Simulation\Events\AnomalyDetected;
use App\Modules\World\Models\Universe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PersistWorldEventBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_narrative_event_is_persisted_to_world_events(): void
    {
        event(new HistoricalEpochShifted(universeId: 5, tick: 42, zoneId: 1, eventType: 'war', impactScore: 0.7, triggerData: []));

        $row = DB::table('world_events')->where('universe_id', 5)->first();

        $this->assertNotNull($row);
        $this->assertSame('history.shifted', $row->type);
        $this->assertSame(42, (int) $row->tick);
        $payload = json_decode($row->payload, true);
        $this->assertSame('notable', $payload['severity']);
        $this->assertSame('war', $payload['data']['event_type']);
    }

    public function test_anomaly_event_is_persisted(): void
    {
        $universe = Universe::factory()->create(['current_tick' => 7]);

        event(new AnomalyDetected($universe, ['title' => 'Spike', 'description' => 'x', 'severity' => 'high']));

        $this->assertDatabaseHas('world_events', [
            'universe_id' => $universe->id,
            'type' => 'anomaly.detected',
            'tick' => 7,
        ]);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận fail**

Run: `docker compose -f deployment/docker-compose.prod.yml exec backend php artisan test --filter=PersistWorldEventBroadcastTest`
Expected: FAIL — không có row nào trong `world_events`

- [ ] **Step 3: Implement listener + đăng ký**

`backend/app/Modules/WorldOS/Listeners/PersistWorldEventBroadcast.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\WorldOS\Listeners;

use App\Support\Broadcasting\WorldEventBroadcast;
use Illuminate\Support\Facades\DB;

/**
 * Ghi các sự kiện tường thuật vào world_events để Observatory feed
 * đọc lại được lịch sử (broadcast Centrifugo vốn fire-and-forget).
 */
class PersistWorldEventBroadcast
{
    public function handle(object $event): void
    {
        if (! $event instanceof WorldEventBroadcast) {
            return;
        }

        $envelope = $event->envelope();

        DB::table('world_events')->insert([
            'id' => $envelope->id,
            'universe_id' => $envelope->universeId,
            'tick' => $envelope->tick,
            'type' => $envelope->type,
            'payload' => json_encode([
                'severity' => $envelope->severity,
                'world_id' => $envelope->worldId,
                'occurred_at' => $envelope->occurredAt,
                'data' => $envelope->payload,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
```

Trong `WorldOSServiceProvider::boot()` thêm (import `Illuminate\Support\Facades\Event` và listener):

```php
Event::listen([
    \App\Modules\Simulation\Events\EpochTransitioned::class,
    \App\Modules\Simulation\Events\AnomalyDetected::class,
    \App\Modules\Simulation\Events\AutopoiesisMutationApplied::class,
    \App\Modules\World\Events\ArtifactDiscovered::class,
    \App\Modules\SocialGraph\Events\CelebrityEmerged::class,
    \App\Modules\Narrative\Events\HistoricalEpochShifted::class,
], \App\Modules\WorldOS\Listeners\PersistWorldEventBroadcast::class);
```

(KHÔNG đăng ký cho `UniversePulsed`/`UniverseSimulationPulsed`/`SimulationEventStreamReceived` — pulse mỗi tick đã có `universe_snapshots`, tick-summary đã có `WorldEventBus` ghi `world_events`.)

- [ ] **Step 4: Chạy test, xác nhận pass**

Run: `docker compose -f deployment/docker-compose.prod.yml exec backend php artisan test --filter=PersistWorldEventBroadcastTest`
Expected: PASS (2 tests)

- [ ] **Step 5: Commit**

```bash
git add backend/app/Modules/WorldOS/Listeners/ backend/app/Modules/WorldOS/Providers/WorldOSServiceProvider.php backend/tests/Feature/Observatory/
git commit -m "feat(be): persist 6 sự kiện tường thuật vào world_events cho observatory feed"
```

---

### Task 7: TimelineController — envelope hóa `narrative.completed`

**Files:**
- Modify: `backend/app/Modules/WorldOS/Http/Controllers/Api/TimelineController.php:120-133` và `:205-221`
- Test: `backend/tests/Feature/Observatory/LoomWebhookBroadcastTest.php`

**Interfaces:**
- Consumes: `WorldEventEnvelope` (Task 1).
- Produces: 2 chỗ direct-publish phát envelope type `chronicle.generated` trên `['universes:{id}:narrative', 'public:universes']`, payload chứa `chronicle_id` (+ `task_id`, `headline` ở webhook).

- [ ] **Step 1: Viết test fail**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Observatory;

use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LoomWebhookBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_pipeline_done_broadcasts_chronicle_generated_envelope_on_narrative_channel(): void
    {
        config([
            'centrifugo.url' => 'http://centrifugo:8000',
            'centrifugo.api_key' => 'test-key',
            'services.loom.shared_secret' => 'test-secret',
        ]);
        Http::fake(['http://centrifugo:8000/api' => Http::response(['result' => []], 200)]);

        $world = World::factory()->create();
        $universe = Universe::factory()->create(['world_id' => $world->id]);

        $response = $this->postJson('/api/worldos/narrative-loom/webhook', [
            'type' => 'pipeline_done',
            'task_id' => 'task-1',
            'world_id' => $world->id,
            'final_prose' => 'Sử thi...',
            'news_headline' => 'Đế chế sụp đổ',
            'tick_start' => 10,
            'tick_end' => 20,
        ], ['X-Loom-Secret' => 'test-secret']);

        $response->assertOk();
        $this->assertDatabaseHas('chronicles', ['universe_id' => $universe->id, 'from_tick' => 10, 'to_tick' => 20]);

        Http::assertSent(function ($request) use ($universe) {
            $body = (string) $request->body();

            return str_contains($body, "universes:{$universe->id}:narrative")
                && str_contains($body, '"chronicle.generated"');
        });
    }
}
```

Ghi chú: nếu `World::factory()` chưa tồn tại, tạo universe qua `Universe::factory()` rồi đọc `world_id` của nó (factory Universe đã dùng ở test khác — xem cách nó sinh world) thay vì tạo World trực tiếp; giữ nguyên phần còn lại của test.

- [ ] **Step 2: Chạy test, xác nhận fail**

Run: `docker compose -f deployment/docker-compose.prod.yml exec backend php artisan test --filter=LoomWebhookBroadcastTest`
Expected: FAIL — body hiện phát trên `public:universes` với payload phẳng, không có `chronicle.generated`

- [ ] **Step 3: Sửa 2 chỗ trong TimelineController**

Chỗ 1 — fallback trong `generateChronicle` (dòng 120-133), thay block broadcast:

```php
try {
    $envelope = new \App\Support\Broadcasting\WorldEventEnvelope(
        type: 'chronicle.generated',
        tick: (int) $chronicle->to_tick,
        universeId: (int) $universeId,
        severity: 'notable',
        payload: ['chronicle_id' => $chronicle->id],
    );
    app(\App\Broadcasting\CentrifugoBroadcaster::class)->broadcast(
        ["universes:{$universeId}:narrative", 'public:universes'],
        'chronicle.generated',
        $envelope->toArray()
    );
} catch (\Throwable $e) {
    Log::warning('Failed to broadcast narrative completion: ' . $e->getMessage());
}
```

Chỗ 2 — trong `loomWebhook` (dòng 205-221), thay block broadcast:

```php
try {
    $envelope = new \App\Support\Broadcasting\WorldEventEnvelope(
        type: 'chronicle.generated',
        tick: (int) $chronicle->to_tick,
        universeId: (int) $universe->id,
        severity: 'notable',
        payload: [
            'chronicle_id' => $chronicle->id,
            'task_id' => $taskId,
            'headline' => $newsHeadline,
        ],
    );
    app(\App\Broadcasting\CentrifugoBroadcaster::class)->broadcast(
        ["universes:{$universe->id}:narrative", 'public:universes'],
        'chronicle.generated',
        $envelope->toArray()
    );
} catch (\Throwable $e) {
    Log::warning('Failed to broadcast narrative completion: ' . $e->getMessage());
}
```

- [ ] **Step 4: Chạy test, xác nhận pass**

Run: `docker compose -f deployment/docker-compose.prod.yml exec backend php artisan test --filter=LoomWebhookBroadcastTest`
Expected: PASS

Run: `docker compose -f deployment/docker-compose.prod.yml exec backend php artisan test --filter=LoomSecurityTest`
Expected: PASS (5 tests — không phá lớp bảo mật P0-3)

- [ ] **Step 5: Commit**

```bash
git add backend/app/Modules/WorldOS/Http/Controllers/Api/TimelineController.php backend/tests/Feature/Observatory/
git commit -m "feat(be): envelope hóa narrative.completed → chronicle.generated trên kênh narrative"
```

---

### Task 8: Observatory feed — Action + Controller + route

**Files:**
- Create: `backend/app/Modules/WorldOS/Actions/GetObservatoryFeedAction.php`
- Create: `backend/app/Modules/WorldOS/Http/Controllers/Api/ObservatoryController.php`
- Modify: `backend/app/Modules/WorldOS/routes/api.php` (thêm route vào group public, sau dòng 56 mục Analytics)
- Test: `backend/tests/Feature/Observatory/ObservatoryFeedTest.php`

**Interfaces:**
- Consumes: shape json `world_events.payload` từ Task 6; model `App\Modules\Narrative\Models\Chronicle` (import Model chéo module — được phép).
- Produces (hợp đồng cho Plan 2 FE): `GET /api/worldos/observatory/universes/{id}/feed?after_tick=&before_tick=&types=&limit=` →

```json
{"data": [{"id": "...", "kind": "event|chronicle", "type": "...", "tick": 42,
           "severity": "...", "occurred_at": "...", "payload": {}}],
 "meta": {"count": 2, "next_before_tick": 10}}
```

`data` sắp giảm dần theo tick; `types` là danh sách phẩy (`epoch.transitioned,chronicle`); mục chronicle có `payload.{chronicle_id, chronicle_type, importance, content, has_animation}`.

- [ ] **Step 1: Viết test fail**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Observatory;

use App\Modules\Narrative\Models\Chronicle;
use App\Modules\World\Models\Universe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ObservatoryFeedTest extends TestCase
{
    use RefreshDatabase;

    private function seedEvent(int $universeId, int $tick, string $type): void
    {
        DB::table('world_events')->insert([
            'id' => (string) Str::uuid(),
            'universe_id' => $universeId,
            'tick' => $tick,
            'type' => $type,
            'payload' => json_encode(['severity' => 'notable', 'world_id' => null, 'occurred_at' => '2026-07-15T00:00:00+00:00', 'data' => ['k' => 'v']]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_feed_merges_events_and_chronicles_ordered_by_tick_desc(): void
    {
        $universe = Universe::factory()->create();
        $this->seedEvent($universe->id, 5, 'anomaly.detected');
        $this->seedEvent($universe->id, 20, 'epoch.transitioned');
        Chronicle::create(['universe_id' => $universe->id, 'from_tick' => 8, 'to_tick' => 10, 'type' => 'narrative_loom', 'importance' => 0.8, 'content' => 'Sử thi']);

        $response = $this->getJson("/api/worldos/observatory/universes/{$universe->id}/feed");

        $response->assertOk();
        $ticks = collect($response->json('data'))->pluck('tick')->all();
        $this->assertSame([20, 10, 5], $ticks);
        $kinds = collect($response->json('data'))->pluck('kind')->all();
        $this->assertSame(['event', 'chronicle', 'event'], $kinds);
        $this->assertSame(5, $response->json('meta.next_before_tick'));
    }

    public function test_feed_filters_by_types_and_tick_window(): void
    {
        $universe = Universe::factory()->create();
        $this->seedEvent($universe->id, 5, 'anomaly.detected');
        $this->seedEvent($universe->id, 20, 'epoch.transitioned');
        Chronicle::create(['universe_id' => $universe->id, 'from_tick' => 8, 'to_tick' => 10, 'type' => 'narrative_loom', 'importance' => 0.8, 'content' => 'Sử thi']);

        $onlyEpoch = $this->getJson("/api/worldos/observatory/universes/{$universe->id}/feed?types=epoch.transitioned");
        $this->assertSame(['epoch.transitioned'], collect($onlyEpoch->json('data'))->pluck('type')->all());

        $window = $this->getJson("/api/worldos/observatory/universes/{$universe->id}/feed?after_tick=5&before_tick=20");
        $this->assertSame([10], collect($window->json('data'))->pluck('tick')->all());
    }

    public function test_feed_respects_limit_and_isolates_universes(): void
    {
        $universe = Universe::factory()->create();
        $other = Universe::factory()->create();
        foreach ([1, 2, 3] as $tick) {
            $this->seedEvent($universe->id, $tick, 'anomaly.detected');
        }
        $this->seedEvent($other->id, 99, 'anomaly.detected');

        $response = $this->getJson("/api/worldos/observatory/universes/{$universe->id}/feed?limit=2");

        $this->assertCount(2, $response->json('data'));
        $this->assertSame([3, 2], collect($response->json('data'))->pluck('tick')->all());
    }

    public function test_feed_empty_universe_returns_ok_with_empty_data(): void
    {
        $universe = Universe::factory()->create();

        $response = $this->getJson("/api/worldos/observatory/universes/{$universe->id}/feed");

        $response->assertOk();
        $this->assertSame([], $response->json('data'));
        $this->assertNull($response->json('meta.next_before_tick'));
    }
}
```

Ghi chú: nếu `Chronicle::create` bị chặn mass-assignment, đổi sang `Chronicle::query()->forceCreate([...])` trong test (cột đã xác nhận tồn tại qua migration).

- [ ] **Step 2: Chạy test, xác nhận fail**

Run: `docker compose -f deployment/docker-compose.prod.yml exec backend php artisan test --filter=ObservatoryFeedTest`
Expected: FAIL — 404 (route chưa tồn tại)

- [ ] **Step 3: Implement Action + Controller + route**

`backend/app/Modules/WorldOS/Actions/GetObservatoryFeedAction.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\WorldOS\Actions;

use App\Contracts\ActionInterface;
use App\Modules\Narrative\Models\Chronicle;
use Illuminate\Support\Facades\DB;

class GetObservatoryFeedAction implements ActionInterface
{
    private const DEFAULT_LIMIT = 50;

    /**
     * @param array{after_tick?: int|null, before_tick?: int|null, types?: string[]|null, limit?: int|null} $filters
     * @return array{data: array<int, array<string, mixed>>, meta: array{count: int, next_before_tick: int|null}}
     */
    public function handle(int $universeId, array $filters = []): array
    {
        $limit = $filters['limit'] ?? self::DEFAULT_LIMIT;
        $afterTick = $filters['after_tick'] ?? null;
        $beforeTick = $filters['before_tick'] ?? null;
        $types = $filters['types'] ?? null;

        $events = DB::table('world_events')
            ->where('universe_id', $universeId)
            ->when($afterTick !== null, fn ($q) => $q->where('tick', '>', $afterTick))
            ->when($beforeTick !== null, fn ($q) => $q->where('tick', '<', $beforeTick))
            ->when($types !== null, fn ($q) => $q->whereIn('type', $types))
            ->orderByDesc('tick')
            ->limit($limit)
            ->get()
            ->map(function (object $row): array {
                $payload = json_decode($row->payload ?? '{}', true) ?: [];

                return [
                    'id' => (string) $row->id,
                    'kind' => 'event',
                    'type' => $row->type,
                    'tick' => (int) $row->tick,
                    'severity' => $payload['severity'] ?? 'info',
                    'occurred_at' => $payload['occurred_at'] ?? (string) $row->created_at,
                    'payload' => $payload['data'] ?? $payload,
                ];
            });

        $chronicles = collect();
        if ($types === null || in_array('chronicle', $types, true)) {
            $chronicles = Chronicle::query()
                ->where('universe_id', $universeId)
                ->when($afterTick !== null, fn ($q) => $q->whereRaw('COALESCE(to_tick, from_tick) > ?', [$afterTick]))
                ->when($beforeTick !== null, fn ($q) => $q->whereRaw('COALESCE(to_tick, from_tick) < ?', [$beforeTick]))
                ->orderByDesc('to_tick')
                ->limit($limit)
                ->get()
                ->map(fn (Chronicle $c): array => [
                    'id' => 'chronicle-' . $c->id,
                    'kind' => 'chronicle',
                    'type' => 'chronicle',
                    'tick' => (int) ($c->to_tick ?? $c->from_tick ?? 0),
                    'severity' => 'notable',
                    'occurred_at' => $c->created_at?->toIso8601String(),
                    'payload' => [
                        'chronicle_id' => $c->id,
                        'chronicle_type' => $c->type,
                        'importance' => $c->importance,
                        'content' => $c->content,
                        'has_animation' => ! empty($c->animation_script) || ! empty($c->raw_payload['animation_script'] ?? null),
                    ],
                ]);
        }

        $items = $events->concat($chronicles)->sortByDesc('tick')->values()->take($limit);
        $oldest = $items->last();

        return [
            'data' => $items->all(),
            'meta' => [
                'count' => $items->count(),
                'next_before_tick' => $oldest['tick'] ?? null,
            ],
        ];
    }
}
```

`backend/app/Modules/WorldOS/Http/Controllers/Api/ObservatoryController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\WorldOS\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\WorldOS\Actions\GetObservatoryFeedAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ObservatoryController extends Controller
{
    public function __construct(
        private readonly GetObservatoryFeedAction $getObservatoryFeedAction
    ) {}

    public function feed(Request $request, int $universeId): JsonResponse
    {
        $validated = $request->validate([
            'after_tick' => ['sometimes', 'integer', 'min:0'],
            'before_tick' => ['sometimes', 'integer', 'min:0'],
            'types' => ['sometimes', 'string', 'max:500'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:200'],
        ]);

        $types = isset($validated['types'])
            ? array_values(array_filter(array_map('trim', explode(',', $validated['types']))))
            : null;

        return response()->json($this->getObservatoryFeedAction->handle($universeId, [
            'after_tick' => $validated['after_tick'] ?? null,
            'before_tick' => $validated['before_tick'] ?? null,
            'types' => $types !== [] ? $types : null,
            'limit' => $validated['limit'] ?? null,
        ]));
    }
}
```

Trong `backend/app/Modules/WorldOS/routes/api.php` — thêm import `use App\Modules\WorldOS\Http\Controllers\Api\ObservatoryController;` và trong group public (sau mục `// 5. Analytics`):

```php
    // 5b. Observatory (GET — public)
    Route::get('observatory/universes/{id}/feed', [ObservatoryController::class, 'feed'])
        ->name('worldos.observatory.feed');
```

- [ ] **Step 4: Chạy test, xác nhận pass**

Run: `docker compose -f deployment/docker-compose.prod.yml exec backend php artisan test --filter=ObservatoryFeedTest`
Expected: PASS (4 tests)

- [ ] **Step 5: Commit**

```bash
git add backend/app/Modules/WorldOS/Actions/GetObservatoryFeedAction.php backend/app/Modules/WorldOS/Http/Controllers/Api/ObservatoryController.php backend/app/Modules/WorldOS/routes/api.php backend/tests/Feature/Observatory/
git commit -m "feat(be): observatory feed — hợp nhất world_events + chronicles theo tick"
```

---

### Task 9: Hồi quy toàn suite + lint + cập nhật trạng thái

**Files:**
- Modify: `.dev_status.md` (thêm mục session mới)

**Interfaces:**
- Consumes: toàn bộ Task 1-8.
- Produces: baseline mới đã xác minh cho Plan 2.

- [ ] **Step 1: Chạy toàn bộ test suite**

Run: `docker compose -f deployment/docker-compose.prod.yml exec backend php artisan test`
Expected: pass-count ≥ baseline; đúng 3 fail pre-existing (`MeaningEngineTest` ×2, `IntelligenceExplosionTest` ×1). Nếu có fail MỚI → dừng, điều tra bằng skill systematic-debugging trước khi tiếp tục.

- [ ] **Step 2: Chạy guardrail kiến trúc**

Run: `docker compose -f deployment/docker-compose.prod.yml exec backend php artisan test --filter=ModuleBoundaryTest`
Expected: PASS (listener/action mới chỉ import Event + Model chéo module — được phép)

- [ ] **Step 3: Lint**

Run: `docker compose -f deployment/docker-compose.prod.yml exec backend vendor/bin/pint --dirty`
Expected: sửa format nếu có, không lỗi

- [ ] **Step 4: Cập nhật `.dev_status.md`** — thêm mục session: Plan 1 Observatory hoàn thành (kênh chuẩn hóa, envelope, persist, feed endpoint), kèm kết quả test thật.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "chore: hoàn thành Observatory Plan 1 — backend realtime core"
```

---

## Ghi chú cho các plan kế tiếp (không thuộc plan này)

- **Plan 2 (FE nền tảng):** tiêu thụ hợp đồng kênh + envelope + feed ở trên. Frontend cũ đang parse payload pulse dạng base64/phẳng sẽ hiển thị sai sau Plan 1 — chấp nhận trong big-bang, Plan 2 thay thế.
- **Plan 3:** thêm 3 endpoint lens (psyche/civilization/world) ngay trước khi FE lens tiêu thụ.
- **Plan 4:** xóa `PowerSystemTransitionTriggered` (dead code), sửa 2 chỗ `Event::fake` namespace cũ (`App\Events\Simulation\UniverseSimulationPulsed`) trong `WorldosSimulationTest`/`SimulationPulseOrderTest`, xóa dashboard cũ.
