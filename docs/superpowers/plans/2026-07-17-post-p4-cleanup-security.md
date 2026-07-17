# Post-P4 Cleanup + Security Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Trả nợ hậu-P4: vá lỗ hổng `test-weave`, xóa route/dead-code 0-consumer hai phía, wire save thật cho `/ops/settings`, chuẩn hóa query key, batch test/polish nhỏ.

**Architecture:** 9 task nhỏ độc lập trên branch `chore/post-p4-cleanup` (từ `main` 75006ae). BE: sửa/xóa route + test khóa hành vi + xóa cây dead code (grep gate trước mọi lần xóa — tiền lệ `chronicles/raw` P4). FE: nối handleSave vào mutation có sẵn + hydrate ngược, xóa dead exports, chuẩn hóa key. Task 1-4 (BE) và 5-8 (FE) độc lập nhau; Task 9 gate cuối.

**Tech Stack:** Laravel 13 + PHPUnit (sqlite `:memory:`), Pint; Next.js 16 + TanStack Query v5 + Vitest (`--pool=threads`).

**Spec:** `docs/superpowers/specs/2026-07-17-post-p4-cleanup-security-design.md` — kèm HIỆU CHỈNH quan trọng bên dưới.

## Hiệu chỉnh spec (khảo sát live 2026-07-17, curl thật trên artisan serve)

- **S1 SAI MỘT NỬA:** `POST universes/{id}/generate-chronicle` (routes/api.php:96) nằm TRONG group `auth:sanctum` (mở ở dòng 84) — curl không token trả **401**. Comment dòng 95 "bỏ qua auth tạm thời để test" là comment CŨ gây hiểu nhầm (final review P4 + `.dev_status.md` đều ghi sai theo nó). Việc cần làm: xóa comment sai + test khóa 401 (regression). KHÔNG cần đổi middleware.
- **Lỗ hổng thật là `test-weave`:** `POST test-weave/{id}` (dòng 20-22, group public `middleware('api')`, chỉ `throttle:10,1`) trả **200 không cần token** và gọi cùng method `TimelineController::generateChronicle` (kích hoạt LLM). Đã có sẵn TODO bảo mật trong comment. 0 consumer FE/Python → XÓA (S2).
- `worlds/{id}/pulse` (dòng 103) cũng ĐÃ trong group sanctum — curl trả 401. Xóa vì 0 consumer (dead surface), không phải vì bảo mật.

## Global Constraints

- **KHÔNG chạy npm/composer/php trên host.** Mọi lệnh qua Incus `worldos-dev` (mount `/work`):
  - FE test: `incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads 2>&1 | tail -4'` (BẮT BUỘC `--pool=threads`); check: `npm run check`.
  - BE test: `incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --filter=<Tên> 2>&1 | tail -5'`; Unit suite: `--testsuite=Unit`.
  - Pint **CHỈ trên file đã sửa**: `vendor/bin/pint <đường/dẫn/file...>` — TUYỆT ĐỐI không chạy trên thư mục module (bài học P4 Task 15: 502 file bị reformat).
- **Baseline:** FE 40 file / 150 test, check 0 error / 0 warning. BE Unit 170-171 pass / 92 skip (fail duy nhất được phép: `IntelligenceExplosionTest` flake stochastic). Feature suite có ~91 fail pre-existing — task nào chạm Feature test chỉ cần KHÔNG TỆ HƠN trước đó (đo trước/sau).
- **Layering FE:** app → features → shared; feature import qua `@/features/<name>` (index.ts); guardrail ESLint cấm `@/lib|@/hooks|@/contexts|@/components|@/types`.
- **BE:** PSR-12, không sửa migration; `ModuleBoundaryTest` ratchet chỉ được giảm; MỌI lần xóa đều chạy grep gate trước, grep lộ consumer → GIỮ phần đó + ghi report (không tự quyết xóa).
- TDD từng task (task xóa thuần dùng verification command thay test-first); mỗi task kết thúc gate xanh + commit.

---

### Task 1: BE — xóa route `test-weave` + sửa comment sai + test khóa auth

