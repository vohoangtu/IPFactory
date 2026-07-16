import { beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import type { ReactNode } from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

const mockGet = vi.hoisted(() => vi.fn());

vi.mock('@/shared/lib/apiClient', () => ({
  TOKEN_KEY: 'worldos_token',
  apiClient: { get: mockGet },
}));

import { useWavefunction } from '../hooks';

const wrapper = ({ children }: { children: ReactNode }) => {
  const qc = new QueryClient({ defaultOptions: { queries: { retry: false, gcTime: 0 } } });
  return <QueryClientProvider client={qc}>{children}</QueryClientProvider>;
};

describe('useWavefunction', () => {
  beforeEach(() => {
    mockGet.mockReset();
  });

  it('gọi endpoint wavefunction và trả về read-model', async () => {
    mockGet.mockResolvedValueOnce({
      data: {
        universe_id: 1,
        tick: 42,
        wavefunction: {
          entropy: 0.5,
          stability_index: 0.6,
          information_density: 0.4,
          active_attractor: 'order',
          collapse_probability: 0.2,
          fields: {},
          pressures: {},
        },
        causal_topology: { ancestor_ids: [], residual_seeds: [], inherited_attractor: 'order' },
        autopoiesis: { enabled: true, entropy_threshold: 0.7, mutation_history_size: 0, last_mutation_vector: null },
      },
    });
    const { result } = renderHook(() => useWavefunction(1), { wrapper });
    await waitFor(() => expect(result.current.wavefunction?.tick).toBe(42));
    expect(mockGet).toHaveBeenCalledWith('/apex/wavefunction/1');
  });

  it('universeId null → không gọi API', () => {
    renderHook(() => useWavefunction(null), { wrapper });
    expect(mockGet).not.toHaveBeenCalled();
  });
});
