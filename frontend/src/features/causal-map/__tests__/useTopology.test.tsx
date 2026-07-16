import { beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import type { ReactNode } from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

const mockGet = vi.hoisted(() => vi.fn());

vi.mock('@/shared/lib/apiClient', () => ({
  TOKEN_KEY: 'worldos_token',
  apiClient: { get: mockGet },
}));

import { useTopology } from '../hooks';

const wrapper = ({ children }: { children: ReactNode }) => {
  const qc = new QueryClient({ defaultOptions: { queries: { retry: false, gcTime: 0 } } });
  return <QueryClientProvider client={qc}>{children}</QueryClientProvider>;
};

describe('useTopology', () => {
  beforeEach(() => {
    mockGet.mockReset();
  });

  it('gọi endpoint topology và trả về read-model', async () => {
    mockGet.mockResolvedValueOnce({
      data: {
        universe_id: 1,
        tick: 42,
        topology: {
          nodes: [{ id: 'zone-1', type: 'zone', label: 'Zone 1', metrics: {} }],
          edges: [],
        },
      },
    });
    const { result } = renderHook(() => useTopology(1), { wrapper });
    await waitFor(() => expect(result.current.topology?.tick).toBe(42));
    expect(mockGet).toHaveBeenCalledWith('/apex/v10/universes/1/topology');
  });

  it('universeId null → không gọi API', () => {
    renderHook(() => useTopology(null), { wrapper });
    expect(mockGet).not.toHaveBeenCalled();
  });
});