**Files:**
- Modify: `backend/app/Modules/WorldOS/routes/api.php` (dòng 18-22 xóa; dòng 95 sửa comment)
- Create: `backend/tests/Feature/WorldosRouteAuthTest.php`
- Modify: `docs/superpowers/specs/2026-07-17-post-p4-cleanup-security-design.md` (thêm mục "Hiệu chỉnh" — copy nguyên khối "Hiệu chỉnh spec" của plan này)

**Interfaces:**
- Consumes: route file hiện tại (xem dòng chính xác ở trên).
- Produces: `tests/Feature/WorldosRouteAuthTest.php` — Task 2 sẽ THÊM case vào file này.

- [ ] **Step 1: Grep gate test-weave** — `grep -rn "test-weave" backend frontend/src narrative-loom sim --include='*.php' --include='*.ts' --include='*.tsx' --include='*.py' | grep -v routes/api.php | grep -v .dev_status` → kỳ vọng 0 (đã khảo sát sơ bộ). Có hit → GIỮ route, đổi sang `auth:sanctum`, ghi report.

- [ ] **Step 2: Viết test fail** — `backend/tests/Feature/WorldosRouteAuthTest.php` (401/404 xảy ra ở middleware/router, không cần DB — KHÔNG dùng RefreshDatabase):

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class WorldosRouteAuthTest extends TestCase
{
    public function test_generate_chronicle_requires_auth(): void
    {
        $this->postJson('/api/worldos/universes/1/generate-chronicle')
            ->assertStatus(401);
    }

    public function test_test_weave_route_is_removed(): void
    {
        $this->postJson('/api/worldos/test-weave/1')
            ->assertStatus(404);
    }
}
```

- [ ] **Step 3: Chạy fail** — `php artisan test --filter=WorldosRouteAuthTest` → case 1 PASS sẵn (đã protected — chính là regression lock), case 2 FAIL (route còn → 200/429).

- [ ] **Step 4: Xóa route + sửa comment** — trong `routes/api.php`: xóa nguyên khối dòng 18-22 (2 dòng comment "Test route..."/"TODO(bảo mật)..." + 3 dòng `Route::post('test-weave/{id}'...)`). Dòng 95: thay comment `// Test route: generate-chronicle (bỏ qua auth tạm thời để test)` bằng `// Sinh chronicle theo yêu cầu — mutation, cần auth:sanctum như mọi POST khác trong group này.`

- [ ] **Step 5: Test pass + suite + pint + spec + commit**

```bash
incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --filter=WorldosRouteAuthTest 2>&1 | tail -4'   # 2 pass
incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --testsuite=Unit 2>&1 | tail -3'
incus exec worldos-dev -- sh -c 'cd /work/backend && vendor/bin/pint app/Modules/WorldOS/routes/api.php tests/Feature/WorldosRouteAuthTest.php 2>&1 | tail -2'
git add backend docs/superpowers/specs/2026-07-17-post-p4-cleanup-security-design.md
git commit -m "fix(be): xoa route test-weave khong auth (trigger LLM) + khoa 401 generate-chronicle bang test"
```

---

### Task 2: BE — xóa 3 route 0-consumer còn lại (`history-timeline`, `analytics/ticks`, `worlds/pulse`)

**Files:**
- Modify: `backend/app/Modules/WorldOS/routes/api.php` (dòng 45, 57, 103 — số dòng TRƯỚC Task 1, tự xê dịch)
- Có thể Delete: `backend/app/Modules/WorldOS/Http/Controllers/Api/AnalyticsController.php` + Action `GetTickAnalyticsAction` (CHỈ nếu grep gate xác nhận 0 caller khác)
- Có thể Modify: `TimelineController.php` (xóa method `history`), `UniverseController.php` (xóa method `pulse`) — cùng điều kiện gate
- Modify: `backend/tests/Feature/WorldosRouteAuthTest.php` (thêm 3 case 404)

**Interfaces:**
- Consumes: `WorldosRouteAuthTest` từ Task 1.
- Produces: route surface gọn; report ghi rõ mỗi nhóm xóa-tới-tầng-nào.

- [ ] **Step 1: Grep gate từng nhóm** (chạy cả 3, dán output vào report):

