# Frontend Rewrite — P0 Foundations Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stand up the new feature-first frontend skeleton (shared layer + Zustand sim-store + Centrifugo→store bridge + Universe Workspace shell + auth + new route group) so that `login → select a universe → see the live tick & connection status in a persistent context bar with a 3-mode switcher` works — coexisting with the old `/dashboard` app (strangler).

**Architecture:** New code lives under `src/shared/*` and `src/features/*` with a thin `src/app/(workspace)/*` route group; old `src/app/dashboard/*` + `src/components/dashboard/*` + `src/contexts/*` stay untouched. State is split: React Query (server) + Zustand (live sim-state + UI) + URL. Centrifugo pushes ticks into the store; the shell reacts. ESLint import boundaries enforce the layering.

**Tech Stack:** Next.js 16 (App Router) · React 19 · TypeScript strict · Tailwind 4 · Zustand · @tanstack/react-query · axios · centrifuge · vitest + @testing-library/react (jsdom). All commands run **inside the frontend Docker container** (per CLAUDE.md — never `npm` on host). Shorthand below: `FE` = `docker compose -f deployment/docker-compose.prod.yml -p worldos exec -T frontend`.

> Spec: `docs/superpowers/specs/2026-06-13-frontend-rewrite-design.md`. This plan implements **Phase P0** only. P1 (LIVE), P2 (REPLAY), P3 (MULTIVERSE), P4 (drill-downs), P5 (admin+cutover) get their own plans.

> **Prerequisite:** new deps must be installed once in the frontend container before tests/build: `FE npm i zustand` (React Query/axios/centrifuge already present). Confirm with `FE npm ls zustand`.

---

## File Structure (created in P0)

| File | Responsibility |
|---|---|
| `src/shared/config/queryKeys.ts` | Central React Query key registry |
| `src/shared/config/routes.ts` | Workspace URL builders (`/u/:id/live` …) |
| `src/shared/lib/apiClient.ts` | axios instance (bearer token, envelope unwrap) — clean rewrite of old `lib/api.ts` |
| `src/shared/lib/queryClient.ts` | React Query `QueryClient` factory |
| `src/shared/lib/centrifugo.ts` | Centrifuge client factory (token from backend) |
| `src/shared/types/domain.ts` | Domain DTOs: `Universe`, `LiveMetrics`, `SimEvent`, `Snapshot` |
| `src/shared/store/simStore.ts` | Zustand store: connection, selectedUniverse, live{tick,metrics,events}, view{mode,…} |
| `src/shared/realtime/useUniverseChannel.ts` | Hook: subscribe `universes:{id}` → store actions |
| `src/shared/ui/Panel.tsx` `Pill.tsx` `Button.tsx` | Design-system primitives (Tailwind) |
| `src/features/universe-workspace/components/ContextBar.tsx` | Persistent bar: universe switcher + tick/status/connection + controls |
| `src/features/universe-workspace/components/ModeSwitcher.tsx` | LIVE/REPLAY/MULTIVERSE tabs (URL-driven) |
| `src/features/universe-workspace/components/WorkspaceLayout.tsx` | Shell layout composing ContextBar + ModeSwitcher + slot |
| `src/features/universe-workspace/hooks/useUniverses.ts` | RQ hook: list universes + single universe |
| `src/features/universe-workspace/index.ts` | Public API of the feature |
| `src/features/auth/{hooks/useAuth.ts,components/AuthGate.tsx,index.ts}` | Auth (bearer token, login, guard) |
| `src/app/(workspace)/layout.tsx` | Providers: QueryClient + AuthGate + realtime bootstrap |
| `src/app/(workspace)/u/[id]/live/page.tsx` | LIVE route — renders WorkspaceLayout (empty mode body placeholder for P1) |
| `src/app/(workspace)/multiverse/page.tsx` | MULTIVERSE route placeholder |
| `src/app/(workspace)/login/page.tsx` | Login form (uses useAuth) |
| `eslint.config.mjs` (modify) | Add `import/no-restricted-paths` boundary rules |
| `src/test/render.tsx` | Test render helper (QueryClient wrapper) |
| `src/test/fakeCentrifuge.ts` | Fake Centrifuge subscription for realtime tests |

---

## Task 1: Dependency + config scaffolding

**Files:**
- Modify: `frontend/tsconfig.json` (path aliases)
- Create: `frontend/src/shared/config/queryKeys.ts`, `frontend/src/shared/config/routes.ts`
- Test: `frontend/src/shared/config/__tests__/routes.test.ts`

- [ ] **Step 1: Install zustand + confirm aliases**

Run: `FE npm i zustand && FE npm ls zustand`
Expected: zustand listed, no errors.

Add to `frontend/tsconfig.json` `compilerOptions.paths` (alongside existing `"@/*"`):
```json
"@/shared/*": ["./src/shared/*"],
"@/features/*": ["./src/features/*"]
```

- [ ] **Step 2: Write failing test for route builders**

