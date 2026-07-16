import { beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import type { ReactNode } from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import type { UniverseCivilization, UniverseWorldState } from '../types';

const mockGet = vi.hoisted(() => vi.fn());

vi.mock('@/shared/lib/apiClient', () => ({
  TOKEN_KEY: 'worldos_token',
  apiClient: { get: mockGet },
}));

import { useCivilization, useWorldState } from '../hooks';

const wrapper = ({ children }: { children: ReactNode }) => {
  const qc = new QueryClient({ defaultOptions: { queries: { retry: false, gcTime: 0 } } });
  return <QueryClientProvider client={qc}>{children}</QueryClientProvider>;
};

const civFixture: UniverseCivilization = {
  universe_id: 1,
  status: 'active',
  current_tick: 42,
  epoch: 2,
  metrics: { entropy: 0.42, stability_index: 0.7, structural_coherence: 0.6, fitness_score: 0.8 },
  complexity: { actor_count: 10, living_actor_count: 8, supreme_entity_count: 1 },
  snapshot: null,
};

const worldFixture: UniverseWorldState = {
  universe_id: 1,
  world_id: 5,
  epoch: { id: 2, name: 'Iron', theme: 'war', description: 'Kỷ nguyên sắt', start_tick: 10, end_tick: null, status: 'active' },
  religions: [{ id: 1, name: 'Solism', followers: 100, spread_rate: 0.1, doctrine: null }],
  treaties: [],
  technologies: [{ id: 1, name: 'fire', code: 'fire', adopters: 5, avg_level: 1.2 }],
};

describe('useCivilization / useWorldState', () => {
  beforeEach(() => {
    mockGet.mockReset();
  });

  it('useCivilization gọi endpoint civilization', async () => {
    mockGet.mockResolvedValueOnce({ data: civFixture });
    const { result } = renderHook(() => useCivilization(1), { wrapper });
    await waitFor(() => expect(result.current.civilization?.universe_id).toBe(1));
    expect(mockGet).toHaveBeenCalledWith('/worldos/observatory/universes/1/civilization');
  });

  it('useWorldState gọi endpoint world', async () => {
    mockGet.mockResolvedValueOnce({ data: worldFixture });
    const { result } = renderHook(() => useWorldState(1), { wrapper });
    await waitFor(() => expect(result.current.world?.epoch?.name).toBe('Iron'));
    expect(mockGet).toHaveBeenCalledWith('/worldos/observatory/universes/1/world');
  });

  it('universeId null → không gọi API', () => {
    renderHook(() => useCivilization(null), { wrapper });
    renderHook(() => useWorldState(null), { wrapper });
    expect(mockGet).not.toHaveBeenCalled();
  });
});
