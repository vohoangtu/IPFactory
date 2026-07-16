# Observatory Plan 3 — Lenses + 3 Endpoint BE + Cinema Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Hoàn thiện tầng quan sát sâu của Observatory: 3 endpoint read-model backend (psyche/civilization/world), 4 lens frontend (actors, civilization, causality, wavefunction), cinema VAF port, filter sự kiện + actor nổi bật ở hero, constellation landing, và trả nợ P2 (backfill `after_tick`, a11y, status realtime).

**Architecture:** Backend giữ nguyên pattern P1: Action mỏng trong module WorldOS chỉ đọc **Models + dữ liệu persist** (guardrail cho phép import Model chéo module), riêng needs/goals của psyche đi qua interface mới `App\Contracts\ActorPsycheProjectorInterface` (impl ở module Psychology). Frontend nâng cấp các feature cũ tại chỗ (swap `@/lib/api` → `apiClient`, bỏ `useCentrifugo` cũ, thêm `index.ts` public API) rồi gắn vào route group `(observatory)` qua tab-nav lens trong `WorkspaceLayout`; VAF player chuyển nguyên khối vào `features/cinema`.

**Tech Stack:** Laravel 13 + PHPUnit (sqlite `:memory:`); Next.js 16 App Router, React 19, TanStack Query v5, zustand, centrifuge-js, Recharts, @xyflow/react (ReactFlow), framer-motion, Tailwind v4, Vitest + Testing Library.

## Global Constraints

- **KHÔNG chạy `npm`/`composer` trên host.** Mọi lệnh chạy trong Incus container `worldos-dev` (project mount `/work`):
  - FE test: `incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads'` (một file: `npm test -- --pool=threads <path>`); check: `npm run check`.
  - BE test: `incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --filter=<Tên>'`; lint: `vendor/bin/pint <files>`.
  - **BẮT BUỘC `--pool=threads`** cho vitest (pool forks crash trong Incus). `npm run build` KHÔNG chạy được trong worldos-dev (AppArmor) — không verify build ở plan này.
- **Hợp đồng P1/P2 (KHÔNG phá):** envelope `{id,type,tick,universe_id,world_id,severity,occurred_at,payload}`; feed `GET /api/worldos/observatory/universes/{id}/feed` trả body 2 key `{data, meta:{count, next_before_tick}}` (interceptor không bóc); kênh `universes:{id}[|:narrative|:anomaly|:autopoiesis]`.
- **Guardrail BE** (`tests/Unit/Architecture/ModuleBoundaryTest.php`): regex chỉ bắt `use App\Modules\X\(Services|Actions|Repositories)` chéo module, ratchet baseline chỉ-được-giảm; file có `/Http/` hoặc `/Console/` được miễn trừ. → Action mới trong WorldOS **chỉ được import Models chéo module + interface từ `App\Contracts`**. Baseline `WorldOS->Psychology`, `WorldOS->Institutions`, `WorldOS->World` = 0 (World Models thì OK, Services thì KHÔNG).
- **Guardrail FE** (`eslint.config.mjs`): trong glob `src/shared/**`, `src/features/**`, `src/app/(observatory)/**` — feature chỉ import qua `@/features/<name>` (index.ts); mọi feature bị đụng trong plan này PHẢI có `index.ts` public API. Code mới KHÔNG import `@/lib/api`, `@/hooks/useCentrifugo`, `@/contexts/*` (legacy, xóa ở P4).
- **PHP:** `declare(strict_types=1)`, PSR-12 (pint), Action pattern (`implements App\Contracts\ActionInterface`), GET public trong nhóm `Route::middleware('api')->prefix('worldos')`.
- **TS:** strict, functional components, PascalCase; test trong `__tests__/` cạnh code; KHÔNG thêm dependency mới.
- **Legacy:** khác P2, plan này ĐƯỢC PHÉP đụng legacy ở mức tối thiểu: (a) COPY component dashboard cũ vào features (không xóa bản gốc), (b) `git mv` cây VAF (`src/lib/vaf`, `src/components/vaf`, `src/hooks/useVAFPlayer.ts`) vào `features/cinema` + đổi trang `src/app/narrative-cinema/[chronicleId]` thành redirect, (c) sửa import `AnimationScript` trong `src/types/api.ts`. KHÔNG xóa gì khác — thanh lý ở P4.
- **Thẩm mỹ:** dùng token trong `src/app/globals.css` (dark-only; `--color-bg-base/surface/elevated`, `--color-primary` cyan, `--color-accent` violet, `--color-danger/amber/emerald/info`, utility `.glass`, `.text-glow-cyan`, `.custom-scrollbar`, `.animate-fade-in-up`; font Space Grotesk / JetBrains Mono). Task UI (9, 10, 11, 12, 13, 15): implementer NÊN đọc skill `frontend-design` (và `dataviz` cho chart/tile) trước khi viết component.
- **Baseline:** FE 21 file / 113 case, check 0 error / 2 warning pre-existing (`src/lib/__tests__/centrifugo.test.ts`); BE Unit 171 pass / 92 skip (fail duy nhất được phép: `IntelligenceExplosionTest` — flake stochastic pre-existing). Đo lại ở Task 1; các task sau không được tạo fail mới.

## Quyết định phạm vi (chốt khi viết plan)

1. **Visual QA hero (mở đầu P3)** = smoke QA qua `next dev` trong worldos-dev (trang trả 200, SSR shell không crash) + checklist tĩnh. QA thị giác đầy đủ cần stack Docker chạy dữ liệu thật — nằm ngoài khả năng môi trường hiện tại, ghi nhận không chặn.
2. **3 endpoint BE đọc dữ liệu persist**, không gọi engine runtime (`EntropyEngine`/`GreatFilterEngine`… là write-path nhận `UniverseState` in-memory, không phù hợp read API). Psyche cần needs/goals (pure function từ `psych_state`) → interface `ActorPsycheProjectorInterface` trong `app/Contracts` (theo tiền lệ 8 interface Session 14), impl + binding trong module Psychology.
3. **Endpoint civilization KHÔNG duplicate ascension/great-filter/omega** — dữ liệu đó đã có qua apex API (`/apex/v10/universes/{id}/ascension-filters`, `/consciousness`…) mà lens wavefunction tiêu thụ. Civilization = metrics persist (`universes.entropy/structural_coherence/fitness_score` + `latestSnapshot.stability_index/metrics`) + đếm actors/supreme-entities.
4. **Constellation landing = 2D SVG** (spec loại 3D khỏi phạm vi), dùng API sẵn `/apex/multiverse/bloom` + `/resonance`. Lưới thẻ hiện tại giữ lại làm danh sách bên dưới + fallback khi bloom lỗi.
5. **Filter hero theo nhóm type** (BE feed đã hỗ trợ `types`); filter theo severity KHÔNG làm (YAGNI — severity đã thể hiện bằng màu entry).
6. **Nâng cấp feature cũ tại chỗ** (actors, wavefunction, causal-map, multiverse): swap sang `apiClient` + helper `takeData` (interceptor mới chỉ bóc body 1-key `{data}`, khác client cũ), bỏ `useCentrifugoConnection`/`useAdaptiveRefetchInterval` (legacy) → `refetchInterval` cố định. Trang dashboard cũ vẫn compile vì subpath import không đổi (`@/features/*/hooks`) và glob guardrail không phủ `src/app/dashboard/**`.
7. **Trả nợ P2 trong plan:** backfill `after_tick` thay `refetchLatest` khi live-gap; a11y batch (`role="log"`/`aria-live`, `aria-hidden` icon trang trí, `overscroll-contain`); `live.status` set từ pulse (BE thêm `status` vào payload `universe.pulsed`); fake Centrifugo thêm `getSubscription`.
8. **1-frame stale flash khi nav giữa 2 `/u/[id]`** (P2 note): chấp nhận tiếp, không sửa (hiếm gặp, plan-mandated từ P2).

---

### Task 1: Baseline + smoke QA hero

**Files:** không đổi code repo (chỉ đo baseline + ghi nhận QA).

**Interfaces:**
- Consumes: container `worldos-dev` (Node 22 + npm đã cài ở P2, PHP 8.4 + sqlite đã có từ P1).
- Produces: số liệu baseline FE/BE ghi vào report; kết quả smoke QA hero (200/crash/skip).

- [ ] **Step 1: Baseline FE**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads 2>&1 | tail -5'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check 2>&1 | tail -5'
```
Expected: ~21 file / 113 test pass; check 0 error / 2 warning pre-existing. Ghi số chính xác vào report. Nếu `node_modules` thiếu → chạy `npm ci` trước.

- [ ] **Step 2: Baseline BE**

```bash
incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --testsuite=Unit 2>&1 | tail -5'
```
Expected: ~171 pass / 92 skip / 0-1 fail (fail duy nhất được phép: `IntelligenceExplosionTest`). Ghi số chính xác.

- [ ] **Step 3: Smoke QA hero qua dev server (best-effort)**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && nohup npm run dev >/tmp/next-dev.log 2>&1 & sleep 30; for p in /multiverse /u/1; do echo "$p: $(curl -s -o /dev/null -w "%{http_code}" http://localhost:5000$p)"; done'
```
Expected: cả hai trang trả `200` (SSR shell render được — backend không chạy nên nội dung là loading/degraded state, chấp nhận). Đọc `/tmp/next-dev.log` tìm error compile. Nếu dev server treo (AppArmor — cùng gốc rễ với build): ghi nhận **SKIP có lý do**, không chặn plan.

- [ ] **Step 4: Dọn dev server + report**

```bash
incus exec worldos-dev -- sh -c 'pkill -f "next dev" || true'
```
Không có thay đổi code → không commit; chỉ viết report baseline + kết quả QA.

---

### Task 2: BE — pulse payload thêm `status` (nợ P2 "status realtime")

**Files:**
- Modify: `backend/app/Modules/Simulation/Events/UniversePulsed.php` (method `toEnvelope`, dòng ~48-61)
- Test: `backend/tests/Feature/Broadcasting/WorldEventBroadcastContractTest.php` (case `universe_pulsed`, dòng ~115-127)

**Interfaces:**
- Consumes: envelope P1 (trait `EmitsWorldEvent`, build eager tại constructor).
- Produces: payload `universe.pulsed` có thêm key `status: string` (giá trị `universes.status`: `active|paused|halted`). FE Task 6 đọc key này trong `applyPulse`.

- [ ] **Step 1: Viết test fail** — trong test `universe_pulsed` hiện có (sau assert `entropy`), thêm:

```php
$this->assertSame('active', $data['payload']['status']);
```
(`Universe::factory()` mặc định `status = 'active'`.)

- [ ] **Step 2: Chạy test để thấy fail**

```bash
incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --filter=WorldEventBroadcastContractTest 2>&1 | tail -8'
```
Expected: FAIL — `undefined array key "status"` (hoặc assert fail).

- [ ] **Step 3: Sửa `toEnvelope`** — thêm 1 dòng vào mảng `payload`:

```php
payload: [
    'entropy' => $this->snapshot->entropy,
    'stability_index' => $this->snapshot->stability_index,
    'status' => (string) $this->universe->status,
    'metrics' => $this->snapshot->metrics,
],
```

- [ ] **Step 4: Chạy test pass**

Run: lệnh Step 2. Expected: PASS toàn bộ file.

- [ ] **Step 5: Pint + commit**

```bash
incus exec worldos-dev -- sh -c 'cd /work/backend && vendor/bin/pint app/Modules/Simulation/Events/UniversePulsed.php tests/Feature/Broadcasting/WorldEventBroadcastContractTest.php'
git add backend/app/Modules/Simulation/Events/UniversePulsed.php backend/tests/Feature/Broadcasting/WorldEventBroadcastContractTest.php
git commit -m "feat(be): universe.pulsed payload mang status — nền cho status realtime P3"
```

---

### Task 3: BE — endpoint `GET observatory/actors/{actorId}/psyche`

**Files:**
- Create: `backend/app/Contracts/ActorPsycheProjectorInterface.php`
- Create: `backend/app/Modules/Psychology/Services/ActorPsycheProjector.php`
- Create: `backend/app/Modules/WorldOS/Actions/GetActorPsycheAction.php`
- Modify: `backend/app/Modules/Psychology/Providers/PsychologyServiceProvider.php` (thêm binding trong `register()`)
- Modify: `backend/app/Modules/WorldOS/Http/Controllers/Api/ObservatoryController.php` (constructor + method mới)
- Modify: `backend/app/Modules/WorldOS/routes/api.php` (thêm route cạnh dòng ~60-61)
- Test: `backend/tests/Feature/Observatory/ObservatoryPsycheTest.php`

**Interfaces:**
- Consumes: `App\Modules\Intelligence\Models\Actor` (cột JSON `metrics` chứa `psych_state`, `trait_vector`), `App\Modules\Intelligence\Models\AgentDecision` (bảng `agent_decisions`), `Psychology\ValueObjects\PsychologicalState` + `Psychology\Services\GoalGenerator` (chỉ trong module Psychology).
- Produces: `GET /api/worldos/observatory/actors/{actorId}/psyche` → `{data: {actor, emotions, needs, goals, trait_vector, recent_decisions}}`; interface `ActorPsycheProjectorInterface::project(array $psychState): array{emotions, needs, goals}`. FE Task 9 tiêu thụ shape này (type `ActorPsyche`).
- LƯU Ý guardrail: `GetActorPsycheAction` KHÔNG được `use App\Modules\Psychology\Services\...` — chỉ dùng interface `App\Contracts\ActorPsycheProjectorInterface`.

- [ ] **Step 1: Viết feature test fail**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Observatory;

use App\Modules\Intelligence\Models\Actor;
use App\Modules\Intelligence\Models\AgentDecision;
use App\Modules\World\Models\Universe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObservatoryPsycheTest extends TestCase
{
    use RefreshDatabase;

    private function makeActor(int $universeId): Actor
    {
        return Actor::create([
            'universe_id' => $universeId,
            'name' => 'Aria',
            'archetype' => 'sage',
            'is_alive' => true,
            'life_stage' => 'adult',
            'metrics' => [
                'psych_state' => ['fear' => 0.9, 'anger' => 0.1, 'sadness' => 0.2, 'joy' => 0.3, 'stress' => 0.8, 'trust' => 0.4],
                'trait_vector' => [0.5, 0.7, 0.2],
            ],
        ]);
    }

    public function test_psyche_returns_emotions_needs_goals_and_recent_decisions(): void
    {
        $universe = Universe::factory()->create();
        $actor = $this->makeActor($universe->id);
        AgentDecision::create(['actor_id' => $actor->id, 'universe_id' => $universe->id, 'tick' => 5, 'action_type' => 'gather', 'reasoning' => 'đói', 'utility_score' => 0.4, 'confidence' => 0.6]);
        AgentDecision::create(['actor_id' => $actor->id, 'universe_id' => $universe->id, 'tick' => 9, 'action_type' => 'flee', 'reasoning' => 'nguy hiểm', 'utility_score' => 0.9, 'confidence' => 0.8]);

        $response = $this->getJson("/api/worldos/observatory/actors/{$actor->id}/psyche");

        $response->assertOk()
            ->assertJsonPath('data.actor.id', $actor->id)
            ->assertJsonPath('data.actor.name', 'Aria')
            ->assertJsonPath('data.emotions.fear', 0.9)
            ->assertJsonPath('data.trait_vector', [0.5, 0.7, 0.2])
            ->assertJsonPath('data.recent_decisions.0.tick', 9)
            ->assertJsonPath('data.recent_decisions.0.action_type', 'flee')
            ->assertJsonCount(2, 'data.recent_decisions');

        // fear=0.9 + stress=0.8 → need "survive" = 0.9*0.7 + 0.8*0.5 = 1.03, vượt ngưỡng 0.25 → goal đầu tiên
        $goals = $response->json('data.goals');
        $this->assertNotEmpty($goals);
        $this->assertSame('survive', $goals[0]['type']);
        $this->assertEqualsWithDelta(1.03, $response->json('data.needs.survive'), 0.001);
    }

    public function test_psyche_handles_actor_without_psych_state(): void
    {
        $universe = Universe::factory()->create();
        $actor = Actor::create(['universe_id' => $universe->id, 'name' => 'Blank', 'is_alive' => true]);

        $response = $this->getJson("/api/worldos/observatory/actors/{$actor->id}/psyche");

        // psych_state rỗng → baseline (trust 0.5): emotions đủ 6 key, goals có thể rỗng, decisions rỗng
        $response->assertOk()
            ->assertJsonPath('data.emotions.trust', 0.5)
            ->assertJsonPath('data.recent_decisions', [])
            ->assertJsonPath('data.trait_vector', []);
    }

    public function test_psyche_returns_404_for_missing_actor(): void
    {
        $this->getJson('/api/worldos/observatory/actors/999999/psyche')->assertNotFound();
    }
}
```
Lưu ý: nếu bảng `actors` có cột NOT NULL khác không default (lỗi khi chạy) → bổ sung giá trị tối thiểu vào `makeActor`, KHÔNG sửa migration.

- [ ] **Step 2: Chạy test thấy fail**

```bash
incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --filter=ObservatoryPsycheTest 2>&1 | tail -8'
```
Expected: FAIL 404 (route chưa tồn tại).

- [ ] **Step 3: Tạo interface `app/Contracts/ActorPsycheProjectorInterface.php`**

```php
<?php

