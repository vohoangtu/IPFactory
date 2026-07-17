import { queryOptions } from '@tanstack/react-query';
import { apiClient } from '@/shared/lib/apiClient';
import { takeData } from '@/shared/lib/unwrap';
import { qk } from '@/shared/config/queryKeys';
import type { PaginatedAiLogs, AiStats } from '@/shared/types/api';

export interface AiLogFilters {
  feature?: string;
  driver?: string;
  model?: string;
  status?: string;
  search?: string;
  page?: number;
  limit?: number;
}

function buildLogParams(filters: AiLogFilters): URLSearchParams {
  const { feature, driver, model, status, search, page = 1, limit = 15 } = filters;
  const params = new URLSearchParams();
  if (feature) params.append('feature', feature);
  if (driver) params.append('driver', driver);
  if (model) params.append('model', model);
  if (status) params.append('status', status);
  if (search) params.append('search', search);
  params.append('page', page.toString());
  params.append('limit', limit.toString());
  return params;
}

export const intelligenceQueries = {
  logs: (filters: AiLogFilters = {}) => {
    const params = buildLogParams(filters);
    return queryOptions({
      queryKey: qk.aiLogs(params.toString()),
      // Body /ai-logs là {data, current_page, last_page, total, per_page} (5 key) — interceptor
      // apiClient chỉ bóc body {data} 1-key thuần nên không đụng vào; đọc nguyên response.data
      // như fetchFeed (P2) để giữ nguyên current_page/last_page/total, KHÔNG áp takeData ở đây.
      queryFn: async (): Promise<PaginatedAiLogs> =>
        (await apiClient.get(`/ai-logs?${params.toString()}`)).data as PaginatedAiLogs,
      staleTime: 4_000,
      refetchInterval: 5_000,
    });
  },

  stats: () =>
    queryOptions({
      queryKey: qk.aiStats(),
      queryFn: async (): Promise<AiStats> =>
        takeData<AiStats>((await apiClient.get('/ai-logs/stats')).data),
      staleTime: 4_000,
      refetchInterval: 5_000,
    }),
};
