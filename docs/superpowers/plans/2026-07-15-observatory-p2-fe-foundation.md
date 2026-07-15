# Observatory Plan 2 — FE Nền Tảng (shell + landing + hero Living Chronicle) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Xây nền frontend Observatory trên kiến trúc mới: parser envelope + hook realtime đa kênh, route group `(observatory)` với landing chọn universe và hero **Living Chronicle** tiêu thụ feed + 4 kênh Centrifugo của Plan 1.

**Architecture:** Mọi thứ đi qua hợp đồng P1: `shared/realtime/envelope.ts` là parser duy nhất; `useUniverseChannels` subscribe `universes:{id}` (+`:narrative`/`:anomaly`/`:autopoiesis`), route pulse → `simStore`, sự kiện tường thuật → `feedStore`; `features/chronicle` gộp live (`feedStore`) + lịch sử (infinite query trên `observatory/feed`, cursor `next_before_tick`) dedup theo `id` envelope. Route group `(workspace)` được ĐỔI TÊN thành `(observatory)` (route group không namespace URL — không thể tồn tại song song).

**Tech Stack:** Next.js 16 App Router, React 19, TanStack React Query v5 (`useInfiniteQuery`), zustand, centrifuge-js, Tailwind v4 (token trong `globals.css`), Vitest + Testing Library (jsdom).

## Global Constraints

- **KHÔNG chạy `npm` trên host.** Node/npm nằm trong Incus container `worldos-dev` (cài ở Task 1). Mọi lệnh FE: `incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test'` (một file: `npm test -- <đường-dẫn-file-test>`; typecheck+lint: `npm run check`).
- **Hợp đồng P1 (đã merge, KHÔNG sửa backend trong plan này)** — nguồn: `docs/superpowers/plans/2026-07-15-observatory-p1-backend-realtime.md` mục "Quy ước kênh & envelope":
  - Kênh: `universes:{id}` (universe.pulsed, epoch.transitioned, simulation.event), `universes:{id}:narrative` (artifact.discovered, celebrity.emerged, history.shifted, chronicle.generated), `universes:{id}:anomaly` (anomaly.detected), `universes:{id}:autopoiesis` (autopoiesis.mutation).
  - Envelope: `{id: string, type: string, tick: number, universe_id: number, world_id: number|null, severity: 'info'|'notable'|'critical', occurred_at: string ISO8601, payload: object}`. Payload pulse: `{entropy, stability_index, metrics}`.
  - Feed: `GET /api/worldos/observatory/universes/{id}/feed?after_tick=&before_tick=&types=&limit=` → `{data: FeedItem[], meta: {count, next_before_tick}}`; item `{id, kind:'event'|'chronicle', type, tick, universe_id, severity, occurred_at, payload}`; DESC theo tick; trang trọn-tick-biên (có thể vượt nhẹ limit); `next_before_tick=null` = HẾT; trang kế `before_tick=<giá trị>`; dedup theo `id` (`id` ổn định giữa broadcast và persist). Body có 2 key (`data`+`meta`) nên interceptor `unwrapEnvelope` KHÔNG bóc — đọc nguyên body.
- Layering ESLint: app → features → shared; feature chỉ import qua `@/features/<name>` (index.ts); shared KHÔNG import từ features.
- File mới: TypeScript strict, functional components, PascalCase component. Test đặt trong `__tests__/` cạnh code (pattern hiện có). KHÔNG thêm dependency mới (mọi lib cần đều đã có).
- KHÔNG đụng code legacy (`src/components/`, `src/hooks/`, `src/lib/`, `src/contexts/`, `src/app/dashboard/`, `src/app/narrative-*`) — thanh lý ở Plan 4. Chỉ đụng `src/shared/`, `src/features/`, `src/app/(workspace)`→`(observatory)`, `src/test/`, `eslint.config.mjs`.
- Thẩm mỹ: dùng token sẵn trong `src/app/globals.css` (dark-only; `--color-bg-base/surface/elevated`, `--color-primary` cyan `#6EE7F7`, `--color-accent` violet, `--color-danger`, utility `.glass`, `.text-glow-cyan`, `.custom-scrollbar`, `.animate-fade-in-up`; font Space Grotesk / JetBrains Mono). Task UI (6, 7, 8): implementer NÊN đọc skill `frontend-design` trước khi viết component.
- Baseline test FE đo ở Task 1 — các task sau không được tạo fail mới.

## Quyết định phạm vi (chốt khi viết plan)

- **Landing multiverse P2 = lưới thẻ universe** (tên, trạng thái, tick, link vào hero) dùng `useUniverses` sẵn có. "Chòm sao bloom/resonance" 3D dời sang P3 (lens multiverse) — hero là ưu tiên.
- Hero side panel P2 = sparkline entropy/stability từ lịch sử pulse in-session + đồng hồ tick/epoch ở shell. "Actor nổi bật" dời sang P3 (lens actors).
- Lọc sự kiện theo loại/severity ở hero: **dời sang P3** (khi có lenses và volume sự kiện thật để thiết kế filter đúng). P2 hiển thị tất cả.
- Bấm một chronicle → mở cinema (VAF player): **dời sang P3** cùng với cinema port. P2 chronicle entry chỉ hiển thị prose.
- `ModeSwitcher` (LIVE/REPLAY/MULTIVERSE) bị xóa — REPLAY không tồn tại; nav mới tối giản trong shell. `view/setMode/replayTick` trong simStore là dead code → xóa luôn.

---

### Task 1: Môi trường Node trong worldos-dev + baseline FE

**Files:** không đổi code repo (chỉ môi trường + ghi nhận baseline).

**Interfaces:**
- Consumes: container `worldos-dev` (đã có, project mount `/work`).
- Produces: Node 22 + `npm` chạy được trong container; `node_modules` cài từ `package-lock.json`; số liệu baseline `npm test` và `npm run check` ghi vào report.

- [ ] **Step 1: Cài Node 22 (NodeSource) trong container**

```bash
incus exec worldos-dev -- sh -c 'apt-get install -yq curl >/dev/null 2>&1; curl -fsSL https://deb.nodesource.com/setup_22.x | bash - >/dev/null 2>&1 && DEBIAN_FRONTEND=noninteractive apt-get install -yq nodejs >/dev/null 2>&1 && node -v && npm -v'
```
Expected: in ra `v22.x` và version npm.

- [ ] **Step 2: Cài dependencies**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm ci 2>&1 | tail -3'
```
Expected: cài xong không lỗi (cảnh báo deprecated được phép).

- [ ] **Step 3: Chạy baseline test + check**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test 2>&1 | tail -5'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check 2>&1 | tail -5'
```
Expected: ghi lại chính xác số test pass/fail và kết quả check vào report. Nếu baseline có fail/lỗi pre-existing → GHI RÕ danh sách (các task sau so với baseline này), KHÔNG tự sửa ngoài phạm vi.

- [ ] **Step 4: Commit** — không có thay đổi code thì bỏ qua commit; chỉ viết report.

---

### Task 2: Envelope parser + kiểu FeedItem (shared)

**Files:**
- Create: `frontend/src/shared/realtime/envelope.ts`
- Modify: `frontend/src/shared/types/domain.ts` (nới `LiveMetrics`, thêm `MetricPoint`; giữ nguyên các type khác)
- Test: `frontend/src/shared/realtime/__tests__/envelope.test.ts`

**Interfaces:**
- Consumes: không có.
- Produces (các task sau dùng đúng tên này):
  - `type EnvelopeSeverity = 'info' | 'notable' | 'critical'`
  - `interface WorldEventEnvelope { id: string; type: string; tick: number; universe_id: number; world_id: number | null; severity: EnvelopeSeverity; occurred_at: string; payload: Record<string, unknown> }`
  - `interface FeedItem { id: string; kind: 'event' | 'chronicle'; type: string; tick: number; universe_id: number; severity: EnvelopeSeverity; occurred_at: string; payload: Record<string, unknown> }`
  - `parseEnvelope(data: unknown): WorldEventEnvelope | null`
  - `envelopeToFeedItem(env: WorldEventEnvelope): FeedItem`
  - `domain.ts`: `LiveMetrics` thành `{ entropy?: number; stability?: number; [key: string]: number | undefined }`; thêm `interface MetricPoint { tick: number; entropy: number | null; stability: number | null }`.

- [ ] **Step 1: Viết test fail**

