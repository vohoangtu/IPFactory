import { queryOptions } from '@tanstack/react-query';
import { apiClient } from '@/shared/lib/apiClient';
import { takeData } from '@/shared/lib/unwrap';
import { qk } from '@/shared/config/queryKeys';
import type { MultiverseBloom, MultiverseResonance } from '@/types/api';

export const multiverseQueries = {
  bloom: () =>
    queryOptions({
      queryKey: qk.bloom(),
      queryFn: async (): Promise<MultiverseBloom> =>
        takeData<MultiverseBloom>((await apiClient.get('/apex/multiverse/bloom')).data),
      staleTime: 12_000,
      refetchInterval: 15_000,
    }),

  resonance: () =>
    queryOptions({
      queryKey: qk.resonance(),
      queryFn: async (): Promise<MultiverseResonance> =>
        takeData<MultiverseResonance>((await apiClient.get('/apex/multiverse/resonance')).data),
      staleTime: 8_000,
      refetchInterval: 10_000,
    }),
};
