import { queryOptions } from '@tanstack/react-query';
import { apiClient } from '@/shared/lib/apiClient';
import { takeData } from '@/shared/lib/unwrap';
import type { UniverseMetrics } from '@/shared/types/api';

// ── Universe metrics ─────────────────────────────

/**
 * Default polling interval when WebSocket is not connected.
 * When WebSocket is active, pass `false` to disable polling.
 */
const DETAIL_POLL_MS = 10_000;

export const universeQueries = {
  /** Aggregated metrics for a single universe */
  metrics: (id: number, refetchInterval: number | false = DETAIL_POLL_MS) =>
    queryOptions({
      queryKey: ['universes', id, 'metrics'] as const,
      queryFn: async (): Promise<UniverseMetrics> =>
        takeData<UniverseMetrics>((await apiClient.get(`/worldos/universes/${id}/metrics`)).data),
      staleTime: 8_000,
      refetchInterval,
      enabled: id > 0,
    }),
};