```ts
import { describe, expect, it } from 'vitest';
import { parseEnvelope, envelopeToFeedItem } from '../envelope';

const valid = {
  id: 'uuid-1', type: 'epoch.transitioned', tick: 120, universe_id: 5,
  world_id: 3, severity: 'notable', occurred_at: '2026-07-15T00:00:00+00:00',
  payload: { old_epoch: { name: 'Bronze' } },
};

describe('parseEnvelope', () => {
  it('chấp nhận envelope hợp lệ và giữ nguyên field', () => {
    const env = parseEnvelope(valid);
    expect(env).not.toBeNull();
    expect(env!.type).toBe('epoch.transitioned');
    expect(env!.tick).toBe(120);
    expect(env!.world_id).toBe(3);
    expect(env!.severity).toBe('notable');
    expect(env!.payload).toEqual({ old_epoch: { name: 'Bronze' } });
  });

  it('trả null khi thiếu field bắt buộc hoặc sai kiểu', () => {
    expect(parseEnvelope(null)).toBeNull();
    expect(parseEnvelope('x')).toBeNull();
    expect(parseEnvelope({ ...valid, id: 7 })).toBeNull();
    expect(parseEnvelope({ ...valid, tick: 'abc' })).toBeNull();
    expect(parseEnvelope({ ...valid, universe_id: undefined })).toBeNull();
  });

  it('chuẩn hóa field lỏng: severity lạ → info, world_id thiếu → null, payload thiếu → {}', () => {
    const env = parseEnvelope({ ...valid, severity: 'WEIRD', world_id: undefined, payload: undefined });
    expect(env!.severity).toBe('info');
    expect(env!.world_id).toBeNull();
    expect(env!.payload).toEqual({});
  });
});

describe('envelopeToFeedItem', () => {
  it('chuyển envelope thành FeedItem kind=event', () => {
    const item = envelopeToFeedItem(parseEnvelope(valid)!);
    expect(item).toEqual({
      id: 'uuid-1', kind: 'event', type: 'epoch.transitioned', tick: 120,
      universe_id: 5, severity: 'notable',
      occurred_at: '2026-07-15T00:00:00+00:00', payload: { old_epoch: { name: 'Bronze' } },
    });
  });
});
```

- [ ] **Step 2: Chạy test, xác nhận fail**

Run: `incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- src/shared/realtime/__tests__/envelope.test.ts'`
Expected: FAIL — `Cannot find module '../envelope'`

- [ ] **Step 3: Implement**

`frontend/src/shared/realtime/envelope.ts`:

```ts
export type EnvelopeSeverity = 'info' | 'notable' | 'critical';

/** Hợp đồng broadcast P1 — mọi payload Centrifugo đều là envelope này. */
export interface WorldEventEnvelope {
  id: string;
  type: string;
  tick: number;
  universe_id: number;
  world_id: number | null;
  severity: EnvelopeSeverity;
  occurred_at: string;
  payload: Record<string, unknown>;
}

/** Một mục trong dòng biên niên sử — trùng shape item của observatory/feed. */
export interface FeedItem {
  id: string;
  kind: 'event' | 'chronicle';
  type: string;
  tick: number;
  universe_id: number;
  severity: EnvelopeSeverity;
  occurred_at: string;
  payload: Record<string, unknown>;
}

export function parseEnvelope(data: unknown): WorldEventEnvelope | null {
  if (!data || typeof data !== 'object' || Array.isArray(data)) return null;
  const d = data as Record<string, unknown>;
  if (typeof d.id !== 'string' || typeof d.type !== 'string') return null;
  if (typeof d.tick !== 'number' || typeof d.universe_id !== 'number') return null;
  const severity: EnvelopeSeverity =
    d.severity === 'notable' || d.severity === 'critical' ? d.severity : 'info';
  return {
    id: d.id,
    type: d.type,
    tick: d.tick,
    universe_id: d.universe_id,
    world_id: typeof d.world_id === 'number' ? d.world_id : null,
    severity,
    occurred_at: typeof d.occurred_at === 'string' ? d.occurred_at : '',
    payload:
      d.payload && typeof d.payload === 'object' && !Array.isArray(d.payload)
        ? (d.payload as Record<string, unknown>)
        : {},
  };
}

export function envelopeToFeedItem(env: WorldEventEnvelope): FeedItem {
  return {
    id: env.id,
    kind: 'event',
    type: env.type,
    tick: env.tick,
    universe_id: env.universe_id,
    severity: env.severity,
    occurred_at: env.occurred_at,
    payload: env.payload,
  };
}
```

Trong `frontend/src/shared/types/domain.ts` — thay định nghĩa `LiveMetrics` hiện tại:

```ts
// TRƯỚC:
export interface LiveMetrics { stability: number; entropy: number; era: number; [key: string]: number; }
// SAU:
export interface LiveMetrics { entropy?: number; stability?: number; [key: string]: number | undefined; }
export interface MetricPoint { tick: number; entropy: number | null; stability: number | null; }
```

(`SimEvent`, `Snapshot`, `Universe`, `UniverseStatus` giữ nguyên.)

- [ ] **Step 4: Chạy test, xác nhận pass**

Run: `incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- src/shared/realtime/__tests__/envelope.test.ts'`
Expected: PASS (4 tests). Chạy thêm `npm run check` — nếu chỗ khác vỡ vì `LiveMetrics` đổi (old `useUniverseChannel` dùng nó — sẽ bị thay ở Task 4), ghi nhận nhưng CHƯA sửa nếu thuộc file sẽ xóa ở Task 4; nếu `check` fail vì file sẽ bị xóa, được phép hoãn check xanh đến Task 4 và GHI RÕ trong report.

- [ ] **Step 5: Commit**

```bash
git add frontend/src/shared/realtime/ frontend/src/shared/types/domain.ts
git commit -m "feat(fe): P2 envelope parser + FeedItem — hợp đồng realtime P1"
```

---

### Task 3: simStore rework (applyPulse + history) + feedStore

**Files:**
- Modify: `frontend/src/shared/store/simStore.ts` (thay `applyTick` → `applyPulse`, thêm `history`, xóa `view/setMode/setReplayTick`)
- Create: `frontend/src/shared/store/feedStore.ts`
- Test: `frontend/src/shared/store/__tests__/simStore.test.ts` (sửa/thay test hiện có cho khớp store mới), `frontend/src/shared/store/__tests__/feedStore.test.ts`

**Interfaces:**
- Consumes: `WorldEventEnvelope`, `FeedItem` (Task 2); `LiveMetrics`, `MetricPoint` (domain).
- Produces:
  - `simStore`: `{ connection; selectedUniverseId; live: { tick: number; metrics: LiveMetrics | null; status: string | null; history: MetricPoint[] }; selectUniverse(id); setConnection(c); applyPulse(env: WorldEventEnvelope); reset() }`. `history` cap 120 điểm, cũ→mới.
  - `feedStore`: `useFeedStore` zustand `{ items: FeedItem[]; pushLive(item: FeedItem): void; clear(): void }` — prepend, dedup theo `id` (item trùng id bị bỏ qua), cap 300.
  - LƯU Ý: `ContextBar` hiện đọc `s.live.tick`, `s.live.status`, `s.connection`, `s.selectedUniverseId` — các field này PHẢI giữ nguyên tên.

- [ ] **Step 1: Viết test fail**

`feedStore.test.ts`:

```ts
import { beforeEach, describe, expect, it } from 'vitest';
import { useFeedStore } from '../feedStore';
import type { FeedItem } from '@/shared/realtime/envelope';

const item = (id: string, tick = 1): FeedItem => ({
  id, kind: 'event', type: 'anomaly.detected', tick, universe_id: 5,
  severity: 'critical', occurred_at: '2026-07-15T00:00:00+00:00', payload: {},
});

describe('feedStore', () => {
  beforeEach(() => useFeedStore.getState().clear());

  it('prepend item mới nhất lên đầu', () => {
    useFeedStore.getState().pushLive(item('a', 1));
    useFeedStore.getState().pushLive(item('b', 2));
    expect(useFeedStore.getState().items.map((i) => i.id)).toEqual(['b', 'a']);
  });

  it('dedup theo id — push trùng id bị bỏ qua', () => {
    useFeedStore.getState().pushLive(item('a'));
    useFeedStore.getState().pushLive(item('a'));
    expect(useFeedStore.getState().items).toHaveLength(1);
  });

  it('cap 300 item', () => {
    for (let i = 0; i < 310; i++) useFeedStore.getState().pushLive(item(`e${i}`, i));
    expect(useFeedStore.getState().items).toHaveLength(300);
    expect(useFeedStore.getState().items[0].id).toBe('e309');
  });
});
```

Sửa `simStore.test.ts` — thay toàn bộ nội dung bằng:

```ts
import { beforeEach, describe, expect, it } from 'vitest';
import { useSimStore } from '../simStore';
import type { WorldEventEnvelope } from '@/shared/realtime/envelope';

const pulse = (tick: number, entropy = 0.4, stability = 0.9): WorldEventEnvelope => ({
  id: `p${tick}`, type: 'universe.pulsed', tick, universe_id: 5, world_id: 3,
  severity: 'info', occurred_at: '2026-07-15T00:00:00+00:00',
  payload: { entropy, stability_index: stability, metrics: { population: 10 } },
});

describe('simStore', () => {
  beforeEach(() => useSimStore.getState().reset());

  it('applyPulse cập nhật tick, metrics và history', () => {
    useSimStore.getState().applyPulse(pulse(8));
    const { live } = useSimStore.getState();
    expect(live.tick).toBe(8);
    expect(live.metrics).toMatchObject({ entropy: 0.4, stability: 0.9, population: 10 });
    expect(live.history).toEqual([{ tick: 8, entropy: 0.4, stability: 0.9 }]);
  });

  it('history giữ tối đa 120 điểm, cũ → mới', () => {
    for (let t = 1; t <= 130; t++) useSimStore.getState().applyPulse(pulse(t));
    const { history } = useSimStore.getState().live;
    expect(history).toHaveLength(120);
    expect(history[0].tick).toBe(11);
    expect(history[119].tick).toBe(130);
  });

  it('selectUniverse reset live state', () => {
    useSimStore.getState().applyPulse(pulse(8));
    useSimStore.getState().selectUniverse(6);
    expect(useSimStore.getState().live.tick).toBe(0);
    expect(useSimStore.getState().live.history).toEqual([]);
    expect(useSimStore.getState().selectedUniverseId).toBe(6);
  });
});
```