```bash
grep -rn "history-timeline\|->history(" backend/app backend/tests frontend/src --include='*.php' --include='*.ts' --include='*.tsx' | grep -v routes/api.php
grep -rn "GetTickAnalyticsAction\|getTickAnalytics\|analytics/ticks" backend/app backend/tests frontend/src | grep -v routes/api.php | grep -v "AnalyticsController.php"
grep -rn "worlds/pulse\|'pulse'\|->pulse(" backend/app backend/tests frontend/src --include='*.php' --include='*.ts' --include='*.tsx' | grep -v routes/api.php
```
Quy tắc: route luôn xóa được (0 consumer HTTP đã khảo sát); method/controller/action chỉ xóa khi grep nhóm tương ứng 0 caller còn lại (lưu ý `->history(` và `pulse` dễ trùng tên chung chung — đọc từng hit, phân biệt method của controller này với method cùng tên nơi khác).

- [ ] **Step 2: Viết 3 test fail** — thêm vào `WorldosRouteAuthTest`:

```php
    public function test_history_timeline_route_is_removed(): void
    {
        $this->getJson('/api/worldos/universes/1/history-timeline')->assertStatus(404);
    }

    public function test_analytics_ticks_route_is_removed(): void
    {
        $this->getJson('/api/worldos/analytics/ticks')->assertStatus(404);
    }

    public function test_worlds_pulse_route_is_removed(): void
    {
        $this->postJson('/api/worldos/worlds/1/pulse')->assertStatus(404);
    }
```

- [ ] **Step 3: Chạy fail** (3 case mới fail vì route còn) → **Step 4: Xóa** 3 dòng route + method/controller/action theo kết quả gate Step 1. Nếu xóa `AnalyticsController` → xóa cả import trong routes file.

- [ ] **Step 5: Test + suite + pint file đã sửa + commit**

```bash
incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --filter=WorldosRouteAuthTest 2>&1 | tail -4'   # 5 pass
incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --testsuite=Unit 2>&1 | tail -3'
git add -A backend && git commit -m "chore(be): xoa 3 route 0-consumer history-timeline/analytics-ticks/worlds-pulse (+ dead controller/action theo gate)"
```

---

### Task 2b: BE — vá nút Advance: wire `simulation/advance` vào handler thật + dọn `simulation-status`

> Bổ sung ngoài plan gốc (controller, sau phát hiện Task 2, đã verify curl thật): `POST /worldos/simulation/advance` — endpoint mà nút "Advance Simulation" của `/ops/simulation` gọi (`features/simulation/hooks/index.ts:109`, payload `{universe_id, ticks}`) — trỏ `UniverseController::advance` KHÔNG TỒN TẠI từ commit Init → 500 "Call to undefined method". Handler đúng có sẵn: `App\Modules\Simulation\Services\Meta\UniverseRuntimeService::advance(int $universeId, int $ticks): array` (delegate `AdvanceSimulationAction`). Cùng bệnh: `GET worlds/{id}/simulation-status` → `UniverseController::status` không tồn tại.

**Files:**
- Modify: `backend/app/Modules/WorldOS/Http/Controllers/UniverseController.php` (thêm method `advance`)
- Modify: `backend/app/Modules/WorldOS/routes/api.php` (xóa route `worlds/{id}/simulation-status` nếu gate 0 consumer)
- Modify: `backend/tests/Feature/WorldosRouteAuthTest.php` (case 401 + case simulation-status 404)
- Delete (gộp minor Task 2): `backend/app/Modules/WorldOS/Http/Resources/TimelineEventResource.php` nếu grep gate 0 consumer
- Create: `backend/tests/Feature/SimulationAdvanceRouteTest.php`

**Interfaces:**
- Consumes: `UniverseRuntimeService::advance(int, int): array`; hợp đồng FE: POST body `{universe_id: number, ticks: number}`, response được FE đọc qua `takeData` → trả `{data: <array kết quả>}`.
- Produces: endpoint advance hoạt động; route surface sạch.

