import { queryOptions } from '@tanstack/react-query';
import api from '@/lib/api';
import type { TopologyData, CausalLinkData, RealityState } from '@/types/api';

export const causalMapQueries = {
  topology: (universeId: number) =>
    queryOptions({
      queryKey: ['topology', universeId] as const,
      queryFn: (): Promise<TopologyData> =>
        api
          .get(`/apex/v10/universes/${universeId}/topology`)
          .then((r) => r.data),
      staleTime: 12_000,
      refetchInterval: 15_000,
      enabled: universeId > 0,
    }),

  causalLinks: (universeId: number, fromTick?: number, toTick?: number) =>
    queryOptions({
      queryKey: ['causal-links', universeId, fromTick, toTick] as const,
      queryFn: (): Promise<CausalLinkData> => {
        const params: Record<string, number> = {};
        if (fromTick !== undefined) params.from_tick = fromTick;
        if (toTick !== undefined) params.to_tick = toTick;
        return api
          .get(`/worldos/universes/${universeId}/causal-links`, { params })
          .then((r) => r.data);
      },
      // manual refetch only — disabled by default
      enabled: false,
    }),

  realityState: (universeId: number) =>
    queryOptions({
      queryKey: ['reality-state', universeId] as const,
      queryFn: (): Promise<RealityState> =>
        api
          .get(`/worldos/universes/${universeId}/reality-state`)
          .then((r) => r.data),
      staleTime: 12_000,
      refetchInterval: 15_000,
      enabled: universeId > 0,
    }),
};