- [ ] **Step 2: Chạy test, xác nhận fail**

Run: `incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- src/shared/store'`
Expected: FAIL — `feedStore` chưa tồn tại; simStore chưa có `applyPulse`/`history`.

- [ ] **Step 3: Implement**

`frontend/src/shared/store/simStore.ts` — thay toàn bộ file:

```ts
import { create } from 'zustand';
import type { LiveMetrics, MetricPoint } from '@/shared/types/domain';
import type { WorldEventEnvelope } from '@/shared/realtime/envelope';

const MAX_HISTORY = 120;

interface LiveState {
  tick: number;
  metrics: LiveMetrics | null;
  status: string | null;
  history: MetricPoint[];
}

export interface SimStore {
  connection: 'connecting' | 'connected' | 'disconnected';
  selectedUniverseId: number | null;
  live: LiveState;
  selectUniverse: (id: number | null) => void;
  setConnection: (c: SimStore['connection']) => void;
  applyPulse: (env: WorldEventEnvelope) => void;
  reset: () => void;
}

const emptyLive = (): LiveState => ({ tick: 0, metrics: null, status: null, history: [] });

export const useSimStore = create<SimStore>((set) => ({
  connection: 'disconnected',
  selectedUniverseId: null,
  live: emptyLive(),
  selectUniverse: (id) => set({ selectedUniverseId: id, live: emptyLive() }),
  setConnection: (connection) => set({ connection }),
  applyPulse: (env) => set((s) => {
    const p = env.payload as { entropy?: number; stability_index?: number; metrics?: Record<string, number> };
    const entropy = typeof p.entropy === 'number' ? p.entropy : null;
    const stability = typeof p.stability_index === 'number' ? p.stability_index : null;
    const metrics: LiveMetrics = {
      ...(s.live.metrics ?? {}),
      ...(p.metrics ?? {}),
      ...(entropy != null ? { entropy } : {}),
      ...(stability != null ? { stability } : {}),
    };
    return {
      live: {
        tick: env.tick,
        metrics,
        status: s.live.status,
        history: [...s.live.history, { tick: env.tick, entropy, stability }].slice(-MAX_HISTORY),
      },
    };
  }),
  reset: () => set({ connection: 'disconnected', selectedUniverseId: null, live: emptyLive() }),
}));
```

TRƯỚC khi xóa `events`/`view` khỏi simStore: grep `live.events`, `view.mode`, `setMode`, `setReplayTick`, `applyTick` trong toàn `frontend/src` — consumer duy nhất được phép còn lại là `useUniverseChannel` cũ (thay ở Task 4), `ModeSwitcher` và trang stub live (xử lý ở Task 4/7). Nếu grep lộ consumer khác → dừng, báo cáo NEEDS_CONTEXT.

`frontend/src/shared/store/feedStore.ts`:

```ts
import { create } from 'zustand';
import type { FeedItem } from '@/shared/realtime/envelope';

const MAX_ITEMS = 300;

export interface FeedStore {
  items: FeedItem[];
  pushLive: (item: FeedItem) => void;
  clear: () => void;
}

/** Sự kiện tường thuật đến qua realtime, mới nhất trước; nguồn "live" của Living Chronicle. */
export const useFeedStore = create<FeedStore>((set) => ({
  items: [],
  pushLive: (item) => set((s) => {
    if (s.items.some((i) => i.id === item.id)) return s;
    return { items: [item, ...s.items].slice(0, MAX_ITEMS) };
  }),
  clear: () => set({ items: [] }),
}));
```

- [ ] **Step 4: Chạy test, xác nhận pass**

Run: `incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- src/shared/store'`
Expected: PASS (6 tests). Lưu ý: `useUniverseChannel` cũ (gọi `applyTick`) và trang stub `live` (gọi `setMode`) sẽ vỡ typecheck — được phép để `npm run check` đỏ đến hết Task 4 (ghi rõ trong report); test suite các phần khác vẫn phải pass: chạy `npm test` toàn bộ, các fail được phép DUY NHẤT là test của `useUniverseChannel` cũ (xóa ở Task 4).

- [ ] **Step 5: Commit**

```bash
git add frontend/src/shared/store/
git commit -m "feat(fe): P2 simStore applyPulse + history sparkline; feedStore live dedup"
```

---

### Task 4: useUniverseChannels — realtime đa kênh (thay useUniverseChannel)

**Files:**
- Create: `frontend/src/shared/realtime/useUniverseChannels.ts`
- Delete: `frontend/src/shared/realtime/useUniverseChannel.ts`, `frontend/src/shared/realtime/__tests__/useUniverseChannel.test.ts`
- Modify: `frontend/src/test/fakeCentrifuge.ts` (thêm fake đa kênh, GIỮ hàm cũ nếu còn ai dùng — kiểm tra bằng grep, nếu chỉ test bị xóa dùng thì thay luôn)
- Modify: `frontend/src/app/(workspace)/u/[id]/live/page.tsx` — trang stub đang import hook cũ + `setMode`: thay tạm bằng nội dung tối thiểu để repo build (trang này bị THAY HẲN ở Task 8):

```tsx
'use client';
export default function LiveStubPage() {
  return null; // Thay bằng hero Living Chronicle ở Task 8 (route đổi thành /u/[id]).
}
```

- Test: `frontend/src/shared/realtime/__tests__/useUniverseChannels.test.ts`

**Interfaces:**
- Consumes: `parseEnvelope`, `envelopeToFeedItem` (Task 2); `useSimStore.applyPulse`, `useFeedStore.pushLive` (Task 3); `getCentrifuge()` (sẵn có).
- Produces: `useUniverseChannels(universeId: number | null, opts?: { onLiveGap?: () => void }): void` — subscribe 4 kênh `universes:{id}`, `universes:{id}:narrative`, `universes:{id}:anomaly`, `universes:{id}:autopoiesis`; envelope `type === 'universe.pulsed'` → `applyPulse`; type `'pulsed'` (UniverseSimulationPulsed trên public — không subscribe) không liên quan; MỌI type khác → `pushLive(envelopeToFeedItem(env))` nếu `env.universe_id === universeId`; `onLiveGap` gọi khi kênh gốc re-subscribe sau lần đầu (mất kết nối → có thể lỡ event → caller refetch feed).
- Fake mới: `makeFakeCentrifugeMulti(): { centrifuge; emit(channel: string, data: unknown): void; resubscribe(channel: string): void; subscribedChannels(): string[] }`.

- [ ] **Step 1: Viết fake đa kênh + test fail**

Thêm vào `frontend/src/test/fakeCentrifuge.ts`:

```ts
type Handler = (ctx: { data: unknown }) => void;

export function makeFakeCentrifugeMulti() {
  const handlers = new Map<string, { publication: Handler[]; subscribed: (() => void)[] }>();
  const subs = new Map<string, unknown>();

  const centrifuge = {
    connect: vi.fn(),
    disconnect: vi.fn(),
    newSubscription: vi.fn((channel: string) => {
      const entry = { publication: [] as Handler[], subscribed: [] as (() => void)[] };
      handlers.set(channel, entry);
      const sub = {
        on: vi.fn((event: string, cb: Handler | (() => void)) => {
          if (event === 'publication') entry.publication.push(cb as Handler);
          if (event === 'subscribed') entry.subscribed.push(cb as () => void);
          return sub;
        }),
        subscribe: vi.fn(() => entry.subscribed.forEach((cb) => cb())),
        unsubscribe: vi.fn(),
        removeAllListeners: vi.fn(),
      };
      subs.set(channel, sub);
      return sub;
    }),
  };

  return {
    centrifuge,
    emit: (channel: string, data: unknown) =>
      handlers.get(channel)?.publication.forEach((cb) => cb({ data })),
    resubscribe: (channel: string) => handlers.get(channel)?.subscribed.forEach((cb) => cb()),
    subscribedChannels: () => [...handlers.keys()],
  };
}
```

`useUniverseChannels.test.ts`:

