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
