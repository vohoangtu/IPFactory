import { beforeEach, describe, expect, it, vi } from 'vitest';
import { act, renderHook } from '@testing-library/react';
import type { ReactNode } from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

const mockGet = vi.hoisted(() => vi.fn());
const mockPost = vi.hoisted(() => vi.fn());
const mockDelete = vi.hoisted(() => vi.fn());

vi.mock('@/shared/lib/apiClient', () => ({
  TOKEN_KEY: 'worldos_token',
  apiClient: { get: mockGet, post: mockPost, delete: mockDelete },
}));

import { useAdvanceSimulation } from '../hooks';

const wrapper = ({ children }: { children: ReactNode }) => {
  const qc = new QueryClient({ defaultOptions: { queries: { retry: false, gcTime: 0 } } });
  return <QueryClientProvider client={qc}>{children}</QueryClientProvider>;
};

describe('useAdvanceSimulation', () => {
  beforeEach(() => {
    mockGet.mockReset();
    mockPost.mockReset();
    mockDelete.mockReset();
  });

  it('advance gọi POST /worldos/simulation/advance với universe_id + ticks', async () => {
    mockPost.mockResolvedValueOnce({ data: { ok: true } });
    const { result } = renderHook(() => useAdvanceSimulation(), { wrapper });
    await act(async () => {
      await result.current.mutateAsync({ universeId: 3, ticks: 5 });
    });
    expect(mockPost).toHaveBeenCalledWith('/worldos/simulation/advance', { universe_id: 3, ticks: 5 });
  });
});
