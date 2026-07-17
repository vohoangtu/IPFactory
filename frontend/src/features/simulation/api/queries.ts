import { queryOptions } from '@tanstack/react-query';
import { apiClient } from '@/shared/lib/apiClient';
import { takeData } from '@/shared/lib/unwrap';
import { qk } from '@/shared/config/queryKeys';
import type { Snapshot, BranchSummary } from '@/shared/types/api';

/**
 * Default polling interval (15s) when WebSocket is not connected.
 * When WebSocket is active, pass `false` to disable polling.
 */
const DEFAULT_POLL_MS = 15_000;

export const simulationQueries = {
  /** Snapshots for a universe */
  snapshots: (universeId: number, refetchInterval: number | false = DEFAULT_POLL_MS) =>
    queryOptions({
      queryKey: qk.snapshots(universeId),
      queryFn: async (): Promise<Snapshot[]> => {
        const data = takeData<Snapshot[] | undefined>(
          (await apiClient.get(`/worldos/universes/${universeId}/snapshots`, { params: { limit: 50 } })).data,
        );
        return Array.isArray(data) ? data : [];
      },
      staleTime: 10_000,
      refetchInterval,
      enabled: universeId > 0,
    }),

  /** Forks / branches for a universe */
  forks: (universeId: number, refetchInterval: number | false = DEFAULT_POLL_MS) =>
    queryOptions({
      queryKey: qk.forks(universeId),
      queryFn: async (): Promise<BranchSummary[]> => {
        const data = takeData<BranchSummary[] | undefined>(
          (await apiClient.get(`/worldos/universes/${universeId}/forks`)).data,
        );
        return Array.isArray(data) ? data : [];
      },
      staleTime: 10_000,
      refetchInterval,
      enabled: universeId > 0,
    }),

  /** Settings / config (no refetch interval — manual refresh) */
  config: () =>
    queryOptions({
      queryKey: ['simulation', 'settings'] as const,
      queryFn: async () => takeData((await apiClient.get('/apex/settings')).data),
      staleTime: 30_000,
    }),
};
