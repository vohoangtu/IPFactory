import { queryOptions } from '@tanstack/react-query';
import { apiClient } from '@/shared/lib/apiClient';
import { takeData } from '@/shared/lib/unwrap';
import { qk } from '@/shared/config/queryKeys';
import type {
  WavefunctionData,
  InformationalMass,
  ConsciousnessField,
  AscensionFilterData,
  StateDelta,
} from '@/shared/types/api';

export const wavefunctionQueries = {
  snapshot: (universeId: number) =>
    queryOptions({
      queryKey: qk.wavefunction(universeId),
      queryFn: async (): Promise<WavefunctionData> =>
        takeData<WavefunctionData>((await apiClient.get(`/apex/wavefunction/${universeId}`)).data),
      staleTime: 4_000,
      refetchInterval: 5_000,
      enabled: universeId > 0,
    }),

  informationalMass: (universeId: number) =>
    queryOptions({
      queryKey: qk.informationalMass(universeId),
      queryFn: async (): Promise<InformationalMass> =>
        takeData<InformationalMass>((await apiClient.get(`/apex/informational-mass/${universeId}`)).data),
      staleTime: 8_000,
      refetchInterval: 10_000,
      enabled: universeId > 0,
    }),

  consciousness: (universeId: number) =>
    queryOptions({
      queryKey: qk.consciousness(universeId),
      queryFn: async (): Promise<ConsciousnessField> =>
        takeData<ConsciousnessField>(
          (await apiClient.get(`/apex/v10/universes/${universeId}/consciousness`)).data,
        ),
      staleTime: 8_000,
      refetchInterval: 10_000,
      enabled: universeId > 0,
    }),

  ascensionFilters: (universeId: number) =>
    queryOptions({
      queryKey: qk.ascensionFilters(universeId),
      queryFn: async (): Promise<AscensionFilterData> =>
        takeData<AscensionFilterData>(
          (await apiClient.get(`/apex/v10/universes/${universeId}/ascension-filters`)).data,
        ),
      staleTime: 8_000,
      refetchInterval: 10_000,
      enabled: universeId > 0,
    }),

  stateDelta: (universeId: number) =>
    queryOptions({
      queryKey: qk.stateDelta(universeId),
      queryFn: async (): Promise<StateDelta> =>
        takeData<StateDelta>((await apiClient.get(`/apex/v10/universes/${universeId}/delta`)).data),
      staleTime: 12_000,
      refetchInterval: 15_000,
      enabled: universeId > 0,
    }),
};