- [ ] **Step 1: Grep gate** — `grep -rn "simulation-status" backend frontend/src --include='*.php' --include='*.ts' --include='*.tsx' | grep -v routes/api.php` (kỳ vọng 0 → xóa route); `grep -rn "TimelineEventResource" backend/app backend/tests | grep -v "Resources/TimelineEventResource.php"` (kỳ vọng 0 → xóa file).

- [ ] **Step 2: Viết test fail** — `SimulationAdvanceRouteTest.php`: (a) case không token → 401 (không cần DB); (b) case có auth → KHÔNG còn 500 "undefined method": dùng `Laravel\Sanctum\Sanctum::actingAs(User::factory()->create())` + bind mock `UniverseRuntimeService` vào container (`$this->mock(UniverseRuntimeService::class)->shouldReceive('advance')->once()->with(5, 3)->andReturn(['tick' => 8])`) → POST `{universe_id: 5, ticks: 3}` → 200 + json `data.tick == 8`; (c) case thiếu `universe_id` → 422. Nếu hạ tầng test auth (User factory/model) vướng test-rot pre-existing không dựng nổi actingAs → giữ case (a) + viết case (b/c) dạng skip có lý do rõ, ghi report (KHÔNG được im lặng bỏ).
Thêm vào `WorldosRouteAuthTest`: `test_worlds_simulation_status_route_is_removed` → getJson 404.

- [ ] **Step 3: Implement** — `UniverseController` thêm (composition root — import cross-module Service ở controller là hợp lệ theo miễn trừ ModuleBoundaryTest):

```php
    public function advance(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'universe_id' => 'required|integer',
            'ticks' => 'sometimes|integer|min:1|max:100',
        ]);

        $result = app(\App\Modules\Simulation\Services\Meta\UniverseRuntimeService::class)
            ->advance((int) $validated['universe_id'], (int) ($validated['ticks'] ?? 1));

        return response()->json(['data' => $result]);
    }
```
(Style import/DI theo file thật — nếu controller đã dùng constructor injection thì theo; use-statement thay FQN inline theo chuẩn file.) Xóa route `simulation-status` + `TimelineEventResource.php` theo gate Step 1.

- [ ] **Step 4: Test + Unit suite + pint file đã sửa + commit**

```bash
incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --filter="SimulationAdvanceRouteTest|WorldosRouteAuthTest" 2>&1 | tail -5'
incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --testsuite=Unit 2>&1 | tail -3'
git add -A backend && git commit -m "fix(be): wire simulation/advance vao UniverseRuntimeService — nut Advance /ops/simulation het 500; don simulation-status + TimelineEventResource"
```

---

### Task 3: BE — fix shadowing `ai-provider-models/{id}` che `/export`

**Files:**
- Modify: `backend/app/Modules/Intelligence/routes/api.php:34` (`Route::get('ai-provider-models/{id}', ...)`)
- Create: `backend/tests/Feature/AiProviderModelsRoutingTest.php`

**Interfaces:**
- Consumes: nhóm route Intelligence (GET public dòng ~33-34; `GET ai-provider-models/export` protected dòng ~53).
- Produces: route `{id}` chỉ nhận số; `/export` resolve đúng handler.

- [ ] **Step 1: Viết test fail**

```php
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
```

- [ ] **Step 2: Chạy fail** — `php artisan test --filter=AiProviderModelsRoutingTest` → FAIL (nhận 404/500 thay vì 401).

- [ ] **Step 3: Fix** — dòng 34 thêm ràng buộc:

```php
Route::get('ai-provider-models/{id}', [AiProviderModelsController::class, 'show'])->whereNumber('id');
```
(Đối chiếu PUT/DELETE `{id}` trong group protected: cùng verb với `/import`? Không — `import` là POST, không shadowing; chỉ cần sửa GET. Nếu đọc file thấy khác, xử lý tương tự và ghi report.)

- [ ] **Step 4: Test pass + Unit suite + pint 2 file + commit**

```bash
git add backend/app/Modules/Intelligence/routes/api.php backend/tests/Feature/AiProviderModelsRoutingTest.php
git commit -m "fix(be): whereNumber cho ai-provider-models/{id} — het shadowing route /export"
```

---

### Task 4: BE — xóa cây `Services/Transition/` orphan + block DI

