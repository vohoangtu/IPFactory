'use client';

import { useQuery, useQueryClient } from '@tanstack/react-query';
import { universeQueries } from '../api/queries';
import type { UniverseOption } from '@/types/api';

type ResourceCollection<T> = { data?: T[] };

/** List of all universes (with polling). */
export function useUniverseOptions() {
  const { data, error, isLoading } = useQuery(universeQueries.list());
  const universes: UniverseOption[] = Array.isArray(data)
    ? data
    : ((data as ResourceCollection<UniverseOption>)?.data ?? []);

  return { universes, isLoading, isError: !!error };
}

/** Aggregated metrics for the active universe. */
export function useUniverseMetrics(universeId?: number | null) {
  const queryClient = useQueryClient();
  const { data, error, isLoading } = useQuery({
    ...universeQueries.metrics(universeId ?? 0),
    enabled: !!universeId,
  });

  const mutate = () =>
    queryClient.invalidateQueries({ queryKey: ['universes', universeId, 'metrics'] });

  return { metrics: data, isLoading, isError: !!error, mutate };
}

/** Full dossier for the active universe. */
export function useUniverseDossier(universeId?: number | null) {
  const queryClient = useQueryClient();
  const { data, error, isLoading } = useQuery({
    ...universeQueries.dossier(universeId ?? 0),
    enabled: !!universeId,
  });

  const mutate = () =>
    queryClient.invalidateQueries({ queryKey: ['universes', universeId, 'dossier'] });

  return { dossier: data, isLoading, isError: !!error, mutate };
}
