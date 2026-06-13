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
