'use client';

import { useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@/shared/lib/apiClient';
import { intelligenceQueries, type AiLogFilters } from '../api/queries';

export function useAiLogs(filters: AiLogFilters = {}) {
  const queryClient = useQueryClient();

  const { data, error, isLoading } = useQuery(intelligenceQueries.logs(filters));

  const clearLogs = async () => {
    await apiClient.delete('/ai-logs/clear');
    // Key cũ lồng ['ai-logs', 'stats'] dưới prefix ['ai-logs'] nên purge cũng làm mất hiệu lực
    // cache stats; qk mới tách ['ops','ai-logs'] / ['ops','ai-stats'] là 2 nhánh song song nên
    // phải invalidate rõ cả hai để giữ đúng hành vi cũ.
    await Promise.all([
      queryClient.invalidateQueries({ queryKey: ['ops', 'ai-logs'] }),
      queryClient.invalidateQueries({ queryKey: ['ops', 'ai-stats'] }),
    ]);
  };

  const mutate = () => queryClient.invalidateQueries({ queryKey: ['ops', 'ai-logs'] });

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
    queryKey: ['ops', 'ai-settings', 'pool'],
    queryFn: async () => {
      const response = await apiClient.get<{ key: string; value: unknown }[]>('/ai-settings');
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

  const { data, error, isLoading } = useQuery(intelligenceQueries.stats());

  const mutate = () => queryClient.invalidateQueries({ queryKey: ['ops', 'ai-stats'] });

  return {
    stats: data,
    isLoading,
    isError: !!error,
    mutate,
  };
}