`frontend/src/shared/config/__tests__/routes.test.ts`:
```ts
import { describe, it, expect } from 'vitest';
import { routes } from '../routes';

describe('routes', () => {
  it('builds live/replay/multiverse/actor URLs', () => {
    expect(routes.live(2)).toBe('/u/2/live');
    expect(routes.replay(2, 15)).toBe('/u/2/replay?tick=15');
    expect(routes.replay(2)).toBe('/u/2/replay');
    expect(routes.multiverse()).toBe('/multiverse');
    expect(routes.actor(2, 9)).toBe('/u/2/actor/9');
  });
});
```

- [ ] **Step 3: Run test — expect FAIL**

Run: `FE npx vitest run src/shared/config/__tests__/routes.test.ts`
Expected: FAIL — cannot find module `../routes`.

- [ ] **Step 4: Implement routes + queryKeys**

`frontend/src/shared/config/routes.ts`:
```ts
export const routes = {
  login: () => '/login',
  multiverse: () => '/multiverse',
  live: (id: number) => `/u/${id}/live`,
  replay: (id: number, tick?: number) => tick == null ? `/u/${id}/replay` : `/u/${id}/replay?tick=${tick}`,
  actor: (id: number, actorId: number) => `/u/${id}/actor/${actorId}`,
} as const;
```
`frontend/src/shared/config/queryKeys.ts`:
```ts
export const qk = {
  universes: () => ['universes'] as const,
  universe: (id: number) => ['universes', id] as const,
  metrics: (id: number) => ['universes', id, 'metrics'] as const,
  snapshot: (id: number, tick: number) => ['universes', id, 'snapshot', tick] as const,
  chronicles: (id: number) => ['universes', id, 'chronicles'] as const,
  forkTree: () => ['multiverse', 'fork-tree'] as const,
} as const;
```

- [ ] **Step 5: Run test — expect PASS, then commit**

Run: `FE npx vitest run src/shared/config/__tests__/routes.test.ts` → PASS.
```bash
git add frontend/tsconfig.json frontend/src/shared/config frontend/package.json frontend/package-lock.json
git commit -m "feat(fe): P0 scaffolding — path aliases, route + query-key registries"
```

---

## Task 2: Domain types

**Files:**
- Create: `frontend/src/shared/types/domain.ts`

- [ ] **Step 1: Define types (no test — pure types)**

`frontend/src/shared/types/domain.ts`:
```ts
export type UniverseStatus = 'active' | 'paused' | 'halted';

export interface Universe {
  id: number;
  world_id: number;
  name: string;
  status: UniverseStatus;
  current_tick: number;
  era: number;
}

export interface LiveMetrics {
  stability: number;
  entropy: number;
  era: number;
  [key: string]: number;
}

export interface SimEvent {
  tick: number;
  type: string;
  summary: string;
}

export interface Snapshot {
  tick: number;
  metrics: LiveMetrics;
  events: SimEvent[];
}
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/shared/types
git commit -m "feat(fe): P0 domain types (Universe, LiveMetrics, SimEvent, Snapshot)"
```

---

## Task 3: Zustand sim-store (keystone)

**Files:**
- Create: `frontend/src/shared/store/simStore.ts`
- Test: `frontend/src/shared/store/__tests__/simStore.test.ts`

- [ ] **Step 1: Write failing tests**

`frontend/src/shared/store/__tests__/simStore.test.ts`:
```ts
import { describe, it, expect, beforeEach } from 'vitest';
import { useSimStore } from '../simStore';

const reset = () => useSimStore.getState().reset();

describe('simStore', () => {
  beforeEach(reset);

  it('selectUniverse sets id and clears live state', () => {
    useSimStore.getState().applyTick({ tick: 5 });
    useSimStore.getState().selectUniverse(2);
    const s = useSimStore.getState();
    expect(s.selectedUniverseId).toBe(2);
    expect(s.live.tick).toBe(0);
    expect(s.live.events).toEqual([]);
  });

  it('applyTick merges metrics and prepends events (newest first)', () => {
    useSimStore.getState().applyTick({ tick: 10, metrics: { stability: 0.5, entropy: 0.2, era: 1 }, event: { tick: 10, type: 'a', summary: 'A' } });
    useSimStore.getState().applyTick({ tick: 11, event: { tick: 11, type: 'b', summary: 'B' } });
    const s = useSimStore.getState();
    expect(s.live.tick).toBe(11);
    expect(s.live.metrics?.stability).toBe(0.5); // retained when not in payload
    expect(s.live.events.map((e) => e.summary)).toEqual(['B', 'A']);
  });

  it('caps events at MAX_EVENTS (200)', () => {
    for (let i = 0; i < 250; i++) useSimStore.getState().applyTick({ tick: i, event: { tick: i, type: 't', summary: `e${i}` } });
    expect(useSimStore.getState().live.events).toHaveLength(200);
    expect(useSimStore.getState().live.events[0].summary).toBe('e249');
  });

  it('setMode and setReplayTick update view', () => {
    useSimStore.getState().setMode('replay');
    useSimStore.getState().setReplayTick(7);
    const v = useSimStore.getState().view;
    expect(v.mode).toBe('replay');
    expect(v.replayTick).toBe(7);
  });
});
```

- [ ] **Step 2: Run — expect FAIL**

Run: `FE npx vitest run src/shared/store/__tests__/simStore.test.ts`
Expected: FAIL — cannot find module `../simStore`.

- [ ] **Step 3: Implement the store**