```ts
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook } from '@testing-library/react';
import { makeFakeCentrifugeMulti } from '@/test/fakeCentrifuge';

const fake = makeFakeCentrifugeMulti();
vi.mock('@/shared/lib/centrifugo', () => ({ getCentrifuge: () => fake.centrifuge }));

import { useUniverseChannels } from '../useUniverseChannels';
import { useSimStore } from '@/shared/store/simStore';
import { useFeedStore } from '@/shared/store/feedStore';

const envelope = (type: string, over: Record<string, unknown> = {}) => ({
  id: `id-${type}-${JSON.stringify(over)}`, type, tick: 8, universe_id: 5, world_id: 3,
  severity: 'notable', occurred_at: '2026-07-15T00:00:00+00:00',
  payload: { entropy: 0.4, stability_index: 0.9 }, ...over,
});

describe('useUniverseChannels', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    useSimStore.getState().reset();
    useFeedStore.getState().clear();
  });

  it('subscribe đủ 4 kênh của universe', () => {
    renderHook(() => useUniverseChannels(5));
    expect(fake.subscribedChannels()).toEqual(
      expect.arrayContaining(['universes:5', 'universes:5:narrative', 'universes:5:anomaly', 'universes:5:autopoiesis']),
    );
  });

  it('universe.pulsed → simStore; sự kiện tường thuật → feedStore', () => {
    renderHook(() => useUniverseChannels(5));
    fake.emit('universes:5', envelope('universe.pulsed'));
    fake.emit('universes:5:anomaly', envelope('anomaly.detected'));
    fake.emit('universes:5:narrative', envelope('chronicle.generated'));
    expect(useSimStore.getState().live.tick).toBe(8);
    expect(useFeedStore.getState().items.map((i) => i.type)).toEqual(['chronicle.generated', 'anomaly.detected']);
  });

  it('bỏ qua payload không phải envelope và envelope của universe khác', () => {
    renderHook(() => useUniverseChannels(5));
    fake.emit('universes:5', { tick: 9 }); // shape cũ, không envelope
    fake.emit('universes:5:anomaly', envelope('anomaly.detected', { universe_id: 99 }));
    expect(useSimStore.getState().live.tick).toBe(0);
    expect(useFeedStore.getState().items).toHaveLength(0);
  });

  it('gọi onLiveGap khi kênh gốc re-subscribe (mất kết nối)', () => {
    const onLiveGap = vi.fn();
    renderHook(() => useUniverseChannels(5, { onLiveGap }));
    expect(onLiveGap).not.toHaveBeenCalled(); // lần subscribe đầu không phải gap
    fake.resubscribe('universes:5');
    expect(onLiveGap).toHaveBeenCalledTimes(1);
  });
});
```

- [ ] **Step 2: Chạy test, xác nhận fail**

Run: `incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- src/shared/realtime/__tests__/useUniverseChannels.test.ts'`
Expected: FAIL — module chưa tồn tại.

- [ ] **Step 3: Implement**

`frontend/src/shared/realtime/useUniverseChannels.ts`:

```ts
'use client';
import { useEffect, useRef } from 'react';
import type { PublicationContext } from 'centrifuge';
import { getCentrifuge } from '@/shared/lib/centrifugo';
import { useSimStore } from '@/shared/store/simStore';
import { useFeedStore } from '@/shared/store/feedStore';
import { envelopeToFeedItem, parseEnvelope } from './envelope';

const LENS_SUFFIXES = ['', ':narrative', ':anomaly', ':autopoiesis'] as const;

interface Options { onLiveGap?: () => void }

/**
 * Subscribe cụm kênh Observatory của một universe (hợp đồng P1).
 * universe.pulsed → simStore; mọi sự kiện tường thuật khác → feedStore.
 * onLiveGap được gọi khi kênh gốc re-subscribe sau khi mất kết nối (caller nên refetch feed).
 */
export function useUniverseChannels(universeId: number | null, opts: Options = {}): void {
  const setConnection = useSimStore((s) => s.setConnection);
  const applyPulse = useSimStore((s) => s.applyPulse);
  const pushLive = useFeedStore((s) => s.pushLive);
  const onLiveGapRef = useRef(opts.onLiveGap);
  onLiveGapRef.current = opts.onLiveGap;

  useEffect(() => {
    if (universeId == null) return;
    const centrifuge = getCentrifuge();
    setConnection('connecting');
    centrifuge.connect();

    const onPublication = (ctx: PublicationContext) => {
      const env = parseEnvelope(ctx.data);
      if (!env || env.universe_id !== universeId) return;
      if (env.type === 'universe.pulsed') applyPulse(env);
      else pushLive(envelopeToFeedItem(env));
    };

    let firstSubscribe = true;
    const subs = LENS_SUFFIXES.map((suffix) => {
      const sub = centrifuge.newSubscription(`universes:${universeId}${suffix}`);
      sub.on('publication', onPublication);
      if (suffix === '') {
        sub.on('subscribed', () => {
          setConnection('connected');
          if (!firstSubscribe) onLiveGapRef.current?.();
          firstSubscribe = false;
        });
      }
      sub.subscribe();
      return sub;
    });

    return () => {
      subs.forEach((sub) => { sub.removeAllListeners(); sub.unsubscribe(); });
      setConnection('disconnected');
    };
  }, [universeId, applyPulse, pushLive, setConnection]);
}
```

Xóa `useUniverseChannel.ts` + test cũ; thay nội dung trang stub `live` như phần Files; grep `useUniverseChannel\b` toàn `src/` để chắc không còn ai import (nếu còn — sửa nốt chỗ import đó sang stub/null, ghi vào report).

- [ ] **Step 4: Chạy test + check, xác nhận xanh**

Run: `incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test'`
Expected: toàn bộ pass (không còn test hook cũ).
Run: `incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check'`
Expected: PASS — typecheck sạch trở lại (mọi tham chiếu `applyTick`/`setMode`/hook cũ đã bị loại).

- [ ] **Step 5: Commit**

```bash
git add -A frontend/src
git commit -m "feat(fe): P2 useUniverseChannels — realtime 4 kênh theo envelope, thay hook cũ"
```

---

### Task 5: features/chronicle — feed API + useChronicleFeed + mergeFeed

**Files:**
- Create: `frontend/src/features/chronicle/api/feed.ts`
- Create: `frontend/src/features/chronicle/hooks/useChronicleFeed.ts`
- Create: `frontend/src/features/chronicle/lib/mergeFeed.ts`
- Create: `frontend/src/features/chronicle/index.ts`
- Modify: `frontend/src/shared/config/queryKeys.ts` (thêm key `feed`)
- Test: `frontend/src/features/chronicle/__tests__/mergeFeed.test.ts`, `frontend/src/features/chronicle/__tests__/useChronicleFeed.test.tsx`

**Interfaces:**
- Consumes: `FeedItem` (Task 2), `apiClient` (sẵn có), `useFeedStore` (Task 3), `qk` (sửa ở đây).
- Produces:
  - `interface FeedPage { data: FeedItem[]; meta: { count: number; next_before_tick: number | null } }`
  - `fetchFeed(universeId: number, params?: { before_tick?: number; after_tick?: number; limit?: number }): Promise<FeedPage>`
  - `useChronicleFeed(universeId: number | null)` → trả `{ items: FeedItem[]; fetchOlder: () => void; hasOlder: boolean; isLoadingOlder: boolean; isError: boolean; refetchLatest: () => void }` — `items` ĐÃ gộp live (feedStore) + các trang lịch sử, dedup id, DESC theo tick.
  - `mergeFeed(live: FeedItem[], pages: FeedPage[]): FeedItem[]` (pure — dedup theo id, sort tick DESC, tie-break: `kind==='chronicle'` xếp sau event cùng tick, rồi theo `occurred_at` DESC).
  - `qk.feed = (id: number) => ['universes', id, 'feed'] as const`.
  - `index.ts` export: `useChronicleFeed`, `fetchFeed`, các type; (Task 6 sẽ export thêm components).

- [ ] **Step 1: Viết test fail**

`mergeFeed.test.ts`:

```ts
import { describe, expect, it } from 'vitest';
import { mergeFeed } from '../lib/mergeFeed';
import type { FeedItem } from '@/shared/realtime/envelope';
import type { FeedPage } from '../api/feed';

const item = (id: string, tick: number, over: Partial<FeedItem> = {}): FeedItem => ({
  id, kind: 'event', type: 'anomaly.detected', tick, universe_id: 5,
  severity: 'info', occurred_at: `2026-07-15T00:00:${String(tick).padStart(2, '0')}+00:00`,
  payload: {}, ...over,
});
const page = (...data: FeedItem[]): FeedPage => ({ data, meta: { count: data.length, next_before_tick: null } });

describe('mergeFeed', () => {
  it('gộp live + pages, dedup theo id, sort tick DESC', () => {
    const live = [item('live-1', 30), item('dup', 20)];
    const pages = [page(item('dup', 20), item('old-1', 10))];
    expect(mergeFeed(live, pages).map((i) => i.id)).toEqual(['live-1', 'dup', 'old-1']);
  });

  it('cùng tick: event trước chronicle', () => {
    const pages = [page(item('c', 10, { kind: 'chronicle', type: 'chronicle' }), item('e', 10))];
    expect(mergeFeed([], pages).map((i) => i.id)).toEqual(['e', 'c']);
  });

  it('rỗng an toàn', () => {
    expect(mergeFeed([], [])).toEqual([]);
  });
});
```

`useChronicleFeed.test.tsx`:

```tsx
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import type { ReactNode } from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

vi.mock('@/shared/lib/apiClient', () => ({
  TOKEN_KEY: 'worldos_token',
  apiClient: {
    get: vi.fn().mockResolvedValue({
      data: {
        data: [{
          id: 'h1', kind: 'event', type: 'epoch.transitioned', tick: 5, universe_id: 5,
          severity: 'notable', occurred_at: '2026-07-15T00:00:00+00:00', payload: {},
        }],
        meta: { count: 1, next_before_tick: null },
      },
    }),
  },
}));

import { apiClient } from '@/shared/lib/apiClient';
import { useChronicleFeed } from '../hooks/useChronicleFeed';
import { useFeedStore } from '@/shared/store/feedStore';

const wrapper = ({ children }: { children: ReactNode }) => {
  const qc = new QueryClient({ defaultOptions: { queries: { retry: false, gcTime: 0 } } });
  return <QueryClientProvider client={qc}>{children}</QueryClientProvider>;
};

describe('useChronicleFeed', () => {
  beforeEach(() => useFeedStore.getState().clear());

  it('tải trang đầu và gộp với live store', async () => {
    useFeedStore.getState().pushLive({
      id: 'live1', kind: 'event', type: 'anomaly.detected', tick: 9, universe_id: 5,
      severity: 'critical', occurred_at: '2026-07-15T00:01:00+00:00', payload: {},
    });
    const { result } = renderHook(() => useChronicleFeed(5), { wrapper });
    await waitFor(() => expect(result.current.items).toHaveLength(2));
    expect(result.current.items.map((i) => i.id)).toEqual(['live1', 'h1']);
    expect(result.current.hasOlder).toBe(false);
    expect(vi.mocked(apiClient.get)).toHaveBeenCalledWith(
      '/worldos/observatory/universes/5/feed',
      expect.objectContaining({ params: expect.objectContaining({ limit: 50 }) }),
    );
  });
});
```

- [ ] **Step 2: Chạy test, xác nhận fail**

Run: `incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- src/features/chronicle'`
Expected: FAIL — module chưa tồn tại.

- [ ] **Step 3: Implement**

Thêm vào `frontend/src/shared/config/queryKeys.ts` (trong object `qk`):

```ts
  feed: (id: number) => ['universes', id, 'feed'] as const,
```

`frontend/src/features/chronicle/api/feed.ts`:

```ts
import { apiClient } from '@/shared/lib/apiClient';
import type { FeedItem } from '@/shared/realtime/envelope';

export interface FeedPage {
  data: FeedItem[];
  meta: { count: number; next_before_tick: number | null };
}

const DEFAULT_LIMIT = 50;

/** Body feed có 2 key (data+meta) nên interceptor unwrapEnvelope không bóc — đọc nguyên body. */
export async function fetchFeed(
  universeId: number,
  params: { before_tick?: number; after_tick?: number; limit?: number } = {},
): Promise<FeedPage> {
  const res = await apiClient.get(`/worldos/observatory/universes/${universeId}/feed`, {
    params: { limit: DEFAULT_LIMIT, ...params },
  });
  return res.data as FeedPage;
}
```

`frontend/src/features/chronicle/lib/mergeFeed.ts`:

```ts
import type { FeedItem } from '@/shared/realtime/envelope';
import type { FeedPage } from '../api/feed';

/** Gộp live (realtime) + các trang lịch sử: dedup theo id, DESC theo tick;
 *  cùng tick thì event trước chronicle, rồi occurred_at mới trước. */
export function mergeFeed(live: FeedItem[], pages: FeedPage[]): FeedItem[] {
  const seen = new Set<string>();
  const all: FeedItem[] = [];
  for (const item of [...live, ...pages.flatMap((p) => p.data)]) {
    if (seen.has(item.id)) continue;
    seen.add(item.id);
    all.push(item);
  }
  return all.sort((a, b) => {
    if (b.tick !== a.tick) return b.tick - a.tick;
    if (a.kind !== b.kind) return a.kind === 'event' ? -1 : 1;
    return b.occurred_at.localeCompare(a.occurred_at);
  });
}
```

`frontend/src/features/chronicle/hooks/useChronicleFeed.ts`:

```ts
'use client';
import { useMemo } from 'react';
import { useInfiniteQuery } from '@tanstack/react-query';
import { qk } from '@/shared/config/queryKeys';
import { useFeedStore } from '@/shared/store/feedStore';
import type { FeedItem } from '@/shared/realtime/envelope';
import { fetchFeed, type FeedPage } from '../api/feed';
import { mergeFeed } from '../lib/mergeFeed';

export interface ChronicleFeed {
  items: FeedItem[];
  fetchOlder: () => void;
  hasOlder: boolean;
  isLoadingOlder: boolean;
  isError: boolean;
  refetchLatest: () => void;
}

export function useChronicleFeed(universeId: number | null): ChronicleFeed {
  const liveItems = useFeedStore((s) => s.items);

  const query = useInfiniteQuery({
    queryKey: universeId != null ? qk.feed(universeId) : ['universes', 'none', 'feed'],
    enabled: universeId != null,
    queryFn: ({ pageParam }) =>
      fetchFeed(universeId as number, pageParam != null ? { before_tick: pageParam } : {}),
    initialPageParam: undefined as number | undefined,
    getNextPageParam: (last: FeedPage) => last.meta.next_before_tick ?? undefined,
  });

  const items = useMemo(
    () => mergeFeed(
      liveItems.filter((i) => i.universe_id === universeId),
      query.data?.pages ?? [],
    ),
    [liveItems, query.data, universeId],
  );

  return {
    items,
    fetchOlder: () => { void query.fetchNextPage(); },
    hasOlder: query.hasNextPage ?? false,
    isLoadingOlder: query.isFetchingNextPage,
    isError: query.isError,
    refetchLatest: () => { void query.refetch(); },
  };
}
```

`frontend/src/features/chronicle/index.ts`:

```ts
export { useChronicleFeed, type ChronicleFeed } from './hooks/useChronicleFeed';
export { fetchFeed, type FeedPage } from './api/feed';
export { mergeFeed } from './lib/mergeFeed';
```

- [ ] **Step 4: Chạy test, xác nhận pass**

Run: `incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- src/features/chronicle'`
Expected: PASS (4 tests).

- [ ] **Step 5: Commit**

```bash
git add frontend/src/features/chronicle/ frontend/src/shared/config/queryKeys.ts
git commit -m "feat(fe): P2 chronicle feed — fetchFeed + useChronicleFeed (infinite, gộp live, dedup)"
```

---

### Task 6: features/chronicle — ChronicleEntry + ChronicleStream

**Files:**
- Create: `frontend/src/features/chronicle/components/ChronicleEntry.tsx`
- Create: `frontend/src/features/chronicle/components/ChronicleStream.tsx`
- Modify: `frontend/src/features/chronicle/index.ts` (export thêm 2 component)
- Test: `frontend/src/features/chronicle/__tests__/ChronicleEntry.test.tsx`, `frontend/src/features/chronicle/__tests__/ChronicleStream.test.tsx`

**Interfaces:**
- Consumes: `FeedItem` (Task 2). Icon từ `lucide-react` (đã có). Token CSS `globals.css`.
- Produces:
  - `ChronicleEntry({ item }: { item: FeedItem })` — render một mục theo `type`:
    - `chronicle` (kind chronicle): thẻ prose `.glass`, hiện `payload.content` (string, có thể null → "(chưa có nội dung tường thuật)"), icon ScrollText, viền accent violet.
    - `epoch.transitioned`: dòng nổi bật `payload.old_epoch?.name → payload.new_epoch?.name`, icon Landmark, chữ `.text-glow-cyan`.
    - `anomaly.detected`: `payload.title` + `payload.description`, icon AlertTriangle; severity `critical` → viền `--color-danger`, ngược lại amber.
    - `celebrity.emerged`: icon Crown, "Nhân vật #`payload.agent_id` nổi lên (`payload.vocation`)".
    - `artifact.discovered`: icon Gem, "Cổ vật #`payload.artifact_id` được phát hiện".
    - `autopoiesis.mutation`: icon Dna, "Luật thế giới tự biến đổi".
    - `history.shifted`: icon BookOpen, "`payload.event_type`".
    - default: icon Activity, hiện `type`.
    - Mọi mục hiện `tick` dạng mono (`T{tick}`).
  - `ChronicleStream({ items, hasOlder, isLoadingOlder, onLoadOlder }: { items: FeedItem[]; hasOlder: boolean; isLoadingOlder: boolean; onLoadOlder: () => void })` — danh sách DESC (mới nhất trên đầu), scroll container `.custom-scrollbar`, nút "Tải thêm quá khứ" ở đáy khi `hasOlder`, empty state khi `items` rỗng: "Vũ trụ chưa có biến cố nào — hãy chạy tick để lịch sử bắt đầu."

- [ ] **Step 1: Viết test fail**

`ChronicleEntry.test.tsx`:

