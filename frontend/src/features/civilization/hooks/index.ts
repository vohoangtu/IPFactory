'use client';
import { useQuery } from '@tanstack/react-query';
import { civilizationQueries } from '../api/queries';

export function useCivilization(universeId: number | null) {
  const { data, error, isLoading } = useQuery({ ...civilizationQueries.civilization(universeId ?? 0), enabled: !!universeId });
  return { civilization: data ?? null, isLoading, isError: !!error };
}

export function useWorldState(universeId: number | null) {
  const { data, error, isLoading } = useQuery({ ...civilizationQueries.world(universeId ?? 0), enabled: !!universeId });
  return { world: data ?? null, isLoading, isError: !!error };
}