`frontend/src/shared/store/simStore.ts`:
```ts
import { create } from 'zustand';
import type { LiveMetrics, SimEvent } from '@/shared/types/domain';

export type SimMode = 'live' | 'replay' | 'multiverse';
const MAX_EVENTS = 200;

interface LiveState { tick: number; metrics: LiveMetrics | null; events: SimEvent[]; status: string | null; }
interface ViewState { mode: SimMode; replayTick: number | null; selectedActorId: number | null; }

export interface SimStore {
  connection: 'connecting' | 'connected' | 'disconnected';
  selectedUniverseId: number | null;
  live: LiveState;
  view: ViewState;
  selectUniverse: (id: number | null) => void;
  setConnection: (c: SimStore['connection']) => void;
  applyTick: (p: { tick: number; metrics?: LiveMetrics; event?: SimEvent; status?: string }) => void;
  setMode: (m: SimMode) => void;
  setReplayTick: (t: number | null) => void;
  reset: () => void;
}

const emptyLive = (): LiveState => ({ tick: 0, metrics: null, events: [], status: null });
const emptyView = (): ViewState => ({ mode: 'live', replayTick: null, selectedActorId: null });

export const useSimStore = create<SimStore>((set) => ({
  connection: 'disconnected',
  selectedUniverseId: null,
  live: emptyLive(),
  view: emptyView(),
  selectUniverse: (id) => set({ selectedUniverseId: id, live: emptyLive() }),
  setConnection: (connection) => set({ connection }),
  applyTick: (p) => set((s) => ({
    live: {
      tick: p.tick ?? s.live.tick,
      metrics: p.metrics ?? s.live.metrics,
      status: p.status ?? s.live.status,
      events: p.event ? [p.event, ...s.live.events].slice(0, MAX_EVENTS) : s.live.events,
    },
  })),
  setMode: (mode) => set((s) => ({ view: { ...s.view, mode } })),
  setReplayTick: (replayTick) => set((s) => ({ view: { ...s.view, replayTick } })),
  reset: () => set({ connection: 'disconnected', selectedUniverseId: null, live: emptyLive(), view: emptyView() }),
}));
```

- [ ] **Step 4: Run — expect PASS, then commit**

Run: `FE npx vitest run src/shared/store/__tests__/simStore.test.ts` → PASS.
```bash
git add frontend/src/shared/store
git commit -m "feat(fe): P0 Zustand sim-store (live tick/metrics/events + view state)"
```

---

## Task 4: API client + QueryClient + Centrifugo client

**Files:**
- Create: `frontend/src/shared/lib/apiClient.ts`, `frontend/src/shared/lib/queryClient.ts`, `frontend/src/shared/lib/centrifugo.ts`
- Test: `frontend/src/shared/lib/__tests__/apiClient.test.ts`

- [ ] **Step 1: Write failing test for envelope unwrap**

`frontend/src/shared/lib/__tests__/apiClient.test.ts`:
```ts
import { describe, it, expect } from 'vitest';
import { unwrapEnvelope } from '../apiClient';

describe('unwrapEnvelope', () => {
  it('unwraps { data: X } to X', () => {
    expect(unwrapEnvelope({ data: [1, 2] })).toEqual([1, 2]);
  });
  it('leaves payloads with meta/links wrapped', () => {
    const body = { data: [1], meta: { total: 1 } };
    expect(unwrapEnvelope(body)).toBe(body);
  });
  it('passes through non-envelope objects', () => {
    expect(unwrapEnvelope({ ok: true })).toEqual({ ok: true });
  });
});
```

- [ ] **Step 2: Run — expect FAIL**

Run: `FE npx vitest run src/shared/lib/__tests__/apiClient.test.ts`
Expected: FAIL — cannot find module `../apiClient`.

- [ ] **Step 3: Implement the three lib files**

`frontend/src/shared/lib/apiClient.ts`:
```ts
import axios, { AxiosResponse, AxiosError } from 'axios';
import { toast } from 'sonner';

export const TOKEN_KEY = 'worldos_token';

export function unwrapEnvelope(body: unknown): unknown {
  if (body && typeof body === 'object' && !Array.isArray(body) && 'data' in body) {
    const keys = Object.keys(body).filter((k) => k !== 'meta' && k !== 'links');
    if (keys.length === 1 && keys[0] === 'data') return (body as { data: unknown }).data;
  }
  return body;
}

export const apiClient = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL || '/api',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
});

apiClient.interceptors.request.use((config) => {
  if (typeof window !== 'undefined') {
    const token = localStorage.getItem(TOKEN_KEY);
    if (token && config.headers) config.headers.set('Authorization', `Bearer ${token}`);
  }
  return config;
});

apiClient.interceptors.response.use(
  (res: AxiosResponse) => { res.data = unwrapEnvelope(res.data); return res; },
  (error: AxiosError<{ message?: string }>) => {
    toast.error(error.response?.data?.message || 'Đã xảy ra lỗi kết nối.');
    return Promise.reject(error);
  },
);
```
`frontend/src/shared/lib/queryClient.ts`:
```ts
import { QueryClient } from '@tanstack/react-query';

export function makeQueryClient(): QueryClient {
  return new QueryClient({
    defaultOptions: { queries: { staleTime: 5_000, retry: 1, refetchOnWindowFocus: false } },
  });
}
```
`frontend/src/shared/lib/centrifugo.ts`:
```ts
import { Centrifuge } from 'centrifuge';
import { apiClient } from './apiClient';

let instance: Centrifuge | null = null;

/** Singleton Centrifuge client; fetches a connection token from the backend. */
export function getCentrifuge(): Centrifuge {
  if (instance) return instance;
  const wsUrl = (process.env.NEXT_PUBLIC_WS_URL || '/connection/websocket');
  instance = new Centrifuge(wsUrl, {
    getToken: async () => {
      const res = await apiClient.post('/worldos/centrifugo/token');
      return (res.data as { token: string }).token;
    },
  });
  return instance;
}
```

