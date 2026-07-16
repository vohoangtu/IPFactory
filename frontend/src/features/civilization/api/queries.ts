import { queryOptions } from '@tanstack/react-query';
import { apiClient } from '@/shared/lib/apiClient';
import { qk } from '@/shared/config/queryKeys';
import type { UniverseCivilization, UniverseWorldState } from '../types';

export const civilizationQueries = {
  civilization: (universeId: number) =>
    queryOptions({
      queryKey: qk.civilization(universeId),
      queryFn: async (): Promise<UniverseCivilization> =>
        (await apiClient.get(`/worldos/observatory/universes/${universeId}/civilization`)).data as UniverseCivilization,
      staleTime: 8_000,
      refetchInterval: 15_000,
      enabled: universeId > 0,
    }),
  world: (universeId: number) =>
    queryOptions({
      queryKey: qk.worldState(universeId),
      queryFn: async (): Promise<UniverseWorldState> =>
        (await apiClient.get(`/worldos/observatory/universes/${universeId}/world`)).data as UniverseWorldState,
      staleTime: 8_000,
      refetchInterval: 15_000,
      enabled: universeId > 0,
    }),
};
