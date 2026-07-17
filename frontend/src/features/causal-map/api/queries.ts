import { queryOptions } from '@tanstack/react-query';
import { apiClient } from '@/shared/lib/apiClient';
import { takeData } from '@/shared/lib/unwrap';
import { qk } from '@/shared/config/queryKeys';
import type { TopologyData, CausalLinkData, RealityState } from '@/shared/types/api';

export const causalMapQueries = {
  topology: (universeId: number) =>
    queryOptions({
      queryKey: qk.topology(universeId),
      queryFn: async (): Promise<TopologyData> =>
        takeData<TopologyData>((await apiClient.get(`/apex/v10/universes/${universeId}/topology`)).data),
      staleTime: 12_000,
      refetchInterval: 15_000,
      enabled: universeId > 0,
    }),

  causalLinks: (universeId: number, fromTick?: number, toTick?: number) =>
    queryOptions({
      queryKey: qk.causalLinks(universeId, fromTick, toTick),
      queryFn: async (): Promise<CausalLinkData> => {
        const params: Record<string, number> = {};
        if (fromTick !== undefined) params.from_tick = fromTick;
        if (toTick !== undefined) params.to_tick = toTick;
        return takeData<CausalLinkData>(
          (await apiClient.get(`/worldos/universes/${universeId}/causal-links`, { params })).data,
        );
      },
      // manual refetch only — disabled by default
      enabled: false,
    }),

  realityState: (universeId: number) =>
    queryOptions({
      queryKey: qk.realityState(universeId),
      queryFn: async (): Promise<RealityState> =>
        takeData<RealityState>((await apiClient.get(`/worldos/universes/${universeId}/reality-state`)).data),
      staleTime: 12_000,
      refetchInterval: 15_000,
      enabled: universeId > 0,
    }),
};