```tsx
import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { ChronicleEntry } from '../components/ChronicleEntry';
import type { FeedItem } from '@/shared/realtime/envelope';

const base = (over: Partial<FeedItem>): FeedItem => ({
  id: 'x', kind: 'event', type: 'anomaly.detected', tick: 42, universe_id: 5,
  severity: 'critical', occurred_at: '2026-07-15T00:00:00+00:00', payload: {}, ...over,
});

describe('ChronicleEntry', () => {
  it('render anomaly với title + tick', () => {
    render(<ChronicleEntry item={base({ payload: { title: 'Entropy spike', description: 'x' } })} />);
    expect(screen.getByText('Entropy spike')).toBeTruthy();
    expect(screen.getByText('T42')).toBeTruthy();
  });

  it('render epoch shift với tên 2 epoch', () => {
    render(<ChronicleEntry item={base({
      type: 'epoch.transitioned', severity: 'notable',
      payload: { old_epoch: { id: 1, name: 'Bronze' }, new_epoch: { id: 2, name: 'Iron' } },
    })} />);
    expect(screen.getByText(/Bronze/)).toBeTruthy();
    expect(screen.getByText(/Iron/)).toBeTruthy();
  });

  it('render chronicle prose từ payload.content', () => {
    render(<ChronicleEntry item={base({
      kind: 'chronicle', type: 'chronicle',
      payload: { chronicle_id: 9, content: 'Sử thi về đế chế sụp đổ', importance: 0.8, has_animation: false },
    })} />);
    expect(screen.getByText('Sử thi về đế chế sụp đổ')).toBeTruthy();
  });

  it('type lạ không vỡ — hiện type', () => {
    render(<ChronicleEntry item={base({ type: 'unknown.thing', severity: 'info' })} />);
    expect(screen.getByText(/unknown\.thing/)).toBeTruthy();
  });
});
```

`ChronicleStream.test.tsx`:

```tsx
import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen } from '@testing-library/react';
import { ChronicleStream } from '../components/ChronicleStream';
import type { FeedItem } from '@/shared/realtime/envelope';

const item = (id: string, tick: number): FeedItem => ({
  id, kind: 'event', type: 'anomaly.detected', tick, universe_id: 5,
  severity: 'info', occurred_at: '2026-07-15T00:00:00+00:00', payload: { title: `Sự kiện ${id}` },
});

describe('ChronicleStream', () => {
  it('empty state khi không có item', () => {
    render(<ChronicleStream items={[]} hasOlder={false} isLoadingOlder={false} onLoadOlder={() => {}} />);
    expect(screen.getByText(/chưa có biến cố/)).toBeTruthy();
  });

  it('render danh sách + nút tải quá khứ gọi onLoadOlder', () => {
    const onLoadOlder = vi.fn();
    render(<ChronicleStream items={[item('a', 2), item('b', 1)]} hasOlder isLoadingOlder={false} onLoadOlder={onLoadOlder} />);
    expect(screen.getByText('Sự kiện a')).toBeTruthy();
    fireEvent.click(screen.getByRole('button', { name: /Tải thêm quá khứ/ }));
    expect(onLoadOlder).toHaveBeenCalledTimes(1);
  });

  it('ẩn nút khi hết quá khứ', () => {
    render(<ChronicleStream items={[item('a', 2)]} hasOlder={false} isLoadingOlder={false} onLoadOlder={() => {}} />);
    expect(screen.queryByRole('button', { name: /Tải thêm quá khứ/ })).toBeNull();
  });
});
```

- [ ] **Step 2: Chạy test, xác nhận fail**

Run: `incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- src/features/chronicle/__tests__/ChronicleEntry.test.tsx src/features/chronicle/__tests__/ChronicleStream.test.tsx'`
Expected: FAIL — component chưa tồn tại.

- [ ] **Step 3: Implement** (đọc skill `frontend-design` trước khi viết; đây là bộ mặt của sản phẩm)

`ChronicleEntry.tsx` — khung yêu cầu (nội dung text/điều kiện PHẢI đúng như test; styling dùng token, tự do trong khuôn):

```tsx
'use client';
import type { ReactNode } from 'react';
import {
  Activity, AlertTriangle, BookOpen, Crown, Dna, Gem, Landmark, ScrollText,
} from 'lucide-react';
import type { FeedItem } from '@/shared/realtime/envelope';

interface Visual { icon: ReactNode; tone: string; body: ReactNode }

function visualFor(item: FeedItem): Visual {
  const p = item.payload as Record<string, unknown>;
  switch (item.type) {
    case 'chronicle':
      return {
        icon: <ScrollText size={16} />, tone: 'border-l-[var(--color-accent)]',
        body: <p className="leading-relaxed text-[var(--color-text-primary)]">{(p.content as string) ?? '(chưa có nội dung tường thuật)'}</p>,
      };
    case 'epoch.transitioned': {
      const oldName = (p.old_epoch as { name?: string } | undefined)?.name ?? '?';
      const newName = (p.new_epoch as { name?: string } | undefined)?.name ?? '?';
      return {
        icon: <Landmark size={16} />, tone: 'border-l-[var(--color-primary)]',
        body: <p className="text-glow-cyan font-medium">Kỷ nguyên chuyển mình: {oldName} → {newName}</p>,
      };
    }
    case 'anomaly.detected':
      return {
        icon: <AlertTriangle size={16} />,
        tone: item.severity === 'critical' ? 'border-l-[var(--color-danger)]' : 'border-l-[var(--color-amber)]',
        body: (
          <div>
            <p className="font-medium">{(p.title as string) ?? 'Dị thường'}</p>
            {typeof p.description === 'string' && <p className="text-sm text-[var(--color-text-muted)]">{p.description}</p>}
          </div>
        ),
      };
    case 'celebrity.emerged':
      return { icon: <Crown size={16} />, tone: 'border-l-[var(--color-amber)]', body: <p>Nhân vật #{String(p.agent_id ?? '?')} nổi lên ({String(p.vocation ?? '?')})</p> };
    case 'artifact.discovered':
      return { icon: <Gem size={16} />, tone: 'border-l-[var(--color-accent)]', body: <p>Cổ vật #{String(p.artifact_id ?? '?')} được phát hiện</p> };
    case 'autopoiesis.mutation':
      return { icon: <Dna size={16} />, tone: 'border-l-[var(--color-emerald)]', body: <p>Luật thế giới tự biến đổi</p> };
    case 'history.shifted':
      return { icon: <BookOpen size={16} />, tone: 'border-l-[var(--color-info)]', body: <p>{String(p.event_type ?? 'Biến cố lịch sử')}</p> };
    default:
      return { icon: <Activity size={16} />, tone: 'border-l-[var(--border-muted)]', body: <p className="text-[var(--color-text-muted)]">{item.type}</p> };
  }
}

export function ChronicleEntry({ item }: { item: FeedItem }) {
  const v = visualFor(item);
  return (
    <article className={`glass flex gap-3 rounded-lg border-l-2 p-3 animate-fade-in-up ${v.tone}`}>
      <span className="mt-0.5 shrink-0 text-[var(--color-text-muted)]">{v.icon}</span>
      <div className="min-w-0 flex-1">{v.body}</div>
      <span className="shrink-0 font-mono text-xs text-[var(--color-text-disabled)]">T{item.tick}</span>
    </article>
  );
}
```

`ChronicleStream.tsx`:

```tsx
'use client';
import type { FeedItem } from '@/shared/realtime/envelope';
import { ChronicleEntry } from './ChronicleEntry';

interface Props {
  items: FeedItem[];
  hasOlder: boolean;
  isLoadingOlder: boolean;
  onLoadOlder: () => void;
}

export function ChronicleStream({ items, hasOlder, isLoadingOlder, onLoadOlder }: Props) {
  if (items.length === 0) {
    return (
      <div className="flex h-full items-center justify-center p-8 text-center text-[var(--color-text-muted)]">
        <p>Vũ trụ chưa có biến cố nào — hãy chạy tick để lịch sử bắt đầu.</p>
      </div>
    );
  }
  return (
    <div className="custom-scrollbar flex h-full flex-col gap-2 overflow-y-auto pr-1">
      {items.map((item) => <ChronicleEntry key={item.id} item={item} />)}
      {hasOlder && (
        <button
          type="button"
          onClick={onLoadOlder}
          disabled={isLoadingOlder}
          className="mx-auto my-3 rounded-full border border-[var(--border-subtle)] px-4 py-1.5 text-sm text-[var(--color-text-secondary)] hover:border-[var(--color-primary)] disabled:opacity-50"
        >
          {isLoadingOlder ? 'Đang lật trang sử…' : 'Tải thêm quá khứ'}
        </button>
      )}
    </div>
  );
}
```

Thêm vào `frontend/src/features/chronicle/index.ts`:

```ts
export { ChronicleEntry } from './components/ChronicleEntry';
export { ChronicleStream } from './components/ChronicleStream';
```

- [ ] **Step 4: Chạy test, xác nhận pass**

Run: `incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- src/features/chronicle'`
Expected: PASS (11 tests toàn feature).

- [ ] **Step 5: Commit**

```bash
git add frontend/src/features/chronicle/
git commit -m "feat(fe): P2 ChronicleEntry + ChronicleStream — dòng biên niên sử theo loại sự kiện"
```

---

### Task 7: Route group (observatory) + shell + landing multiverse

