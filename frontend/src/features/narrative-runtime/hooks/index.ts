'use client';

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { narrativeQueries, generateChronicle } from '../api/queries';
import { useAdaptiveRefetchInterval } from '@/hooks/useCentrifugo';
import { useCentrifugoConnection } from '@/hooks/useCentrifugo';

export function useLoomStatus() {
  const { data, error, isLoading, refetch } = useQuery(narrativeQueries.loomStatus());

  return {
    loomStatus: data ?? null,
    isLoading,
    isError: !!error,
    refresh: refetch,
  };
}

export function useLoomTaskStatus(taskId: string | null) {
  const { state: connectionState } = useCentrifugoConnection();
  const refetchInterval = useAdaptiveRefetchInterval(connectionState, 5_000);

  const { data, error, isLoading } = useQuery({
    ...narrativeQueries.loomTaskStatus(taskId),
    refetchInterval,
  });

  return {
    taskStatus: data ?? null,
    isLoading,
    isError: !!error,
  };
}

export { usePipelineManifest } from './usePipelineManifest';

export function useGenerateChronicle() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (universeId: number) => generateChronicle(universeId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: narrativeQueries.loomStatus().queryKey });
    },
  });
}