- [ ] **Step 4: Run — expect PASS, then commit**

Run: `FE npx vitest run src/shared/lib/__tests__/apiClient.test.ts` → PASS.
```bash
git add frontend/src/shared/lib
git commit -m "feat(fe): P0 shared lib — apiClient, queryClient, centrifugo factory"
```

---

## Task 5: Centrifugo → store realtime bridge

**Files:**
- Create: `frontend/src/shared/realtime/useUniverseChannel.ts`, `frontend/src/test/fakeCentrifuge.ts`
- Test: `frontend/src/shared/realtime/__tests__/useUniverseChannel.test.ts`

- [ ] **Step 1: Implement fake Centrifuge helper**

`frontend/src/test/fakeCentrifuge.ts`:
```ts
import { vi } from 'vitest';

export function makeFakeCentrifuge() {
  const handlers: Record<string, (ctx: { data: unknown }) => void> = {};
  const sub = {
    on: vi.fn((event: string, cb: (ctx: { data: unknown }) => void) => { handlers[event] = cb; return sub; }),
    subscribe: vi.fn(),
    unsubscribe: vi.fn(),
    removeAllListeners: vi.fn(),
  };
  const centrifuge = {
    connect: vi.fn(),
    disconnect: vi.fn(),
    newSubscription: vi.fn(() => sub),
    getSubscription: vi.fn(() => null),
  };
  return { centrifuge, sub, emit: (data: unknown) => handlers['publication']?.({ data }) };
}
```

- [ ] **Step 2: Write failing test**

`frontend/src/shared/realtime/__tests__/useUniverseChannel.test.ts`:
```ts
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { renderHook } from '@testing-library/react';
import { makeFakeCentrifuge } from '@/test/fakeCentrifuge';
import { useSimStore } from '@/shared/store/simStore';

const fake = makeFakeCentrifuge();
vi.mock('@/shared/lib/centrifugo', () => ({ getCentrifuge: () => fake.centrifuge }));

import { useUniverseChannel } from '../useUniverseChannel';

describe('useUniverseChannel', () => {
  beforeEach(() => useSimStore.getState().reset());

  it('subscribes to universes:{id} and pushes ticks into the store', () => {
    renderHook(() => useUniverseChannel(2));
    expect(fake.centrifuge.newSubscription).toHaveBeenCalledWith('universes:2');
    fake.emit({ tick: 12, metrics: { stability: 0.4, entropy: 0.6, era: 2 }, event: { tick: 12, type: 'x', summary: 'X' } });
    const s = useSimStore.getState();
    expect(s.live.tick).toBe(12);
    expect(s.live.events[0].summary).toBe('X');
  });

  it('does nothing when id is null', () => {
    renderHook(() => useUniverseChannel(null));
    expect(fake.centrifuge.newSubscription).not.toHaveBeenCalled();
  });
});
```

- [ ] **Step 3: Run — expect FAIL**

Run: `FE npx vitest run src/shared/realtime/__tests__/useUniverseChannel.test.ts`
Expected: FAIL — cannot find module `../useUniverseChannel`.

- [ ] **Step 4: Implement the bridge hook**

`frontend/src/shared/realtime/useUniverseChannel.ts`:
```ts
'use client';
import { useEffect } from 'react';
import type { PublicationContext } from 'centrifuge';
import { getCentrifuge } from '@/shared/lib/centrifugo';
import { useSimStore } from '@/shared/store/simStore';
import type { LiveMetrics, SimEvent } from '@/shared/types/domain';

interface TickPayload { tick: number; metrics?: LiveMetrics; event?: SimEvent; status?: string; }

/** Subscribe the selected universe's Centrifugo channel and stream ticks into the sim-store. */
export function useUniverseChannel(universeId: number | null): void {
  const setConnection = useSimStore((s) => s.setConnection);
  const applyTick = useSimStore((s) => s.applyTick);

  useEffect(() => {
    if (universeId == null) return;
    const centrifuge = getCentrifuge();
    setConnection('connecting');
    centrifuge.connect();
    const sub = centrifuge.newSubscription(`universes:${universeId}`);
    sub.on('publication', (ctx: PublicationContext) => {
      const p = ctx.data as TickPayload;
      if (typeof p?.tick === 'number') applyTick(p);
    });
    sub.on('subscribed', () => setConnection('connected'));
    sub.subscribe();
    return () => { sub.removeAllListeners(); sub.unsubscribe(); setConnection('disconnected'); };
  }, [universeId, applyTick, setConnection]);
}
```

- [ ] **Step 5: Run — expect PASS, then commit**

