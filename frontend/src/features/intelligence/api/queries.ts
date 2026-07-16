import { queryOptions } from '@tanstack/react-query';
import api from '@/lib/api';
import type { PaginatedAiLogs, AiStats } from '@/shared/types/api';

interface AiLogFilters {
  feature?: string;
  driver?: string;
  model?: string;
  status?: string;
  search?: string;
  page?: number;
  limit?: number;
}

export const intelligenceQueries = {
  logs: (filters: AiLogFilters = {}) => {
    const { feature, driver, model, status, search, page = 1, limit = 15 } = filters;
    const params = new URLSearchParams();
    if (feature) params.append('feature', feature);
    if (driver)  params.append('driver', driver);
    if (model)   params.append('model', model);
    if (status)  params.append('status', status);
    if (search)  params.append('search', search);
    params.append('page', page.toString());
    params.append('limit', limit.toString());

    return queryOptions({
      queryKey: ['ai-logs', feature, driver, model, status, search, page, limit] as const,
      queryFn: (): Promise<PaginatedAiLogs> =>
        api.get(`/ai-logs?${params.toString()}`).then((r) => r.data),
      staleTime: 4_000,
      refetchInterval: 5_000,
    });
  },

  stats: () =>
    queryOptions({
      queryKey: ['ai-logs', 'stats'] as const,
      queryFn: (): Promise<AiStats> =>
        api.get('/ai-logs/stats').then((r) => r.data),
      staleTime: 4_000,
      refetchInterval: 5_000,
    }),
};
