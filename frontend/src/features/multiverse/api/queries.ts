import { queryOptions } from '@tanstack/react-query';
import api from '@/lib/api';
import type { MultiverseBloom, MultiverseResonance } from '@/types/api';

export const multiverseQueries = {
  bloom: () =>
    queryOptions({
      queryKey: ['multiverse', 'bloom'] as const,
      queryFn: (): Promise<MultiverseBloom> =>
        api.get('/apex/multiverse/bloom').then((r) => r.data),
      staleTime: 12_000,
      refetchInterval: 15_000,
    }),

  resonance: () =>
    queryOptions({
      queryKey: ['multiverse', 'resonance'] as const,
      queryFn: (): Promise<MultiverseResonance> =>
        api.get('/apex/multiverse/resonance').then((r) => r.data),
      staleTime: 8_000,
      refetchInterval: 10_000,
    }),
};