Run: `FE npx vitest run src/shared/realtime/__tests__/useUniverseChannel.test.ts` → PASS.
```bash
git add frontend/src/shared/realtime frontend/src/test/fakeCentrifuge.ts
git commit -m "feat(fe): P0 Centrifugo->store realtime bridge (useUniverseChannel)"
```

---

## Task 6: UI primitives (design system)

**Files:**
- Create: `frontend/src/shared/ui/Panel.tsx`, `frontend/src/shared/ui/Pill.tsx`, `frontend/src/shared/ui/Button.tsx`
- Test: `frontend/src/shared/ui/__tests__/Pill.test.tsx`

- [ ] **Step 1: Write failing test for Pill**

`frontend/src/shared/ui/__tests__/Pill.test.tsx`:
```tsx
import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { Pill } from '../Pill';

describe('Pill', () => {
  it('renders label and tone class', () => {
    render(<Pill tone="active">LIVE</Pill>);
    const el = screen.getByText('LIVE');
    expect(el).toBeTruthy();
    expect(el.className).toContain('bg-');
  });
});
```

- [ ] **Step 2: Run — expect FAIL**

Run: `FE npx vitest run src/shared/ui/__tests__/Pill.test.tsx`
Expected: FAIL — cannot find module `../Pill`.

- [ ] **Step 3: Implement primitives**

`frontend/src/shared/ui/Pill.tsx`:
```tsx
import type { ReactNode } from 'react';

const TONES: Record<string, string> = {
  active: 'bg-teal-500/20 text-teal-300 border-teal-500/40',
  paused: 'bg-amber-500/20 text-amber-300 border-amber-500/40',
  halted: 'bg-rose-500/20 text-rose-300 border-rose-500/40',
  neutral: 'bg-white/10 text-gray-300 border-white/20',
};

export function Pill({ tone = 'neutral', children }: { tone?: keyof typeof TONES | string; children: ReactNode }) {
  return (
    <span className={`inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-medium ${TONES[tone] ?? TONES.neutral}`}>
      {children}
    </span>
  );
}
```
`frontend/src/shared/ui/Panel.tsx`:
```tsx
import type { ReactNode } from 'react';

export function Panel({ title, children, className = '' }: { title?: string; children: ReactNode; className?: string }) {
  return (
    <section className={`rounded-xl border border-white/10 bg-black/30 p-4 ${className}`}>
      {title && <h3 className="mb-2 text-[11px] font-bold uppercase tracking-wider text-gray-500">{title}</h3>}
      {children}
    </section>
  );
}
```
`frontend/src/shared/ui/Button.tsx`:
```tsx
import type { ButtonHTMLAttributes, ReactNode } from 'react';

export function Button({ children, className = '', ...rest }: ButtonHTMLAttributes<HTMLButtonElement> & { children: ReactNode }) {
  return (
    <button
      className={`rounded-lg border border-white/15 px-3 py-1.5 text-sm text-gray-200 transition-colors hover:bg-white/10 disabled:opacity-40 ${className}`}
      {...rest}
    >
      {children}
    </button>
  );
}
```

- [ ] **Step 4: Run — expect PASS, then commit**

Run: `FE npx vitest run src/shared/ui/__tests__/Pill.test.tsx` → PASS.
```bash
git add frontend/src/shared/ui
git commit -m "feat(fe): P0 UI primitives (Panel, Pill, Button)"
```

---

## Task 7: Auth feature

**Files:**
- Create: `frontend/src/features/auth/hooks/useAuth.ts`, `frontend/src/features/auth/components/AuthGate.tsx`, `frontend/src/features/auth/index.ts`
- Test: `frontend/src/features/auth/__tests__/useAuth.test.tsx`

- [ ] **Step 1: Write failing test**

`frontend/src/features/auth/__tests__/useAuth.test.tsx`:
```tsx
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { renderHook, act, waitFor } from '@testing-library/react';

vi.mock('@/shared/lib/apiClient', () => ({
  TOKEN_KEY: 'worldos_token',
  apiClient: { post: vi.fn().mockResolvedValue({ data: { token: 'tok123' } }) },
}));

import { useAuth } from '../hooks/useAuth';

describe('useAuth', () => {
  beforeEach(() => localStorage.clear());

  it('starts unauthenticated, logs in and stores token', async () => {
    const { result } = renderHook(() => useAuth());
    expect(result.current.isAuthenticated).toBe(false);
    await act(async () => { await result.current.login('a@b.com', 'pw'); });
    await waitFor(() => expect(result.current.isAuthenticated).toBe(true));
    expect(localStorage.getItem('worldos_token')).toBe('tok123');
  });
});
```

- [ ] **Step 2: Run — expect FAIL**

Run: `FE npx vitest run src/features/auth/__tests__/useAuth.test.tsx`
Expected: FAIL — cannot find module `../hooks/useAuth`.

- [ ] **Step 3: Implement useAuth + AuthGate + index**

