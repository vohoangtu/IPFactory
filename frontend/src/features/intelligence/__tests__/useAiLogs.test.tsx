import { beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import type { ReactNode } from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

const mockGet = vi.hoisted(() => vi.fn());
const mockDelete = vi.hoisted(() => vi.fn());

vi.mock('@/shared/lib/apiClient', () => ({
  TOKEN_KEY: 'worldos_token',
  apiClient: { get: mockGet, delete: mockDelete },
}));

import { useAiLogs } from '../hooks';

const wrapper = ({ children }: { children: ReactNode }) => {
  const qc = new QueryClient({ defaultOptions: { queries: { retry: false, gcTime: 0 } } });
  return <QueryClientProvider client={qc}>{children}</QueryClientProvider>;
};

describe('useAiLogs', () => {
  beforeEach(() => {
    mockGet.mockReset();
    mockDelete.mockReset();
  });

  it('gọi GET /ai-logs kèm params filter, và giữ nguyên pagination (không bị takeData nuốt meta)', async () => {
    mockGet.mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 1,
            feature: 'narrative',
            driver: 'openai',
            model: 'gpt-4o',
            input: {},
            output: {},
            latency_ms: 120,
            status: 'success',
            created_at: '2026-07-17T00:00:00Z',
          },
        ],
        current_page: 2,
        last_page: 5,
        total: 42,
        per_page: 15,
      },
    });

    const { result } = renderHook(
      () => useAiLogs({ page: 2, status: 'success', driver: 'openai', search: 'foo', limit: 15 }),
      { wrapper },
    );

    await waitFor(() => expect(result.current.isLoading).toBe(false));

    expect(mockGet).toHaveBeenCalledTimes(1);
    expect(mockGet).toHaveBeenCalledWith('/ai-logs?driver=openai&status=success&search=foo&page=2&limit=15');

    // Pagination contract: body {data, current_page, last_page, total, per_page} phải đọc nguyên vẹn,
    // không bị interceptor/takeData bóc mất current_page/last_page/total.
    expect(result.current.logs).toHaveLength(1);
    expect(result.current.pagination).toEqual({ current_page: 2, last_page: 5, total: 42 });
  });

  it('clearLogs gọi DELETE /ai-logs/clear', async () => {
    mockGet.mockResolvedValue({
      data: { data: [], current_page: 1, last_page: 1, total: 0, per_page: 15 },
    });
    mockDelete.mockResolvedValueOnce({ data: { ok: true } });

    const { result } = renderHook(() => useAiLogs(), { wrapper });
    await waitFor(() => expect(result.current.isLoading).toBe(false));

    await result.current.clearLogs();

    expect(mockDelete).toHaveBeenCalledWith('/ai-logs/clear');
  });
});