**Files:**
- Delete: `backend/app/Modules/Simulation/Services/Transition/` (toàn bộ: `TransitionProcessor.php`, `Contracts/`, `Guards/`, `Transformers/`)
- Modify: `backend/app/Modules/Simulation/Providers/EngineServiceProvider.php` (xóa khối "Phase 100: Power System Transition" — dòng ~221-246: 4 singleton Transformers + 2 singleton Guards + closure singleton TransitionProcessor, GIỮ comment `// Advanced V10 Engines` phía sau)

**Interfaces:**
- Consumes: kết quả P4 Task 15 (listener `HandlePowerSystemTransition` — injection site duy nhất — đã xóa).
- Produces: cây Simulation không còn code Transition; `ModuleBoundaryTest` baseline không tăng.

- [ ] **Step 1: Grep gate** — `grep -rn "Services\\\\Transition\|Services/Transition\|TransitionProcessor" backend/app backend/tests | grep -v "Modules/Simulation/Services/Transition/" | grep -v EngineServiceProvider` → kỳ vọng 0 (khảo sát final review P4: chỉ còn định nghĩa + đăng ký DI). Có hit thật → BLOCKED, báo controller.

- [ ] **Step 2: Xóa** — `git rm -r backend/app/Modules/Simulation/Services/Transition` + xóa khối DI trong provider.

- [ ] **Step 3: Gate** — Unit suite (`--testsuite=Unit`, đạt baseline) + `php artisan test --filter=ModuleBoundaryTest` PASS + xác nhận container boot: `php artisan route:list > /dev/null && echo BOOT_OK`.

- [ ] **Step 4: Pint provider + commit** — `git add -A backend && git commit -m "chore(be): xoa cay Services/Transition orphan + block DI — het dead code Phase 100"`

---

### Task 5: FE — wire save thật `/ops/settings` + hydrate ngược

**Files:**
- Modify: `frontend/src/app/(ops)/ops/settings/page.tsx` (handleSave dòng ~64-73; thêm hydrate)
- Test: `frontend/src/features/admin/__tests__/OpsSettingsSave.test.tsx` (mới)

**Interfaces:**
- Consumes: `useUpdateAiSetting()` (`@/features/admin`) — mutation payload `{key: string; value: unknown; group?: string; is_secret?: boolean}`, POST `/ai-settings/update`; `useLoomAgents()` (`@/features/admin`) — GET `/ai-settings/loom-agents`, trả list record `{key: 'loom_agents.<agentId>', agent_name, value}`; `useAiSettings()` — GET `/ai-settings` trả list `{key, value, ...}`; `AgentConfig {agentId, model, temperature, maxTokens, retryAttempts}` + `EpistemicConfig {noiseLevel, tier, strictMode}` (`features/admin/components/ai-settings/types.ts`).
- Produces: quy ước key lưu — mỗi agent: `loom_agents.<agentId>`, group `loom_agents`, value snake_case `{model, temperature, max_tokens, retry_attempts}` (ĐỐI CHIẾU shape thật trong `AiSettingsController::importLoomAgents` trước khi viết — nếu import dùng field khác thì THEO import, ghi report); epistemic: key `narrative.epistemic`, group `narrative`, value `{noise_level, tier, strict_mode}`.

- [ ] **Step 1: Viết test fail** — `OpsSettingsSave.test.tsx`: mock `@/features/admin` (giữ types thật qua `importOriginal`, thay `useUpdateAiSetting` bằng mock trả `{mutateAsync}`; `useLoomAgents`/`useAiSettings` trả data rỗng), mock `@/features/narrative-runtime` (NARRATIVE_PIPELINE_NODES rút gọn 2 node + `useNarrativeRuntime` trả `{loomStatus: null}`), mock `@/features/ops-shell` (UniverseSelect → null). Render page (default export của `app/(ops)/ops/settings/page.tsx`), click nút "Save", assert `mutateAsync` được gọi 3 lần (2 agent + 1 epistemic) với key `loom_agents.<id>` đúng và `narrative.epistemic`; case thứ hai: `mutateAsync` reject → toast.error (spy `sonner`).

- [ ] **Step 2: Chạy fail** (handleSave hiện chỉ setTimeout — 0 call mutation).