`frontend/src/features/auth/hooks/useAuth.ts`:
```ts
'use client';
import { useState, useCallback, useEffect } from 'react';
import { apiClient, TOKEN_KEY } from '@/shared/lib/apiClient';

export function useAuth() {
  const [token, setToken] = useState<string | null>(null);
  useEffect(() => { setToken(localStorage.getItem(TOKEN_KEY)); }, []);

  const login = useCallback(async (email: string, password: string) => {
    const res = await apiClient.post('/auth/login', { email, password });
    const t = (res.data as { token: string }).token;
    localStorage.setItem(TOKEN_KEY, t);
    setToken(t);
  }, []);

  const logout = useCallback(() => { localStorage.removeItem(TOKEN_KEY); setToken(null); }, []);

  return { token, isAuthenticated: !!token, login, logout };
}
```
`frontend/src/features/auth/components/AuthGate.tsx`:
```tsx
'use client';
import { type ReactNode, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '../hooks/useAuth';
import { routes } from '@/shared/config/routes';

export function AuthGate({ children }: { children: ReactNode }) {
  const { isAuthenticated } = useAuth();
  const router = useRouter();
  useEffect(() => { if (!isAuthenticated) router.replace(routes.login()); }, [isAuthenticated, router]);
  if (!isAuthenticated) return null;
  return <>{children}</>;
}
```
`frontend/src/features/auth/index.ts`:
```ts
export { useAuth } from './hooks/useAuth';
export { AuthGate } from './components/AuthGate';
```

- [ ] **Step 4: Run — expect PASS, then commit**

Run: `FE npx vitest run src/features/auth/__tests__/useAuth.test.tsx` → PASS.
```bash
git add frontend/src/features/auth
git commit -m "feat(fe): P0 auth feature (useAuth, AuthGate)"
```

---

## Task 8: Universe Workspace shell (ContextBar, ModeSwitcher, layout, useUniverses)

**Files:**
- Create: `frontend/src/features/universe-workspace/hooks/useUniverses.ts`, `.../components/ContextBar.tsx`, `.../components/ModeSwitcher.tsx`, `.../components/WorkspaceLayout.tsx`, `.../index.ts`
- Test: `frontend/src/features/universe-workspace/__tests__/ContextBar.test.tsx`, `frontend/src/test/render.tsx`

- [ ] **Step 1: Implement test render helper**

`frontend/src/test/render.tsx`:
```tsx
import { type ReactNode } from 'react';
import { render } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

export function renderWithClient(ui: ReactNode) {
  const qc = new QueryClient({ defaultOptions: { queries: { retry: false, gcTime: 0 } } });
  return render(<QueryClientProvider client={qc}>{ui}</QueryClientProvider>);
}
```

- [ ] **Step 2: Write failing test for ContextBar**

`frontend/src/features/universe-workspace/__tests__/ContextBar.test.tsx`:
```tsx
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import { renderWithClient } from '@/test/render';
import { useSimStore } from '@/shared/store/simStore';

vi.mock('@/shared/lib/apiClient', () => ({
  TOKEN_KEY: 'worldos_token',
  apiClient: { get: vi.fn().mockResolvedValue({ data: [{ id: 2, world_id: 1, name: 'Demo World', status: 'active', current_tick: 15, era: 3 }] }) },
}));
vi.mock('next/navigation', () => ({ useRouter: () => ({ push: vi.fn(), replace: vi.fn() }) }));

import { ContextBar } from '../components/ContextBar';

describe('ContextBar', () => {
  beforeEach(() => useSimStore.getState().reset());

  it('shows selected universe + live tick from store', async () => {
    useSimStore.getState().selectUniverse(2);
    useSimStore.getState().applyTick({ tick: 42, status: 'active' });
    renderWithClient(<ContextBar />);
    await waitFor(() => expect(screen.getByText('Demo World')).toBeTruthy());
    expect(screen.getByText(/Tick 42/)).toBeTruthy();
  });
});
```

- [ ] **Step 3: Run — expect FAIL**

Run: `FE npx vitest run src/features/universe-workspace/__tests__/ContextBar.test.tsx`
Expected: FAIL — cannot find module `../components/ContextBar`.

- [ ] **Step 4: Implement hook + components + index**

