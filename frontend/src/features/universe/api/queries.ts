import { queryOptions } from '@tanstack/react-query';
import api from '@/lib/api';
import type { UniverseOption, UniverseMetrics, UniverseDossier } from '@/shared/types/api';

// ── Universe list ────────────────────────────────

interface ResourceCollection<T> {
  data?: T[];
}

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
      queryFn: (): Promise<UniverseOption[] | ResourceCollection<UniverseOption>> =>
        api.get('/worldos/universes').then((r) => r.data),
      staleTime: 10_000,
      refetchInterval,
    }),

  /** Aggregated metrics for a single universe */
  metrics: (id: number, refetchInterval: number | false = DETAIL_POLL_MS) =>
    queryOptions({
      queryKey: ['universes', id, 'metrics'] as const,
      queryFn: (): Promise<UniverseMetrics> =>
        api.get(`/worldos/universes/${id}/metrics`).then((r) => r.data),
      staleTime: 8_000,
      refetchInterval,
      enabled: id > 0,
    }),

  /** Full dossier for a single universe */
  dossier: (id: number, refetchInterval: number | false = DETAIL_POLL_MS) =>
    queryOptions({
      queryKey: ['universes', id, 'dossier'] as const,
      queryFn: (): Promise<UniverseDossier> =>
        api.get(`/worldos/universes/${id}/dossier`).then((r) => r.data),
      staleTime: 8_000,
      refetchInterval,
      enabled: id > 0,
    }),
};