declare(strict_types=1);

namespace App\Contracts;

interface ActorPsycheProjectorInterface
{
    /**
     * Chiếu psych_state đã persist (actors.metrics['psych_state']) thành read-model tâm lý.
     *
     * @param  array<string, mixed>  $psychState
     * @return array{emotions: array<string, float>, needs: array<string, float>, goals: array<int, array{type: string, priority: float}>}
     */
    public function project(array $psychState): array;
}
```

- [ ] **Step 4: Tạo `app/Modules/Psychology/Services/ActorPsycheProjector.php`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Psychology\Services;

use App\Contracts\ActorPsycheProjectorInterface;
use App\Modules\Psychology\ValueObjects\PsychologicalState;

class ActorPsycheProjector implements ActorPsycheProjectorInterface
{
    public function __construct(private readonly GoalGenerator $goalGenerator)
    {
    }

    public function project(array $psychState): array
    {
        $state = PsychologicalState::fromArray($psychState);

        return [
            'emotions' => $state->toArray(),
            'needs' => array_map(fn (float $v): float => round($v, 3), $this->goalGenerator->computeNeeds($state)),
            'goals' => $this->goalGenerator->generate($state),
        ];
    }
}
```

- [ ] **Step 5: Binding trong `PsychologyServiceProvider::register()`** — thêm sau khối "Services":

```php
$this->app->singleton(
    \App\Contracts\ActorPsycheProjectorInterface::class,
    \App\Modules\Psychology\Services\ActorPsycheProjector::class,
);
```

- [ ] **Step 6: Tạo `app/Modules/WorldOS/Actions/GetActorPsycheAction.php`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\WorldOS\Actions;

use App\Contracts\ActionInterface;
use App\Contracts\ActorPsycheProjectorInterface;
use App\Modules\Intelligence\Models\Actor;
use App\Modules\Intelligence\Models\AgentDecision;

class GetActorPsycheAction implements ActionInterface
{
    private const DECISION_LIMIT = 10;

    public function __construct(private readonly ActorPsycheProjectorInterface $psycheProjector)
    {
    }

    /** @return array{data: array<string, mixed>} */
    public function handle(int $actorId): array
    {
        $actor = Actor::query()->findOrFail($actorId);
        $metrics = is_array($actor->metrics) ? $actor->metrics : [];
        $psychState = is_array($metrics['psych_state'] ?? null) ? $metrics['psych_state'] : [];
        $psyche = $this->psycheProjector->project($psychState);

        $decisions = AgentDecision::query()
            ->where('actor_id', $actorId)
            ->orderByDesc('tick')
            ->orderByDesc('id')
            ->limit(self::DECISION_LIMIT)
            ->get()
            ->map(fn (AgentDecision $d): array => [
                'id' => $d->id,
                'tick' => (int) $d->tick,
                'action_type' => $d->action_type,
                'reasoning' => $d->reasoning,
                'utility_score' => $d->utility_score !== null ? (float) $d->utility_score : null,
                'confidence' => $d->confidence !== null ? (float) $d->confidence : null,
                'impact' => $d->impact,
            ])
            ->all();

        return [
            'data' => [
                'actor' => [
                    'id' => $actor->id,
                    'universe_id' => (int) $actor->universe_id,
                    'name' => $actor->name,
                    'archetype' => $actor->archetype,
                    'is_alive' => (bool) $actor->is_alive,
                    'life_stage' => $actor->life_stage,
                ],
                'emotions' => $psyche['emotions'],
                'needs' => $psyche['needs'],
                'goals' => $psyche['goals'],
                'trait_vector' => is_array($metrics['trait_vector'] ?? null) ? array_values($metrics['trait_vector']) : [],
                'recent_decisions' => $decisions,
            ],
        ];
    }
}
```

- [ ] **Step 7: Controller + route** — `ObservatoryController`: thêm param constructor `private readonly GetActorPsycheAction $getActorPsycheAction` và method:

```php
public function actorPsyche(int $actorId): JsonResponse
{
    return response()->json($this->getActorPsycheAction->handle($actorId));
}
```

`routes/api.php` — thêm ngay dưới route feed (mục "5b. Observatory"):

```php
Route::get('observatory/actors/{actorId}/psyche', [ObservatoryController::class, 'actorPsyche'])
    ->name('worldos.observatory.actor-psyche');
```

- [ ] **Step 8: Chạy test pass + guardrail**

```bash
incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --filter=ObservatoryPsycheTest 2>&1 | tail -8'
incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --filter=ModuleBoundaryTest 2>&1 | tail -5'
```
Expected: cả hai PASS (Action chỉ import Models + Contracts → không thêm coupling).

- [ ] **Step 9: Pint + commit**

```bash
incus exec worldos-dev -- sh -c 'cd /work/backend && vendor/bin/pint app/Contracts/ActorPsycheProjectorInterface.php app/Modules/Psychology/Services/ActorPsycheProjector.php app/Modules/WorldOS/Actions/GetActorPsycheAction.php app/Modules/WorldOS/Http/Controllers/Api/ObservatoryController.php tests/Feature/Observatory/ObservatoryPsycheTest.php'
git add backend/app/Contracts/ActorPsycheProjectorInterface.php backend/app/Modules/Psychology/Services/ActorPsycheProjector.php backend/app/Modules/WorldOS/Actions/GetActorPsycheAction.php backend/app/Modules/Psychology/Providers/PsychologyServiceProvider.php backend/app/Modules/WorldOS/Http/Controllers/Api/ObservatoryController.php backend/app/Modules/WorldOS/routes/api.php backend/tests/Feature/Observatory/ObservatoryPsycheTest.php
git commit -m "feat(be): observatory psyche endpoint — emotions/needs/goals + agent decisions"
```

---

### Task 4: BE — endpoint `GET observatory/universes/{id}/civilization`

**Files:**
- Create: `backend/app/Modules/WorldOS/Actions/GetUniverseCivilizationAction.php`
- Modify: `backend/app/Modules/WorldOS/Http/Controllers/Api/ObservatoryController.php`
- Modify: `backend/app/Modules/WorldOS/routes/api.php`
- Test: `backend/tests/Feature/Observatory/ObservatoryCivilizationTest.php`

**Interfaces:**
- Consumes: `World\Models\Universe` (cột `entropy`, `structural_coherence`, `fitness_score`, `epoch`, `status`, `current_tick`; quan hệ `latestSnapshot()`, `actors()`, `supremeEntities()`), `Simulation\Models\UniverseSnapshot` (cột `tick`, `stability_index`, `metrics`).
- Produces: `GET /api/worldos/observatory/universes/{id}/civilization` → `{data: {universe_id, status, current_tick, epoch, metrics:{entropy, stability_index, structural_coherence, fitness_score}, complexity:{actor_count, living_actor_count, supreme_entity_count}, snapshot:{tick, metrics}|null}}`. FE Task 11 tiêu thụ (type `UniverseCivilization`).

- [ ] **Step 1: Viết feature test fail**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Observatory;

use App\Modules\Intelligence\Models\Actor;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\World\Models\Universe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObservatoryCivilizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_civilization_returns_persisted_metrics_and_complexity(): void
    {
        $universe = Universe::factory()->create(['entropy' => 0.42, 'structural_coherence' => 0.9, 'current_tick' => 33]);
        UniverseSnapshot::factory()->create(['universe_id' => $universe->id, 'tick' => 30, 'stability_index' => 0.7, 'metrics' => ['population' => 12]]);
        UniverseSnapshot::factory()->create(['universe_id' => $universe->id, 'tick' => 33, 'stability_index' => 0.66, 'metrics' => ['population' => 15]]);
        Actor::create(['universe_id' => $universe->id, 'name' => 'A1', 'is_alive' => true]);
        Actor::create(['universe_id' => $universe->id, 'name' => 'A2', 'is_alive' => false]);

        $response = $this->getJson("/api/worldos/observatory/universes/{$universe->id}/civilization");

        $response->assertOk()
            ->assertJsonPath('data.universe_id', $universe->id)
            ->assertJsonPath('data.current_tick', 33)
            ->assertJsonPath('data.metrics.entropy', 0.42)
            ->assertJsonPath('data.metrics.stability_index', 0.66)   // snapshot mới nhất theo tick
            ->assertJsonPath('data.metrics.structural_coherence', 0.9)
            ->assertJsonPath('data.complexity.actor_count', 2)
            ->assertJsonPath('data.complexity.living_actor_count', 1)
            ->assertJsonPath('data.complexity.supreme_entity_count', 0)
            ->assertJsonPath('data.snapshot.tick', 33)
            ->assertJsonPath('data.snapshot.metrics.population', 15);
    }

    public function test_civilization_without_snapshot_returns_null_snapshot(): void
    {
        $universe = Universe::factory()->create();

        $response = $this->getJson("/api/worldos/observatory/universes/{$universe->id}/civilization");

        $response->assertOk()
            ->assertJsonPath('data.snapshot', null)
            ->assertJsonPath('data.metrics.stability_index', null);
    }

    public function test_civilization_returns_404_for_missing_universe(): void
    {
        $this->getJson('/api/worldos/observatory/universes/999999/civilization')->assertNotFound();
    }
}
```
Lưu ý: nếu `UniverseSnapshotFactory` yêu cầu field khác → xem factory hiện có (`database/factories/UniverseSnapshotFactory.php`) và truyền đúng.

- [ ] **Step 2: Chạy test thấy fail** — `php artisan test --filter=ObservatoryCivilizationTest` → FAIL 404.

- [ ] **Step 3: Tạo `GetUniverseCivilizationAction.php`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\WorldOS\Actions;

use App\Contracts\ActionInterface;
use App\Modules\World\Models\Universe;

class GetUniverseCivilizationAction implements ActionInterface
{
    /** @return array{data: array<string, mixed>} */
    public function handle(int $universeId): array
    {
        $universe = Universe::query()->with('latestSnapshot')->findOrFail($universeId);
        $snapshot = $universe->latestSnapshot;

        return [
            'data' => [
                'universe_id' => $universe->id,
                'status' => $universe->status,
                'current_tick' => (int) $universe->current_tick,
                'epoch' => $universe->epoch !== null ? (int) $universe->epoch : null,
                'metrics' => [
                    'entropy' => $universe->entropy !== null ? (float) $universe->entropy : null,
                    'stability_index' => $snapshot?->stability_index !== null ? (float) $snapshot->stability_index : null,
                    'structural_coherence' => $universe->structural_coherence !== null ? (float) $universe->structural_coherence : null,
                    'fitness_score' => $universe->fitness_score !== null ? (float) $universe->fitness_score : null,
                ],
                'complexity' => [
                    'actor_count' => $universe->actors()->count(),
                    'living_actor_count' => $universe->actors()->where('is_alive', true)->count(),
                    'supreme_entity_count' => $universe->supremeEntities()->count(),
                ],
                'snapshot' => $snapshot !== null ? [
                    'tick' => (int) $snapshot->tick,
                    'metrics' => is_array($snapshot->metrics) ? $snapshot->metrics : [],
                ] : null,
            ],
        ];
    }
}
```

- [ ] **Step 4: Controller + route** — constructor thêm `private readonly GetUniverseCivilizationAction $getUniverseCivilizationAction`; method:

```php
public function civilization(int $id): JsonResponse
{
    return response()->json($this->getUniverseCivilizationAction->handle($id));
}
```

Route (mục 5b):

```php
Route::get('observatory/universes/{id}/civilization', [ObservatoryController::class, 'civilization'])
    ->name('worldos.observatory.civilization');
