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

  it('applyPulse set status từ payload', () => {
    useSimStore.getState().applyPulse({
      id: 'e1', type: 'universe.pulsed', tick: 5, universe_id: 1, world_id: null,
      severity: 'info', occurred_at: '', payload: { entropy: 0.4, stability_index: 0.8, status: 'paused' },
    });
    expect(useSimStore.getState().live.status).toBe('paused');
  });
  it('applyPulse giữ status cũ khi payload không có status', () => {
    useSimStore.getState().applyPulse({
      id: 'e1', type: 'universe.pulsed', tick: 5, universe_id: 1, world_id: null,
      severity: 'info', occurred_at: '', payload: { entropy: 0.4, stability_index: 0.8, status: 'paused' },
    });
    useSimStore.getState().applyPulse({
      id: 'e2', type: 'universe.pulsed', tick: 6, universe_id: 1, world_id: null,
      severity: 'info', occurred_at: '', payload: { entropy: 0.4 },
    });
    expect(useSimStore.getState().live.status).toBe('paused');
  });
});
