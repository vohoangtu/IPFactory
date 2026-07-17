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

import { useAiLogs, useAiPool } from '../hooks';

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

  it('clearLogs invalidate ca 2 nhanh qk — ["ops","ai-logs"] va ["ops","ai-stats"] (regression dual-invalidation)', async () => {
    mockGet.mockResolvedValue({
      data: { data: [], current_page: 1, last_page: 1, total: 0, per_page: 15 },
    });
    mockDelete.mockResolvedValueOnce({ data: { ok: true } });

    const qc = new QueryClient({ defaultOptions: { queries: { retry: false, gcTime: 0 } } });
    const invalidateSpy = vi.spyOn(qc, 'invalidateQueries');
    const localWrapper = ({ children }: { children: ReactNode }) => (
      <QueryClientProvider client={qc}>{children}</QueryClientProvider>
    );

    const { result } = renderHook(() => useAiLogs(), { wrapper: localWrapper });
    await waitFor(() => expect(result.current.isLoading).toBe(false));

    await result.current.clearLogs();

    // Key cu long ['ai-logs', 'stats'] duoi prefix ['ai-logs'] nen purge cung lam mat hieu luc
    // cache stats; qk moi tach ['ops','ai-logs'] / ['ops','ai-stats'] la 2 nhanh song song nen
    // clearLogs phai invalidate ro ca hai — khoa lai hanh vi nay de tranh regression.
    expect(invalidateSpy).toHaveBeenCalledWith({ queryKey: ['ops', 'ai-logs'] });
    expect(invalidateSpy).toHaveBeenCalledWith({ queryKey: ['ops', 'ai-stats'] });
  });
});

describe('useAiPool', () => {
  beforeEach(() => {
    mockGet.mockReset();
    mockDelete.mockReset();
  });

  it('đọc use_pool khi body là {data} thuần (interceptor đã tự bóc, takeData no-op)', async () => {
    // apiClient thật đã tự bóc envelope 1-key {data} trong interceptor, nên response.data
    // (giá trị apiClient.get trả về) chính là mảng đã bóc — mock ở đây mô phỏng đúng hành vi đó.
    mockGet.mockResolvedValueOnce({
      data: [{ key: 'use_pool', value: true }],
    });

    const { result } = renderHook(() => useAiPool(), { wrapper });
    await waitFor(() => expect(result.current.isLoading).toBe(false));

    expect(result.current.usePool).toBe(true);
    expect(result.current.isError).toBe(false);
  });

  it('đọc use_pool khi body là {data, meta} (interceptor không bóc, takeData phải tự bóc)', async () => {
    mockGet.mockResolvedValueOnce({
      data: { data: [{ key: 'use_pool', value: true }], meta: {} },
    });

    const { result } = renderHook(() => useAiPool(), { wrapper });
    await waitFor(() => expect(result.current.isLoading).toBe(false));

    expect(result.current.usePool).toBe(true);
    expect(result.current.isError).toBe(false);
  });
});
