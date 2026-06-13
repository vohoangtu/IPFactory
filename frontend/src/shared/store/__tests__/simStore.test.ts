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
    expect(s.live.metrics?.stability).toBe(0.5);
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
