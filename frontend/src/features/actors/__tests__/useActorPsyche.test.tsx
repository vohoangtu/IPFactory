import { beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import type { ReactNode } from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

const mockGet = vi.hoisted(() => vi.fn());

vi.mock('@/shared/lib/apiClient', () => ({
  TOKEN_KEY: 'worldos_token',
  apiClient: { get: mockGet },
}));

import { useActorPsyche } from '../hooks';

const wrapper = ({ children }: { children: ReactNode }) => {
  const qc = new QueryClient({ defaultOptions: { queries: { retry: false, gcTime: 0 } } });
  return <QueryClientProvider client={qc}>{children}</QueryClientProvider>;
};

describe('useActorPsyche', () => {
  beforeEach(() => {
    mockGet.mockReset();
  });

  it('gọi endpoint psyche và trả về read-model', async () => {
    mockGet.mockResolvedValueOnce({ data: {
      actor: { id: 3, universe_id: 1, name: 'Aria', archetype: 'sage', is_alive: true, life_stage: 'adult' },
      emotions: { fear: 0.9, anger: 0.1, sadness: 0.2, joy: 0.3, stress: 0.8, trust: 0.4 },
      needs: { survive: 1.03, safety: 0.64, belong: 0.48, esteem: 0.26 },
      goals: [{ type: 'survive', priority: 1.03 }],
      trait_vector: [0.5],
      recent_decisions: [],
    } });
    const { result } = renderHook(() => useActorPsyche(3), { wrapper });
    await waitFor(() => expect(result.current.psyche?.actor.name).toBe('Aria'));
    expect(mockGet).toHaveBeenCalledWith('/worldos/observatory/actors/3/psyche');
  });

  it('actorId null → không gọi API', () => {
    renderHook(() => useActorPsyche(null), { wrapper });
    expect(mockGet).not.toHaveBeenCalled();
  });
});