**Files:**
- Rename: `git mv 'frontend/src/app/(workspace)' 'frontend/src/app/(observatory)'`
- Modify: `frontend/src/shared/config/routes.ts` (bỏ `live/replay/actor`, thêm `universe`)
- Modify: `frontend/src/features/universe-workspace/components/WorkspaceLayout.tsx` (bỏ ModeSwitcher, thêm link Đa vũ trụ)
- Modify: `frontend/src/features/universe-workspace/components/ContextBar.tsx` (đổi `routes.live(` → `routes.universe(`)
- Delete: `frontend/src/features/universe-workspace/components/ModeSwitcher.tsx` (+ test của nó nếu có)
- Modify: `frontend/src/app/(observatory)/multiverse/page.tsx` (landing thật thay stub)
- Modify: `frontend/src/app/login/page.tsx` — chỉ nếu đang hardcode `'/multiverse'` thì thay bằng `routes.multiverse()` (kiểm tra, nếu đã đúng thì bỏ qua)
- Modify: `frontend/eslint.config.mjs` — glob guardrail `"src/app/(workspace)/**/*.{ts,tsx}"` → `"src/app/(observatory)/**/*.{ts,tsx}"`
- Test: cập nhật `frontend/src/features/universe-workspace/__tests__/ContextBar.test.tsx` (assertion route mới nếu có), tạo `frontend/src/app/(observatory)/__tests__/multiverse-page.test.tsx`

**Interfaces:**
- Consumes: `useUniverses` (public API `@/features/universe-workspace`), `Pill`/`Panel` (`@/shared/ui`), `routes` (sửa ở đây), `qk`.
- Produces:
  - `routes = { login: () => '/login', multiverse: () => '/multiverse', universe: (id: number) => \`/u/\${id}\` } as const` — Task 8 dùng `routes.universe`.
  - Landing `/multiverse`: lưới thẻ universe (tên, `Pill` status, tick hiện tại) — mỗi thẻ là `<Link href={routes.universe(u.id)}>`.
  - Shell `WorkspaceLayout` giữ export cũ (`@/features/universe-workspace`): header = ContextBar + link "Đa vũ trụ" (`routes.multiverse()`), main full-height.

- [ ] **Step 1: Viết test fail cho landing**

`frontend/src/app/(observatory)/__tests__/multiverse-page.test.tsx`:

```tsx
import { describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { renderWithClient } from '@/test/render';

vi.mock('@/shared/lib/apiClient', () => ({
  TOKEN_KEY: 'worldos_token',
  apiClient: {
    get: vi.fn().mockResolvedValue({
      data: [
        { id: 1, world_id: 1, name: 'Aurora', status: 'active', current_tick: 120, era: 2 },
        { id: 2, world_id: 1, name: 'Umbra', status: 'paused', current_tick: 40, era: 1 },
      ],
    }),
  },
}));
vi.mock('next/navigation', () => ({ useRouter: () => ({ push: vi.fn(), replace: vi.fn() }) }));

import MultiversePage from '../multiverse/page';

describe('Landing multiverse', () => {
  it('render thẻ universe với link vào hero', async () => {
    renderWithClient(<MultiversePage />);
    expect(await screen.findByText('Aurora')).toBeTruthy();
    expect(screen.getByText('Umbra')).toBeTruthy();
    const link = screen.getByRole('link', { name: /Aurora/ });
    expect(link.getAttribute('href')).toBe('/u/1');
  });
});
```

- [ ] **Step 2: Chạy test, xác nhận fail**

Run: `incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- "src/app/(observatory)"'`
Expected: FAIL — thư mục `(observatory)` chưa tồn tại / page còn là stub.

- [ ] **Step 3: Implement**

```bash
cd /home/tuzy/Working/IPFactory && git mv 'frontend/src/app/(workspace)' 'frontend/src/app/(observatory)'
```

`frontend/src/shared/config/routes.ts` — thay toàn bộ:

```ts
export const routes = {
  login: () => '/login',
  multiverse: () => '/multiverse',
  universe: (id: number) => `/u/${id}`,
} as const;
```

Trong `ContextBar.tsx`: thay MỌI `routes.live(` bằng `routes.universe(` (grep xác nhận không còn `routes.live`/`routes.replay`/`routes.actor` ở bất kỳ đâu trong `src/features` + `src/app/(observatory)`; nếu login page hardcode `'/multiverse'` giữ nguyên cũng được — chỉ sửa nếu tiện).

`WorkspaceLayout.tsx` — thay toàn bộ (server component, giữ export name):

```tsx
import Link from 'next/link';
import type { ReactNode } from 'react';
import { routes } from '@/shared/config/routes';
import { ContextBar } from './ContextBar';

/** Shell của Observatory: thanh bối cảnh (universe + tick + trạng thái) + nội dung. */
export function WorkspaceLayout({ children }: { children: ReactNode }) {
  return (
    <div className="flex min-h-screen flex-col bg-[var(--color-bg-base)] text-[var(--color-text-primary)]">
      <header className="flex items-center justify-between gap-4 border-b border-[var(--border-subtle)] px-4 py-2">
        <ContextBar />
        <Link
          href={routes.multiverse()}
          className="shrink-0 text-sm text-[var(--color-text-secondary)] hover:text-[var(--color-primary)]"
        >
          Đa vũ trụ
        </Link>
      </header>
      <main className="min-h-0 flex-1 p-4">{children}</main>
    </div>
  );
}
```

LƯU Ý: đọc `WorkspaceLayout.tsx` hiện tại TRƯỚC khi thay — nếu ContextBar đang được render với props hoặc header có phần tử khác cần giữ (ví dụ aria attributes từ fix P0 cũ), bảo toàn hành vi đó và ghi vào report. Xóa `ModeSwitcher.tsx` + mọi import của nó; nếu simStore không còn `view` (Task 3) thì component này đằng nào cũng vỡ — xóa là bắt buộc.

`frontend/src/app/(observatory)/multiverse/page.tsx` — thay toàn bộ:

```tsx
'use client';
import Link from 'next/link';
import { useUniverses, WorkspaceLayout } from '@/features/universe-workspace';
import { Pill } from '@/shared/ui/Pill';
import { routes } from '@/shared/config/routes';

export default function MultiversePage() {
  const { data: universes, isLoading, isError } = useUniverses();

  return (
    <WorkspaceLayout>
      <h1 className="mb-4 font-mono text-xs uppercase tracking-[0.3em] text-[var(--color-text-muted)]">
        Đa vũ trụ — chọn một vũ trụ để quan sát
      </h1>
      {isLoading && <p className="skeleton h-24 rounded-lg" aria-label="Đang tải danh sách vũ trụ" />}
      {isError && <p className="text-[var(--color-danger)]">Không tải được danh sách vũ trụ.</p>}
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        {(universes ?? []).map((u) => (
          <Link
            key={u.id}
            href={routes.universe(u.id)}
            className="glass group rounded-xl border border-[var(--border-subtle)] p-4 transition hover:border-[var(--color-primary)]"
          >
            <div className="flex items-center justify-between gap-2">
              <span className="truncate font-medium group-hover:text-glow-cyan">{u.name}</span>
              <Pill tone={u.status}>{u.status}</Pill>
            </div>
            <p className="mt-2 font-mono text-xs text-[var(--color-text-muted)]">T{u.current_tick}</p>
          </Link>
        ))}
      </div>
    </WorkspaceLayout>
  );
}
```

LƯU Ý: `useUniverses` export từ `@/features/universe-workspace` (index) và WorkspaceLayout cũng vậy — kiểm tra `index.ts` của feature exports cả hai (đã có sẵn).

`frontend/eslint.config.mjs`: trong mảng `files` của block guardrail, thay `"src/app/(workspace)/**/*.{ts,tsx}"` bằng `"src/app/(observatory)/**/*.{ts,tsx}"`.

Cập nhật `ContextBar.test.tsx` nếu nó assert đường dẫn `/u/1/live` → `/u/1`.

- [ ] **Step 4: Chạy test + check, xác nhận pass**

Run: `incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test'`
Expected: toàn bộ pass (landing test mới + ContextBar test đã cập nhật).
Run: `incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check'`
Expected: PASS (guardrail phủ `(observatory)`, không vi phạm import).

- [ ] **Step 5: Commit**

```bash
git add -A frontend
git commit -m "feat(fe): P2 route group (observatory) + shell + landing chọn vũ trụ"
```

---

### Task 8: Hero — Living Chronicle tại /u/[id]

**Files:**
- Create: `frontend/src/app/(observatory)/u/[id]/page.tsx`
- Create: `frontend/src/features/chronicle/components/MetricsSparkline.tsx`
- Delete: `frontend/src/app/(observatory)/u/[id]/live/` (toàn bộ thư mục stub)
- Modify: `frontend/src/features/chronicle/index.ts` (export `MetricsSparkline`)
- Test: `frontend/src/app/(observatory)/__tests__/universe-hero-page.test.tsx`

