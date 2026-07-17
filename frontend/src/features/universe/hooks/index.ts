'use client';

import { useQuery, useQueryClient } from '@tanstack/react-query';
import { universeQueries } from '../api/queries';

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
