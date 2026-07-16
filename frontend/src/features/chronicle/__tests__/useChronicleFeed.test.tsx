import { beforeEach, describe, expect, it, vi } from 'vitest';
import { act, renderHook, waitFor } from '@testing-library/react';
import type { ReactNode } from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import type { FeedItem } from '@/shared/realtime/envelope';

const mockGet = vi.hoisted(() => vi.fn());

vi.mock('@/shared/lib/apiClient', () => ({
  TOKEN_KEY: 'worldos_token',
  apiClient: { get: mockGet },
}));

import { useChronicleFeed } from '../hooks/useChronicleFeed';
import { useFeedStore } from '@/shared/store/feedStore';

const wrapper = ({ children }: { children: ReactNode }) => {
  const qc = new QueryClient({ defaultOptions: { queries: { retry: false, gcTime: 0 } } });
  return <QueryClientProvider client={qc}>{children}</QueryClientProvider>;
};

function makeItem(id: string, tick: number, universeId = 1): FeedItem {
  return {
    id, kind: 'event', type: 'epoch.transitioned', tick, universe_id: universeId,
    severity: 'notable', occurred_at: '2026-07-15T00:00:00+00:00', payload: {},
  };
}

describe('useChronicleFeed', () => {
  beforeEach(() => {
    useFeedStore.getState().clear();
    mockGet.mockReset();
  });

  it('tải trang đầu và gộp với live store', async () => {
    mockGet.mockResolvedValueOnce({
      data: { data: [makeItem('h1', 5, 5)], meta: { count: 1, next_before_tick: null } },
    });
    useFeedStore.getState().pushLive({
      id: 'live1', kind: 'event', type: 'anomaly.detected', tick: 9, universe_id: 5,
      severity: 'critical', occurred_at: '2026-07-15T00:01:00+00:00', payload: {},
    });
    const { result } = renderHook(() => useChronicleFeed(5), { wrapper });
    await waitFor(() => expect(result.current.items).toHaveLength(2));
    expect(result.current.items.map((i) => i.id)).toEqual(['live1', 'h1']);
    expect(result.current.hasOlder).toBe(false);
    expect(mockGet).toHaveBeenCalledWith(
      '/worldos/observatory/universes/5/feed',
      expect.objectContaining({ params: expect.objectContaining({ limit: 50 }) }),
    );
  });

  it('backfillLatest fetch after_tick = tick mới nhất - 1 và đẩy kết quả vào feedStore', async () => {
    // trang đầu: item tick 10
    mockGet.mockResolvedValueOnce({ data: { data: [makeItem('a', 10)], meta: { count: 1, next_before_tick: null } } });
    const { result } = renderHook(() => useChronicleFeed(1), { wrapper });
    await waitFor(() => expect(result.current.items).toHaveLength(1));

    // backfill: trả về 1 item mới tick 12
    mockGet.mockResolvedValueOnce({ data: { data: [makeItem('b', 12)], meta: { count: 1, next_before_tick: null } } });
    await act(async () => { result.current.backfillLatest(); });

    await waitFor(() => expect(result.current.items.map((i) => i.id)).toEqual(['b', 'a']));
    expect(mockGet).toHaveBeenLastCalledWith(
      '/worldos/observatory/universes/1/feed',
      expect.objectContaining({ params: expect.objectContaining({ after_tick: 9 }) }),
    );
  });

  it('backfillLatest fallback refetch khi trang backfill đầy (>= FEED_PAGE_LIMIT)', async () => {
    mockGet.mockResolvedValueOnce({ data: { data: [makeItem('a', 10)], meta: { count: 1, next_before_tick: null } } });
    const { result } = renderHook(() => useChronicleFeed(1), { wrapper });
    await waitFor(() => expect(result.current.items).toHaveLength(1));

    const bigPage = Array.from({ length: 50 }, (_, i) => makeItem(`x${i}`, 11 + i));
    mockGet.mockResolvedValueOnce({ data: { data: bigPage, meta: { count: 50, next_before_tick: null } } });
    // refetch (trang đầu mới) sau fallback
    mockGet.mockResolvedValueOnce({ data: { data: [makeItem('fresh', 99)], meta: { count: 1, next_before_tick: null } } });

    await act(async () => { result.current.backfillLatest(); });
    await waitFor(() => expect(mockGet).toHaveBeenCalledTimes(3));
  });
});