- [ ] **Step 3: Implement** — trong page:

```tsx
const updateSetting = useUpdateAiSetting();
const { data: loomAgentRecords } = useLoomAgents();
const { data: aiSettings } = useAiSettings();

// Hydrate agentConfigs từ bản đã lưu (một lần khi data về; loomStatus sync vẫn giữ nguyên effect cũ)
useEffect(() => {
  if (!loomAgentRecords?.length) return;
  setAgentConfigs((prev) =>
    prev.map((cfg) => {
      const saved = loomAgentRecords.find((r) => r.key === `loom_agents.${cfg.agentId}`)?.value as
        | { model?: string; temperature?: number; max_tokens?: number; retry_attempts?: number }
        | undefined;
      return saved
        ? {
            ...cfg,
            model: saved.model ?? cfg.model,
            temperature: saved.temperature ?? cfg.temperature,
            maxTokens: saved.max_tokens ?? cfg.maxTokens,
            retryAttempts: saved.retry_attempts ?? cfg.retryAttempts,
          }
        : cfg;
    }),
  );
}, [loomAgentRecords]);

// Hydrate epistemic từ key narrative.epistemic
useEffect(() => {
  const rec = aiSettings?.find((s) => s.key === 'narrative.epistemic');
  if (!rec) return;
  const v = rec.value as { noise_level?: number; tier?: EpistemicConfig['tier']; strict_mode?: boolean };
  setEpistemic((prev) => ({
    noiseLevel: v.noise_level ?? prev.noiseLevel,
    tier: v.tier ?? prev.tier,
    strictMode: v.strict_mode ?? prev.strictMode,
  }));
}, [aiSettings]);

const handleSave = async () => {
  setIsSaving(true);
  try {
    await Promise.all([
      ...agentConfigs.map((cfg) =>
        updateSetting.mutateAsync({
          key: `loom_agents.${cfg.agentId}`,
          group: 'loom_agents',
          value: {
            model: cfg.model,
            temperature: cfg.temperature,
            max_tokens: cfg.maxTokens,
            retry_attempts: cfg.retryAttempts,
          },
        }),
      ),
      updateSetting.mutateAsync({
        key: 'narrative.epistemic',
        group: 'narrative',
        value: { noise_level: epistemic.noiseLevel, tier: epistemic.tier, strict_mode: epistemic.strictMode },
      }),
    ]);
    toast.success('Đã lưu cấu hình.');
  } catch {
    toast.error('Lưu thất bại.');
  } finally {
    setIsSaving(false);
  }
};
```
(Kiểu của `aiSettings`/`loomAgentRecords` theo hook thật — đọc `features/admin/hooks/index.ts` khi implement; shape record loomAgents đã tả ở Interfaces. `useAiSettings` nếu trả object khác list — điều chỉnh find tương ứng, test theo bản thật.)

- [ ] **Step 4: Test pass + full FE + check + commit**

```bash
git add "frontend/src/app/(ops)/ops/settings" frontend/src/features/admin/__tests__/OpsSettingsSave.test.tsx
git commit -m "feat(fe): /ops/settings save that qua useUpdateAiSetting + hydrate tu ai-settings"
```

---

### Task 6: FE — xóa dead exports

**Files:**
- Modify: `frontend/src/features/simulation/api/queries.ts` (xóa `config()` dòng ~44-50), `frontend/src/features/simulation/hooks/index.ts` + `frontend/src/features/simulation/index.ts` (xóa `useCompareBranch`/`useBranchComparison` — tên thật đối chiếu file), `frontend/src/features/universe/hooks/index.ts` (xóa `useUniverseOptions`/`useUniverseDossier` + queries tương ứng nếu 0 consumer), `frontend/src/features/narrative-runtime/index.ts` (gỡ export `usePipelineManifest` — hook GIỮ, LoomMonitor import nội bộ)

**Interfaces:**
- Consumes: kết quả khảo sát final review P4 (0 consumer các export trên).
- Produces: public API các feature gọn đúng thực dùng.

