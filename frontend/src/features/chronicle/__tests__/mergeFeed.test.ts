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