`frontend/src/features/universe-workspace/hooks/useUniverses.ts`:
```ts
'use client';
import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@/shared/lib/apiClient';
import { qk } from '@/shared/config/queryKeys';
import type { Universe } from '@/shared/types/domain';

export function useUniverses() {
  return useQuery({ queryKey: qk.universes(), queryFn: async () => (await apiClient.get('/worldos/universes')).data as Universe[] });
}
```
`frontend/src/features/universe-workspace/components/ContextBar.tsx`:
```tsx
'use client';
import { useRouter } from 'next/navigation';
import { useSimStore } from '@/shared/store/simStore';
import { useUniverses } from '../hooks/useUniverses';
import { routes } from '@/shared/config/routes';
import { Pill } from '@/shared/ui/Pill';

export function ContextBar() {
  const router = useRouter();
  const { data: universes = [] } = useUniverses();
  const selectedId = useSimStore((s) => s.selectedUniverseId);
  const tick = useSimStore((s) => s.live.tick);
  const status = useSimStore((s) => s.live.status);
  const connection = useSimStore((s) => s.connection);
  const selected = universes.find((u) => u.id === selectedId);

  return (
    <header className="flex items-center gap-4 border-b border-white/10 bg-black/40 px-4 py-2">
      <select
        className="rounded-lg border border-white/15 bg-black/40 px-2 py-1 text-sm text-gray-200"
        value={selectedId ?? ''}
        onChange={(e) => { const id = Number(e.target.value); router.push(routes.live(id)); }}
      >
        <option value="" disabled>Chọn Universe…</option>
        {universes.map((u) => <option key={u.id} value={u.id}>{u.name}</option>)}
      </select>
      {selected && (
        <span className="text-sm text-gray-300">
          {selected.name} · Era {selected.era} · Tick {tick || selected.current_tick}
        </span>
      )}
      <span className="ml-auto flex items-center gap-2">
        <Pill tone={status === 'paused' ? 'paused' : status === 'halted' ? 'halted' : 'active'}>{status ?? selected?.status ?? '—'}</Pill>
        <Pill tone={connection === 'connected' ? 'active' : 'neutral'}>{connection === 'connected' ? '● LIVE' : connection}</Pill>
      </span>
    </header>
  );
}
```
`frontend/src/features/universe-workspace/components/ModeSwitcher.tsx`:
```tsx
'use client';
import { useRouter } from 'next/navigation';
import { useSimStore, type SimMode } from '@/shared/store/simStore';
import { routes } from '@/shared/config/routes';

const MODES: { key: SimMode; label: string }[] = [
  { key: 'live', label: 'LIVE' },
  { key: 'replay', label: 'REPLAY' },
  { key: 'multiverse', label: 'MULTIVERSE' },
];

export function ModeSwitcher() {
  const router = useRouter();
  const mode = useSimStore((s) => s.view.mode);
  const id = useSimStore((s) => s.selectedUniverseId);
  return (
    <nav className="flex flex-col gap-1 border-r border-white/10 p-2">
      {MODES.map((m) => (
        <button
          key={m.key}
          aria-current={mode === m.key}
          onClick={() => {
            if (m.key === 'multiverse') return router.push(routes.multiverse());
            if (id != null) router.push(m.key === 'live' ? routes.live(id) : routes.replay(id));
          }}
          className={`rounded-lg px-3 py-2 text-left text-xs font-bold tracking-wider ${mode === m.key ? 'bg-white/15 text-white' : 'text-gray-400 hover:bg-white/5'}`}
        >
          {m.label}
        </button>
      ))}
    </nav>
  );
}
```
`frontend/src/features/universe-workspace/components/WorkspaceLayout.tsx`:
```tsx
import type { ReactNode } from 'react';
import { ContextBar } from './ContextBar';
import { ModeSwitcher } from './ModeSwitcher';

export function WorkspaceLayout({ children }: { children: ReactNode }) {
  return (
    <div className="flex h-screen flex-col bg-[#0a0a0f] text-gray-200">
      <ContextBar />
      <div className="flex flex-1 overflow-hidden">
        <ModeSwitcher />
        <main className="flex-1 overflow-auto p-4">{children}</main>
      </div>
    </div>
  );
}
```
`frontend/src/features/universe-workspace/index.ts`:
```ts
export { WorkspaceLayout } from './components/WorkspaceLayout';
export { useUniverses } from './hooks/useUniverses';
```

- [ ] **Step 5: Run — expect PASS, then commit**

Run: `FE npx vitest run src/features/universe-workspace/__tests__/ContextBar.test.tsx` → PASS.
```bash
git add frontend/src/features/universe-workspace frontend/src/test/render.tsx
git commit -m "feat(fe): P0 Universe Workspace shell (ContextBar, ModeSwitcher, layout)"
```

---

## Task 9: Route group + providers (wire it together)

**Files:**
- Create: `frontend/src/app/(workspace)/layout.tsx`, `.../u/[id]/live/page.tsx`, `.../multiverse/page.tsx`, `.../login/page.tsx`

- [ ] **Step 1: Implement the workspace layout (providers + realtime bootstrap)**

`frontend/src/app/(workspace)/layout.tsx`:
```tsx
'use client';
import { type ReactNode, useState } from 'react';
import { QueryClientProvider } from '@tanstack/react-query';
import { makeQueryClient } from '@/shared/lib/queryClient';
import { AuthGate } from '@/features/auth';

export default function WorkspaceRootLayout({ children }: { children: ReactNode }) {
  const [qc] = useState(makeQueryClient);
  return (
    <QueryClientProvider client={qc}>
      <AuthGate>{children}</AuthGate>
    </QueryClientProvider>
  );
}
```

- [ ] **Step 2: Implement LIVE route (selects universe + opens channel + shell)**

`frontend/src/app/(workspace)/u/[id]/live/page.tsx`:
```tsx
'use client';
import { useEffect } from 'react';
import { useParams } from 'next/navigation';
import { WorkspaceLayout } from '@/features/universe-workspace';
import { useSimStore } from '@/shared/store/simStore';
import { useUniverseChannel } from '@/shared/realtime/useUniverseChannel';
import { Panel } from '@/shared/ui/Panel';

export default function LivePage() {
  const params = useParams<{ id: string }>();
  const id = Number(params.id);
  const selectUniverse = useSimStore((s) => s.selectUniverse);
  const setMode = useSimStore((s) => s.setMode);
  useEffect(() => { selectUniverse(id); setMode('live'); }, [id, selectUniverse, setMode]);
  useUniverseChannel(id);
  return (
    <WorkspaceLayout>
      <Panel title="Live Monitor">
        <p className="text-sm text-gray-500">Panels sẽ được xây ở P1 (metrics live, event/narrative stream, zones/actors).</p>
      </Panel>
    </WorkspaceLayout>
  );
}
```

