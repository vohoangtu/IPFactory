import { beforeEach, describe, expect, it, vi } from 'vitest';
import { act, renderHook, waitFor } from '@testing-library/react';
import type { ReactNode } from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

const mockGet = vi.hoisted(() => vi.fn());
const mockPost = vi.hoisted(() => vi.fn());

vi.mock('@/shared/lib/apiClient', () => ({
  TOKEN_KEY: 'worldos_token',
  apiClient: { get: mockGet, post: mockPost },
}));

import { useServiceStatus, useSimulationSettings } from '../hooks';

const wrapper = ({ children }: { children: ReactNode }) => {
  const qc = new QueryClient({ defaultOptions: { queries: { retry: false, gcTime: 0 } } });
  return <QueryClientProvider client={qc}>{children}</QueryClientProvider>;
};

describe('useServiceStatus', () => {
  beforeEach(() => {
    mockGet.mockReset();
    mockPost.mockReset();
  });

  it('gọi GET /worldos/service-status và map dữ liệu healthy count', async () => {
    mockGet.mockResolvedValueOnce({
      data: {
        overall: 'healthy',
        services: { db: { status: 'ok' }, redis: { status: 'ok' } },
        checked_at: 'now',
      },
    });

    const { result } = renderHook(() => useServiceStatus(), { wrapper });

    await waitFor(() => expect(result.current.serviceStatus?.overall).toBe('healthy'));
    expect(mockGet).toHaveBeenCalledWith('/worldos/service-status');
    expect(result.current.isHealthy).toBe(true);
    expect(result.current.healthyCount).toBe(2);
    expect(result.current.totalCount).toBe(2);
  });
});

describe('useSimulationSettings — updateSettings mutation', () => {
  beforeEach(() => {
    mockGet.mockReset();
    mockPost.mockReset();
  });

  it('updateSettings gọi POST /apex/settings/update với { settings }', async () => {
    mockGet.mockResolvedValueOnce({ data: { general: [] } });
    mockPost.mockResolvedValueOnce({ data: { ok: true } });

    const { result } = renderHook(() => useSimulationSettings(), { wrapper });

    const payload = [{ key: 'tick_rate', value: 5, group: 'general' }];
    await act(async () => {
      await result.current.updateSettings(payload);
    });

    expect(mockPost).toHaveBeenCalledWith('/apex/settings/update', { settings: payload });
  });
});
