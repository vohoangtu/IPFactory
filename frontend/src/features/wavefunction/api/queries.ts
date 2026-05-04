import { queryOptions } from '@tanstack/react-query';
import api from '@/lib/api';
import type {
  WavefunctionData,
  InformationalMass,
  ConsciousnessField,
  AscensionFilterData,
  StateDelta,
} from '@/types/api';

export const wavefunctionQueries = {
  snapshot: (universeId: number) =>
    queryOptions({
      queryKey: ['wavefunction', universeId] as const,
      queryFn: (): Promise<WavefunctionData> =>
        api.get(`/apex/wavefunction/${universeId}`).then((r) => r.data),
      staleTime: 4_000,
      refetchInterval: 5_000,
      enabled: universeId > 0,
    }),

  informationalMass: (universeId: number) =>
    queryOptions({
      queryKey: ['informational-mass', universeId] as const,
      queryFn: (): Promise<InformationalMass> =>
        api.get(`/apex/informational-mass/${universeId}`).then((r) => r.data),
      staleTime: 8_000,
      refetchInterval: 10_000,
      enabled: universeId > 0,
    }),

  consciousness: (universeId: number) =>
    queryOptions({
      queryKey: ['consciousness', universeId] as const,
      queryFn: (): Promise<ConsciousnessField> =>
        api
          .get(`/apex/v10/universes/${universeId}/consciousness`)
          .then((r) => r.data),
      staleTime: 8_000,
      refetchInterval: 10_000,
      enabled: universeId > 0,
    }),

  ascensionFilters: (universeId: number) =>
    queryOptions({
      queryKey: ['ascension-filters', universeId] as const,
      queryFn: (): Promise<AscensionFilterData> =>
        api
          .get(`/apex/v10/universes/${universeId}/ascension-filters`)
          .then((r) => r.data),
      staleTime: 8_000,
      refetchInterval: 10_000,
      enabled: universeId > 0,
    }),

  stateDelta: (universeId: number) =>
    queryOptions({
      queryKey: ['state-delta', universeId] as const,
      queryFn: (): Promise<StateDelta> =>
        api
          .get(`/apex/v10/universes/${universeId}/delta`)
          .then((r) => r.data),
      staleTime: 12_000,
      refetchInterval: 15_000,
      enabled: universeId > 0,
    }),
};
