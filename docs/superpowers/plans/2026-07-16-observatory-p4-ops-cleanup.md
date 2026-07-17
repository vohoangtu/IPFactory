# Observatory Plan 4 — Ops Port + Thanh Lý Code Cũ Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Hoàn tất big-bang Observatory: port 6 trang nghiệp vụ quản trị vào route group `(ops)`, xóa toàn bộ kiến trúc frontend cũ (`src/components/`, `src/contexts/`, `src/hooks/`, `src/lib/`, `src/types/`, `src/app/dashboard/`, `narrative-studio`, `narrative-cinema`), mở rộng guardrail ESLint phủ toàn `src/app/**`, dọn dead code backend, và cập nhật docs.

**Architecture:** Chiến lược port = **lift & shift**: copy component dashboard cũ vào feature tương ứng, swap tầng data (`@/lib/api` → `apiClient`+`takeData`, `@/lib/utils` → `@/shared/lib/utils`, `useCentrifugo` adaptive → interval cố định / bản nội bộ feature), page mỏng trong `(ops)` — KHÔNG redesign UI, giữ nguyên hành vi. Xóa theo lớp, mỗi lớp một task với gate xanh (check + test), ESLint mở rộng SAU khi xóa. Thứ tự bắt buộc: nền tảng (Task 1-3) → port (Task 4-11) → xóa (Task 12-13) → guardrail (Task 14) → BE cleanup (Task 15-16, độc lập) → gate cuối + docs (Task 17).

**Tech Stack:** Next.js 16 App Router, React 19, TanStack Query v5, zustand, centrifuge-js, Tailwind v4, Vitest (`--pool=threads`); Laravel 13 + PHPUnit (sqlite `:memory:`), Pint.

## Global Constraints

- **KHÔNG chạy `npm`/`composer` trên host.** Mọi lệnh qua Incus `worldos-dev` (mount `/work`):
  - FE: `incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads'` (BẮT BUỘC `--pool=threads`); check: `npm run check`. `npm run build` KHÔNG chạy được (AppArmor) — discharge ở CI/Docker.
  - BE: `incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --filter=<Tên>'`; pint: `vendor/bin/pint <files>`.
- **Baseline:** FE 32 file / 145 test, check 0 error / 2 warning pre-existing (`src/lib/__tests__/centrifugo.test.ts` — file này SẼ bị xóa ở Task 13 cùng warning của nó); BE Unit 170-171 pass / 92 skip (fail duy nhất được phép: `IntelligenceExplosionTest` flake). Sau Task 13, baseline FE MỚI = số đo tại gate Task 13 (xóa 3 file/21 test legacy có chủ đích, cộng test mới Task 1-11).
- **Kiến trúc mới (KHÔNG phá):** `(observatory)` (AuthGate) + `(cinema)` (public) + features P1-P3 (chronicle, cinema, actors, causal-map, wavefunction, multiverse, civilization, universe-workspace, auth) — chỉ được THÊM export/mở rộng, không đổi hành vi hiện có. Hợp đồng P1 (envelope, feed, kênh) giữ nguyên.
- **Layering ESLint:** app → features → shared; feature chỉ import qua `@/features/<name>` (index.ts). Code MỚI trong plan này KHÔNG import `@/lib/*`, `@/hooks/*`, `@/contexts/*`, `@/components/*`, `@/types/*` (dùng bản shared/feature tương ứng).
- **Port giữ hành vi:** component copy giữ nguyên logic/JSX; chỉ sửa import + cắt phụ thuộc legacy. Endpoint gọi giữ nguyên URL. `apiClient` unwrap chặt hơn `api` cũ (chỉ bóc body 1-key `{data}`; cũ bóc cả khi có `meta`/`links`) → endpoint list/paginated qua `takeData` (`@/shared/lib/unwrap`) — công thức đã dùng ở P3 (`features/actors/api/queries.ts` là mẫu chuẩn).
- **PHP:** `declare(strict_types=1)` file mới, PSR-12, không sửa migration.
- **Guardrail BE** (`ModuleBoundaryTest`): ratchet chỉ-được-giảm — các task BE chỉ XÓA code, không thêm import chéo module.
- **Thẩm mỹ:** token `globals.css` như P2/P3. Task port UI: implementer NÊN đọc skill `frontend-design`; ưu tiên giữ nguyên component cũ (đã đạt), chỉ chỉnh khi vỡ layout trong shell mới.
- Mỗi task kết thúc: full FE test + check (hoặc BE filter + pint) XANH so với baseline hiện hành, rồi commit.

## Quyết định phạm vi (chốt khi viết plan)

1. **Ops = 6 trang** (user chốt 2026-07-16): `/ops/simulation`, `/ops/loom`, `/ops/ai-runtime`, `/ops/settings`, `/ops/system`, `/ops/intelligence` (trang thứ 6 ngoài spec — công cụ vận hành AI). **`achievements` + `timeline` XÓA THẲNG** không port (endpoint BE giữ nguyên, dựng lại được sau).
2. **`(ops)` có AuthGate** (nghiệp vụ quản trị — nhất quán observatory); shell riêng `features/ops-shell`: nav 6 trang + link "← Đài quan sát" + nút Đăng xuất (`useAuth().logout`). KHÔNG mở rộng `features/auth` (không cần user/me — YAGNI).
3. **Active universe cho simulation/loom:** component `UniverseSelect` (trong ops-shell) dùng `useUniverses` + `simStore.selectUniverse/selectedUniverseId` — tái dùng store sẵn có, KHÔNG subscribe kênh realtime ở ops (loom tự quản kênh `narrative:*` riêng trong `useNarrativeRuntime`).
4. **`useNarrativeRuntime` (554 dòng) GIỮ NGUYÊN LOGIC**, chỉ swap: `useUniverse()` (Context) → tham số `universeId: number | null`; `@/lib/centrifugo` → `@/shared/lib/centrifugo`; `useCentrifugoConnection` → bản port nội bộ `features/narrative-runtime/hooks/useCentrifugoConnection.ts`. Đây là port rủi ro nhất — reviewer soi kỹ diff.
5. **`src/types/api.ts` (433 dòng) DỜI** `git mv` → `src/shared/types/api.ts`, sed toàn bộ import `@/types/api` → `@/shared/types/api` (8 feature mới + code port). Làm TRƯỚC mọi task port.
6. **`ui/shared` cũ (16 component) không port nguyên khối:** mỗi task port chỉ copy đúng component `ui/shared`/`ui/*` mà cây của nó dùng vào `src/shared/ui/` (giữ tên file); `cn`/`formatMetric`/`sentenceCase`/`getRecord`/`getEntries` vào `src/shared/lib/utils.ts` (Task 1); `log-utils` → `features/intelligence/lib/log-utils.ts` (Task 10). Component copy trùng giữa 2 task: task sau kiểm tra tồn tại trước khi copy (đã có thì dùng luôn).
7. **Root:** `app/providers.tsx` mới (QueryClientProvider từ `@/shared/lib/queryClient` + `Toaster` + devtools, KHÔNG AuthProvider — `features/auth` đọc localStorage trực tiếp); root layout swap import. Group layouts giữ provider riêng (nested — chấp nhận, không đổi hành vi). Trang `/` viết lại: 2 card → `/multiverse` + `/ops/simulation`.
8. **Xóa `narrative-cinema` redirect** (spec 4.5; mọi link nội bộ tới nó chết cùng dashboard ở Task 12).
9. **BE:** xóa dead event `PowerSystemTransitionTriggered` (+ listener + đăng ký provider + cây `Services/Transition/` nếu chỉ listener dùng); sửa 2 `Event::fake` namespace (`App\Events\Simulation\...` → `App\Modules\Simulation\Events\...`); mở rộng `CentrifugoBroadcaster::auth()` cho `global_universe` + `narrative:{worldId}:{taskId}` + test; **KHÔNG đổi `client.insecure`** (không có stack runtime để verify — chỉ document trong config); xóa route `chronicles/raw` (0 consumer FE, kiểm caller nội bộ trước). Các route nghi vấn khác (`history-timeline`, `analytics/ticks`, `worlds/pulse`, `test-weave`) — NGOÀI phạm vi P4, chỉ ghi nhận vào .dev_status.
10. **Test FE giảm có chủ đích** ở Task 13: xóa `src/lib/__tests__/api.test.ts` (9), `src/lib/__tests__/centrifugo.test.ts` (6), `src/hooks/__tests__/useAchievements.test.tsx` (6) — coverage tương đương đã có ở `shared/lib/__tests__`; useAchievements chết theo tính năng.

