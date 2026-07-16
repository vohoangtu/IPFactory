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
