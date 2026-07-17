import { queryOptions } from '@tanstack/react-query';
import { apiClient } from '@/shared/lib/apiClient';
import { takeData } from '@/shared/lib/unwrap';
import type { UniverseOption, UniverseMetrics, UniverseDossier } from '@/shared/types/api';

// ── Universe list ────────────────────────────────

/**
 * Default polling intervals when WebSocket is not connected.
 * When WebSocket is active, pass `false` to disable polling.
 */
const LIST_POLL_MS = 15_000;
const DETAIL_POLL_MS = 10_000;

export const universeQueries = {
  /** All universes list */
  list: (refetchInterval: number | false = LIST_POLL_MS) =>
    queryOptions({
      queryKey: ['universes'] as const,
      queryFn: async (): Promise<UniverseOption[]> =>
        takeData<UniverseOption[]>((await apiClient.get('/worldos/universes')).data),
      staleTime: 10_000,
      refetchInterval,
    }),

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

  /** Full dossier for a single universe */
  dossier: (id: number, refetchInterval: number | false = DETAIL_POLL_MS) =>
    queryOptions({
      queryKey: ['universes', id, 'dossier'] as const,
      queryFn: async (): Promise<UniverseDossier> =>
        takeData<UniverseDossier>((await apiClient.get(`/worldos/universes/${id}/dossier`)).data),
      staleTime: 8_000,
      refetchInterval,
      enabled: id > 0,
    }),
};
