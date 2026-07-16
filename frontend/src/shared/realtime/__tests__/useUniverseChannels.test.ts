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
