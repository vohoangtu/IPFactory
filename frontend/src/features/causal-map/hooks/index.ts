'use client';

import { useQuery } from '@tanstack/react-query';
import { causalMapQueries } from '../api/queries';

export function useTopology(universeId: number | null) {
  const { data, error, isLoading, refetch } = useQuery({
    ...causalMapQueries.topology(universeId ?? 0),
    enabled: !!universeId,
  });
  return { topology: data, isLoading, isError: !!error, refetch };
}

export function useCausalLinks(
  universeId: number | null,
  fromTick?: number,
  toTick?: number,
) {
  const { data, error, isLoading, refetch } = useQuery({
    ...causalMapQueries.causalLinks(universeId ?? 0, fromTick, toTick),
    enabled: false, // manual refetch only
  });
  return { causalLinks: data, isLoading, isError: !!error, refetch };
}

export function useRealityState(universeId: number | null) {
  const { data, error, isLoading } = useQuery({
    ...causalMapQueries.realityState(universeId ?? 0),
    enabled: !!universeId,
  });
  return { realityState: data, isLoading, isError: !!error };
}