**Interfaces:**
- Consumes: `useUniverseChannels` (Task 4), `useChronicleFeed` + `ChronicleStream` (Task 5-6), `useSimStore` (Task 3), `WorkspaceLayout` (`@/features/universe-workspace`), `Panel` (`@/shared/ui/Panel`), recharts (đã có).
- Produces:
  - Trang hero `/u/[id]`: chọn universe vào store, subscribe realtime, stream giữa (2/3) + panel phải (1/3): sparkline entropy/stability + trạng thái kết nối; banner degraded khi feed lỗi; `onLiveGap` → `refetchLatest()`.
  - `MetricsSparkline({ history }: { history: MetricPoint[] })` — LineChart recharts 2 line (entropy `--color-danger`-ish, stability `--color-primary`), không trục rườm rà, chiều cao ~96px; khi `history` rỗng render dòng "Chưa có nhịp đập nào." (KHÔNG render chart rỗng).

- [ ] **Step 1: Viết test fail**

`universe-hero-page.test.tsx`:

```tsx
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { renderWithClient } from '@/test/render';
import { makeFakeCentrifugeMulti } from '@/test/fakeCentrifuge';

const fake = makeFakeCentrifugeMulti();
vi.mock('@/shared/lib/centrifugo', () => ({ getCentrifuge: () => fake.centrifuge }));
vi.mock('next/navigation', () => ({
  useRouter: () => ({ push: vi.fn(), replace: vi.fn() }),
  useParams: () => ({ id: '5' }),
}));
vi.mock('@/shared/lib/apiClient', () => ({
  TOKEN_KEY: 'worldos_token',
  apiClient: {
    get: vi.fn().mockImplementation((url: string) => {
      if (url.includes('/feed')) {
        return Promise.resolve({ data: { data: [{
          id: 'h1', kind: 'chronicle', type: 'chronicle', tick: 5, universe_id: 5,
          severity: 'notable', occurred_at: '2026-07-15T00:00:00+00:00',
          payload: { chronicle_id: 1, content: 'Khởi nguyên của Aurora', importance: 0.8, has_animation: false },
        }], meta: { count: 1, next_before_tick: null } } });
      }
      return Promise.resolve({ data: [] });
    }),
  },
}));

import UniverseHeroPage from '../u/[id]/page';
import { useSimStore } from '@/shared/store/simStore';
import { useFeedStore } from '@/shared/store/feedStore';

describe('Hero Living Chronicle', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    useSimStore.getState().reset();
    useFeedStore.getState().clear();
  });

  it('render chronicle từ feed và nhận sự kiện realtime mới', async () => {
    renderWithClient(<UniverseHeroPage />);
    expect(await screen.findByText('Khởi nguyên của Aurora')).toBeTruthy();

    fake.emit('universes:5:anomaly', {
      id: 'live-a1', type: 'anomaly.detected', tick: 9, universe_id: 5, world_id: 3,
      severity: 'critical', occurred_at: '2026-07-15T00:01:00+00:00',
      payload: { title: 'Entropy spike', description: 'x' },
    });
    expect(await screen.findByText('Entropy spike')).toBeTruthy();
  });

  it('chọn universe từ params vào store', async () => {
    renderWithClient(<UniverseHeroPage />);
    await screen.findByText('Khởi nguyên của Aurora');
    expect(useSimStore.getState().selectedUniverseId).toBe(5);
  });
});
```

- [ ] **Step 2: Chạy test, xác nhận fail**

Run: `incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test -- "src/app/(observatory)/__tests__/universe-hero-page.test.tsx"'`
Expected: FAIL — page chưa tồn tại.

- [ ] **Step 3: Implement** (đọc skill `frontend-design` trước)

`MetricsSparkline.tsx`:

```tsx
'use client';
import { Line, LineChart, ResponsiveContainer, YAxis } from 'recharts';
import type { MetricPoint } from '@/shared/types/domain';

export function MetricsSparkline({ history }: { history: MetricPoint[] }) {
  if (history.length === 0) {
    return <p className="text-sm text-[var(--color-text-muted)]">Chưa có nhịp đập nào.</p>;
  }
  return (
    <div className="h-24 w-full" role="img" aria-label="Diễn biến entropy và stability">
      <ResponsiveContainer width="100%" height="100%">
        <LineChart data={history} margin={{ top: 4, right: 4, bottom: 0, left: 4 }}>
          <YAxis hide domain={[0, 1]} />
          <Line type="monotone" dataKey="stability" stroke="var(--color-primary)" dot={false} strokeWidth={1.5} isAnimationActive={false} />
          <Line type="monotone" dataKey="entropy" stroke="var(--color-danger)" dot={false} strokeWidth={1.5} isAnimationActive={false} />
        </LineChart>
      </ResponsiveContainer>
    </div>
  );
}
```

`frontend/src/app/(observatory)/u/[id]/page.tsx`:

```tsx
'use client';
import { useEffect, useMemo } from 'react';
import { useParams } from 'next/navigation';
import { WorkspaceLayout } from '@/features/universe-workspace';
import { ChronicleStream, MetricsSparkline, useChronicleFeed } from '@/features/chronicle';
import { useUniverseChannels } from '@/shared/realtime/useUniverseChannels';
import { useSimStore } from '@/shared/store/simStore';
import { useFeedStore } from '@/shared/store/feedStore';
import { Panel } from '@/shared/ui/Panel';

export default function UniverseHeroPage() {
  const params = useParams<{ id: string }>();
  const universeId = useMemo(() => {
    const n = Number(params?.id);
    return Number.isFinite(n) ? n : null;
  }, [params?.id]);

  const selectUniverse = useSimStore((s) => s.selectUniverse);
  const selectedUniverseId = useSimStore((s) => s.selectedUniverseId);
  const connection = useSimStore((s) => s.connection);
  const history = useSimStore((s) => s.live.history);
  const clearFeed = useFeedStore((s) => s.clear);

  useEffect(() => {
    if (universeId != null && selectedUniverseId !== universeId) {
      clearFeed();
      selectUniverse(universeId);
    }
  }, [universeId, selectedUniverseId, selectUniverse, clearFeed]);

  const feed = useChronicleFeed(universeId);
  useUniverseChannels(universeId, { onLiveGap: feed.refetchLatest });

  return (
    <WorkspaceLayout>
      {feed.isError && (
        <div className="mb-3 rounded-lg border border-[var(--color-amber)] bg-[var(--bg-elevated)] px-3 py-2 text-sm text-[var(--color-amber)]" role="alert">
          Chế độ suy giảm: không tải được lịch sử — chỉ hiển thị sự kiện realtime.
        </div>
      )}
      <div className="grid h-full min-h-0 grid-cols-1 gap-4 lg:grid-cols-3">
        <section className="min-h-0 lg:col-span-2" aria-label="Dòng biên niên sử">
          <ChronicleStream
            items={feed.items}
            hasOlder={feed.hasOlder}
            isLoadingOlder={feed.isLoadingOlder}
            onLoadOlder={feed.fetchOlder}
          />
        </section>
        <aside className="flex flex-col gap-4">
          <Panel title="Nhịp đập vũ trụ">
            <MetricsSparkline history={history} />
          </Panel>
          <Panel title="Kết nối">
            <p className="font-mono text-sm text-[var(--color-text-secondary)]">{connection}</p>
          </Panel>
        </aside>
      </div>
    </WorkspaceLayout>
  );
}
```

Xóa thư mục `frontend/src/app/(observatory)/u/[id]/live/`. Thêm export vào `features/chronicle/index.ts`:

```ts
export { MetricsSparkline } from './components/MetricsSparkline';
```

- [ ] **Step 4: Chạy test + check + build**

Run: `incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test'`
Expected: toàn bộ pass.
Run: `incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check'`
Expected: PASS.
Run: `incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run build 2>&1 | tail -5'`
Expected: build Next thành công (xác nhận route group + pages hợp lệ ở mức production build).

- [ ] **Step 5: Commit**

```bash
git add -A frontend
git commit -m "feat(fe): P2 hero Living Chronicle tại /u/[id] — stream + sparkline + degraded mode"
```

---

### Task 9: Cổng hồi quy P2 + cập nhật trạng thái

**Files:**
- Modify: `.dev_status.md` (mục Session mới: P2 hoàn thành, kết quả test thật, tiếp theo P3)

**Interfaces:**
- Consumes: toàn bộ Task 1-8.
- Produces: baseline FE mới cho P3.

- [ ] **Step 1: Chạy toàn bộ gate**

```bash
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm test 2>&1 | tail -3'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run check 2>&1 | tail -3'
incus exec worldos-dev -- sh -c 'cd /work/frontend && npm run build 2>&1 | tail -3'
incus exec worldos-dev -- sh -c 'cd /work/backend && php artisan test --testsuite=Unit 2>&1 | grep "Tests:"'
```
Expected: FE test pass toàn bộ (không fail mới so với baseline Task 1), check PASS, build OK; backend Unit không đổi (170-171 pass — flake IntelligenceExplosionTest đã biết). Fail mới → dừng, systematic-debugging.

- [ ] **Step 2: Cập nhật `.dev_status.md`** — thêm mục Session: P2 hoàn thành (envelope parser, useUniverseChannels 4 kênh, feedStore, chronicle feature, route group (observatory), landing, hero) + số liệu test thật + "Tiếp theo: P3 lenses (kèm 3 endpoint BE psyche/civilization/world) + cinema port; P4 ops + thanh lý".

- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "chore: hoàn thành Observatory Plan 2 — FE nền tảng (shell + landing + hero)"
```
