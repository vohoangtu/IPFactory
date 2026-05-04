'use client';

import { useQuery } from '@tanstack/react-query';
import { multiverseQueries } from '../api/queries';
import { useCentrifugoConnection, useAdaptiveRefetchInterval } from '@/hooks/useCentrifugo';

export function useMultiverseBloom() {
  const { state } = useCentrifugoConnection();
  const refetchInterval = useAdaptiveRefetchInterval(state, 15_000);
  const { data, error, isLoading } = useQuery({
    ...multiverseQueries.bloom(),
    refetchInterval,
  });
  return { bloom: data, isLoading, isError: !!error };
}

export function useMultiverseResonance() {
  const { state } = useCentrifugoConnection();
  const refetchInterval = useAdaptiveRefetchInterval(state, 10_000);
  const { data, error, isLoading } = useQuery({
    ...multiverseQueries.resonance(),
    refetchInterval,
  });
  return { resonance: data, isLoading, isError: !!error };
}