---

### Task 1: FE nền tảng ops — `shared/lib/utils`, routes, qk, root providers

**Files:**
- Create: `frontend/src/shared/lib/utils.ts`
- Create: `frontend/src/app/providers.tsx`
- Modify: `frontend/src/app/layout.tsx` (swap import Providers)
- Modify: `frontend/src/shared/config/routes.ts`
- Modify: `frontend/src/shared/config/queryKeys.ts`
- Test: `frontend/src/shared/lib/__tests__/utils.test.ts`

**Interfaces:**
- Consumes: `@/shared/lib/queryClient` (đã có — chính là module `(observatory)/layout` dùng; xem export thật của nó khi viết `providers.tsx`, nếu tên là `makeQueryClient` thì dùng đúng tên đó).
- Produces (task sau dùng verbatim):
  - `shared/lib/utils.ts`: `cn(...inputs: ClassValue[]): string`, `formatMetric(value, digits=3): string`, `sentenceCase(value): string`, `getRecord<T>(value): T`, `getEntries(value): Array<[string, number]>` — nội dung copy VERBATIM từ `src/lib/utils.ts` (52 dòng, giữ nguyên cả JSDoc).
  - `routes`: thêm `opsSimulation: () => '/ops/simulation'`, `opsLoom: () => '/ops/loom'`, `opsAiRuntime: () => '/ops/ai-runtime'`, `opsSettings: () => '/ops/settings'`, `opsSystem: () => '/ops/system'`, `opsIntelligence: () => '/ops/intelligence'`.
  - `qk`: thêm `serviceStatus: () => ['ops','service-status']`, `simulationSettings: () => ['ops','simulation-settings']`, `aiSettings: () => ['ops','ai-settings']`, `providerModels: () => ['ops','provider-models']`, `keyPool: () => ['ops','key-pool']`, `loomAgents: () => ['ops','loom-agents']`, `aiLogs: (filters: string) => ['ops','ai-logs',filters]`, `aiStats: () => ['ops','ai-stats']`, `loomStatus: () => ['loom','status']`, `loomTask: (taskId: string) => ['loom','task',taskId]`, `snapshots: (id: number) => ['universes',id,'snapshots']`, `forks: (id: number) => ['universes',id,'forks']` (tất cả `as const`).
  - `app/providers.tsx`: `export function AppProviders({ children })` — QueryClientProvider (client tạo qua `useState(() => ...)` từ shared queryClient factory) + `<Toaster richColors position="top-right" theme="dark" closeButton />` + `ReactQueryDevtools` khi development. KHÔNG AuthProvider.

- [ ] **Step 1: Viết test fail** — `src/shared/lib/__tests__/utils.test.ts`:

```ts
import { describe, expect, it } from 'vitest';
import { cn, formatMetric, sentenceCase, getRecord, getEntries } from '../utils';

describe('shared utils', () => {
  it('cn merge conflict tailwind', () => {
    expect(cn('p-2', 'p-4')).toBe('p-4');
    expect(cn('a', false && 'b')).toBe('a');
  });
  it('formatMetric fallback + fixed digits', () => {
    expect(formatMetric(0.12345)).toBe('0.123');
    expect(formatMetric(null)).toBe('0.000');
    expect(formatMetric(2, 1)).toBe('2.0');
  });
  it('sentenceCase snake/kebab', () => {
    expect(sentenceCase('great_filter')).toBe('Great Filter');
    expect(sentenceCase(null)).toBe('Unknown');
  });
  it('getRecord/getEntries guard', () => {
    expect(getRecord(null)).toEqual({});
    expect(getEntries({ a: 2, b: '3' })).toEqual([['a', 2], ['b', 3]]);
  });
});
```

- [ ] **Step 2: Chạy fail** — `npm test -- --pool=threads src/shared/lib/__tests__/utils.test.ts` → FAIL (module chưa có).

- [ ] **Step 3: Implement** — `shared/lib/utils.ts` = copy VERBATIM toàn bộ nội dung `src/lib/utils.ts` (không sửa gì — 5 hàm + import clsx/tailwind-merge). `routes.ts` + `queryKeys.ts` thêm đúng các entry ở Produces (giữ nguyên entry hiện có).

- [ ] **Step 4: `app/providers.tsx` + root layout**

```tsx
'use client';

import { QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import { Toaster } from 'sonner';
import { useState } from 'react';
import { makeQueryClient } from '@/shared/lib/queryClient';

/** Provider gốc toàn app: React Query + Toaster. Auth do features/auth tự quản (đọc localStorage). */
export function AppProviders({ children }: { children: React.ReactNode }) {
  const [queryClient] = useState(() => makeQueryClient());

  return (
    <QueryClientProvider client={queryClient}>
      {children}
      <Toaster richColors position="top-right" theme="dark" closeButton />
      {process.env.NODE_ENV === 'development' && (
        <ReactQueryDevtools initialIsOpen={false} buttonPosition="bottom-right" />
      )}
    </QueryClientProvider>
  );
}
```
(Nếu `@/shared/lib/queryClient` export tên khác `makeQueryClient` — dùng đúng tên thật, xem file.) Root `app/layout.tsx`: đổi `import { Providers } from '@/components/Providers'` → `import { AppProviders } from './providers'` và `<Providers>` → `<AppProviders>`. KHÔNG đụng phần metadata/font.

