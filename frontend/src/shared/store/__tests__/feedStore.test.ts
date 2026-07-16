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
