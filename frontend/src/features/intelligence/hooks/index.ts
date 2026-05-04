'use client';

import { useQuery, useQueryClient } from '@tanstack/react-query';
import { intelligenceQueries } from '../api/queries';
import api from '@/lib/api';
import { useCentrifugoConnection, useAdaptiveRefetchInterval } from '@/hooks/useCentrifugo';

export function useAiLogs(
  filters: {
    feature?: string;
    driver?: string;
    model?: string;
    status?: string;
    search?: string;
    page?: number;
    limit?: number;
  } = {},
) {
  const queryClient = useQueryClient();
  const { state } = useCentrifugoConnection();
  const refetchInterval = useAdaptiveRefetchInterval(state, 5_000);

  const { data, error, isLoading } = useQuery({
    ...intelligenceQueries.logs(filters),
    refetchInterval,
  });

  const clearLogs = async () => {
    await api.delete('/ai-logs/clear');
    await queryClient.invalidateQueries({ queryKey: ['ai-logs'] });
  };

  const mutate = () => queryClient.invalidateQueries({ queryKey: ['ai-logs'] });

  return {
    logs: data?.data ?? [],
    pagination: data
      ? {
          current_page: data.current_page,
          last_page: data.last_page,
          total: data.total,
        }
      : null,
    isLoading,
    isError: !!error,
    mutate,
    clearLogs,
  };
}

export function useAiPool() {
  const { data, error, isLoading } = useQuery({
    queryKey: ['ai-settings', 'pool'],
    queryFn: async () => {
      const response = await api.get<{ key: string; value: unknown }[]>('/ai-settings');
      const usePoolRecord = response.data.find((record) => record.key === 'use_pool');
      const value = usePoolRecord?.value;
      return typeof value === 'boolean' ? value : String(value).toLowerCase() === 'true';
    },
    staleTime: 30_000,
  });

  return {
    usePool: data ?? null,
    isLoading,
    isError: !!error,
  };
}

export function useAiStats() {
  const queryClient = useQueryClient();
  const { state } = useCentrifugoConnection();
  const refetchInterval = useAdaptiveRefetchInterval(state, 5_000);

  const { data, error, isLoading } = useQuery({
    ...intelligenceQueries.stats(),
    refetchInterval,
  });

  const mutate = () => queryClient.invalidateQueries({ queryKey: ['ai-logs', 'stats'] });

  return {
    stats: data,
    isLoading,
    isError: !!error,
    mutate,
  };
}