- [ ] **Step 3: Implement multiverse + login placeholders**

`frontend/src/app/(workspace)/multiverse/page.tsx`:
```tsx
'use client';
import { WorkspaceLayout } from '@/features/universe-workspace';
import { Panel } from '@/shared/ui/Panel';
export default function MultiversePage() {
  return <WorkspaceLayout><Panel title="Multiverse">Cây fork/branch sẽ được xây ở P3.</Panel></WorkspaceLayout>;
}
```
`frontend/src/app/(workspace)/login/page.tsx`:
```tsx
'use client';
import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/features/auth';
import { Button } from '@/shared/ui/Button';

export default function LoginPage() {
  const { login } = useAuth();
  const router = useRouter();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    try { await login(email, password); router.replace('/multiverse'); }
    catch { setError('Đăng nhập thất bại'); }
  };
  return (
    <main className="flex h-screen items-center justify-center bg-[#0a0a0f] text-gray-200">
      <form onSubmit={submit} className="flex w-80 flex-col gap-3 rounded-xl border border-white/10 bg-black/40 p-6">
        <h1 className="text-lg font-bold">WorldOS</h1>
        <input className="rounded border border-white/15 bg-black/40 px-3 py-2 text-sm" placeholder="Email" value={email} onChange={(e) => setEmail(e.target.value)} />
        <input type="password" className="rounded border border-white/15 bg-black/40 px-3 py-2 text-sm" placeholder="Mật khẩu" value={password} onChange={(e) => setPassword(e.target.value)} />
        {error && <p className="text-xs text-rose-400">{error}</p>}
        <Button type="submit">Đăng nhập</Button>
      </form>
    </main>
  );
}
```

- [ ] **Step 4: Commit**

```bash
git add "frontend/src/app/(workspace)"
git commit -m "feat(fe): P0 workspace route group — layout, live, multiverse, login"
```

---

## Task 10: ESLint boundaries guardrail + full verification

**Files:**
- Modify: `frontend/eslint.config.mjs`

- [ ] **Step 1: Add import boundary rules**

Append to the `defineConfig([...])` array in `frontend/eslint.config.mjs` (after the existing test override block, before `globalIgnores`):
```js
  // P0-6-style guardrail: enforce app → features → shared layering; no cross-feature internals.
  {
    files: ["src/**/*.{ts,tsx}"],
    rules: {
      "no-restricted-imports": ["error", {
        patterns: [
          { group: ["@/features/*/*", "!@/features/*/index", "!@/features/*"], message: "Import features only via their index.ts (public API)." },
          { group: ["@/shared/*/**/internal/*"], message: "Do not import shared internals." },
        ],
      }],
    },
  },
```

- [ ] **Step 2: Run typecheck + lint**

Run: `FE npm run check`
Expected: PASS (0 errors). Fix any boundary violations the rule surfaces by routing imports through `index.ts`.

- [ ] **Step 3: Run the full test suite**

Run: `FE npm test`
Expected: all suites pass (existing 76 + new P0 tests for routes, simStore, apiClient, useUniverseChannel, Pill, useAuth, ContextBar).

- [ ] **Step 4: Production build smoke**

Run: `FE npm run build`
Expected: "Compiled successfully"; the new routes `/u/[id]/live`, `/multiverse`, `/login` appear in the route list alongside the old `/dashboard/*`.

- [ ] **Step 5: Manual smoke (running stack)**

Open `http://localhost:8090/login` → log in → navigate to `/u/2/live` → confirm: ContextBar shows the universe + tick, connection pill shows status, ModeSwitcher renders. (Old `/dashboard` still works.)

- [ ] **Step 6: Commit**

```bash
git add frontend/eslint.config.mjs
git commit -m "feat(fe): P0 ESLint import-boundary guardrail + P0 verification green"
```

---

## Self-Review notes (done while writing)

- **Spec coverage (P0 slice):** shared layer (lib/store/realtime/ui/config/types) ✓, feature-first structure ✓, 3-tier state (RQ + Zustand + URL) ✓ (live store + useUniverses + URL routes), Centrifugo→store bridge ✓, workspace shell with context bar + 3-mode switcher ✓, auth ✓, route group coexisting with old app (strangler) ✓, boundaries guardrail ✓, tests per slice ✓. (LIVE panels, replay, multiverse tree, drill-downs = deferred to P1–P4 per spec — out of P0 scope.)
- **Placeholder scan:** none — every code step has real code; mode bodies are intentionally minimal P0 stubs labelled for P1/P3.
- **Type consistency:** `applyTick` payload `{ tick, metrics?, event?, status? }` consistent across simStore (Task 3), useUniverseChannel (Task 5). `Universe` fields (id, name, status, current_tick, era) consistent in domain.ts (Task 4), useUniverses (Task 8), ContextBar test (Task 8). `routes.live(id)` signature consistent (Task 1 → 8 → 9). `qk.universes()` used in Task 8.