```

- [ ] **Step 5: Test pass + pint + commit**

```bash
incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --filter=ObservatoryCivilizationTest 2>&1 | tail -6'
incus exec worldos-dev -- sh -c 'cd /work/backend && vendor/bin/pint app/Modules/WorldOS/Actions/GetUniverseCivilizationAction.php app/Modules/WorldOS/Http/Controllers/Api/ObservatoryController.php tests/Feature/Observatory/ObservatoryCivilizationTest.php'
git add backend/app/Modules/WorldOS/Actions/GetUniverseCivilizationAction.php backend/app/Modules/WorldOS/Http/Controllers/Api/ObservatoryController.php backend/app/Modules/WorldOS/routes/api.php backend/tests/Feature/Observatory/ObservatoryCivilizationTest.php
git commit -m "feat(be): observatory civilization endpoint — metrics persist + complexity"
```

---

### Task 5: BE — endpoint `GET observatory/universes/{id}/world`

**Files:**
- Create: `backend/app/Modules/WorldOS/Actions/GetUniverseWorldAction.php`
- Modify: `backend/app/Modules/WorldOS/Http/Controllers/Api/ObservatoryController.php`
- Modify: `backend/app/Modules/WorldOS/routes/api.php`
- Test: `backend/tests/Feature/Observatory/ObservatoryWorldTest.php`

**Interfaces:**
- Consumes: `World\Models\{Universe, Epoch, Religion, DiplomaticTreaty}`, bảng `technologies` + pivot `actor_technologies` (cột `actor_id`, `technology_id`, `level`) + bảng `actors` (qua `DB::table` join). Epoch gắn `world_id` (KHÔNG phải universe): chọn epoch `status='active'` mới nhất, fallback epoch có `start_tick <= current_tick` lớn nhất.
- Produces: `GET /api/worldos/observatory/universes/{id}/world` → `{data: {universe_id, world_id, epoch|null, religions[], treaties[], technologies[]}}`. FE Task 11 tiêu thụ (type `UniverseWorldState`).

- [ ] **Step 1: Viết feature test fail**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Observatory;

use App\Modules\Intelligence\Models\Actor;
use App\Modules\World\Models\DiplomaticTreaty;
use App\Modules\World\Models\Epoch;
use App\Modules\World\Models\Religion;
use App\Modules\World\Models\Technology;
use App\Modules\World\Models\Universe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ObservatoryWorldTest extends TestCase
{
    use RefreshDatabase;

    public function test_world_returns_epoch_religions_treaties_technologies(): void
    {
        $universe = Universe::factory()->create(['current_tick' => 50]);
        Epoch::create(['world_id' => $universe->world_id, 'name' => 'Bronze', 'start_tick' => 0, 'end_tick' => 30, 'status' => 'ended']);
        Epoch::create(['world_id' => $universe->world_id, 'name' => 'Iron', 'start_tick' => 31, 'status' => 'active']);
        Religion::create(['universe_id' => $universe->id, 'name' => 'Solism', 'followers' => 120, 'spread_rate' => 0.3]);
        Religion::create(['universe_id' => $universe->id, 'name' => 'Lunism', 'followers' => 40, 'spread_rate' => 0.1]);
        DiplomaticTreaty::create(['universe_id' => $universe->id, 'source_civ_id' => 1, 'target_civ_id' => 2, 'treaty_type' => 'trade', 'started_at_tick' => 10, 'is_active' => true]);
        DiplomaticTreaty::create(['universe_id' => $universe->id, 'source_civ_id' => 1, 'target_civ_id' => 3, 'treaty_type' => 'war', 'started_at_tick' => 5, 'is_active' => false]);

        $tech = Technology::create(['name' => 'Lửa', 'code' => 'fire']);
        $a1 = Actor::create(['universe_id' => $universe->id, 'name' => 'A1', 'is_alive' => true]);
        $a2 = Actor::create(['universe_id' => $universe->id, 'name' => 'A2', 'is_alive' => true]);
        DB::table('actor_technologies')->insert([
            ['actor_id' => $a1->id, 'technology_id' => $tech->id, 'level' => 0.4, 'created_at' => now(), 'updated_at' => now()],
            ['actor_id' => $a2->id, 'technology_id' => $tech->id, 'level' => 0.8, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->getJson("/api/worldos/observatory/universes/{$universe->id}/world");

        $response->assertOk()
            ->assertJsonPath('data.epoch.name', 'Iron')
            ->assertJsonPath('data.religions.0.name', 'Solism')     // followers DESC
            ->assertJsonCount(2, 'data.religions')
            ->assertJsonCount(1, 'data.treaties')                    // chỉ is_active
            ->assertJsonPath('data.treaties.0.treaty_type', 'trade')
            ->assertJsonPath('data.technologies.0.code', 'fire')
            ->assertJsonPath('data.technologies.0.adopters', 2)
            ->assertJsonPath('data.technologies.0.avg_level', 0.6);
    }

    public function test_world_without_data_returns_empty_lists_and_null_epoch(): void
    {
        $universe = Universe::factory()->create();

        $this->getJson("/api/worldos/observatory/universes/{$universe->id}/world")
            ->assertOk()
            ->assertJsonPath('data.epoch', null)
            ->assertJsonPath('data.religions', [])
            ->assertJsonPath('data.treaties', [])
            ->assertJsonPath('data.technologies', []);
    }

    public function test_world_returns_404_for_missing_universe(): void
    {
        $this->getJson('/api/worldos/observatory/universes/999999/world')->assertNotFound();
    }
}
```
Lưu ý: các model `Technology/Religion/Epoch/DiplomaticTreaty` đều đã tồn tại ở `App\Modules\World\Models\` (đã xác minh khi viết plan); nếu cột NOT NULL thiếu khi seed → bổ sung giá trị tối thiểu, KHÔNG sửa migration.

- [ ] **Step 2: Chạy test thấy fail** — `php artisan test --filter=ObservatoryWorldTest` → FAIL 404.

- [ ] **Step 3: Tạo `GetUniverseWorldAction.php`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\WorldOS\Actions;

use App\Contracts\ActionInterface;
use App\Modules\World\Models\DiplomaticTreaty;
use App\Modules\World\Models\Epoch;
use App\Modules\World\Models\Religion;
use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\DB;

class GetUniverseWorldAction implements ActionInterface
{
    private const RELIGION_LIMIT = 20;
    private const TREATY_LIMIT = 50;
    private const TECHNOLOGY_LIMIT = 50;

    /** @return array{data: array<string, mixed>} */
    public function handle(int $universeId): array
    {
        $universe = Universe::query()->findOrFail($universeId);

        return [
            'data' => [
                'universe_id' => $universe->id,
                'world_id' => $universe->world_id !== null ? (int) $universe->world_id : null,
                'epoch' => $this->currentEpoch($universe),
                'religions' => $this->religions($universeId),
                'treaties' => $this->treaties($universeId),
                'technologies' => $this->technologies($universeId),
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    private function currentEpoch(Universe $universe): ?array
    {
        if ($universe->world_id === null) {
            return null;
        }

        $epoch = Epoch::query()
            ->where('world_id', $universe->world_id)
            ->where('status', 'active')
            ->orderByDesc('start_tick')
            ->first()
            ?? Epoch::query()
                ->where('world_id', $universe->world_id)
                ->where('start_tick', '<=', (int) $universe->current_tick)
                ->orderByDesc('start_tick')
                ->first();

        if ($epoch === null) {
            return null;
        }

        return [
            'id' => $epoch->id,
            'name' => $epoch->name,
            'theme' => $epoch->theme,
            'description' => $epoch->description,
            'start_tick' => $epoch->start_tick !== null ? (int) $epoch->start_tick : null,
            'end_tick' => $epoch->end_tick !== null ? (int) $epoch->end_tick : null,
            'status' => $epoch->status,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function religions(int $universeId): array
    {
        return Religion::query()
            ->where('universe_id', $universeId)
            ->orderByDesc('followers')
            ->limit(self::RELIGION_LIMIT)
            ->get()
            ->map(fn (Religion $r): array => [
                'id' => $r->id,
                'name' => $r->name,
                'followers' => (int) $r->followers,
                'spread_rate' => $r->spread_rate !== null ? (float) $r->spread_rate : null,
                'doctrine' => $r->doctrine,
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function treaties(int $universeId): array
    {
        return DiplomaticTreaty::query()
            ->where('universe_id', $universeId)
            ->where('is_active', true)
            ->orderByDesc('started_at_tick')
            ->limit(self::TREATY_LIMIT)
            ->get()
            ->map(fn (DiplomaticTreaty $t): array => [
                'id' => $t->id,
                'treaty_type' => $t->treaty_type,
                'source_civ_id' => $t->source_civ_id,
                'target_civ_id' => $t->target_civ_id,
                'started_at_tick' => (int) $t->started_at_tick,
                'ends_at_tick' => $t->ends_at_tick !== null ? (int) $t->ends_at_tick : null,
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function technologies(int $universeId): array
    {
        return DB::table('technologies')
            ->join('actor_technologies', 'actor_technologies.technology_id', '=', 'technologies.id')
            ->join('actors', 'actors.id', '=', 'actor_technologies.actor_id')
            ->where('actors.universe_id', $universeId)
            ->groupBy('technologies.id', 'technologies.name', 'technologies.code')
            ->orderByDesc(DB::raw('COUNT(actor_technologies.id)'))
            ->limit(self::TECHNOLOGY_LIMIT)
            ->select([
                'technologies.id',
                'technologies.name',
                'technologies.code',
                DB::raw('COUNT(actor_technologies.id) as adopters'),
                DB::raw('AVG(actor_technologies.level) as avg_level'),
            ])
            ->get()
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'code' => $row->code,
                'adopters' => (int) $row->adopters,
                'avg_level' => round((float) $row->avg_level, 3),
            ])
            ->all();
    }
}
```

- [ ] **Step 4: Controller + route** — constructor thêm `private readonly GetUniverseWorldAction $getUniverseWorldAction`; method:

```php
public function world(int $id): JsonResponse
{
    return response()->json($this->getUniverseWorldAction->handle($id));
}
```

Route (mục 5b):

```php
Route::get('observatory/universes/{id}/world', [ObservatoryController::class, 'world'])
    ->name('worldos.observatory.world');
```

- [ ] **Step 5: Test pass + guardrail + pint + commit**

```bash
incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --filter="ObservatoryWorldTest|ModuleBoundaryTest" 2>&1 | tail -6'
incus exec worldos-dev -- sh -c 'cd /work/backend && vendor/bin/pint app/Modules/WorldOS/Actions/GetUniverseWorldAction.php app/Modules/WorldOS/Http/Controllers/Api/ObservatoryController.php tests/Feature/Observatory/ObservatoryWorldTest.php'
git add backend/app/Modules/WorldOS/Actions/GetUniverseWorldAction.php backend/app/Modules/WorldOS/Http/Controllers/Api/ObservatoryController.php backend/app/Modules/WorldOS/routes/api.php backend/tests/Feature/Observatory/ObservatoryWorldTest.php
git commit -m "feat(be): observatory world endpoint — epoch/religions/treaties/technologies"
```

---

### Task 6: FE nền tảng — routes/qk mở rộng, status realtime, LensNav, `useObservedUniverse`, fake `getSubscription`, helper `takeData`

**Files:**
- Modify: `frontend/src/shared/config/routes.ts`
- Modify: `frontend/src/shared/config/queryKeys.ts`
- Modify: `frontend/src/shared/store/simStore.ts` (đọc `status` từ pulse)
- Modify: `frontend/src/test/fakeCentrifuge.ts` (thêm `getSubscription`)
- Create: `frontend/src/shared/lib/unwrap.ts`
- Create: `frontend/src/features/universe-workspace/components/LensNav.tsx`
- Create: `frontend/src/features/universe-workspace/hooks/useObservedUniverse.ts`
- Modify: `frontend/src/features/universe-workspace/components/WorkspaceLayout.tsx` (prop `universeId` + render LensNav)
- Modify: `frontend/src/features/universe-workspace/index.ts`
- Modify: `frontend/src/app/(observatory)/u/[id]/page.tsx` (dùng `useObservedUniverse`, truyền `universeId` cho layout)
- Test: `frontend/src/shared/store/__tests__/simStore.test.ts` (bổ sung 2 case status), `frontend/src/shared/lib/__tests__/unwrap.test.ts`, `frontend/src/features/universe-workspace/__tests__/LensNav.test.tsx`

**Interfaces:**
- Consumes: `simStore`/`feedStore`/`useUniverseChannels` hiện có; payload pulse có `status` (Task 2).
- Produces (các task sau dùng đúng tên này):
  - `routes.universeActors(id)`, `routes.universeCivilization(id)`, `routes.universeCausality(id)`, `routes.universeWavefunction(id)` → `/u/{id}/actors|civilization|causality|wavefunction`; `routes.chronicle(chronicleId)` → `/chronicle/{chronicleId}`.
  - `qk.feed(id, types?: string[])` (types rỗng/không truyền → key như cũ + phần tử `''`), `qk.actors(id)`, `qk.actorPsyche(actorId)`, `qk.supremeEntities(id)`, `qk.civilization(id)`, `qk.worldState(id)`, `qk.topology(id)`, `qk.causalLinks(id, fromTick?, toTick?)`, `qk.realityState(id)`, `qk.wavefunction(id)`, `qk.informationalMass(id)`, `qk.consciousness(id)`, `qk.ascensionFilters(id)`, `qk.stateDelta(id)`, `qk.bloom()`, `qk.resonance()`, `qk.chronicle(chronicleId)`.
  - `takeData<T>(body: unknown): T` — bóc `{data, meta?, links?}` (parity với client cũ) cho endpoint list cũ.
  - `WorkspaceLayout({ children, universeId? })` — có `universeId` → render `LensNav`.
  - `useObservedUniverse(universeId, opts?: { onLiveGap?: () => void })` — gộp effect select/clear + `useUniverseChannels`.
  - `simStore.live.status` được set từ `payload.status` của pulse.
  - Fake: `centrifuge.getSubscription(channel)` trả sub đã đăng ký hoặc `null`.

- [ ] **Step 1: Viết test fail**

`src/shared/lib/__tests__/unwrap.test.ts`:

```ts
import { describe, expect, it } from 'vitest';
import { takeData } from '../unwrap';

describe('takeData', () => {
  it('bóc {data} khi chỉ có data + meta/links', () => {
    expect(takeData<{ x: number }[]>({ data: [{ x: 1 }], meta: { total: 1 } })).toEqual([{ x: 1 }]);
    expect(takeData<number[]>({ data: [1, 2] })).toEqual([1, 2]);
  });
  it('giữ nguyên body không có wrapper', () => {
    expect(takeData<number[]>([1, 2])).toEqual([1, 2]);
    expect(takeData<{ a: 1; b: 2 }>({ a: 1, b: 2 })).toEqual({ a: 1, b: 2 });
  });
  it('KHÔNG bóc khi có key khác ngoài data/meta/links', () => {
    expect(takeData<object>({ data: [1], extra: true })).toEqual({ data: [1], extra: true });
  });
});
```

Bổ sung vào file test simStore hiện có (case mới):

```ts
it('applyPulse set status từ payload', () => {
  useSimStore.getState().applyPulse({
    id: 'e1', type: 'universe.pulsed', tick: 5, universe_id: 1, world_id: null,
    severity: 'info', occurred_at: '', payload: { entropy: 0.4, stability_index: 0.8, status: 'paused' },
  });
  expect(useSimStore.getState().live.status).toBe('paused');
});
it('applyPulse giữ status cũ khi payload không có status', () => {
  useSimStore.getState().applyPulse({
    id: 'e2', type: 'universe.pulsed', tick: 6, universe_id: 1, world_id: null,
    severity: 'info', occurred_at: '', payload: { entropy: 0.4 },
  });
  expect(useSimStore.getState().live.status).toBe('paused');
});
```

`src/features/universe-workspace/__tests__/LensNav.test.tsx`:

```tsx
import { describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import { LensNav } from '../components/LensNav';

vi.mock('next/navigation', () => ({ usePathname: () => '/u/7/actors' }));

describe('LensNav', () => {
  it('render 5 lens, đánh dấu lens hiện tại bằng aria-current', () => {
    render(<LensNav universeId={7} />);
    const nav = screen.getByRole('navigation', { name: /lens/i });
    expect(nav).toBeTruthy();
    expect(screen.getByRole('link', { name: 'Biên niên sử' }).getAttribute('href')).toBe('/u/7');
    const actors = screen.getByRole('link', { name: 'Actors' });
    expect(actors.getAttribute('href')).toBe('/u/7/actors');
    expect(actors.getAttribute('aria-current')).toBe('page');
    expect(screen.getByRole('link', { name: 'Văn minh' }).getAttribute('href')).toBe('/u/7/civilization');
    expect(screen.getByRole('link', { name: 'Nhân quả' }).getAttribute('href')).toBe('/u/7/causality');
    expect(screen.getByRole('link', { name: 'Wavefunction' }).getAttribute('href')).toBe('/u/7/wavefunction');
  });
});
```

- [ ] **Step 2: Chạy test thấy fail**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads src/shared/lib/__tests__/unwrap.test.ts src/features/universe-workspace/__tests__/LensNav.test.tsx 2>&1 | tail -8'
```
Expected: FAIL — module `../unwrap`, `../components/LensNav` chưa tồn tại.

- [ ] **Step 3: Implement `routes.ts`**

```ts
export const routes = {
  login: () => '/login',
  multiverse: () => '/multiverse',
  universe: (id: number) => `/u/${id}`,
  universeActors: (id: number) => `/u/${id}/actors`,
  universeCivilization: (id: number) => `/u/${id}/civilization`,
  universeCausality: (id: number) => `/u/${id}/causality`,
  universeWavefunction: (id: number) => `/u/${id}/wavefunction`,
  chronicle: (chronicleId: number) => `/chronicle/${chronicleId}`,
} as const;
```

- [ ] **Step 4: Implement `queryKeys.ts`**

```ts
export const qk = {
  universes: () => ['universes'] as const,
  universe: (id: number) => ['universes', id] as const,
  metrics: (id: number) => ['universes', id, 'metrics'] as const,
  snapshot: (id: number, tick: number) => ['universes', id, 'snapshot', tick] as const,
  chronicles: (id: number) => ['universes', id, 'chronicles'] as const,
  chronicle: (chronicleId: number) => ['chronicles', chronicleId] as const,
  feed: (id: number, types: string[] = []) =>
    ['universes', id, 'feed', [...types].sort().join(',')] as const,
  actors: (id: number) => ['universes', id, 'actors'] as const,
  actorPsyche: (actorId: number) => ['actors', actorId, 'psyche'] as const,
  supremeEntities: (id: number) => ['universes', id, 'supreme-entities'] as const,
  civilization: (id: number) => ['universes', id, 'civilization'] as const,
  worldState: (id: number) => ['universes', id, 'world'] as const,
  topology: (id: number) => ['universes', id, 'topology'] as const,
  causalLinks: (id: number, fromTick?: number, toTick?: number) =>
    ['universes', id, 'causal-links', fromTick ?? null, toTick ?? null] as const,
  realityState: (id: number) => ['universes', id, 'reality-state'] as const,
  wavefunction: (id: number) => ['universes', id, 'wavefunction'] as const,
  informationalMass: (id: number) => ['universes', id, 'informational-mass'] as const,
  consciousness: (id: number) => ['universes', id, 'consciousness'] as const,
  ascensionFilters: (id: number) => ['universes', id, 'ascension-filters'] as const,
  stateDelta: (id: number) => ['universes', id, 'state-delta'] as const,
  bloom: () => ['multiverse', 'bloom'] as const,
  resonance: () => ['multiverse', 'resonance'] as const,
  forkTree: () => ['multiverse', 'fork-tree'] as const,
} as const;
```
LƯU Ý: `qk.feed` đổi shape (thêm phần tử chuỗi types) — cập nhật test hiện có của `useChronicleFeed` nếu assert queryKey.

- [ ] **Step 5: Implement `shared/lib/unwrap.ts`**

```ts
/**
 * Bóc wrapper Laravel {data, meta?, links?} — parity với interceptor của client cũ (@/lib/api).
 * apiClient chỉ tự bóc body 1-key {data}; endpoint list cũ có thể kèm meta/links → dùng helper này.
 */
export function takeData<T>(body: unknown): T {
  if (body && typeof body === 'object' && !Array.isArray(body) && 'data' in body) {
    const rest = Object.keys(body).filter((k) => k !== 'meta' && k !== 'links');
    if (rest.length === 1 && rest[0] === 'data') return (body as { data: T }).data;
  }
  return body as T;
}
```

- [ ] **Step 6: `simStore.applyPulse` đọc status** — trong `applyPulse`, mở rộng type cục bộ của `p` thêm `status?: string` và thay dòng `status: s.live.status` bằng:

```ts
status: typeof p.status === 'string' ? p.status : s.live.status,
```

- [ ] **Step 7: fake Centrifugo thêm `getSubscription`** — trong object `centrifuge` của `makeFakeCentrifugeMulti`:

```ts
getSubscription: vi.fn((channel: string) => registry.get(channel) ?? null),
```

- [ ] **Step 8: Implement `LensNav.tsx`**

```tsx
'use client';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { routes } from '@/shared/config/routes';