- [ ] **Step 5: Full test + check + commit**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads 2>&1 | tail -4'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check 2>&1 | tail -4'
git add frontend/src/shared frontend/src/app/providers.tsx frontend/src/app/layout.tsx
git commit -m "feat(fe): P4 nen tang — shared utils, routes/qk ops, AppProviders thay Providers legacy"
```
Expected: 33 file / 149 test pass (baseline +1 file/+4 test); check 0 error. LƯU Ý: dashboard cũ vẫn chạy qua AuthGate? KHÔNG — dashboard cũ dùng `AuthProvider` chỉ để cung cấp context; các trang dashboard không có gate bắt buộc, bỏ AuthProvider khỏi root có thể làm `useAuthContext` cũ throw ở `DashboardShell`/`AppHeader`. KIỂM TRA: grep `useAuthContext|useContext(AuthContext)` trong `src/components`; nếu DashboardShell vỡ runtime thì chấp nhận (dashboard chết ở Task 12) NHƯNG check/tsc phải xanh — nếu tsc lỗi, giữ `AuthProvider` import tạm trong `app/providers.tsx` (bọc children) và gỡ ở Task 13 khi xóa contexts. Ghi rõ lựa chọn vào report.

---

### Task 2: `(ops)` shell — feature `ops-shell` + layout group

**Files:**
- Create: `frontend/src/features/ops-shell/components/OpsShell.tsx`
- Create: `frontend/src/features/ops-shell/components/OpsNav.tsx`
- Create: `frontend/src/features/ops-shell/components/UniverseSelect.tsx`
- Create: `frontend/src/features/ops-shell/index.ts`
- Create: `frontend/src/app/(ops)/layout.tsx`
- Test: `frontend/src/features/ops-shell/__tests__/OpsNav.test.tsx`, `frontend/src/features/ops-shell/__tests__/UniverseSelect.test.tsx`

**Interfaces:**
- Consumes: `routes.ops*` (Task 1), `useAuth` (`@/features/auth`), `useUniverses` + `useObservedUniverse`? KHÔNG — chỉ `useUniverses` (`@/features/universe-workspace`), `useSimStore` (`@/shared/store/simStore`), `AuthGate` (`@/features/auth`), QueryClientProvider pattern của `(observatory)/layout.tsx` (copy y nguyên cách làm).
- Produces:
  - `OpsShell({ children }: { children: ReactNode })` — header: tiêu đề "Vận hành" + `OpsNav` + link "← Đài quan sát" (`routes.multiverse()`) + nút "Đăng xuất" (gọi `useAuth().logout()` rồi `router.push(routes.login())`); `<main class="min-h-0 flex-1 p-4">`.
  - `OpsNav()` — 6 tab: Simulation → `routes.opsSimulation()`, Loom → `opsLoom`, AI Runtime → `opsAiRuntime`, Settings → `opsSettings`, System → `opsSystem`, Intelligence → `opsIntelligence`; active bằng `usePathname()` + `aria-current="page"` (pattern LensNav — copy `features/universe-workspace/components/LensNav.tsx` làm mẫu, đổi danh sách).
  - `UniverseSelect()` — `<select aria-label="Chọn Universe">` từ `useUniverses()`, value = `useSimStore(s => s.selectedUniverseId)`, onChange → `selectUniverse(id)` (KHÔNG router.push — ops ở lại trang); hiển thị option "— Chọn universe —" khi null.
  - `(ops)/layout.tsx`: copy đúng cấu trúc `(observatory)/layout.tsx` (QueryClientProvider riêng + `AuthGate`), khác biệt: bọc thêm `<OpsShell>` quanh children.

- [ ] **Step 1: Viết test fail**

`OpsNav.test.tsx`:

```tsx
import { describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
vi.mock('next/navigation', () => ({ usePathname: () => '/ops/loom' }));
import { OpsNav } from '../components/OpsNav';

describe('OpsNav', () => {
  it('render 6 tab ops, tab hiện tại có aria-current', () => {
    render(<OpsNav />);
    expect(screen.getAllByRole('link')).toHaveLength(6);
    const loom = screen.getByRole('link', { name: 'Loom' });
    expect(loom.getAttribute('href')).toBe('/ops/loom');
    expect(loom.getAttribute('aria-current')).toBe('page');
    expect(screen.getByRole('link', { name: 'Simulation' }).getAttribute('href')).toBe('/ops/simulation');
    expect(screen.getByRole('link', { name: 'Intelligence' }).getAttribute('href')).toBe('/ops/intelligence');
  });
});
```

`UniverseSelect.test.tsx` (mock `@/features/universe-workspace` trả `useUniverses` với 2 universe; render, đổi select → assert `useSimStore.getState().selectedUniverseId` đổi):

```tsx
import { describe, expect, it, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { useSimStore } from '@/shared/store/simStore';

vi.mock('@/features/universe-workspace', () => ({
  useUniverses: () => ({ data: [
    { id: 1, world_id: 1, name: 'Alpha', status: 'active', current_tick: 5, era: 1 },
    { id: 2, world_id: 1, name: 'Beta', status: 'halted', current_tick: 9, era: 2 },
  ], isLoading: false, isError: false }),
}));
import { UniverseSelect } from '../components/UniverseSelect';

describe('UniverseSelect', () => {
  it('chọn universe → set simStore.selectedUniverseId', () => {
    useSimStore.getState().reset();
    render(<UniverseSelect />);
    fireEvent.change(screen.getByRole('combobox', { name: 'Chọn Universe' }), { target: { value: '2' } });
    expect(useSimStore.getState().selectedUniverseId).toBe(2);
  });
});
```

- [ ] **Step 2: Chạy fail**, rồi **Step 3: Implement 3 component + index.ts + layout** theo Produces (OpsNav copy pattern LensNav; OpsShell copy pattern WorkspaceLayout của universe-workspace, thay ContextBar bằng tiêu đề + UniverseSelect chỗ trang cần — KHÔNG, UniverseSelect do TỪNG PAGE render, không nằm cứng trong shell). `index.ts`:

```ts
export { OpsShell } from './components/OpsShell';
export { OpsNav } from './components/OpsNav';
export { UniverseSelect } from './components/UniverseSelect';
```

`(ops)/layout.tsx` (đối chiếu `(observatory)/layout.tsx` để lấy đúng import provider):

```tsx
'use client';
import type { ReactNode } from 'react';
import { QueryClientProvider } from '@tanstack/react-query';
import { useState } from 'react';
import { makeQueryClient } from '@/shared/lib/queryClient';
import { AuthGate } from '@/features/auth';
import { OpsShell } from '@/features/ops-shell';

export default function OpsRootLayout({ children }: { children: ReactNode }) {
  const [client] = useState(() => makeQueryClient());
  return (
    <QueryClientProvider client={client}>
      <AuthGate>
        <OpsShell>{children}</OpsShell>
      </AuthGate>
    </QueryClientProvider>
  );
}
```
(Nếu `(observatory)/layout.tsx` cấu trúc khác — ví dụ export component provider riêng, AuthGate import path khác — LÀM THEO ĐÚNG file đó, đây là mẫu chuẩn.)

- [ ] **Step 4: Full test + check + commit**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads 2>&1 | tail -4'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check 2>&1 | tail -4'
git add frontend/src/features/ops-shell "frontend/src/app/(ops)"
git commit -m "feat(fe): (ops) shell — OpsShell/OpsNav/UniverseSelect + layout AuthGate"
```

---

### Task 3: Dời `src/types/api.ts` → `src/shared/types/api.ts`

**Files:**
- Move (git mv): `frontend/src/types/api.ts` → `frontend/src/shared/types/api.ts`
- Modify: mọi file import `@/types/api` (sed toàn repo `frontend/src`)

**Interfaces:**
- Consumes: không có.
- Produces: type path mới `@/shared/types/api` — mọi task port sau dùng path này. `src/types/` trống (xóa thư mục).

- [ ] **Step 1: Move + sed**

```bash
mkdir -p frontend/src/shared/types
git mv frontend/src/types/api.ts frontend/src/shared/types/api.ts
grep -rl "@/types/api" frontend/src --include='*.ts' --include='*.tsx' | xargs sed -i "s|@/types/api|@/shared/types/api|g"
rmdir frontend/src/types 2>/dev/null || ls frontend/src/types
```
Nếu `src/types/` còn file khác (grep trước khi rmdir) → xử lý tương tự (move + sed) và ghi vào report.

- [ ] **Step 2: Verify không còn path cũ + full test + check**

```bash
grep -rn "@/types/api" frontend/src --include='*.ts' --include='*.tsx' | wc -l   # kỳ vọng 0
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads 2>&1 | tail -4'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check 2>&1 | tail -4'
```
Expected: 0 kết quả grep; test/check như sau Task 2 (move thuần không đổi hành vi).

- [ ] **Step 3: Commit**

```bash
git add -A frontend/src
git commit -m "refactor(fe): doi types/api.ts vao shared/types — go phu thuoc cuoi vao src/types"
```

---

### Task 4: Ops Simulation — nâng cấp `features/simulation` + port panels + page `/ops/simulation`

**Files:**
- Modify: `frontend/src/features/simulation/api/queries.ts` + `frontend/src/features/simulation/hooks/index.ts` (swap `@/lib/api` → `apiClient`+`takeData`+`qk`; giữ tên + shape hook)
- Copy: `frontend/src/components/dashboard/simulation/{TickAdvancePanel,UniverseStatusPanel,SnapshotPanel,ForkPanel,CreateUniverseForm}.tsx` → `frontend/src/features/simulation/components/`
- Create: `frontend/src/features/simulation/components/SimulationOps.tsx`
- Create: `frontend/src/features/simulation/index.ts`
- Create: `frontend/src/app/(ops)/ops/simulation/page.tsx`
- Test: `frontend/src/features/simulation/__tests__/useAdvanceSimulation.test.tsx`

**Interfaces:**
- Consumes: hooks hiện có của feature — `useSnapshots(:10)`, `useCreateSnapshot(:18)`, `useForkUniverse(:40)`, `useAdvanceSimulation(:91)` (`POST /worldos/simulation/advance {universe_id, ticks}`), `useToggleUniverse(:116)`, `useCreateUniverse(:131)`, `useDeleteUniverse(:140)`; `UniverseSelect` + `useSimStore.selectedUniverseId` (Task 2); `qk.snapshots/forks` (Task 1).
- Produces (public API `@/features/simulation`): toàn bộ hooks trên + `SimulationOps({ universeId }: { universeId: number | null })` — compose 5 panel; `universeId == null` → empty note "Chọn một universe để điều khiển."
- Quy tắc port panels: 5 component cũ đọc universe từ `useUniverse()` Context → đổi thành prop `universeId: number` (hoặc object universe nếu component cần thêm field — lấy từ `useUniverses` trong `SimulationOps` và truyền xuống); import `@/lib/utils` → `@/shared/lib/utils`; component `ui/shared/*` mà panels dùng (đọc file khi copy — ví dụ Button/MetricCard/PageHeader) → copy từng cái sang `src/shared/ui/` (đổi import nội bộ của chúng sang `@/shared/lib/utils`), panels import từ `@/shared/ui/<Tên>`.

- [ ] **Step 1: Viết test fail** — `useAdvanceSimulation.test.tsx` (pattern mock apiClient `vi.hoisted` như `features/actors/__tests__/useActorPsyche.test.tsx`, nhưng mock cả `post`):

```tsx
it('advance gọi POST /worldos/simulation/advance với universe_id + ticks', async () => {
  mockPost.mockResolvedValueOnce({ data: { ok: true } });
  const { result } = renderHook(() => useAdvanceSimulation(), { wrapper });
  await act(async () => { await result.current.mutateAsync({ universeId: 3, ticks: 5 }); });
  expect(mockPost).toHaveBeenCalledWith('/worldos/simulation/advance', { universe_id: 3, ticks: 5 });
});
```
(Đối chiếu chữ ký mutation THẬT trong `features/simulation/hooks/index.ts:91` trước khi viết — nếu payload/tên biến khác thì test theo đúng bản thật, hành vi giữ nguyên.)

- [ ] **Step 2: Chạy fail** (mock chưa khớp vì queries còn dùng `@/lib/api`), rồi **Step 3: Rewrite queries + hooks** theo công thức P3 (mẫu: `features/actors/api/queries.ts`): `apiClient` + `takeData` cho GET list (`snapshots`, `forks`), mutation dùng `apiClient.post/delete` trực tiếp; queryKey → `qk.snapshots(id)`/`qk.forks(id)`; giữ nguyên tên hook + shape trả về + invalidation hiện có.

- [ ] **Step 4: Copy 5 panel + components `ui/shared` phụ thuộc** theo quy tắc port ở Interfaces (đọc từng file cũ; liệt kê trong report component `ui/shared` nào đã copy sang `shared/ui`).

- [ ] **Step 5: `SimulationOps.tsx` + page + index.ts**

```tsx
'use client';
import { useSimStore } from '@/shared/store/simStore';
import { TickAdvancePanel } from './TickAdvancePanel';
import { UniverseStatusPanel } from './UniverseStatusPanel';
import { SnapshotPanel } from './SnapshotPanel';
import { ForkPanel } from './ForkPanel';
import { CreateUniverseForm } from './CreateUniverseForm';

export function SimulationOps({ universeId }: { universeId: number | null }) {
  if (universeId == null) {
    return (
      <div className="flex flex-col gap-6">
        <p className="rounded-xl border border-dashed border-[var(--border-subtle)] p-6 text-sm text-[var(--color-text-muted)]">
          Chọn một universe để điều khiển — hoặc tạo mới bên dưới.
        </p>
        <CreateUniverseForm />
      </div>
    );
  }
  return (
    <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
      <UniverseStatusPanel universeId={universeId} />
      <TickAdvancePanel universeId={universeId} />
      <SnapshotPanel universeId={universeId} />
      <ForkPanel universeId={universeId} />
      <div className="lg:col-span-2"><CreateUniverseForm /></div>
    </div>
  );
}
```
(Chữ ký props panel theo đúng bản đã port ở Step 4 — nếu panel cần object universe thì `SimulationOps` lấy từ `useUniverses` và truyền; `useSimStore` import dư thì bỏ.)

`index.ts`: export mọi hook + `SimulationOps`. Page `(ops)/ops/simulation/page.tsx`:

```tsx
'use client';
import { UniverseSelect } from '@/features/ops-shell';
import { SimulationOps } from '@/features/simulation';
import { useSimStore } from '@/shared/store/simStore';

export default function OpsSimulationPage() {
  const universeId = useSimStore((s) => s.selectedUniverseId);
  return (
    <div className="mx-auto flex max-w-5xl flex-col gap-4">
      <div className="flex items-center justify-between gap-4">
        <h1 className="font-mono text-xs uppercase tracking-[0.3em] text-[var(--color-text-muted)]">Điều khiển mô phỏng</h1>
        <UniverseSelect />
      </div>
      <SimulationOps universeId={universeId} />
    </div>
  );
}
```

- [ ] **Step 6: Full test + check + commit**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads 2>&1 | tail -4'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check 2>&1 | tail -4'
git add frontend/src/features/simulation frontend/src/shared/ui "frontend/src/app/(ops)/ops/simulation"
git commit -m "feat(fe): ops simulation — port panels tick/snapshot/fork/create + page /ops/simulation"
```
LƯU Ý gate: trang dashboard cũ `dashboard/simulation` vẫn import components/dashboard/simulation (bản gốc chưa xóa) → phải còn compile.

---

### Task 5: Nâng cấp `features/admin` (nguồn data cho ai-runtime / settings / system)

**Files:**
- Modify: `frontend/src/features/admin/api/queries.ts` (77 dòng) + `frontend/src/features/admin/hooks/index.ts` (333 dòng)
- Create: `frontend/src/features/admin/index.ts`
- Test: `frontend/src/features/admin/__tests__/useServiceStatus.test.tsx`

**Interfaces:**
- Consumes: endpoints hiện có: `/apex/settings(/update|reset)`, `/worldos/service-status`, `/ai-settings(/update|sync|import|diagnostics|import-loom-agents|drivers|loom-agents)`, `/ai-provider-models` (CRUD/export/import), `/ai-key-pool` (CRUD); `qk.serviceStatus/simulationSettings/aiSettings/providerModels/keyPool/loomAgents` (Task 1).
- Produces (public API `@/features/admin`): toàn bộ hook hiện có GIỮ NGUYÊN TÊN + SHAPE (trang dashboard cũ import subpath `@/features/admin/hooks` phải compile tiếp — như tiền lệ P3 Task 9); bỏ hết `@/lib/api` + `@/hooks/useCentrifugo` (adaptive interval → interval cố định như queryOptions cũ khai).

- [ ] **Step 1: Viết test fail** — `useServiceStatus.test.tsx` (mock apiClient): assert gọi `/worldos/service-status`; 1 case mutation (vd `useUpdateSimulationSettings` nếu có — đối chiếu tên thật trong hooks/index.ts) assert gọi đúng endpoint.
- [ ] **Step 2: Chạy fail**, rồi **Step 3: Rewrite** theo công thức P3 (list qua `takeData`, body 1-key tự bóc; paginated giữ nguyên body nếu hook cũ đọc `meta`). Đọc KỸ 333 dòng hooks — mọi mutation giữ nguyên invalidation. `index.ts` export toàn bộ hook.
- [ ] **Step 4: Full test + check + commit**

```bash
git add frontend/src/features/admin
git commit -m "refactor(fe): features/admin sang apiClient + public API — nguon data cho 3 trang ops"
```
Gate: 2 trang dashboard cũ (ai-runtime, system) + config/ai-settings vẫn compile.

---

### Task 6: Ops System — port components + page `/ops/system`

**Files:**
- Copy: `frontend/src/components/dashboard/system/*.tsx` (5 file — `ConfigCard`, `SettingRow`, `SettingsGrid`, `ServiceHealthPanel`, `SystemInfoCard`) → `frontend/src/features/admin/components/system/`
- Create: `frontend/src/app/(ops)/ops/system/page.tsx`
- Modify: `frontend/src/features/admin/index.ts` (export `SettingsGrid`, `ServiceHealthPanel`, `SystemInfoCard`)
- Test: `frontend/src/features/admin/__tests__/ServiceHealthPanel.test.tsx`

**Interfaces:**
- Consumes: `useServiceStatus`, `useSimulationSettings` (+ mutation update) từ `../../hooks` (Task 5); `ui/shared` components mà 5 file dùng → copy sang `@/shared/ui` như quy tắc Task 4 (đã có thì dùng lại).
- Produces: page `/ops/system` render nội dung tương đương `dashboard/system/page.tsx` cũ (118 dòng — đọc nó làm khung, bỏ shell/PageHeader cũ, thay bằng heading đơn giản trong OpsShell).

- [ ] **Step 1: Test fail** — `ServiceHealthPanel.test.tsx`: mock hook module (`vi.mock('../../hooks', ...)`) trả service list 2 dịch vụ (1 healthy, 1 down) → render, assert tên dịch vụ + trạng thái hiển thị. (Đọc component thật khi copy để viết fixture khớp props/hook nó dùng.)
- [ ] **Step 2: Chạy fail** → **Step 3: Copy 5 component + adapt import** (quy tắc: `@/lib/utils`→`@/shared/lib/utils`, hooks→relative `../../hooks`, ui/shared→`@/shared/ui`). **Step 4: Page** — port nội dung `dashboard/system/page.tsx` (bỏ `useUniverse` nếu có, không cần universe), heading "Hệ thống".
- [ ] **Step 5: Full test + check + commit** — `git add frontend/src/features/admin frontend/src/shared/ui "frontend/src/app/(ops)/ops/system" && git commit -m "feat(fe): ops system — settings grid + service health + system info"`

---

### Task 7: Nâng cấp `features/narrative-runtime` (nền cho loom + settings)

**Files:**
- Modify: `frontend/src/features/narrative-runtime/api/queries.ts` (63) + `frontend/src/features/narrative-runtime/hooks/*` (46+42)
- Modify: `frontend/src/features/narrative-runtime/useNarrativeRuntime.ts` (554 — CHỈ swap imports + signature, GIỮ NGUYÊN logic)
- Create: `frontend/src/features/narrative-runtime/hooks/useCentrifugoConnection.ts` (port từ `src/hooks/useCentrifugo.ts` phần connection-state)
- Create: `frontend/src/features/narrative-runtime/index.ts`
- Test: `frontend/src/features/narrative-runtime/__tests__/useLoomStatus.test.tsx`

**Interfaces:**
- Consumes: endpoints `/loom-status`, `/loom-tasks/{id}/status`, `/loom/pipeline-manifest`, `POST /worldos/universes/{id}/generate-chronicle`; `qk.loomStatus/loomTask` (Task 1); `getCentrifuge` từ `@/shared/lib/centrifugo`.
- Produces (public API `@/features/narrative-runtime`): `useNarrativeRuntime(universeId: number | null)` — **signature MỚI** nhận universeId thay vì đọc `useUniverse()` Context (mọi chỗ khác giữ nguyên: return shape, session localStorage, polling fallback, kênh `narrative:{worldId}:{taskId}`); `useLoomStatus`, `useLoomTaskStatus`, `useGenerateChronicle`; types + `NARRATIVE_PIPELINE_NODES` (settings dùng).
- Quy tắc swap trong `useNarrativeRuntime.ts`: `import { useUniverse } from '@/contexts/UniverseContext'` → XÓA, thay bằng param; `@/lib/centrifugo` → `@/shared/lib/centrifugo`; `useCentrifugoConnection` từ `@/hooks/useCentrifugo` → `./hooks/useCentrifugoConnection` (bản port: copy đúng phần theo dõi trạng thái kết nối của file cũ — đọc `src/hooks/useCentrifugo.ts` 87 dòng, lấy hook connection, đổi import factory sang shared; KHÔNG port `useAdaptiveRefetchInterval`).

- [ ] **Step 1: Test fail** — `useLoomStatus.test.tsx` (mock apiClient): assert gọi `/loom-status` + trả data. 
- [ ] **Step 2: Chạy fail** → **Step 3: Rewrite queries/hooks nhỏ** (công thức P3). **Step 4: Port `useCentrifugoConnection`** (file mới ~30 dòng theo bản cũ). **Step 5: Swap `useNarrativeRuntime.ts`** theo quy tắc trên — diff phải CHỈ gồm imports + dòng lấy universeId (`const { activeUniverseId } = useUniverse()` → dùng param) — mọi logic khác nguyên vẹn (reviewer đối chiếu). `index.ts` export theo Produces.
- [ ] **Step 6: Full test + check + commit** — LƯU Ý: `narrative-studio` + `loom-workshop` cũ import `useNarrativeRuntime` (signature đổi!) → sửa call-site cũ TỐI THIỂU: truyền `useUniverse().activeUniverseId` tại chỗ gọi (2-3 file, vẫn compile, chết ở Task 12). Ghi rõ các call-site đã chạm vào report.

```bash
git add frontend/src/features/narrative-runtime frontend/src/app frontend/src/components
git commit -m "refactor(fe): narrative-runtime sang apiClient + universeId param — go Context/legacy realtime"
```

---

### Task 8: Ops Loom — port workshop/monitor + page `/ops/loom`

**Files:**
- Copy: `frontend/src/components/dashboard/loom/{RunTab,ReviewTab,LoomMonitor}.tsx` (+ file thứ 4 trong thư mục — đọc `ls` khi làm) → `frontend/src/features/narrative-runtime/components/`
- Copy: `frontend/src/app/dashboard/loom-workshop/sections/{ActorIntentTab,ScribeTab,AssetForgeTab,SystemTab}.tsx` → `frontend/src/features/narrative-runtime/components/sections/`
- Copy: `frontend/src/components/dashboard/loom-workshop/asset-forge/*.tsx` (3 file) → `frontend/src/features/narrative-runtime/components/asset-forge/`
- Create: `frontend/src/features/narrative-runtime/components/LoomOps.tsx`
- Create: `frontend/src/app/(ops)/ops/loom/page.tsx`
- Modify: `frontend/src/features/narrative-runtime/index.ts`
- Test: `frontend/src/features/narrative-runtime/__tests__/LoomOps.test.tsx`

**Interfaces:**
- Consumes: `useNarrativeRuntime(universeId)` (Task 7), `UniverseSelect` + `simStore.selectedUniverseId` (Task 2).
- Produces: `LoomOps({ universeId })` — tab nội bộ: Run | Review | Monitor | Actor Intent | Scribe | Asset Forge | System (khung tab lấy theo `loom-workshop/page.tsx` cũ 89 dòng + trang `loom-monitor` cũ, gộp Monitor thành 1 tab); page `/ops/loom` render `UniverseSelect` + `LoomOps`.
- Quy tắc port: 4 sections cũ import `@/lib/api` TRỰC TIẾP → đổi sang `apiClient` (+`takeData` nếu list); mọi `useUniverse()` → prop `universeId`; `@/lib/utils` → `@/shared/lib/utils`; `ui/shared` → copy sang `@/shared/ui` (tái dùng bản đã copy). GIỮ NGUYÊN logic/JSX từng tab.

- [ ] **Step 1: Test fail** — `LoomOps.test.tsx`: mock `../useNarrativeRuntime` (module) trả state idle tối thiểu → render `LoomOps universeId={1}` → assert các tab hiển thị (`getByRole('tab'|'button', ...)` theo khung tab thật của page cũ) + đổi tab hoạt động; case `universeId=null` → empty note "Chọn một universe để dệt biên niên sử."
- [ ] **Step 2: Chạy fail** → **Step 3: Copy + adapt theo quy tắc** (từng file — liệt kê adapt trong report) → **Step 4: `LoomOps` + page** (page giống mẫu Task 4 Step 5: heading "Narrative Loom" + `UniverseSelect` + `LoomOps universeId từ simStore`). `index.ts` export thêm `LoomOps`.
- [ ] **Step 5: Full test + check + commit**

```bash
git add frontend/src/features/narrative-runtime frontend/src/shared/ui "frontend/src/app/(ops)/ops/loom"
git commit -m "feat(fe): ops loom — port workshop tabs + monitor vao /ops/loom"
```
Gate: loom-workshop/narrative-studio cũ vẫn compile (bản gốc components chưa xóa).

---

### Task 9: Ops Settings — port ai-settings + page `/ops/settings`

**Files:**
- Copy: `frontend/src/components/dashboard/ai-settings/*.tsx` (6 file: `RoutingTab`, `ParamsTab`, `EpistemicTab`, `ModelSelect`, `Slider`, `types.ts`) → `frontend/src/features/admin/components/ai-settings/`
- Create: `frontend/src/app/(ops)/ops/settings/page.tsx`
- Modify: `frontend/src/features/admin/index.ts`
- Test: `frontend/src/features/admin/__tests__/RoutingTab.test.tsx`

**Interfaces:**
- Consumes: hooks `features/admin` (Task 5: `/ai-settings` CRUD) + `NARRATIVE_PIPELINE_NODES` từ `@/features/narrative-runtime` (Task 7 — thay vì `useNarrativeRuntime` nếu trang cũ chỉ cần danh sách node tĩnh; đọc `dashboard/config/ai-settings/page.tsx` 127 dòng để xác định chính xác nó lấy gì từ runtime — nếu cần state runtime thật thì dùng `useNarrativeRuntime(universeId)` với universeId từ simStore).
- Produces: page `/ops/settings` render 3 tab Routing/Params/Epistemic tương đương trang cũ.

- [ ] **Step 1: Test fail** — `RoutingTab.test.tsx`: mock hooks module admin → render RoutingTab với fixture agent routing tối thiểu (đọc component khi copy để viết fixture khớp) → assert render danh sách agent + đổi model gọi mutation mock.
- [ ] **Step 2: Chạy fail** → **Step 3: Copy 6 file + adapt** (quy tắc chung) → **Step 4: Page** port khung `config/ai-settings/page.tsx`. Export cần thiết qua `index.ts`.
- [ ] **Step 5: Full test + check + commit** — `git commit -m "feat(fe): ops settings — routing/params/epistemic per-agent"`

---

### Task 10: Ops Intelligence — nâng cấp `features/intelligence` + port monitor + page `/ops/intelligence`

**Files:**
- Modify: `frontend/src/features/intelligence/api/queries.ts` (44) + `hooks/index.ts` (88) — swap sang `apiClient`+`takeData`+`qk.aiLogs/aiStats`, bỏ `useCentrifugo` (interval cố định); giữ tên/shape hook
- Create: `frontend/src/features/intelligence/lib/log-utils.ts` (copy VERBATIM từ `src/lib/log-utils.ts`, đổi import type `@/types/api` → `@/shared/types/api`)
- Copy: `frontend/src/components/dashboard/intelligence/{StatsBento,LogFilters,LogTable,SynthesisTicker}.tsx` + `frontend/src/components/ui/intelligence/*.tsx` (2 file, gồm `LogDetailModal`) → `frontend/src/features/intelligence/components/`
- Create: `frontend/src/features/intelligence/components/IntelligenceOps.tsx`
- Create: `frontend/src/features/intelligence/index.ts`
- Create: `frontend/src/app/(ops)/ops/intelligence/page.tsx`
- Test: `frontend/src/features/intelligence/__tests__/useAiLogs.test.tsx`

**Interfaces:**
- Consumes: `/ai-logs` (paginated — response có `meta`: giữ nguyên cách hook cũ đọc, KHÔNG để interceptor/takeData nuốt meta; nếu body 2 key `{data, meta}` thì interceptor apiClient KHÔNG bóc — đọc nguyên body như feed P2), `/ai-logs/stats`, `/ai-logs/clear`, `/ai-settings` (useAiPool); `SynthesisTicker` cũ import `@/features/multiverse/hooks` — giữ (đã sạch từ P3).
- Produces: `IntelligenceOps()` compose StatsBento + LogFilters + LogTable + SynthesisTicker + LogDetailModal (khung theo `dashboard/intelligence/monitor/page.tsx` 154 dòng); page `/ops/intelligence`.

- [ ] **Step 1: Test fail** — `useAiLogs.test.tsx` (mock apiClient): assert gọi `/ai-logs` với params filter, trả về `{data, meta}` nguyên vẹn (khóa hợp đồng pagination).
- [ ] **Step 2: Chạy fail** → **Step 3: Rewrite queries/hooks + log-utils** → **Step 4: Copy components + adapt** (`@/lib/log-utils` → `../lib/log-utils`; quy tắc chung) → **Step 5: `IntelligenceOps` + page + index.ts**.
- [ ] **Step 6: Full test + check + commit** — `git commit -m "feat(fe): ops intelligence — ai logs/stats monitor vao /ops/intelligence"`

---

### Task 11: Ops AI Runtime — port + page `/ops/ai-runtime`

**Files:**
- Copy: `frontend/src/components/dashboard/ai-runtime/*.tsx` (9 file: `RuntimeCard`, `LoomAgentEditor`, `PoolRoutingPanel`, `DiagnosticsPanel`, `LoomAgentsPanel`, `ProviderModelsPanel`, `KeyPoolPanel`, `ProviderModelForm`, `ProviderModelCard`) → `frontend/src/features/admin/components/ai-runtime/`
- Copy: `frontend/src/components/ui/key-pool/*.tsx` (3 file) → `frontend/src/features/admin/components/key-pool/`
- Create: `frontend/src/features/admin/components/ai-runtime/AiRuntimeOps.tsx`
- Create: `frontend/src/app/(ops)/ops/ai-runtime/page.tsx`
- Modify: `frontend/src/features/admin/index.ts`
- Test: `frontend/src/features/admin/__tests__/ProviderModelsPanel.test.tsx`

**Interfaces:**
- Consumes: hooks `features/admin` (Task 5 — provider-models/key-pool/loom-agents/diagnostics CRUD).
- Produces: `AiRuntimeOps()` — port khung `dashboard/ai-runtime/page.tsx` (366 dòng) thành component trong feature (page mỏng chỉ render nó); page `/ops/ai-runtime`.
- Quy tắc port như các task trước. Đây là task port LỚN NHẤT về số file — làm tuần tự từng panel, chạy check thường xuyên.

- [ ] **Step 1: Test fail** — `ProviderModelsPanel.test.tsx`: mock hooks module → fixture 2 provider model → render, assert danh sách + nút thêm mở form (đọc component thật để viết đúng).
- [ ] **Step 2: Chạy fail** → **Step 3: Copy 12 file + adapt** → **Step 4: `AiRuntimeOps` (port ruột page cũ) + page + exports**.
- [ ] **Step 5: Full test + check + commit** — `git commit -m "feat(fe): ops ai-runtime — provider models/key pool/loom agents/diagnostics"`

---

### Task 12: Root landing mới + XÓA toàn bộ route cũ

**Files:**
- Rewrite: `frontend/src/app/page.tsx`
- Delete: `frontend/src/app/dashboard/` (toàn bộ), `frontend/src/app/narrative-studio/`, `frontend/src/app/narrative-cinema/`

**Interfaces:**
- Consumes: routes (Task 1), toàn bộ ops pages đã live (Task 4-11).
- Produces: `/` = landing 2 card (Đài quan sát → `routes.multiverse()`, Vận hành → `routes.opsSimulation()`); các URL `/dashboard/*`, `/narrative-studio`, `/narrative-cinema/*` trả 404 (có chủ đích — big-bang).

- [ ] **Step 1: Rewrite `app/page.tsx`**

```tsx
import Link from 'next/link';
import { routes } from '@/shared/config/routes';

const ENTRIES = [
  { href: routes.multiverse(), title: 'Đài quan sát', desc: 'Chòm sao đa vũ trụ, Living Chronicle, các lens quan sát nền văn minh.' },
  { href: routes.opsSimulation(), title: 'Vận hành', desc: 'Điều khiển mô phỏng, Narrative Loom, AI runtime, cấu hình hệ thống.' },
];

export default function HomePage() {
  return (
    <main className="flex min-h-screen flex-col items-center justify-center gap-8 bg-[var(--color-bg-base)] p-8 text-[var(--color-text-primary)]">
      <div className="text-center">
        <h1 className="text-glow-cyan font-mono text-2xl uppercase tracking-[0.4em]">WorldOS</h1>
        <p className="mt-2 text-sm text-[var(--color-text-muted)]">Đài quan sát vũ trụ sống</p>
      </div>
      <div className="grid w-full max-w-2xl grid-cols-1 gap-4 sm:grid-cols-2">
        {ENTRIES.map((e) => (
          <Link
            key={e.href}
            href={e.href}
            className="glass rounded-2xl border border-[var(--border-subtle)] p-6 transition duration-200 ease-out hover:-translate-y-0.5 hover:border-[var(--color-primary)] hover:shadow-[var(--shadow-glow-cyan)]"
          >
            <h2 className="font-medium">{e.title}</h2>
            <p className="mt-1 text-sm text-[var(--color-text-muted)]">{e.desc}</p>
          </Link>
        ))}
      </div>
    </main>
  );
}
```

- [ ] **Step 2: Xóa route cũ**

```bash
git rm -r frontend/src/app/dashboard frontend/src/app/narrative-studio frontend/src/app/narrative-cinema
```

- [ ] **Step 3: Gate — full test + check**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads 2>&1 | tail -4'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check 2>&1 | tail -4'
```
Expected: XANH — nếu check lỗi vì file components cũ import route đã xóa (không thể — components không import page) hoặc vì tsc quét dashboard đã xóa (đã xóa thì không quét); lỗi thực tế duy nhất có thể là file legacy còn import lẫn nhau — xử lý bằng cách đưa file lỗi vào danh sách xóa Task 13 sớm (ghi report), KHÔNG vá code chết.

- [ ] **Step 4: Commit** — `git commit -am "feat(fe)!: landing moi + xoa route dashboard/narrative-studio/narrative-cinema (big-bang)"`

---

### Task 13: XÓA components/contexts/hooks/lib cũ + test legacy

**Files:**
- Delete: `frontend/src/components/` (toàn bộ — Providers.tsx đã hết consumer từ Task 1), `frontend/src/contexts/`, `frontend/src/hooks/` (toàn bộ — gồm `useCentrifugo`, `useNarrativeRuntime` KHÔNG còn ở đây; xác nhận bằng ls), `frontend/src/lib/` (toàn bộ: api.ts, centrifugo.ts, log-utils.ts, utils.ts, api-routes.ts, query-client.ts + `__tests__`)
- Modify: `frontend/src/app/providers.tsx` (nếu Task 1 đã giữ tạm AuthProvider — gỡ ra)

**Interfaces:**
- Consumes: mọi consumer đã port (Task 1-12).
- Produces: cây `src/` chỉ còn `app/`, `features/`, `shared/`, `test/`. Baseline test FE MỚI được ghi nhận.

- [ ] **Step 1: Verify 0 consumer trước khi xóa**

```bash
grep -rn "@/lib/\|@/hooks/\|@/contexts/\|@/components/" frontend/src/app frontend/src/features frontend/src/shared --include='*.ts' --include='*.tsx' | grep -v "__tests__" | grep -v "@/lib/vaf"
```
Expected: **0 kết quả** (nếu còn → đó là consumer sót, port nốt theo quy tắc chung TRƯỚC khi xóa; ghi report). Kiểm tra thêm test files: `grep -rn "@/lib/\|@/hooks/\|@/contexts/\|@/components/" frontend/src --include='*.test.*'` — test legacy nào match sẽ bị xóa cùng.

- [ ] **Step 2: Xóa**

```bash
git rm -r frontend/src/components frontend/src/contexts frontend/src/hooks frontend/src/lib
```

- [ ] **Step 3: Gate — full test + check + ghi baseline mới**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads 2>&1 | tail -5'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check 2>&1 | tail -5'
```
Expected: toàn bộ pass; check **0 error / 0 warning** (2 warning pre-existing nằm trong `src/lib/__tests__/centrifugo.test.ts` — chết cùng file). GHI SỐ file/test mới vào report (ước tính: mất 21 test legacy, còn lại = số sau Task 12 − 21).

- [ ] **Step 4: Commit** — `git commit -am "chore(fe)!: thanh ly toan bo kien truc cu — components/contexts/hooks/lib (big-bang P4)"`

---

### Task 14: Mở rộng guardrail ESLint toàn `src/app/**` + cấm legacy paths

**Files:**
- Modify: `frontend/eslint.config.mjs`

**Interfaces:**
- Consumes: cây sạch sau Task 13.
- Produces: block `no-restricted-imports` áp cho `["src/shared/**/*.{ts,tsx}", "src/features/**/*.{ts,tsx}", "src/app/**/*.{ts,tsx}"]` (thay 3 glob cũ trong đó 2 group-specific); patterns THÊM (giữ 2 pattern hiện có):

```js
{ group: ["@/lib/*", "@/hooks/*", "@/contexts/*", "@/components/*", "@/types/*"], message: "Legacy architecture đã xóa ở P4 — dùng @/shared/* hoặc @/features/*." },
```

- [ ] **Step 1: Sửa config** như Produces.
- [ ] **Step 2: Negative-test guardrail** — tạm thêm `import '@/lib/api';` vào 1 file trong `src/app/`, chạy `npm run lint` → PHẢI báo lỗi restricted; revert dòng đó. Ghi output vào report.
- [ ] **Step 3: Full check + test + commit**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check 2>&1 | tail -4'
git add frontend/eslint.config.mjs && git commit -m "chore(fe): guardrail ESLint phu toan src/app + cam import legacy paths"
```

---

### Task 15: BE — xóa dead code + sửa 2 `Event::fake` + xóa route `chronicles/raw`

**Files:**
- Delete: `backend/app/Modules/Simulation/Events/PowerSystemTransitionTriggered.php`, `backend/app/Modules/Simulation/Listeners/HandlePowerSystemTransition.php`
- Modify: `backend/app/Modules/Simulation/Providers/SimulationServiceProvider.php` (bỏ khối `Event::listen(PowerSystemTransitionTriggered...)` — dòng ~24-27)
- Có thể Delete: `backend/app/Modules/Simulation/Services/Transition/` (CHỈ nếu kiểm chứng listener là consumer duy nhất)
- Modify: `backend/tests/Feature/WorldosSimulationTest.php:25`, `backend/tests/Feature/SimulationPulseOrderTest.php:66`
- Modify: `backend/app/Modules/WorldOS/routes/api.php:94` (xóa route `chronicles/raw`)

**Interfaces:**
- Consumes: kết quả khảo sát (đã xác minh khi viết plan): event KHÔNG có nơi dispatch; 2 test fake class không tồn tại `App\Events\Simulation\UniverseSimulationPulsed` (đúng: `App\Modules\Simulation\Events\UniverseSimulationPulsed`); `chronicles/raw` 0 consumer FE.
- Produces: cây Simulation không còn dead event; 2 test suppress broadcast đúng; route surface gọn.

- [ ] **Step 1: Kiểm chứng lại trước khi xóa** (dispatch có thể xuất hiện sau khảo sát):

```bash
grep -rn "PowerSystemTransitionTriggered" backend/app backend/tests | grep -v "Events/PowerSystemTransitionTriggered.php" | grep -v "Listeners/HandlePowerSystemTransition.php"
grep -rn "Services\\\\Transition\|Services/Transition" backend/app backend/tests | grep -v "Modules/Simulation/Services/Transition/"
grep -rn "getChronicles\|chronicles/raw" backend/app backend/tests frontend/src
```
Expected: nhóm 1 chỉ còn SimulationServiceProvider; nhóm 2 chỉ còn listener (nếu có chỗ khác dùng Transition → GIỮ cây Transition, chỉ xóa event+listener); nhóm 3 chỉ còn route + `TimelineController::getChronicles` (nếu có caller khác của method → giữ method, chỉ xóa route).

- [ ] **Step 2: Sửa 2 test** — cả 2 dòng đổi thành:

```php
Event::fake([\App\Modules\Simulation\Events\UniverseSimulationPulsed::class]);
```

- [ ] **Step 3: Xóa event + listener + đăng ký provider + route** (theo kết quả Step 1). Method `getChronicles` trong TimelineController: xóa nếu 0 caller còn lại.

- [ ] **Step 4: Test + pint + commit**

```bash
incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --filter="WorldosSimulationTest|SimulationPulseOrderTest" 2>&1 | tail -6'
incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --testsuite=Unit 2>&1 | tail -4'
incus exec worldos-dev -- sh -c 'cd /work/backend && vendor/bin/pint app/Modules/Simulation tests/Feature/WorldosSimulationTest.php tests/Feature/SimulationPulseOrderTest.php app/Modules/WorldOS'
git add -A backend && git commit -m "chore(be): xoa dead event PowerSystemTransition + sua Event::fake namespace + bo route chronicles/raw"
```
Expected: 2 test Feature pass (nếu chúng thuộc nhóm 91 fail pre-existing thì ghi nhận trạng thái trước/sau — KHÔNG được tệ hơn trước); Unit ≥ baseline 170/92.

---

### Task 16: BE — Centrifugo auth() phủ kênh hệ thống + document insecure

**Files:**
- Modify: `backend/app/Broadcasting/CentrifugoBroadcaster.php` (method `auth()`, dòng ~36-60)
- Modify: `backend/tests/Feature/Broadcasting/CentrifugoChannelAuthTest.php` (thêm case)
- Modify: `deployment/centrifugo/config.json` (CHỈ thêm key `_readme` document — KHÔNG đổi `client.insecure`)

**Interfaces:**
- Consumes: kênh thực broadcast: `global_universe` (SoundtrackChanged), `narrative:{worldId}:{taskId}` (narrative-loom Python publish); auth() hiện chỉ cover `public:*` + `universes:{id}[:lens]`.
- Produces: `auth()` chấp nhận thêm `global_universe` và `narrative:{số}:{token}`; test khóa hành vi; config có ghi chú vận hành.

- [ ] **Step 1: Viết test fail** — thêm vào `CentrifugoChannelAuthTest`:

```php
public function test_auth_allows_global_universe_channel(): void
{
    $this->assertTrue($this->broadcaster()->auth($this->requestFor('global_universe')));
}

public function test_auth_allows_narrative_task_channels(): void
{
    $this->assertTrue($this->broadcaster()->auth($this->requestFor('narrative:12:task-abc_123')));
    $this->assertFalse($this->broadcaster()->auth($this->requestFor('narrative:abc')));
}
```
(Đọc file test hiện có để tái dùng đúng helper dựng broadcaster/request — nếu tên helper khác `broadcaster()`/`requestFor()` thì theo file; 4 test hiện có là mẫu.)

- [ ] **Step 2: Chạy fail** — `php artisan test --filter=CentrifugoChannelAuthTest` → 2 case mới FAIL (auth trả false).

- [ ] **Step 3: Mở rộng `auth()`** — thêm TRƯỚC nhánh `return false` cuối:

```php
// Kênh hệ thống: soundtrack toàn cục (SoundtrackChanged) — read-only, cho phép mọi client đã kết nối.
if ($channel === 'global_universe') {
    return true;
}

// Kênh task tường thuật: narrative:{worldId}:{taskId} — publish từ narrative-loom, client chỉ nghe.
if (preg_match('/^narrative:\d+:[A-Za-z0-9_-]+$/', $channel)) {
    return true;
}
```

- [ ] **Step 4: Document config** — trong `deployment/centrifugo/config.json` thêm key mức root:

```json
"_readme": "client.insecure=true + không có subscribe_proxy: auth() phía Laravel CHƯA được gọi ở runtime. Khi hardening: bật client.subscribe_proxy trỏ /api/worldos/centrifugo (xem CentrifugoBroadcaster::auth — đã cover public:*, universes:{id}[:lens], global_universe, narrative:{world}:{task}), rồi đặt client.insecure=false. Chỉ làm khi có stack chạy để verify."
```
(JSON không có comment — key `_readme` là quy ước vô hại; Centrifugo bỏ qua key lạ ở root. Nếu Centrifugo strict-validate và từ chối key lạ — chuyển nội dung sang file mới `deployment/centrifugo/README.md` thay thế, ghi report.)

- [ ] **Step 5: Test pass + pint + commit**

```bash
incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --filter=CentrifugoChannelAuthTest 2>&1 | tail -5'
incus exec worldos-dev -- sh -c 'cd /work/backend && vendor/bin/pint app/Broadcasting/CentrifugoBroadcaster.php tests/Feature/Broadcasting/CentrifugoChannelAuthTest.php'
git add backend/app/Broadcasting backend/tests/Feature/Broadcasting deployment/centrifugo
git commit -m "feat(be): centrifugo auth phu global_universe + narrative:* — san sang bat subscribe proxy"
```

---

### Task 17: Gate cuối toàn plan + cập nhật docs

**Files:**
- Modify: `CLAUDE.md` (mục Frontend trong Architecture + Documentation), `.dev_status.md` (Session mới), `AGENTS.md` (nếu có mục frontend architecture — grep trước)

**Interfaces:**
- Consumes: Task 1-16 hoàn thành.
- Produces: bằng chứng test thật; docs khớp kiến trúc mới; danh sách tồn (nếu có) cho hậu-P4.

- [ ] **Step 1: FE full + smoke**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- --pool=threads 2>&1 | tail -6'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check 2>&1 | tail -6'
```
Smoke qua dev server sẵn có port 5000 (KHÔNG start mới; treo → SKIP + lý do): `/`, `/multiverse`, `/u/1`, `/chronicle/1`, `/ops/simulation`, `/ops/loom`, `/ops/ai-runtime`, `/ops/settings`, `/ops/system`, `/ops/intelligence` — kỳ vọng 200; `/dashboard` — kỳ vọng 404. LƯU Ý: dev server chạy từ trước có thể giữ cache route đã xóa — nếu kết quả bất thường, ghi nhận cần `incus restart worldos-dev` + start lại dev server để smoke sạch (làm nếu user cho phép restart, không thì SKIP).

- [ ] **Step 2: BE full**

```bash
incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --testsuite=Unit 2>&1 | tail -4'
incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --filter="Observatory|WorldEventBroadcastContractTest|CentrifugoChannelAuthTest" 2>&1 | tail -6'
```
Expected: Unit ≥ 170 pass/92 skip (flake IntelligenceExplosion được phép); nhóm Observatory + contract + centrifugo auth pass hết.

- [ ] **Step 3: Cập nhật `CLAUDE.md`** — mục "### Frontend" trong Architecture: thay mô tả dashboard/tabs cũ bằng kiến trúc thật sau P4:
  - Route groups: `(observatory)` (`/multiverse`, `/u/[id]` + lens `actors|civilization|causality|wavefunction` — AuthGate), `(cinema)` (`/chronicle/[id]` — public), `(ops)` (`/ops/simulation|loom|ai-runtime|settings|system|intelligence` — AuthGate), `/login`, `/` landing.
  - Layering: `app → features (@/features/<name> qua index.ts) → shared`; guardrail ESLint phủ toàn `src/app/**` + cấm `@/lib|@/hooks|@/contexts|@/components|@/types`.
  - Data: TanStack Query qua `@/shared/lib/apiClient` (+`takeData`), realtime qua `@/shared/realtime/useUniverseChannels` (envelope P1), stores zustand `simStore`/`feedStore`.
  - XÓA các dòng nói về `components/dashboard/tabs`, `DashboardShell`, `frontend/src/hooks/`, `src/lib/centrifugo.ts|api.ts|log-utils.ts` trong "Shared utilities" — thay bằng path shared mới.
  - Mục "Workspace architecture (newer code)": viết lại thành mô tả hiện hành (không còn "newer" — đây là kiến trúc duy nhất).
  `AGENTS.md`: grep các path cũ (`components/dashboard`, `src/hooks`, `src/lib/api`) — nếu có, cập nhật tương tự (nếu file quá lớn, chỉ sửa các câu sai thực tế, ghi report).

- [ ] **Step 4: Cập nhật `.dev_status.md`** — Session mới "Observatory P4 — Ops Port + Thanh Lý" trên cùng: task hoàn thành, số liệu verify THẬT (FE trước/sau xóa, BE), quyết định đã chốt (6 trang ops; xóa achievements/timeline — endpoint BE còn, dựng lại được; insecure Centrifugo giữ nguyên chờ stack), tồn hậu-P4 (route BE nghi vấn: `history-timeline`, `analytics/ticks`, `worlds/pulse`, `test-weave` TODO bảo mật; build + hydration verify ở CI/Docker; bật subscribe proxy khi có stack). Cập nhật "Last Updated" + "Tiếp theo" (roadmap Observatory HOÀN TẤT — 4/4 plan).

- [ ] **Step 5: Commit cuối**

```bash
git add CLAUDE.md AGENTS.md .dev_status.md
git commit -m "docs: hoan thanh Observatory Plan 4 — ops port + thanh ly big-bang, cap nhat kien truc FE"
```

Sau đó dùng skill `superpowers:finishing-a-development-branch` (tiền lệ: merge `feature/observatory-p4` vào `main` bằng `--no-ff`, rồi push nếu user yêu cầu).

---

## Ghi chú thực thi

- **Branch:** tạo `feature/observatory-p4` từ `main` trước Task 1.
- **Thứ tự phụ thuộc:** 1 → 2 → 3 → {4, 5} → 6/9/11 (cần 5), 7 → 8 (loom), 9 (cần 5+7), 10 độc lập sau 3 → 12 (cần 4-11) → 13 → 14. Task 15-16 (BE) độc lập, chạy xen kẽ lúc nào cũng được. 17 cuối cùng.
- **Không chạy 2 implementer song song** (nhiều task đụng chung `features/admin`, `shared/ui`, `index.ts`).
- **Reviewer mỗi task port** đối chiếu: (a) component copy giữ nguyên logic (diff vs bản gốc — chỉ import/props đổi), (b) trang dashboard cũ còn compile cho tới Task 12, (c) không import legacy trong code mới, (d) test không giảm ngoài kế hoạch.
- Task UI (2, 4, 6, 8, 9, 10, 11, 12): implementer đọc skill `frontend-design` trước khi viết page/shell mới.
- Sau merge P4: chương trình Observatory HOÀN TẤT 4/4 plan — cập nhật memory roadmap.