- [ ] **Step 1: Grep gate từng tên** — `grep -rn "<tên>" frontend/src --include='*.ts' --include='*.tsx' | grep -v "<file định nghĩa>" | grep -v __tests__` cho: `useCompareBranch`, `useBranchComparison`, `useUniverseOptions`, `useUniverseDossier`, `simulationQueries.config`, `usePipelineManifest` (riêng cái cuối: consumer nội bộ `LoomMonitor.tsx` là HỢP LỆ — chỉ gỡ khỏi index.ts). Có consumer thật ngoài dự kiến → GIỮ export đó, ghi report.

- [ ] **Step 2: Xóa theo gate** (kèm test file của riêng export chết nếu có — grep `__tests__` theo tên).

- [ ] **Step 3: Gate + commit** — full FE test + check xanh (số test có thể giảm nếu xóa test của dead export — ghi số vào report):

```bash
git add frontend/src && git commit -m "chore(fe): xoa dead exports simulation/universe + go usePipelineManifest khoi public API"
```

---

### Task 7: FE — chuẩn hóa query key + `useAiPool` dùng `takeData`

**Files:**
- Modify: `frontend/src/shared/config/queryKeys.ts` (thêm `aiDrivers: () => ['ops', 'ai-drivers'] as const`)
- Modify: `frontend/src/features/admin/api/queries.ts` (dòng ~47-51: `aiDrivers` dùng `qk.aiDrivers()`)
- Modify: `frontend/src/features/intelligence/hooks/index.ts` (dòng ~41-49: `useAiPool` bọc `takeData`)
- Test: mở rộng `frontend/src/features/intelligence/__tests__/useAiLogs.test.tsx` hoặc file test mới cho `useAiPool`

**Interfaces:**
- Consumes: `takeData` (`@/shared/lib/unwrap`), `qk` (`@/shared/config/queryKeys`).
- Produces: mọi query key admin/intelligence qua `qk`; `useAiPool` chịu được body `{data,meta}` tương lai.

- [ ] **Step 1: Viết test fail** — case `useAiPool`: mock `apiClient.get('/ai-settings')` trả `{ data: [{ key: 'use_pool', value: true }] }` (body envelope 1-key — interceptor apiClient sẽ tự bóc; NHƯNG thêm case body `{data: [...], meta: {}}` 2-key để chứng minh `takeData` xử lý cả hai) → hook trả `true` ở cả 2 case.

- [ ] **Step 2: Chạy fail** (case 2-key fail với code hiện tại — `response.data.find` không phải array).

- [ ] **Step 3: Implement**

```ts
const response = await apiClient.get('/ai-settings');
const records = takeData<{ key: string; value: unknown }[]>(response.data);
const usePoolRecord = records.find((record) => record.key === 'use_pool');
```
`queryKeys.ts` thêm `aiDrivers`; `adminQueries.aiDrivers` đổi `queryKey: qk.aiDrivers()`. LƯU Ý: key đổi từ `['admin','ai-drivers']` → `['ops','ai-drivers']` — grep `'admin', 'ai-drivers'\|admin','ai-drivers` toàn `frontend/src` xác nhận 0 chỗ invalidate/setQueryData theo key cũ (final review đã xác nhận, verify lại).

- [ ] **Step 4: Full FE test + check + commit** — `git add frontend/src && git commit -m "refactor(fe): qk.aiDrivers + useAiPool qua takeData — dong bo cong thuc unwrap"`

---

### Task 8: Batch test/polish nhỏ (BE + FE)

**Files:**
- Modify: `backend/tests/Feature/Broadcasting/CentrifugoChannelAuthTest.php` (thêm 1 case)
- Modify: `frontend/src/features/intelligence/__tests__/useAiLogs.test.tsx` (thêm regression test clearLogs)
- Modify: `frontend/src/app/(ops)/ops/system/page.tsx` (disable nút khi loading)

**Interfaces:**
- Consumes: helper `authFor()` trong CentrifugoChannelAuthTest (Task 16 P4); `useAiLogs().clearLogs` invalidate `qk.aiLogs`+`qk.aiStats`; nút Save/Reset/Discard ở system page (`PanelButton`, có sẵn `disabled` prop — xem cách nút Save đang dùng `disabled`).
- Produces: 3 hành vi được khóa bằng test/props.