const LENSES = [
  { label: 'Biên niên sử', href: (id: number) => routes.universe(id) },
  { label: 'Actors', href: (id: number) => routes.universeActors(id) },
  { label: 'Văn minh', href: (id: number) => routes.universeCivilization(id) },
  { label: 'Nhân quả', href: (id: number) => routes.universeCausality(id) },
  { label: 'Wavefunction', href: (id: number) => routes.universeWavefunction(id) },
] as const;

export function LensNav({ universeId }: { universeId: number }) {
  const pathname = usePathname();
  return (
    <nav aria-label="Lens quan sát" className="flex items-center gap-1 overflow-x-auto px-4">
      {LENSES.map((lens) => {
        const href = lens.href(universeId);
        const active = pathname === href;
        return (
          <Link
            key={lens.label}
            href={href}
            aria-current={active ? 'page' : undefined}
            className={`shrink-0 border-b-2 px-3 py-2 font-mono text-xs uppercase tracking-wider transition-colors duration-200 ${
              active
                ? 'border-[var(--color-primary)] text-[var(--color-primary)]'
                : 'border-transparent text-[var(--color-text-muted)] hover:text-[var(--color-text-secondary)]'
            }`}
          >
            {lens.label}
          </Link>
        );
      })}
    </nav>
  );
}
```

- [ ] **Step 9: `WorkspaceLayout` nhận `universeId`** — thay đổi signature + render nav dưới header:

```tsx
import Link from 'next/link';
import type { ReactNode } from 'react';
import { routes } from '@/shared/config/routes';
import { ContextBar } from './ContextBar';
import { LensNav } from './LensNav';

/** Shell của Observatory: thanh bối cảnh + (khi có universe) tab-nav lens + nội dung. */
export function WorkspaceLayout({ children, universeId }: { children: ReactNode; universeId?: number | null }) {
  return (
    <div className="flex min-h-screen flex-col bg-[var(--color-bg-base)] text-[var(--color-text-primary)]">
      <header className="border-b border-[var(--border-subtle)]">
        <div className="flex items-center justify-between gap-4 px-4 py-2">
          <ContextBar />
          <Link
            href={routes.multiverse()}
            className="shrink-0 text-sm text-[var(--color-text-secondary)] hover:text-[var(--color-primary)]"
          >
            Đa vũ trụ
          </Link>
        </div>
        {universeId != null && <LensNav universeId={universeId} />}
      </header>
      <main className="min-h-0 flex-1 p-4">{children}</main>
    </div>
  );
}
```

- [ ] **Step 10: Implement `useObservedUniverse.ts`**

```ts
'use client';
import { useEffect } from 'react';
import { useSimStore } from '@/shared/store/simStore';
import { useFeedStore } from '@/shared/store/feedStore';
import { useUniverseChannels } from '@/shared/realtime/useUniverseChannels';

/** Chọn universe vào simStore (clear feed khi đổi) + subscribe cụm kênh realtime. Dùng ở hero + mọi lens. */
export function useObservedUniverse(universeId: number | null, opts: { onLiveGap?: () => void } = {}): void {
  const selectUniverse = useSimStore((s) => s.selectUniverse);
  const selectedUniverseId = useSimStore((s) => s.selectedUniverseId);
  const clearFeed = useFeedStore((s) => s.clear);

  useEffect(() => {
    if (universeId != null && selectedUniverseId !== universeId) {
      clearFeed();
      selectUniverse(universeId);
    }
  }, [universeId, selectedUniverseId, selectUniverse, clearFeed]);

  useUniverseChannels(universeId, opts);
}
```

- [ ] **Step 11: Export public API + refactor hero** — `features/universe-workspace/index.ts`:

```ts
export { WorkspaceLayout } from './components/WorkspaceLayout';
export { LensNav } from './components/LensNav';
export { useUniverses } from './hooks/useUniverses';
export { useObservedUniverse } from './hooks/useObservedUniverse';
```

Hero `u/[id]/page.tsx`: xóa effect select/clear + `useUniverseChannels` inline, thay bằng:

```tsx
const feed = useChronicleFeed(universeId);
useObservedUniverse(universeId, { onLiveGap: feed.refetchLatest });
```
(import `useObservedUniverse` từ `@/features/universe-workspace`; bỏ import `useFeedStore`, `useUniverseChannels`, và selector `selectUniverse`/`selectedUniverseId` không còn dùng). Truyền `universeId` cho layout: `<WorkspaceLayout universeId={universeId}>`. (`onLiveGap` sẽ đổi sang backfill ở Task 7.)

- [ ] **Step 12: Chạy test pass + check**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads 2>&1 | tail -6'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check 2>&1 | tail -5'
```
Expected: toàn bộ pass (sửa test cũ nếu vỡ vì `qk.feed` shape hoặc hero refactor — hành vi phải giữ nguyên); check 0 error mới.

- [ ] **Step 13: Commit**

```bash
git add frontend/src/shared frontend/src/test/fakeCentrifuge.ts frontend/src/features/universe-workspace "frontend/src/app/(observatory)/u/[id]/page.tsx"
git commit -m "feat(fe): P3 nền tảng — routes/qk lens, LensNav, useObservedUniverse, status từ pulse, takeData"
```

---

### Task 7: FE — backfill `after_tick` khi live-gap + a11y batch (nợ P2)

**Files:**
- Modify: `frontend/src/features/chronicle/api/feed.ts` (export `FEED_PAGE_LIMIT`)
- Modify: `frontend/src/features/chronicle/hooks/useChronicleFeed.ts` (thêm `backfillLatest`)
- Modify: `frontend/src/app/(observatory)/u/[id]/page.tsx` (`onLiveGap: feed.backfillLatest`; `aria-hidden` cho `AlertTriangle`)
- Modify: `frontend/src/features/chronicle/components/ChronicleStream.tsx` (`role="log"` + `aria-live="polite"` + `overscroll-contain`; `aria-hidden` cho `ScrollText`)
- Modify: `frontend/src/features/chronicle/components/ChronicleEntry.tsx` (`aria-hidden` cho chip icon)
- Test: `frontend/src/features/chronicle/__tests__/useChronicleFeed.test.tsx` (case backfill), `frontend/src/features/chronicle/__tests__/ChronicleStream.test.tsx` (case a11y)

**Interfaces:**
- Consumes: `fetchFeed(universeId, { after_tick })` (param đã có sẵn), `useFeedStore.pushLive` (dedup theo id), `mergeFeed` (sort lại toàn cục — thứ tự push không quan trọng).
- Produces: `ChronicleFeed` thêm `backfillLatest: () => void` — fetch `after_tick = max(0, tickMớiNhấtĐãThấy - 1)`, push vào feedStore; nếu trang đầy (`meta.count >= FEED_PAGE_LIMIT`, có thể còn sót) hoặc fetch lỗi → fallback `query.refetch()`. Chưa có item nào → `refetch()` thẳng.

- [ ] **Step 1: Viết test fail** — bổ sung vào `useChronicleFeed.test.tsx` (theo pattern mock `@/shared/lib/apiClient` hiện có trong file):

```tsx
it('backfillLatest fetch after_tick = tick mới nhất - 1 và đẩy kết quả vào feedStore', async () => {
  // trang đầu: item tick 10
  mockGet.mockResolvedValueOnce({ data: { data: [makeItem('a', 10)], meta: { count: 1, next_before_tick: null } } });
  const { result } = renderHook(() => useChronicleFeed(1), { wrapper });
  await waitFor(() => expect(result.current.items).toHaveLength(1));

  // backfill: trả về 1 item mới tick 12
  mockGet.mockResolvedValueOnce({ data: { data: [makeItem('b', 12)], meta: { count: 1, next_before_tick: null } } });
  await act(async () => { result.current.backfillLatest(); });

  await waitFor(() => expect(result.current.items.map((i) => i.id)).toEqual(['b', 'a']));
  expect(mockGet).toHaveBeenLastCalledWith(
    '/worldos/observatory/universes/1/feed',
    expect.objectContaining({ params: expect.objectContaining({ after_tick: 9 }) }),
  );
});

it('backfillLatest fallback refetch khi trang backfill đầy (>= FEED_PAGE_LIMIT)', async () => {
  mockGet.mockResolvedValueOnce({ data: { data: [makeItem('a', 10)], meta: { count: 1, next_before_tick: null } } });
  const { result } = renderHook(() => useChronicleFeed(1), { wrapper });
  await waitFor(() => expect(result.current.items).toHaveLength(1));

  const bigPage = Array.from({ length: 50 }, (_, i) => makeItem(`x${i}`, 11 + i));
  mockGet.mockResolvedValueOnce({ data: { data: bigPage, meta: { count: 50, next_before_tick: null } } });
  // refetch (trang đầu mới) sau fallback
  mockGet.mockResolvedValueOnce({ data: { data: [makeItem('fresh', 99)], meta: { count: 1, next_before_tick: null } } });

  await act(async () => { result.current.backfillLatest(); });
  await waitFor(() => expect(mockGet).toHaveBeenCalledTimes(3));
});
```
(`makeItem(id, tick)` — helper tạo `FeedItem` tối thiểu; nếu file test đã có helper tương tự thì tái dùng. Nhớ `useFeedStore.getState().clear()` trong `beforeEach` — feedStore là singleton giữa các test.)

Bổ sung `ChronicleStream.test.tsx`:

```tsx
it('container stream có role log + aria-live polite', () => {
  render(<ChronicleStream items={[item]} hasOlder={false} isLoadingOlder={false} onLoadOlder={() => {}} />);
  const log = screen.getByRole('log');
  expect(log.getAttribute('aria-live')).toBe('polite');
});
```

- [ ] **Step 2: Chạy test thấy fail** — `npm test -- --pool=threads src/features/chronicle` → FAIL (`backfillLatest` undefined, `getByRole('log')` không tìm thấy).

- [ ] **Step 3: Implement** — `feed.ts`: đổi `const DEFAULT_LIMIT = 50;` thành `export const FEED_PAGE_LIMIT = 50;` (cập nhật chỗ dùng). `useChronicleFeed.ts`:

```ts
'use client';
import { useCallback, useMemo } from 'react';
import { useInfiniteQuery } from '@tanstack/react-query';
import { qk } from '@/shared/config/queryKeys';
import { useFeedStore } from '@/shared/store/feedStore';
import type { FeedItem } from '@/shared/realtime/envelope';
import { fetchFeed, FEED_PAGE_LIMIT, type FeedPage } from '../api/feed';
import { mergeFeed } from '../lib/mergeFeed';

export interface ChronicleFeed {
  items: FeedItem[];
  fetchOlder: () => void;
  hasOlder: boolean;
  isLoadingOlder: boolean;
  isError: boolean;
  refetchLatest: () => void;
  backfillLatest: () => void;
}
```

Trong thân hook (giữ nguyên `query` + `items` hiện có), thêm trước `return`:

```ts
const refetch = query.refetch;
const latestTick = items.length > 0 ? items[0].tick : null;

const backfillLatest = useCallback(() => {
  if (universeId == null) return;
  if (latestTick == null) { void refetch(); return; }
  void (async () => {
    try {
      const page = await fetchFeed(universeId, { after_tick: Math.max(0, latestTick - 1) });
      const push = useFeedStore.getState().pushLive;
      page.data.forEach(push);
      if (page.meta.count >= FEED_PAGE_LIMIT) void refetch(); // có thể còn sót nhiều hơn 1 trang
    } catch {
      void refetch(); // backfill lỗi → refetch toàn bộ như cũ
    }
  })();
}, [universeId, latestTick, refetch]);
```

và thêm `backfillLatest` vào object return. Hero: `useObservedUniverse(universeId, { onLiveGap: feed.backfillLatest })`.

- [ ] **Step 4: A11y batch**
  - `ChronicleStream.tsx`: container cuộn thêm `role="log"`, `aria-live="polite"`, `aria-label="Dòng sự kiện trực tiếp"`, và class `overscroll-contain` (chặn scroll chaining ra body); `<ScrollText …/>` empty-state thêm `aria-hidden="true"`.
  - `ChronicleEntry.tsx`: `<span>` chip icon thêm `aria-hidden="true"`.
  - Hero `u/[id]/page.tsx`: `<AlertTriangle …/>` trong banner degraded thêm `aria-hidden="true"`.

