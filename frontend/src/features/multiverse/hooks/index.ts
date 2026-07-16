'use client';

import { useQuery } from '@tanstack/react-query';
import { multiverseQueries } from '../api/queries';

export function useMultiverseBloom() {
  const { data, error, isLoading } = useQuery(multiverseQueries.bloom());
  return { bloom: data, isLoading, isError: !!error };
}

export function useMultiverseResonance() {
  const { data, error, isLoading } = useQuery(multiverseQueries.resonance());
  return { resonance: data, isLoading, isError: !!error };
}