- [ ] **Step 1 (BE): test reject trailing garbage** — thêm vào CentrifugoChannelAuthTest (theo helper thật của file):

```php
    public function test_auth_denies_global_universe_with_suffix(): void
    {
        $this->assertFalse($this->authFor('global_universe:x'));
        $this->assertFalse($this->authFor('global_universe_x'));
    }
```
Chạy filter → PASS ngay (code `===` đã đúng — regression lock). Nếu FAIL → báo controller (hành vi khác dự kiến).

- [ ] **Step 2 (FE): regression clearLogs** — thêm case vào `useAiLogs.test.tsx`: spy `queryClient.invalidateQueries`, gọi `clearLogs()`, assert được gọi với queryKey `qk.aiLogs`-prefix VÀ `qk.aiStats()` (đối chiếu chữ ký invalidate thật trong `features/intelligence/hooks/index.ts:16-19`).

- [ ] **Step 3 (FE): disable nút khi loading** — `ops/system/page.tsx`: nút Reset/Discard thêm `disabled={isLoading}` (nút Save đã có `disabled` — gộp điều kiện `disabled={isSaving || isLoading}`; đọc tên biến thật trong page). Không cần test riêng (props tĩnh) — check + eslint gate.

- [ ] **Step 4: Gate 2 phía + pint file BE + commit**

```bash
incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --filter=CentrifugoChannelAuthTest 2>&1 | tail -4'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads 2>&1 | tail -4'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check 2>&1 | tail -4'
git add backend/tests frontend/src && git commit -m "test: khoa global_universe suffix + clearLogs dual-invalidation; polish disable nut khi loading /ops/system"
```

---

### Task 9: Gate cuối + cập nhật `.dev_status.md`

**Files:**
- Modify: `.dev_status.md`

**Interfaces:**
- Consumes: Task 1-8 hoàn thành.
- Produces: bằng chứng test cuối; docs khớp; danh sách tồn còn lại.

- [ ] **Step 1: Full gate 2 phía**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads 2>&1 | tail -5'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check 2>&1 | tail -4'
incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --testsuite=Unit 2>&1 | tail -3'
incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --filter="WorldosRouteAuthTest|AiProviderModelsRoutingTest|CentrifugoChannelAuthTest|Observatory" 2>&1 | tail -5'
```
Expected: FE ≥ baseline (số chính xác ghi report — Task 5/7/8 thêm test, Task 6 có thể bớt); BE Unit đạt baseline; nhóm filter pass hết.

- [ ] **Step 2: Cập nhật `.dev_status.md`** — Session mới "Post-P4 Cleanup + Security" trên cùng: việc đã làm theo 8 task, số liệu gate thật, và **SỬA 2 ghi chú sai từ final review P4** trong mục "Tồn hậu-P4": (a) `generate-chronicle` vốn ĐÃ auth:sanctum (comment cũ gây hiểu nhầm — đã xóa comment + khóa bằng test; lỗ hổng thật là `test-weave`, đã xóa); (b) gạch các mục đã trả trong phase này (4 route, shadowing export, Services/Transition, fake-save, dead exports, qk.aiDrivers). Tồn còn lại: CI/build verify (user hoãn), realtime stack/subscribe proxy, nợ test BE 92 skip/91 feature fail.

- [ ] **Step 3: Commit** — `git add .dev_status.md && git commit -m "docs: hoan thanh phase post-P4 cleanup+security — cap nhat trang thai + sua ghi chu generate-chronicle"`

Sau đó dùng skill `superpowers:finishing-a-development-branch`.

---

## Ghi chú thực thi

- **Thứ tự:** Task 1 → 2 (chung test file); 3, 4 độc lập sau 2; 5 → 6 → 7 → 8 (FE, tuần tự để tránh đụng index.ts/queries); 9 cuối. Không chạy 2 implementer song song.
- Số dòng trong plan là tại thời điểm viết (main 75006ae) — implementer đối chiếu nội dung thật, không tin số dòng mù quáng.
- Mọi lần xóa: grep gate trước, dán output vào report; lộ consumer → giữ + báo, KHÔNG tự quyết.