- [ ] **Step 5: Test pass + check + commit**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads 2>&1 | tail -6'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check 2>&1 | tail -5'
git add frontend/src/features/chronicle "frontend/src/app/(observatory)/u/[id]/page.tsx"
git commit -m "fix(fe): backfill after_tick khi live-gap + a11y stream (role=log, aria-hidden, overscroll)"
```

---

### Task 8: FE — filter sự kiện ở hero (nhóm type)

**Files:**
- Modify: `frontend/src/features/chronicle/api/feed.ts` (param `types`)
- Modify: `frontend/src/features/chronicle/hooks/useChronicleFeed.ts` (opts `types` + lọc live)
- Create: `frontend/src/features/chronicle/lib/feedFilters.ts`
- Create: `frontend/src/features/chronicle/components/FeedFilterChips.tsx`
- Modify: `frontend/src/features/chronicle/index.ts`
- Modify: `frontend/src/app/(observatory)/u/[id]/page.tsx` (state filter + chips)
- Test: `frontend/src/features/chronicle/__tests__/useChronicleFeed.test.tsx`, `frontend/src/features/chronicle/__tests__/FeedFilterChips.test.tsx`

**Interfaces:**
- Consumes: BE feed param `types` (CSV, đã hỗ trợ từ P1); `qk.feed(id, types)` (Task 6).
- Produces:
  - `FEED_FILTERS: readonly { key: string; label: string; types: readonly string[] }[]` — 4 nhóm: `narrative` (chronicle, artifact.discovered, celebrity.emerged, history.shifted), `epoch` (epoch.transitioned), `anomaly` (anomaly.detected), `autopoiesis` (autopoiesis.mutation).
  - `typesForFilters(activeKeys: string[]): string[]` — hợp các nhóm active; rỗng = tất cả.
  - `useChronicleFeed(universeId, opts?: { types?: string[] })` — types đi vào queryKey + query param + lọc live items.
  - `FeedFilterChips({ active, onToggle }: { active: string[]; onToggle: (key: string) => void })`.

- [ ] **Step 1: Viết test fail**

`feedFilters` + hook (bổ sung `useChronicleFeed.test.tsx`):

```tsx
it('truyền types vào query param dạng CSV và lọc live items', async () => {
  useFeedStore.getState().pushLive({ ...makeItem('live-anom', 20), type: 'anomaly.detected' });
  useFeedStore.getState().pushLive({ ...makeItem('live-chr', 21), type: 'chronicle' });
  mockGet.mockResolvedValueOnce({ data: { data: [], meta: { count: 0, next_before_tick: null } } });

  const { result } = renderHook(() => useChronicleFeed(1, { types: ['anomaly.detected'] }), { wrapper });

  await waitFor(() => expect(mockGet).toHaveBeenCalled());
  expect(mockGet).toHaveBeenCalledWith(
    '/worldos/observatory/universes/1/feed',
    expect.objectContaining({ params: expect.objectContaining({ types: 'anomaly.detected' }) }),
  );
  await waitFor(() => expect(result.current.items.map((i) => i.id)).toEqual(['live-anom']));
});
```

`FeedFilterChips.test.tsx`:

```tsx
import { describe, expect, it, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { FeedFilterChips } from '../components/FeedFilterChips';

describe('FeedFilterChips', () => {
  it('render 4 chip, toggle gọi onToggle với key, chip active có aria-pressed', () => {
    const onToggle = vi.fn();
    render(<FeedFilterChips active={['anomaly']} onToggle={onToggle} />);
    const anomaly = screen.getByRole('button', { name: 'Dị thường' });
    expect(anomaly.getAttribute('aria-pressed')).toBe('true');
    fireEvent.click(screen.getByRole('button', { name: 'Tường thuật' }));
    expect(onToggle).toHaveBeenCalledWith('narrative');
    expect(screen.getAllByRole('button')).toHaveLength(4);
  });
});
```

- [ ] **Step 2: Chạy test thấy fail** — module/prop chưa tồn tại.

- [ ] **Step 3: Implement `lib/feedFilters.ts`**

```ts
export const FEED_FILTERS = [
  { key: 'narrative', label: 'Tường thuật', types: ['chronicle', 'artifact.discovered', 'celebrity.emerged', 'history.shifted'] },
  { key: 'epoch', label: 'Kỷ nguyên', types: ['epoch.transitioned'] },
  { key: 'anomaly', label: 'Dị thường', types: ['anomaly.detected'] },
  { key: 'autopoiesis', label: 'Tự biến đổi', types: ['autopoiesis.mutation'] },
] as const;

export type FeedFilterKey = (typeof FEED_FILTERS)[number]['key'];

/** Hợp các nhóm active thành danh sách type gửi BE; rỗng = không lọc (tất cả). */
export function typesForFilters(activeKeys: string[]): string[] {
  return FEED_FILTERS.filter((f) => activeKeys.includes(f.key)).flatMap((f) => [...f.types]);
}
```

- [ ] **Step 4: `feed.ts` nhận `types`**

```ts
export async function fetchFeed(
  universeId: number,
  params: { before_tick?: number; after_tick?: number; limit?: number; types?: string[] } = {},
): Promise<FeedPage> {
  const { types, ...rest } = params;
  const res = await apiClient.get(`/worldos/observatory/universes/${universeId}/feed`, {
    params: {
      limit: FEED_PAGE_LIMIT,
      ...rest,
      ...(types && types.length > 0 ? { types: types.join(',') } : {}),
    },
  });
  return res.data as FeedPage;
}
```

- [ ] **Step 5: `useChronicleFeed` nhận `opts.types`** — signature `useChronicleFeed(universeId: number | null, opts: { types?: string[] } = {})`; `const types = useMemo(() => opts.types ?? [], [opts.types?.join(',')])` (ổn định reference); `queryKey: qk.feed(universeId, types)`; `queryFn` truyền `types` (cả trang đầu lẫn `before_tick`); `backfillLatest` truyền `types` vào `fetchFeed`; lọc live:

```ts
const liveFiltered = liveItems.filter(
  (i) => i.universe_id === universeId && (types.length === 0 || types.includes(i.type)),
);
```

- [ ] **Step 6: Implement `FeedFilterChips.tsx`**

```tsx
'use client';
import { FEED_FILTERS } from '../lib/feedFilters';

interface Props { active: string[]; onToggle: (key: string) => void }

export function FeedFilterChips({ active, onToggle }: Props) {
  return (
    <div className="flex flex-wrap items-center gap-1.5" role="group" aria-label="Lọc sự kiện theo loại">
      {FEED_FILTERS.map((f) => {
        const on = active.includes(f.key);
        return (
          <button
            key={f.key}
            type="button"
            aria-pressed={on}
            onClick={() => onToggle(f.key)}
            className={`rounded-full border px-3 py-1 font-mono text-[11px] uppercase tracking-wider transition-colors duration-200 ${
              on
                ? 'border-[var(--color-primary)] bg-[rgba(110,231,247,0.12)] text-[var(--color-primary)]'
                : 'border-[var(--border-subtle)] text-[var(--color-text-muted)] hover:border-[var(--border-muted)] hover:text-[var(--color-text-secondary)]'
            }`}
          >
            {f.label}
          </button>
        );
      })}
    </div>
  );
}
```

- [ ] **Step 7: Wire vào hero** — `u/[id]/page.tsx`:

```tsx
const [activeFilters, setActiveFilters] = useState<string[]>([]);
const filterTypes = useMemo(() => typesForFilters(activeFilters), [activeFilters]);
const feed = useChronicleFeed(universeId, { types: filterTypes });
```

Toggle: `setActiveFilters((prev) => prev.includes(key) ? prev.filter((k) => k !== key) : [...prev, key])`. Render `<FeedFilterChips active={activeFilters} onToggle={...} />` trong header của section "Biên niên sử" (cạnh đếm mục). Export mới từ `features/chronicle/index.ts`: `FeedFilterChips`, `FEED_FILTERS`, `typesForFilters`.

- [ ] **Step 8: Test pass + check + commit**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads 2>&1 | tail -6'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check 2>&1 | tail -5'
git add frontend/src/features/chronicle "frontend/src/app/(observatory)/u/[id]/page.tsx"
git commit -m "feat(fe): filter su kien hero theo nhom type — chips + types param + loc live"
```

---

### Task 9: FE — lens Actors + psyche (`/u/[id]/actors`)

**Files:**
- Modify: `frontend/src/features/actors/api/queries.ts` (swap `@/lib/api` → `apiClient` + `takeData`, dùng `qk`, thêm `psyche`)
- Modify: `frontend/src/features/actors/hooks/index.ts` (bỏ `useCentrifugo` cũ, thêm `useActorPsyche`)
- Create: `frontend/src/features/actors/types.ts` (type `ActorPsyche`)
- Create: `frontend/src/features/actors/components/ActorGrid.tsx`
- Create: `frontend/src/features/actors/components/ActorPsychePanel.tsx`
- Create: `frontend/src/features/actors/index.ts`
- Create: `frontend/src/app/(observatory)/u/[id]/actors/page.tsx`
- Test: `frontend/src/features/actors/__tests__/useActorPsyche.test.tsx`, `frontend/src/features/actors/__tests__/ActorPsychePanel.test.tsx`

**Interfaces:**
- Consumes: `GET /worldos/universes/{id}/actors` (`ActorSummary[]` — `@/types/api`), `GET /worldos/observatory/actors/{actorId}/psyche` (Task 3; body 1-key `{data}` → interceptor apiClient tự bóc), `useObservedUniverse`, `WorkspaceLayout universeId`, `qk.actors/actorPsyche/supremeEntities`, `takeData`.
- Produces (public API `@/features/actors`): `useActors(universeId)`, `useActorPsyche(actorId)`, `useSupremeEntities(universeId)` (Task 10 dùng), `ActorGrid`, `ActorPsychePanel`, type `ActorPsyche`.
- LƯU Ý tương thích: giữ nguyên các export hiện có của `hooks/index.ts` (`useActorDetail`, `useActorEvents`, `useActorDecisions`, `useMindMeld`) vì trang dashboard cũ còn import subpath — chỉ đổi ruột (client + queryKey), không đổi tên/shape trả về. `useMindMeld` đổi `api` → `apiClient`.

- [ ] **Step 1: Viết test fail**

`useActorPsyche.test.tsx` (mock `@/shared/lib/apiClient` theo pattern `useChronicleFeed.test.tsx`):

```tsx
it('gọi endpoint psyche và trả về read-model', async () => {
  mockGet.mockResolvedValueOnce({ data: {
    actor: { id: 3, universe_id: 1, name: 'Aria', archetype: 'sage', is_alive: true, life_stage: 'adult' },
    emotions: { fear: 0.9, anger: 0.1, sadness: 0.2, joy: 0.3, stress: 0.8, trust: 0.4 },
    needs: { survive: 1.03, safety: 0.64, belong: 0.48, esteem: 0.26 },
    goals: [{ type: 'survive', priority: 1.03 }],
    trait_vector: [0.5],
    recent_decisions: [],
  } });
  const { result } = renderHook(() => useActorPsyche(3), { wrapper });
  await waitFor(() => expect(result.current.psyche?.actor.name).toBe('Aria'));
  expect(mockGet).toHaveBeenCalledWith('/worldos/observatory/actors/3/psyche');
});
it('actorId null → không gọi API', () => {
  renderHook(() => useActorPsyche(null), { wrapper });
  expect(mockGet).not.toHaveBeenCalled();
});
```
(Mock trả `data` ĐÃ bóc — giả lập interceptor apiClient bóc body 1-key `{data}`.)

`ActorPsychePanel.test.tsx`:

```tsx
it('render emotions dạng meter + goals + decisions', () => {
  render(<ActorPsychePanel psyche={psycheFixture} isLoading={false} />);
  expect(screen.getByText('Aria')).toBeTruthy();
  expect(screen.getByRole('meter', { name: /fear/i })).toBeTruthy();
  expect(screen.getByText('survive')).toBeTruthy();
});
it('loading state', () => {
  render(<ActorPsychePanel psyche={null} isLoading />);
  expect(screen.getByText(/Đang đọc tâm trí/i)).toBeTruthy();
});
```

- [ ] **Step 2: Chạy fail** — `npm test -- --pool=threads src/features/actors` → FAIL (module chưa có).

- [ ] **Step 3: `types.ts`**

```ts
export interface ActorPsyche {
  actor: { id: number; universe_id: number; name: string; archetype: string | null; is_alive: boolean; life_stage: string | null };
  emotions: Record<string, number>;
  needs: Record<string, number>;
  goals: { type: string; priority: number }[];
  trait_vector: number[];
  recent_decisions: {
    id: number; tick: number; action_type: string | null; reasoning: string | null;
    utility_score: number | null; confidence: number | null; impact: Record<string, unknown> | null;
  }[];
}
```

- [ ] **Step 4: Viết lại `api/queries.ts`** — mọi query dùng `apiClient` + `qk` + `takeData` (trừ psyche — body 1-key tự bóc):

```ts
import { queryOptions } from '@tanstack/react-query';
import { apiClient } from '@/shared/lib/apiClient';
import { takeData } from '@/shared/lib/unwrap';
import { qk } from '@/shared/config/queryKeys';
import type { ActorSummary, ActorDetail, ActorEvent, ActorDecision, SupremeEntity } from '@/types/api';
import type { ActorPsyche } from '../types';

export const actorQueries = {
  list: (universeId: number) =>
    queryOptions({
      queryKey: qk.actors(universeId),
      queryFn: async (): Promise<ActorSummary[]> =>
        takeData<ActorSummary[]>((await apiClient.get(`/worldos/universes/${universeId}/actors`)).data),
      staleTime: 8_000,
      refetchInterval: 10_000,
      enabled: universeId > 0,
    }),
  detail: (actorId: number) =>
    queryOptions({
      queryKey: ['actors', actorId] as const,
      queryFn: async (): Promise<ActorDetail> =>
        takeData<ActorDetail>((await apiClient.get(`/worldos/actors/${actorId}`)).data),
      enabled: actorId > 0,
    }),
  events: (actorId: number) =>
    queryOptions({
      queryKey: ['actors', actorId, 'events'] as const,
      queryFn: async (): Promise<ActorEvent[]> =>
        takeData<ActorEvent[]>((await apiClient.get(`/worldos/actors/${actorId}/events`)).data),
      enabled: actorId > 0,
    }),
  decisions: (actorId: number) =>
    queryOptions({
      queryKey: ['actors', actorId, 'decisions'] as const,
      queryFn: async (): Promise<ActorDecision[]> =>
        takeData<ActorDecision[]>((await apiClient.get(`/worldos/actors/${actorId}/decisions`)).data),
      enabled: actorId > 0,
    }),
  psyche: (actorId: number) =>
    queryOptions({
      queryKey: qk.actorPsyche(actorId),
      queryFn: async (): Promise<ActorPsyche> =>
        (await apiClient.get(`/worldos/observatory/actors/${actorId}/psyche`)).data as ActorPsyche,
      staleTime: 5_000,
      refetchInterval: 10_000,
      enabled: actorId > 0,
    }),
  supremeEntities: (universeId: number) =>
    queryOptions({
      queryKey: qk.supremeEntities(universeId),
      queryFn: async (): Promise<SupremeEntity[]> =>
        takeData<SupremeEntity[]>((await apiClient.get(`/worldos/universes/${universeId}/supreme-entities`)).data),
      staleTime: 15_000,
      refetchInterval: 20_000,
      enabled: universeId > 0,
    }),
};
```

- [ ] **Step 5: Viết lại `hooks/index.ts`** — giữ nguyên tên + shape trả về, bỏ import `@/hooks/useCentrifugo` và `@/lib/api` (adaptive interval → interval cố định đã khai trong queryOptions), thêm:

```ts
export function useActorPsyche(actorId: number | null) {
  const { data, error, isLoading } = useQuery({
    ...actorQueries.psyche(actorId ?? 0),
    enabled: !!actorId,
  });
  return { psyche: data ?? null, isLoading, isError: !!error };
}
```
`useMindMeld`: đổi `api.post` → `apiClient.post` (import từ `@/shared/lib/apiClient`).

- [ ] **Step 6: `components/ActorGrid.tsx`**

```tsx
'use client';
import type { ActorSummary } from '@/types/api';

interface Props { actors: ActorSummary[]; selectedId: number | null; onSelect: (id: number) => void }

export function ActorGrid({ actors, selectedId, onSelect }: Props) {
  if (actors.length === 0) {
    return (
      <p className="rounded-xl border border-dashed border-[var(--border-subtle)] p-6 text-sm text-[var(--color-text-muted)]">
        Vũ trụ chưa có actor nào — hãy chạy tick để sự sống xuất hiện.
      </p>
    );
  }
  return (
    <ul className="grid grid-cols-1 gap-2 sm:grid-cols-2" role="list">
      {actors.map((a) => (
        <li key={a.id}>
          <button
            type="button"
            onClick={() => onSelect(a.id)}
            aria-pressed={selectedId === a.id}
            className={`glass w-full rounded-lg border p-3 text-left transition-colors duration-200 ${
              selectedId === a.id
                ? 'border-[var(--color-primary)]'
                : 'border-[var(--border-subtle)] hover:border-[var(--border-muted)]'
            }`}
          >
            <div className="flex items-center justify-between gap-2">
              <span className={`truncate font-medium ${a.is_alive ? '' : 'line-through opacity-60'}`}>{a.name}</span>
              <span className="shrink-0 font-mono text-[11px] text-[var(--color-text-disabled)]">#{a.id}</span>
            </div>
            <p className="mt-1 font-mono text-[11px] uppercase tracking-wider text-[var(--color-text-muted)]">
              {a.archetype} · {a.life_stage}{a.is_alive ? '' : ' · đã mất'}
            </p>
          </button>
        </li>
      ))}
    </ul>
  );
}
```

- [ ] **Step 7: `components/ActorPsychePanel.tsx`**

```tsx
'use client';
import type { ActorPsyche } from '../types';

const EMOTION_LABELS: Record<string, string> = {
  fear: 'Sợ hãi', anger: 'Giận dữ', sadness: 'Buồn bã', joy: 'Hân hoan', stress: 'Căng thẳng', trust: 'Tin tưởng',
};

function EmotionMeter({ name, value }: { name: string; value: number }) {
  const pct = Math.round(Math.max(0, Math.min(1, value)) * 100);
  return (
    <div className="flex items-center gap-2">
      <span className="w-20 shrink-0 text-xs text-[var(--color-text-muted)]">{EMOTION_LABELS[name] ?? name}</span>
      <div
        role="meter" aria-label={name} aria-valuenow={pct} aria-valuemin={0} aria-valuemax={100}
        className="h-1.5 flex-1 overflow-hidden rounded-full bg-white/[0.06]"
      >
        <div className="h-full rounded-full bg-[var(--color-primary)]" style={{ width: `${pct}%` }} />
      </div>
      <span className="w-9 shrink-0 text-right font-mono text-[11px] tabular-nums text-[var(--color-text-disabled)]">
        {value.toFixed(2)}
      </span>
    </div>
  );
}

interface Props { psyche: ActorPsyche | null; isLoading: boolean }

export function ActorPsychePanel({ psyche, isLoading }: Props) {
  if (isLoading) return <p className="p-4 text-sm text-[var(--color-text-muted)]">Đang đọc tâm trí…</p>;
  if (!psyche) {
    return <p className="p-4 text-sm text-[var(--color-text-muted)]">Chọn một actor để soi chiếu tâm lý.</p>;
  }
  return (
    <div className="flex flex-col gap-4 p-1">
      <div>
        <h3 className="font-medium">{psyche.actor.name}</h3>
        <p className="font-mono text-[11px] uppercase tracking-wider text-[var(--color-text-muted)]">
          {psyche.actor.archetype ?? '—'} · {psyche.actor.life_stage ?? '—'}
        </p>
      </div>
      <section aria-label="Cảm xúc" className="flex flex-col gap-1.5">
        {Object.entries(psyche.emotions).map(([k, v]) => <EmotionMeter key={k} name={k} value={v} />)}
      </section>
      <section aria-label="Mục tiêu">
        <h4 className="mb-1 font-mono text-[11px] uppercase tracking-wider text-[var(--color-text-muted)]">Mục tiêu (Maslow)</h4>
        {psyche.goals.length === 0
          ? <p className="text-xs text-[var(--color-text-disabled)]">Tâm trí tĩnh lặng — chưa có nhu cầu vượt ngưỡng.</p>
          : (
            <ol className="flex flex-col gap-1">
              {psyche.goals.map((g) => (
                <li key={g.type} className="flex items-center justify-between rounded border border-[var(--border-subtle)] px-2 py-1 text-xs">
                  <span>{g.type}</span>
                  <span className="font-mono tabular-nums text-[var(--color-accent)]">{g.priority.toFixed(2)}</span>
                </li>
              ))}
            </ol>
          )}
      </section>
      <section aria-label="Quyết định gần nhất">
        <h4 className="mb-1 font-mono text-[11px] uppercase tracking-wider text-[var(--color-text-muted)]">Quyết định gần nhất</h4>
        {psyche.recent_decisions.length === 0
          ? <p className="text-xs text-[var(--color-text-disabled)]">Chưa có quyết định nào được ghi.</p>
          : (
            <ul className="flex flex-col gap-1">
              {psyche.recent_decisions.map((d) => (
                <li key={d.id} className="rounded border-l-2 border-[var(--color-info)] bg-white/[0.02] px-2 py-1 text-xs">
                  <span className="font-mono text-[var(--color-text-disabled)]">T{d.tick}</span>{' '}
                  <span className="font-medium">{d.action_type ?? '?'}</span>
                  {d.reasoning && <span className="text-[var(--color-text-muted)]"> — {d.reasoning}</span>}
                </li>
              ))}
            </ul>
          )}
      </section>
    </div>
  );
}
```

- [ ] **Step 8: `index.ts` + page**

`features/actors/index.ts`:

```ts
export { useActors, useActorDetail, useActorEvents, useActorDecisions, useActorPsyche, useSupremeEntities, useMindMeld } from './hooks';
export { ActorGrid } from './components/ActorGrid';
export { ActorPsychePanel } from './components/ActorPsychePanel';
export type { ActorPsyche } from './types';
```
(Nếu `./hooks` không resolve tới `hooks/index.ts` trong cấu hình alias hiện tại thì dùng `./hooks/index`.)

`app/(observatory)/u/[id]/actors/page.tsx`:

```tsx
'use client';
import { useMemo, useState } from 'react';
import { useParams } from 'next/navigation';
import { WorkspaceLayout, useObservedUniverse } from '@/features/universe-workspace';
import { ActorGrid, ActorPsychePanel, useActors, useActorPsyche } from '@/features/actors';
import { Panel } from '@/shared/ui/Panel';

export default function ActorsLensPage() {
  const params = useParams<{ id: string }>();
  const universeId = useMemo(() => {
    const n = Number(params?.id);
    return Number.isFinite(n) ? n : null;
  }, [params?.id]);
  useObservedUniverse(universeId);

  const [selectedActorId, setSelectedActorId] = useState<number | null>(null);
  const { actors, isLoading, isError } = useActors(universeId);
  const psyche = useActorPsyche(selectedActorId);

  return (
    <WorkspaceLayout universeId={universeId ?? undefined}>
      <div className="grid h-full min-h-0 grid-cols-1 gap-4 lg:grid-cols-3">
        <section className="custom-scrollbar min-h-0 overflow-y-auto lg:col-span-2" aria-label="Danh sách actor">
          <h2 className="mb-3 font-mono text-xs uppercase tracking-[0.3em] text-[var(--color-text-muted)]">Actors</h2>
          {isLoading && <p className="skeleton h-24 rounded-lg" aria-hidden="true" />}
          {isError && <p className="text-[var(--color-danger)]">Không tải được danh sách actor.</p>}
          {!isLoading && !isError && (
            <ActorGrid actors={actors} selectedId={selectedActorId} onSelect={setSelectedActorId} />
          )}
        </section>
        <aside className="min-h-0">
          <Panel title="Tâm lý (Psyche)">
            <ActorPsychePanel psyche={psyche.psyche} isLoading={psyche.isLoading} />
          </Panel>
        </aside>
      </div>
    </WorkspaceLayout>
  );
}
```

- [ ] **Step 9: Test pass + check + commit**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads 2>&1 | tail -6'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check 2>&1 | tail -5'
git add frontend/src/features/actors "frontend/src/app/(observatory)/u/[id]/actors"
git commit -m "feat(fe): lens actors + psyche panel — grid, emotions meter, goals, decisions"
```

---

### Task 10: FE — panel "Actor nổi bật" ở hero

**Files:**
- Create: `frontend/src/features/actors/components/NotableActorsPanel.tsx`
- Modify: `frontend/src/features/actors/index.ts`
- Modify: `frontend/src/app/(observatory)/u/[id]/page.tsx` (thêm panel vào aside)
- Test: `frontend/src/features/actors/__tests__/NotableActorsPanel.test.tsx`

**Interfaces:**
- Consumes: `useSupremeEntities(universeId)` (Task 9), `SupremeEntity` (`@/types/api`: `{id, name, entity_type, domain, power_level, status, actor_id}`), `routes.universeActors`.
- Produces: `NotableActorsPanel({ universeId })` — top 5 supreme entities theo `power_level` DESC + link sang lens actors; empty state khi chưa có.

- [ ] **Step 1: Viết test fail** — mock `../hooks` (module hook) để không đụng network:

```tsx
import { describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';

const mockUse = vi.fn();
vi.mock('../hooks', () => ({ useSupremeEntities: (id: number | null) => mockUse(id) }));
import { NotableActorsPanel } from '../components/NotableActorsPanel';

const entity = (id: number, name: string, power: number) => ({
  id, name, entity_type: 'ascended', domain: 'war', power_level: power, alignment: {}, status: 'active', actor_id: id,
});

describe('NotableActorsPanel', () => {
  it('render top 5 theo power_level DESC + link lens actors', () => {
    mockUse.mockReturnValue({ entities: [entity(1, 'Alpha', 0.2), entity(2, 'Omega', 0.9), ...[3, 4, 5, 6].map((i) => entity(i, `E${i}`, 0.5))], isLoading: false, isError: false });
    render(<NotableActorsPanel universeId={7} />);
    const items = screen.getAllByRole('listitem');
    expect(items).toHaveLength(5);
    expect(items[0].textContent).toContain('Omega');
    expect(screen.getByRole('link', { name: /Xem lens Actors/i }).getAttribute('href')).toBe('/u/7/actors');
  });
  it('empty state', () => {
    mockUse.mockReturnValue({ entities: [], isLoading: false, isError: false });
    render(<NotableActorsPanel universeId={7} />);
    expect(screen.getByText(/Chưa có thực thể nổi bật/i)).toBeTruthy();
  });
});
```

- [ ] **Step 2: Chạy fail**, rồi **Step 3: Implement**

```tsx
'use client';
import Link from 'next/link';
import { routes } from '@/shared/config/routes';
import { useSupremeEntities } from '../hooks';

const TOP_N = 5;

export function NotableActorsPanel({ universeId }: { universeId: number }) {
  const { entities, isLoading } = useSupremeEntities(universeId);
  const top = [...entities].sort((a, b) => b.power_level - a.power_level).slice(0, TOP_N);

  return (
    <div className="flex flex-col gap-2">
      {isLoading && <p className="skeleton h-16 rounded-lg" aria-hidden="true" />}
      {!isLoading && top.length === 0 && (
        <p className="text-xs text-[var(--color-text-disabled)]">Chưa có thực thể nổi bật — lịch sử còn đang chờ vĩ nhân.</p>
      )}
      {top.length > 0 && (
        <ul className="flex flex-col gap-1.5" role="list">
          {top.map((e) => (
            <li key={e.id} className="flex items-center justify-between gap-2 rounded border border-[var(--border-subtle)] px-2 py-1.5">
              <div className="min-w-0">
                <p className="truncate text-sm font-medium">{e.name}</p>
                <p className="font-mono text-[10px] uppercase tracking-wider text-[var(--color-text-muted)]">
                  {e.entity_type} · {e.domain}
                </p>
              </div>
              <span className="shrink-0 font-mono text-[11px] tabular-nums text-[var(--color-amber)]">
                ⚡{e.power_level.toFixed(2)}
              </span>
            </li>
          ))}
        </ul>
      )}
      <Link
        href={routes.universeActors(universeId)}
        className="mt-1 text-xs text-[var(--color-text-secondary)] underline-offset-2 hover:text-[var(--color-primary)] hover:underline"
      >
        Xem lens Actors →
      </Link>
    </div>
  );
}
```

Export thêm trong `features/actors/index.ts`: `export { NotableActorsPanel } from './components/NotableActorsPanel';`

- [ ] **Step 4: Wire hero** — trong aside của `u/[id]/page.tsx`, sau Panel "Nhịp đập vũ trụ":

```tsx
<Panel title="Actor nổi bật">
  {universeId != null && <NotableActorsPanel universeId={universeId} />}
</Panel>
```
(import `NotableActorsPanel` từ `@/features/actors`.)

- [ ] **Step 5: Test pass + check + commit**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads 2>&1 | tail -6'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check 2>&1 | tail -5'
git add frontend/src/features/actors "frontend/src/app/(observatory)/u/[id]/page.tsx"
git commit -m "feat(fe): panel actor noi bat o hero — top supreme entities + link lens"
```

---

### Task 11: FE — lens Văn minh (`/u/[id]/civilization`)

**Files:**
- Create: `frontend/src/features/civilization/types.ts`
- Create: `frontend/src/features/civilization/api/queries.ts`
- Create: `frontend/src/features/civilization/hooks/index.ts`
- Create: `frontend/src/features/civilization/components/CivilizationLens.tsx`
- Create: `frontend/src/features/civilization/index.ts`
- Create: `frontend/src/app/(observatory)/u/[id]/civilization/page.tsx`
- Test: `frontend/src/features/civilization/__tests__/useCivilization.test.tsx`, `frontend/src/features/civilization/__tests__/CivilizationLens.test.tsx`

**Interfaces:**
- Consumes: `GET /worldos/observatory/universes/{id}/civilization` (Task 4) và `.../world` (Task 5) — cả hai body 1-key `{data}` → interceptor apiClient tự bóc; `qk.civilization/worldState`.
- Produces (public API `@/features/civilization`): `useCivilization(universeId)`, `useWorldState(universeId)`, `CivilizationLens`, types `UniverseCivilization`, `UniverseWorldState`.

- [ ] **Step 1: `types.ts`** (khớp shape BE Task 4+5):

```ts
export interface UniverseCivilization {
  universe_id: number;
  status: string;
  current_tick: number;
  epoch: number | null;
  metrics: { entropy: number | null; stability_index: number | null; structural_coherence: number | null; fitness_score: number | null };
  complexity: { actor_count: number; living_actor_count: number; supreme_entity_count: number };
  snapshot: { tick: number; metrics: Record<string, unknown> } | null;
}

export interface UniverseWorldState {
  universe_id: number;
  world_id: number | null;
  epoch: { id: number; name: string; theme: string | null; description: string | null; start_tick: number | null; end_tick: number | null; status: string | null } | null;
  religions: { id: number; name: string; followers: number; spread_rate: number | null; doctrine: unknown }[];
  treaties: { id: number; treaty_type: string; source_civ_id: number; target_civ_id: number; started_at_tick: number; ends_at_tick: number | null }[];
  technologies: { id: number; name: string; code: string; adopters: number; avg_level: number }[];
}
```

- [ ] **Step 2: Viết test fail** — `useCivilization.test.tsx` (pattern mock apiClient):

```tsx
it('useCivilization gọi endpoint civilization', async () => {
  mockGet.mockResolvedValueOnce({ data: civFixture });   // fixture khớp UniverseCivilization
  const { result } = renderHook(() => useCivilization(1), { wrapper });
  await waitFor(() => expect(result.current.civilization?.universe_id).toBe(1));
  expect(mockGet).toHaveBeenCalledWith('/worldos/observatory/universes/1/civilization');
});
it('useWorldState gọi endpoint world', async () => {
  mockGet.mockResolvedValueOnce({ data: worldFixture });
  const { result } = renderHook(() => useWorldState(1), { wrapper });
  await waitFor(() => expect(result.current.world?.epoch?.name).toBe('Iron'));
  expect(mockGet).toHaveBeenCalledWith('/worldos/observatory/universes/1/world');
});
```

`CivilizationLens.test.tsx`: render với fixtures → thấy tile "Entropy" giá trị `0.42`, tên epoch `Iron`, religion `Solism`, technology `fire`; empty state khi religions/treaties/technologies rỗng (`Chưa ghi nhận`).

- [ ] **Step 3: Chạy fail**, rồi **Step 4: `api/queries.ts` + `hooks/index.ts`**

```ts
import { queryOptions } from '@tanstack/react-query';
import { apiClient } from '@/shared/lib/apiClient';
import { qk } from '@/shared/config/queryKeys';
import type { UniverseCivilization, UniverseWorldState } from '../types';

export const civilizationQueries = {
  civilization: (universeId: number) =>
    queryOptions({
      queryKey: qk.civilization(universeId),
      queryFn: async (): Promise<UniverseCivilization> =>
        (await apiClient.get(`/worldos/observatory/universes/${universeId}/civilization`)).data as UniverseCivilization,
      staleTime: 8_000,
      refetchInterval: 15_000,
      enabled: universeId > 0,
    }),
  world: (universeId: number) =>
    queryOptions({
      queryKey: qk.worldState(universeId),
      queryFn: async (): Promise<UniverseWorldState> =>
        (await apiClient.get(`/worldos/observatory/universes/${universeId}/world`)).data as UniverseWorldState,
      staleTime: 8_000,
      refetchInterval: 15_000,
      enabled: universeId > 0,
    }),
};
```

```ts
'use client';
import { useQuery } from '@tanstack/react-query';
import { civilizationQueries } from '../api/queries';

export function useCivilization(universeId: number | null) {
  const { data, error, isLoading } = useQuery({ ...civilizationQueries.civilization(universeId ?? 0), enabled: !!universeId });
  return { civilization: data ?? null, isLoading, isError: !!error };
}

export function useWorldState(universeId: number | null) {
  const { data, error, isLoading } = useQuery({ ...civilizationQueries.world(universeId ?? 0), enabled: !!universeId });
  return { world: data ?? null, isLoading, isError: !!error };
}
```

- [ ] **Step 5: `CivilizationLens.tsx`** — component thuần nhận data (dễ test), page lo data-fetching:

```tsx
'use client';
import type { UniverseCivilization, UniverseWorldState } from '../types';

function StatTile({ label, value, tone = 'primary' }: { label: string; value: number | null; tone?: 'primary' | 'danger' | 'emerald' | 'accent' }) {
  const color = { primary: 'var(--color-primary)', danger: 'var(--color-danger)', emerald: 'var(--color-emerald)', accent: 'var(--color-accent)' }[tone];
  return (
    <div className="glass rounded-xl border border-[var(--border-subtle)] p-4">
      <p className="font-mono text-[11px] uppercase tracking-wider text-[var(--color-text-muted)]">{label}</p>
      <p className="mt-1 font-mono text-2xl tabular-nums" style={{ color }}>{value == null ? '—' : value.toFixed(2)}</p>
    </div>
  );
}

function EmptyNote({ children }: { children: string }) {
  return <p className="text-xs text-[var(--color-text-disabled)]">{children}</p>;
}

interface Props { civilization: UniverseCivilization | null; world: UniverseWorldState | null }

export function CivilizationLens({ civilization, world }: Props) {
  return (
    <div className="flex flex-col gap-6">
      <section aria-label="Chỉ số văn minh" className="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <StatTile label="Entropy" value={civilization?.metrics.entropy ?? null} tone="danger" />
        <StatTile label="Stability" value={civilization?.metrics.stability_index ?? null} tone="primary" />
        <StatTile label="Coherence" value={civilization?.metrics.structural_coherence ?? null} tone="emerald" />
        <StatTile label="Fitness" value={civilization?.metrics.fitness_score ?? null} tone="accent" />
      </section>

      <section aria-label="Kỷ nguyên" className="glass rounded-xl border border-[var(--border-subtle)] p-4">
        <h3 className="mb-1 font-mono text-[11px] uppercase tracking-wider text-[var(--color-text-muted)]">Kỷ nguyên hiện tại</h3>
        {world?.epoch ? (
          <div>
            <p className="text-glow-cyan text-lg font-medium">{world.epoch.name}</p>
            {world.epoch.description && <p className="mt-1 text-sm text-[var(--color-text-muted)]">{world.epoch.description}</p>}
            <p className="mt-1 font-mono text-[11px] text-[var(--color-text-disabled)]">
              T{world.epoch.start_tick ?? '?'} → {world.epoch.end_tick != null ? `T${world.epoch.end_tick}` : 'nay'}
            </p>
          </div>
        ) : <EmptyNote>Chưa ghi nhận kỷ nguyên.</EmptyNote>}
        {civilization && (
          <p className="mt-3 border-t border-[var(--border-subtle)] pt-2 font-mono text-[11px] text-[var(--color-text-muted)]">
            {civilization.complexity.living_actor_count}/{civilization.complexity.actor_count} actor còn sống · {civilization.complexity.supreme_entity_count} thực thể tối cao
          </p>
        )}
      </section>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <section aria-label="Tôn giáo" className="glass rounded-xl border border-[var(--border-subtle)] p-4">
          <h3 className="mb-2 font-mono text-[11px] uppercase tracking-wider text-[var(--color-text-muted)]">Tôn giáo</h3>
          {(world?.religions ?? []).length === 0 ? <EmptyNote>Chưa ghi nhận tôn giáo.</EmptyNote> : (
            <ul className="flex flex-col gap-1.5" role="list">
              {world!.religions.map((r) => (
                <li key={r.id} className="flex items-center justify-between text-sm">
                  <span className="truncate">{r.name}</span>
                  <span className="font-mono text-[11px] tabular-nums text-[var(--color-text-muted)]">{r.followers} tín đồ</span>
                </li>
              ))}
            </ul>
          )}
        </section>
        <section aria-label="Hiệp ước" className="glass rounded-xl border border-[var(--border-subtle)] p-4">
          <h3 className="mb-2 font-mono text-[11px] uppercase tracking-wider text-[var(--color-text-muted)]">Hiệp ước hiệu lực</h3>
          {(world?.treaties ?? []).length === 0 ? <EmptyNote>Chưa ghi nhận hiệp ước.</EmptyNote> : (
            <ul className="flex flex-col gap-1.5" role="list">
              {world!.treaties.map((t) => (
                <li key={t.id} className="text-sm">
                  <span className="font-medium">{t.treaty_type}</span>{' '}
                  <span className="font-mono text-[11px] text-[var(--color-text-muted)]">civ {t.source_civ_id} ↔ civ {t.target_civ_id} · từ T{t.started_at_tick}</span>
                </li>
              ))}
            </ul>
          )}
        </section>
        <section aria-label="Công nghệ" className="glass rounded-xl border border-[var(--border-subtle)] p-4">
          <h3 className="mb-2 font-mono text-[11px] uppercase tracking-wider text-[var(--color-text-muted)]">Công nghệ</h3>
          {(world?.technologies ?? []).length === 0 ? <EmptyNote>Chưa ghi nhận công nghệ.</EmptyNote> : (
            <ul className="flex flex-col gap-1.5" role="list">
              {world!.technologies.map((t) => (
                <li key={t.id} className="flex items-center justify-between text-sm">
                  <span className="truncate">{t.name}</span>
                  <span className="font-mono text-[11px] tabular-nums text-[var(--color-text-muted)]">{t.adopters} actor · lv {t.avg_level.toFixed(2)}</span>
                </li>
              ))}
            </ul>
          )}
        </section>
      </div>
    </div>
  );
}
```

- [ ] **Step 6: `index.ts` + page**

```ts
export { useCivilization, useWorldState } from './hooks';
export { CivilizationLens } from './components/CivilizationLens';
export type { UniverseCivilization, UniverseWorldState } from './types';
```

`app/(observatory)/u/[id]/civilization/page.tsx` (khung giống lens actors — parse id, `useObservedUniverse(universeId)`, `WorkspaceLayout universeId`):

```tsx
'use client';
import { useMemo } from 'react';
import { useParams } from 'next/navigation';
import { WorkspaceLayout, useObservedUniverse } from '@/features/universe-workspace';
import { CivilizationLens, useCivilization, useWorldState } from '@/features/civilization';

export default function CivilizationLensPage() {
  const params = useParams<{ id: string }>();
  const universeId = useMemo(() => {
    const n = Number(params?.id);
    return Number.isFinite(n) ? n : null;
  }, [params?.id]);
  useObservedUniverse(universeId);

  const civ = useCivilization(universeId);
  const world = useWorldState(universeId);

  return (
    <WorkspaceLayout universeId={universeId ?? undefined}>
      {(civ.isError || world.isError) && (
        <p className="mb-3 text-sm text-[var(--color-amber)]" role="alert">Một phần dữ liệu văn minh không tải được.</p>
      )}
      <CivilizationLens civilization={civ.civilization} world={world.world} />
    </WorkspaceLayout>
  );
}
```

- [ ] **Step 7: Test pass + check + commit**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads 2>&1 | tail -6'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check 2>&1 | tail -5'
git add frontend/src/features/civilization "frontend/src/app/(observatory)/u/[id]/civilization"
git commit -m "feat(fe): lens van minh — metrics tiles + epoch + religions/treaties/technologies"
```

---

### Task 12: FE — lens Nhân quả (`/u/[id]/causality`, port ReactFlow)

**Files:**
- Modify: `frontend/src/features/causal-map/api/queries.ts` (swap sang `apiClient` + `takeData` + `qk`)
- Modify: `frontend/src/features/causal-map/hooks/index.ts` (giữ tên/shape, bỏ phụ thuộc legacy nếu có)
- Copy: `frontend/src/components/dashboard/causal-map/{TopologyGraph,ZoneNode,CausalLinkPanel,MapControls}.tsx` → `frontend/src/features/causal-map/components/` (KHÔNG xóa bản gốc — P4 thanh lý)
- Create: `frontend/src/features/causal-map/index.ts`
- Create: `frontend/src/app/(observatory)/u/[id]/causality/page.tsx`
- Test: `frontend/src/features/causal-map/__tests__/useTopology.test.tsx`

**Interfaces:**
- Consumes: `GET /apex/v10/universes/{id}/topology`, `GET /worldos/universes/{id}/causal-links?from_tick=&to_tick=` (manual refetch), `GET /worldos/universes/{id}/reality-state`; types `TopologyData/CausalLinkData/RealityState` (`@/types/api`); `@xyflow/react` (đã có trong deps).
- Produces (public API `@/features/causal-map`): `useTopology`, `useCausalLinks`, `useRealityState`, `CausalityLens` (component bọc `ReactFlowProvider` + graph + panel).

- [ ] **Step 1: Viết test fail** — `useTopology.test.tsx` (pattern mock apiClient): assert gọi `/apex/v10/universes/1/topology`, trả `topology` từ `takeData`; case `universeId null → không gọi`.

- [ ] **Step 2: Chạy fail**, rồi **Step 3: Rewrite `api/queries.ts`** — cùng công thức Task 9: `apiClient` + `takeData` + `qk.topology(id)` / `qk.causalLinks(id, fromTick, toTick)` / `qk.realityState(id)`; giữ nguyên `staleTime`/`refetchInterval`/`enabled` hiện có (causalLinks giữ `enabled: false` — manual refetch). `hooks/index.ts` giữ nguyên shape trả về hiện tại (`{topology, isLoading, isError, refetch}`…).

- [ ] **Step 4: Copy 4 component** vào `features/causal-map/components/` và sửa import nội bộ:
  - Import giữa 4 file → relative (`./ZoneNode`…).
  - Import types từ `@/types/api` giữ nguyên; import hooks (nếu có) → `../hooks`.
  - Nếu component cũ import từ `@/lib/*`/`@/contexts/*`: thay bằng props truyền từ page (data xuống, callback lên) — component trong feature KHÔNG import legacy. Đọc kỹ 4 file khi copy; phần logic ReactFlow giữ nguyên.

- [ ] **Step 5: Tạo `components/CausalityLens.tsx` + page** — lens component bọc:

```tsx
'use client';
import { ReactFlowProvider } from '@xyflow/react';
import { useState } from 'react';
import { TopologyGraph } from './TopologyGraph';
import { CausalLinkPanel } from './CausalLinkPanel';
import { MapControls } from './MapControls';
import { useTopology, useCausalLinks, useRealityState } from '../hooks';

export function CausalityLens({ universeId }: { universeId: number }) {
  const { topology, isLoading, isError, refetch } = useTopology(universeId);
  const [tickRange, setTickRange] = useState<{ from?: number; to?: number }>({});
  const causal = useCausalLinks(universeId, tickRange.from, tickRange.to);
  const reality = useRealityState(universeId);
  // Ghép đúng props mà TopologyGraph/CausalLinkPanel/MapControls yêu cầu (đọc file copy ở Step 4);
  // giữ layout full-height: h-[calc(100vh-10rem)]
  ...
}
```
(Phần `...` = wire props theo đúng chữ ký 4 component sau khi copy — chữ ký là của code cũ đã chạy được, chỉ đổi nguồn data từ Context sang hooks + props. Empty state khi `!topology`: "Chưa có topology — hãy chạy tick." Error state đỏ khi `isError`.)

Page `u/[id]/causality/page.tsx`: khung chuẩn lens (parse id + `useObservedUniverse` + `WorkspaceLayout universeId`) render `<CausalityLens universeId={universeId} />` khi `universeId != null`.

`features/causal-map/index.ts`:

```ts
export { useTopology, useCausalLinks, useRealityState } from './hooks';
export { CausalityLens } from './components/CausalityLens';
```

- [ ] **Step 6: Test pass + check + commit**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads 2>&1 | tail -6'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check 2>&1 | tail -5'
git add frontend/src/features/causal-map "frontend/src/app/(observatory)/u/[id]/causality"
git commit -m "feat(fe): lens nhan qua — port ReactFlow topology vao (observatory)"
```

---

### Task 13: FE — lens Wavefunction (`/u/[id]/wavefunction`, port Recharts)

**Files:**
- Modify: `frontend/src/features/wavefunction/api/queries.ts` (swap sang `apiClient` + `takeData` + `qk`)
- Modify: `frontend/src/features/wavefunction/hooks/index.ts` (bỏ `useCentrifugo` cũ — interval cố định trong queryOptions)
- Copy: `frontend/src/components/dashboard/wavefunction/{WavefunctionGauges,EntropyChart,FieldContributions,SingularityRisk,AutopoiesisStatus,AscensionFilters}.tsx` → `frontend/src/features/wavefunction/components/`
- Create: `frontend/src/features/wavefunction/components/WavefunctionLens.tsx`
- Create: `frontend/src/features/wavefunction/index.ts`
- Create: `frontend/src/app/(observatory)/u/[id]/wavefunction/page.tsx`
- Test: `frontend/src/features/wavefunction/__tests__/useWavefunction.test.tsx`

**Interfaces:**
- Consumes: apex endpoints (`/apex/wavefunction/{id}`, `/apex/informational-mass/{id}`, `/apex/v10/universes/{id}/consciousness|ascension-filters|delta`); types `WavefunctionData/InformationalMass/ConsciousnessField/AscensionFilterData/StateDelta`; Recharts (đã có).
- Produces (public API `@/features/wavefunction`): `useWavefunction`, `useInformationalMass`, `useConsciousness`, `useAscensionFilters`, `useStateDelta`, `WavefunctionLens`.

- [ ] **Step 1: Viết test fail** — `useWavefunction.test.tsx`: assert gọi `/apex/wavefunction/1`, `universeId null → không gọi` (pattern mock apiClient).

- [ ] **Step 2: Chạy fail**, rồi **Step 3: Rewrite queries + hooks** — cùng công thức Task 9/12: `apiClient` + `takeData` + `qk.wavefunction/informationalMass/consciousness/ascensionFilters/stateDelta`; `hooks/index.ts` bỏ import `@/hooks/useCentrifugo`, giữ nguyên tên hook + shape trả về (`{wavefunction, isLoading, isError}`…), interval giữ như queryOptions cũ (5s/10s/15s).

- [ ] **Step 4: Copy 6 component** vào `features/wavefunction/components/`, sửa import nội bộ như Task 12 Step 4 (relative + types `@/types/api`; cắt phụ thuộc legacy bằng props). Tạo `WavefunctionLens.tsx` compose grid:

```tsx
'use client';
import { useWavefunction, useInformationalMass, useConsciousness, useAscensionFilters } from '../hooks';
import { WavefunctionGauges } from './WavefunctionGauges';
import { EntropyChart } from './EntropyChart';
import { FieldContributions } from './FieldContributions';
import { SingularityRisk } from './SingularityRisk';
import { AutopoiesisStatus } from './AutopoiesisStatus';
import { AscensionFilters } from './AscensionFilters';

export function WavefunctionLens({ universeId }: { universeId: number }) {
  const { wavefunction, isLoading, isError } = useWavefunction(universeId);
  const mass = useInformationalMass(universeId);
  const consciousness = useConsciousness(universeId);
  const filters = useAscensionFilters(universeId);
  // Grid 2 cột: gauges + entropy chart hàng đầu; singularity/autopoiesis/ascension/field hàng sau.
  // Wire props theo đúng chữ ký component copy ở Step 4. Loading → skeleton; isError → thông báo đỏ;
  // thiếu data từng phần → panel đó hiển thị empty note (không chặn phần khác).
  ...
}
```

`index.ts`:

```ts
export { useWavefunction, useInformationalMass, useConsciousness, useAscensionFilters, useStateDelta } from './hooks';
export { WavefunctionLens } from './components/WavefunctionLens';
```

Page `u/[id]/wavefunction/page.tsx`: khung chuẩn lens, render `<WavefunctionLens universeId={universeId} />`.

- [ ] **Step 5: Test pass + check + commit**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads 2>&1 | tail -6'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check 2>&1 | tail -5'
git add frontend/src/features/wavefunction "frontend/src/app/(observatory)/u/[id]/wavefunction"
git commit -m "feat(fe): lens wavefunction — port gauges/charts apex vao (observatory)"
```

---

### Task 14: FE — Cinema port (`/chronicle/[chronicleId]` + click chronicle → cinema)

**Files:**
- Move (git mv): `frontend/src/lib/vaf/` → `frontend/src/features/cinema/lib/vaf/` (kèm `__tests__`); `frontend/src/components/vaf/` → `frontend/src/features/cinema/components/`; `frontend/src/hooks/useVAFPlayer.ts` → `frontend/src/features/cinema/hooks/useVAFPlayer.ts`
- Create: `frontend/src/features/cinema/api/useChronicleDetail.ts`
- Create: `frontend/src/features/cinema/index.ts`
- Create: `frontend/src/app/(observatory)/chronicle/[chronicleId]/page.tsx`
- Modify: `frontend/src/app/narrative-cinema/[chronicleId]/page.tsx` (→ redirect)
- Modify: `frontend/src/types/api.ts` (import `AnimationScript` theo path mới)
- Modify: `frontend/vitest.config.ts` (coverage include path mới)
- Modify: `frontend/src/features/chronicle/components/ChronicleEntry.tsx` (chronicle → link cinema)
- Test: có sẵn (`parser/scheduler/timeline` tests move theo); bổ sung `frontend/src/features/chronicle/__tests__/ChronicleEntry.test.tsx` (case link cinema)

**Interfaces:**
- Consumes: `GET /worldos/chronicles/{id}` (`Chronicle` — `@/types/api`, có `animation_script`); feed item chronicle có `payload.chronicle_id` + `payload.has_animation` (P1); `routes.chronicle(id)` (Task 6).
- Produces (public API `@/features/cinema`): `CinematicPlayer`, `VAFErrorBoundary`, `parseAnimationScript`, `useVAFPlayer`, `useChronicleDetail`, types `AnimationScript`, `VAFScene`.

- [ ] **Step 1: Move cây VAF**

```bash
mkdir -p frontend/src/features/cinema/lib frontend/src/features/cinema/hooks
git mv frontend/src/lib/vaf frontend/src/features/cinema/lib/vaf
git mv frontend/src/components/vaf frontend/src/features/cinema/components
git mv frontend/src/hooks/useVAFPlayer.ts frontend/src/features/cinema/hooks/useVAFPlayer.ts
```

- [ ] **Step 2: Sửa import sau move**

```bash
grep -rn "@/lib/vaf\|@/components/vaf\|@/hooks/useVAFPlayer" frontend/src --include='*.ts' --include='*.tsx'
```
Sửa toàn bộ kết quả:
  - Trong `features/cinema/**`: dùng đường dẫn tương đối nội bộ feature (`../lib/vaf/types`, `./PlayerControls`, `../hooks/useVAFPlayer`…).
  - `src/types/api.ts`: `import type { AnimationScript } from '@/features/cinema/lib/vaf/types';` (chỉ type-import — hợp lệ layering vì `@/types` là legacy-shared, guardrail không phủ; ghi chú P4 sẽ dời type này).
  - Trang cũ `src/app/narrative-cinema/[chronicleId]/page.tsx`: thay TOÀN BỘ nội dung bằng redirect (Step 5) — hết import VAF.
  - Test cũ nào ngoài cây vaf tham chiếu `@/lib/vaf` (grep cả `src/lib/__tests__`) → cập nhật path.

`vitest.config.ts`: `coverage.include` → `['src/features/cinema/lib/vaf/**/*.ts']`.

- [ ] **Step 3: `api/useChronicleDetail.ts`** (bản observatory — apiClient):

```ts
'use client';
import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@/shared/lib/apiClient';
import { qk } from '@/shared/config/queryKeys';
import { takeData } from '@/shared/lib/unwrap';
import type { Chronicle } from '@/types/api';

export function useChronicleDetail(chronicleId: number | null) {
  const { data, error, isLoading } = useQuery({
    queryKey: qk.chronicle(chronicleId ?? 0),
    queryFn: async (): Promise<Chronicle> =>
      takeData<Chronicle>((await apiClient.get(`/worldos/chronicles/${chronicleId}`)).data),
    enabled: !!chronicleId,
  });
  return { chronicle: data ?? null, isLoading, isError: !!error };
}
```

- [ ] **Step 4: `index.ts` + trang cinema mới** — `features/cinema/index.ts`:

```ts
export { default as CinematicPlayer } from './components/CinematicPlayer';
export { default as VAFErrorBoundary } from './components/VAFErrorBoundary';
export { parseAnimationScript } from './lib/vaf/parser';
export { useVAFPlayer } from './hooks/useVAFPlayer';
export { useChronicleDetail } from './api/useChronicleDetail';
export type { AnimationScript, VAFScene } from './lib/vaf/types';
```
(Nếu `CinematicPlayer`/`VAFErrorBoundary` là named export thì bỏ `default as` — xem file khi move.)

`app/(observatory)/chronicle/[chronicleId]/page.tsx`: port NGUYÊN VẸN nội dung trang `narrative-cinema/[chronicleId]/page.tsx` cũ (loading spinner, error state + retry `retryCount`, fallback text khi không có animation, `CinematicPlayer` full-screen với `key={chronicleId}-${retryCount}`), chỉ đổi imports:

```tsx
import { useChronicleDetail, parseAnimationScript, CinematicPlayer, VAFErrorBoundary } from '@/features/cinema';
```
và nút Back giữ `router.back()`.

- [ ] **Step 5: Trang cũ thành redirect** — thay toàn bộ `src/app/narrative-cinema/[chronicleId]/page.tsx`:

```tsx
import { redirect } from 'next/navigation';

/** Route legacy — cinema đã dời về (observatory)/chronicle. Xóa hẳn ở P4. */
export default async function LegacyCinemaRedirect({ params }: { params: Promise<{ chronicleId: string }> }) {
  const { chronicleId } = await params;
  redirect(`/chronicle/${chronicleId}`);
}
```

- [ ] **Step 6: ChronicleEntry link cinema** — test fail trước (bổ sung `ChronicleEntry.test.tsx`):

```tsx
it('chronicle entry có link mở cinema theo payload.chronicle_id', () => {
  render(<ChronicleEntry item={{ ...base, type: 'chronicle', kind: 'chronicle', payload: { chronicle_id: 42, content: 'Sử thi', has_animation: true } }} />);
  const link = screen.getByRole('link', { name: /Xem cinema/i });
  expect(link.getAttribute('href')).toBe('/chronicle/42');
});
it('chronicle entry không có chronicle_id → không render link', () => {
  render(<ChronicleEntry item={{ ...base, type: 'chronicle', kind: 'chronicle', payload: { content: 'Sử thi' } }} />);
  expect(screen.queryByRole('link')).toBeNull();
});
```

Implement trong `visualFor` case `'chronicle'` — body thành:

```tsx
body: (
  <div>
    <p className="leading-relaxed text-[var(--color-text-primary)]">
      {(p.content as string) ?? '(chưa có nội dung tường thuật)'}
    </p>
    {typeof p.chronicle_id === 'number' && (
      <Link
        href={routes.chronicle(p.chronicle_id)}
        className="mt-1.5 inline-flex items-center gap-1 text-xs text-[var(--color-accent)] underline-offset-2 hover:underline"
      >
        ▶ Xem cinema{p.has_animation === true ? '' : ' (bản chữ)'}
      </Link>
    )}
  </div>
),
```
(import `Link` từ `next/link`, `routes` từ `@/shared/config/routes`.)

- [ ] **Step 7: Test pass + check + commit**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads 2>&1 | tail -6'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check 2>&1 | tail -5'
```
Expected: 55 test VAF vẫn pass ở path mới; không import nào còn trỏ `@/lib/vaf`/`@/components/vaf`/`@/hooks/useVAFPlayer` (grep lại = 0 kết quả ngoài features/cinema).

```bash
git add -A frontend/src frontend/vitest.config.ts
git commit -m "feat(fe): cinema port — features/cinema (VAF), route /chronicle/[id], click chronicle mo cinema"
```

---

### Task 15: FE — Constellation landing (`/multiverse`)

**Files:**
- Modify: `frontend/src/features/multiverse/api/queries.ts` (swap sang `apiClient` + `takeData` + `qk`)
- Modify: `frontend/src/features/multiverse/hooks/index.ts` (bỏ `useCentrifugo` cũ)
- Create: `frontend/src/features/multiverse/components/ConstellationView.tsx`
- Create: `frontend/src/features/multiverse/index.ts`
- Modify: `frontend/src/app/(observatory)/multiverse/page.tsx` (constellation trên, grid dưới)
- Test: `frontend/src/features/multiverse/__tests__/ConstellationView.test.tsx`

**Interfaces:**
- Consumes: `GET /apex/multiverse/bloom` (`MultiverseBloom`: worlds → universes, mỗi universe `{id: string, label, status, sci, saliency}`), `GET /apex/multiverse/resonance` (`MultiverseResonance`: `global_narrative_entropy`, `resonance_pollen[]`); `routes.universe`.
- Produces (public API `@/features/multiverse`): `useMultiverseBloom`, `useMultiverseResonance`, `ConstellationView({ bloom, resonance })` — SVG 2D: mỗi world một cụm, universe = ngôi sao (bán kính/glow theo `saliency`, màu theo `status`), click sao → hero. Degraded: bloom lỗi/rỗng → trả `null` (landing chỉ còn grid).

- [ ] **Step 1: Viết test fail** — `ConstellationView.test.tsx`:

```tsx
import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { ConstellationView } from '../components/ConstellationView';
import type { MultiverseBloom, MultiverseResonance } from '@/types/api';

const bloom: MultiverseBloom = {
  id: 'm1', label: 'Multiverse', sub: '',
  worlds: [{
    id: 'w1', label: 'Terra', genre: 'fantasy', sci: 0.5, status: 'active',
    universes: [
      { id: '7', label: 'U7', sub: '', status: 'active', sci: 0.6, parentUniverseId: null, saliency: 0.9 },
      { id: '8', label: 'U8', sub: '', status: 'halted', sci: 0.2, parentUniverseId: '7', saliency: 0.3 },
    ],
  }],
};
const resonance: MultiverseResonance = { resonance_pollen: [], global_narrative_entropy: 0.42 };

describe('ConstellationView', () => {
  it('render mỗi universe một link-sao trỏ về hero', () => {
    render(<ConstellationView bloom={bloom} resonance={resonance} />);
    expect(screen.getByRole('link', { name: /U7/ }).getAttribute('href')).toBe('/u/7');
    expect(screen.getByRole('link', { name: /U8/ }).getAttribute('href')).toBe('/u/8');
    expect(screen.getByText(/0.42/)).toBeTruthy(); // global narrative entropy
  });
  it('bloom rỗng → null', () => {
    const { container } = render(<ConstellationView bloom={{ ...bloom, worlds: [] }} resonance={null} />);
    expect(container.firstChild).toBeNull();
  });
});
```

- [ ] **Step 2: Chạy fail**, rồi **Step 3: Rewrite queries + hooks** — công thức Task 9: `apiClient` + `takeData` + `qk.bloom()/qk.resonance()`, interval cố định (15s/10s), hooks giữ shape `{bloom, isLoading, isError}` / `{resonance, ...}`.

- [ ] **Step 4: Implement `ConstellationView.tsx`** — SVG thuần, layout deterministic (KHÔNG `Math.random` — vị trí từ index + saliency):

```tsx
'use client';
import Link from 'next/link';
import { routes } from '@/shared/config/routes';
import type { MultiverseBloom, MultiverseResonance, MultiverseUniverse } from '@/types/api';

const W = 900;
const H = 360;
const STATUS_COLOR: Record<string, string> = {
  active: 'var(--color-primary)', paused: 'var(--color-amber)', halted: 'var(--color-text-disabled)',
};

/** Vị trí sao deterministic: world = cột cụm, universe rải quanh tâm cụm theo index (vòng xoắn vàng). */
function starPosition(worldIdx: number, worldCount: number, uniIdx: number, uniCount: number) {
  const cx = ((worldIdx + 0.5) / worldCount) * W;
  const cy = H / 2;
  const angle = uniIdx * 2.39996; // golden angle
  const radius = 26 + 34 * Math.sqrt(uniCount > 1 ? uniIdx / (uniCount - 1) : 0);
  return { x: cx + radius * Math.cos(angle), y: cy + radius * Math.sin(angle) * 0.75 };
}

function Star({ u, x, y }: { u: MultiverseUniverse; x: number; y: number }) {
  const r = 4 + 8 * Math.max(0, Math.min(1, u.saliency));
  const color = STATUS_COLOR[u.status] ?? 'var(--color-text-muted)';
  const id = Number(u.id);
  return (
    <Link href={routes.universe(id)} aria-label={`${u.label} — vào Living Chronicle`}>
      <g className="cursor-pointer transition-opacity hover:opacity-100" opacity={0.9}>
        <circle cx={x} cy={y} r={r * 2.2} fill={color} opacity={0.12} />
        <circle cx={x} cy={y} r={r} fill={color} />
        <text x={x} y={y + r + 12} textAnchor="middle" className="fill-[var(--color-text-muted)] font-mono text-[10px]">
          {u.label}
        </text>
      </g>
    </Link>
  );
}

interface Props { bloom: MultiverseBloom | null; resonance: MultiverseResonance | null }

export function ConstellationView({ bloom, resonance }: Props) {
  const worlds = bloom?.worlds ?? [];
  if (worlds.length === 0) return null;

  return (
    <figure className="glass mb-8 rounded-2xl border border-[var(--border-subtle)] p-4">
      <figcaption className="mb-2 flex items-center justify-between">
        <span className="font-mono text-xs uppercase tracking-[0.3em] text-[var(--color-text-muted)]">
          Chòm sao đa vũ trụ
        </span>
        {resonance && (
          <span className="font-mono text-[11px] tabular-nums text-[var(--color-accent)]">
            Narrative entropy {resonance.global_narrative_entropy.toFixed(2)}
          </span>
        )}
      </figcaption>
      <svg viewBox={`0 0 ${W} ${H}`} className="h-auto w-full" role="img" aria-label="Bản đồ chòm sao các vũ trụ">
        {worlds.map((w, wi) => (
          <g key={w.id}>
            <text
              x={((wi + 0.5) / worlds.length) * W} y={20} textAnchor="middle"
              className="fill-[var(--color-text-disabled)] font-mono text-[10px] uppercase tracking-widest"
            >
              {w.label}
            </text>
            {w.universes.map((u, ui) => {
              const pos = starPosition(wi, worlds.length, ui, w.universes.length);
              return <Star key={u.id} u={u} x={pos.x} y={pos.y} />;
            })}
          </g>
        ))}
      </svg>
      {resonance && resonance.resonance_pollen.length > 0 && (
        <ul className="mt-2 flex flex-wrap gap-2" role="list" aria-label="Cộng hưởng gần đây">
          {resonance.resonance_pollen.slice(0, 4).map((p) => (
            <li key={p.id} className="rounded-full border border-[var(--border-subtle)] px-3 py-1 text-[11px] text-[var(--color-text-muted)]">
              {p.headline} <span className="font-mono text-[var(--color-accent)]">×{p.intensity.toFixed(1)}</span>
            </li>
          ))}
        </ul>
      )}
    </figure>
  );
}
```
LƯU Ý jsdom: `<Link>` bọc `<g>` trong SVG render ra `<a>` — test dùng `getByRole('link')` hoạt động; nếu Next `Link` gây lỗi hydration trong SVG khi chạy thật, chuyển sang `<a href>` thường + `onClick` router (ghi chú cho implementer thử `Link` trước).

- [ ] **Step 5: `index.ts` + wire landing**

```ts
export { useMultiverseBloom, useMultiverseResonance } from './hooks';
export { ConstellationView } from './components/ConstellationView';
```

`app/(observatory)/multiverse/page.tsx`: thêm phía trên grid hiện tại:

```tsx
const { bloom } = useMultiverseBloom();
const { resonance } = useMultiverseResonance();
...
<ConstellationView bloom={bloom ?? null} resonance={resonance ?? null} />
```
(import từ `@/features/multiverse`; grid thẻ + empty/loading state hiện có GIỮ NGUYÊN bên dưới làm danh sách chi tiết + fallback khi bloom lỗi.)

- [ ] **Step 6: Test pass + check + commit**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads 2>&1 | tail -6'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check 2>&1 | tail -5'
git add frontend/src/features/multiverse "frontend/src/app/(observatory)/multiverse/page.tsx"
git commit -m "feat(fe): constellation landing — SVG bloom/resonance, sao theo saliency/status"
```

---

### Task 16: Verify gate toàn plan + cập nhật docs

**Files:**
- Modify: `.dev_status.md` (session mới P3)

**Interfaces:**
- Consumes: toàn bộ Task 1-15 đã hoàn thành.
- Produces: bằng chứng test thật (số liệu so với baseline Task 1); `.dev_status.md` cập nhật; danh sách tồn cho P4.

- [ ] **Step 1: FE full**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads 2>&1 | tail -8'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check 2>&1 | tail -8'
```
Expected: toàn bộ pass (số file/test ≥ baseline + test mới của Task 6-15); check 0 error (2 warning pre-existing được phép). Smoke lại dev server như Task 1 Step 3 cho các route mới (`/u/1/actors`, `/u/1/civilization`, `/u/1/causality`, `/u/1/wavefunction`, `/chronicle/1`) nếu dev server chạy được.

- [ ] **Step 2: BE full**

```bash
incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --testsuite=Unit 2>&1 | tail -5'
incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --filter="Observatory|WorldEventBroadcastContractTest" 2>&1 | tail -8'
```
Expected: Unit ≥ baseline (171 pass / 92 skip, 0 fail mới — `IntelligenceExplosionTest` flake được phép); nhóm Observatory + contract PASS toàn bộ.

- [ ] **Step 3: Ghi nhận hạn chế build** — `npm run build` KHÔNG verify được trong worldos-dev (AppArmor chặn signal worker). Ghi rõ vào report + `.dev_status.md`: cần build ở CI/Docker trước khi deploy.

- [ ] **Step 4: Cập nhật `.dev_status.md`** — thêm section "Session N: Observatory P3" theo format các session trước: task hoàn thành, số liệu verify THẬT (copy output), ghi chú tồn cho P4 (bổ sung: bản copy component dashboard cũ ở `components/dashboard/{causal-map,wavefunction}` chờ xóa; `src/types/api.ts` import type từ `features/cinema` chờ dời; trang redirect `narrative-cinema` chờ xóa; cân nhắc envelope type riêng cho status nếu muốn status đổi ngay cả khi không có pulse).

- [ ] **Step 5: Commit cuối**

```bash
git add .dev_status.md
git commit -m "chore: hoan thanh Observatory Plan 3 — lenses + 3 endpoint BE + cinema"
```

Sau đó dùng skill `superpowers:finishing-a-development-branch` (tiền lệ dự án: merge local `feature/observatory-p3` vào `main` bằng `--no-ff`).

---

## Ghi chú thực thi

- **Branch:** tạo `feature/observatory-p3` từ `main` trước Task 1 (dùng skill `superpowers:using-git-worktrees` nếu cần isolation).
- **Thứ tự phụ thuộc:** Task 2-5 (BE) độc lập với nhau, nhưng Task 9 cần Task 3+6, Task 10 cần Task 9, Task 11 cần Task 4+5+6, Task 12/13 cần Task 6, Task 14 cần Task 6 (routes), Task 15 cần Task 6. Task 7-8 cần Task 6.
- **Reviewer mỗi task** đối chiếu: guardrail (`ModuleBoundaryTest` BE, `npm run lint` FE), số test không giảm, KHÔNG placeholder/TODO trong code mới.
- Task UI (9, 10, 11, 12, 13, 15): implementer đọc skill `frontend-design` + `dataviz` trước khi viết/port component.

