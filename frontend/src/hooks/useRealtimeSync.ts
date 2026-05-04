'use client';

import { useCallback } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { useUniverse } from '@/contexts/UniverseContext';
import {
  useCentrifugoConnection,
  useCentrifugoSubscription,
  useAdaptiveRefetchInterval,
  type ConnectionState,
} from '@/hooks/useCentrifugo';

/**
 * Bridges Centrifugo WebSocket events to React Query cache invalidation.
 *
 * Subscribes to:
 * - `public:universes` — invalidates universe list on any pulse
 * - `universes:{id}` — invalidates metrics, snapshots, dossier for active universe
 *
 * Returns connection state for adaptive polling.
 */
export function useRealtimeSync(): {
  connectionState: ConnectionState;
  refetchInterval: number | false;
} {
  const queryClient = useQueryClient();
  const { activeUniverseId } = useUniverse();
  const { state } = useCentrifugoConnection();

  // Global channel: invalidate universe list
  const handleGlobalPulse = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ['universes'] });
  }, [queryClient]);

  useCentrifugoSubscription('public:universes', handleGlobalPulse);

  // Per-universe channel: invalidate detail queries
  const handleUniversePulse = useCallback(() => {
    if (!activeUniverseId) return;
    queryClient.invalidateQueries({
      queryKey: ['universes', activeUniverseId],
    });
  }, [queryClient, activeUniverseId]);

  const universeChannel = activeUniverseId
    ? `universes:${activeUniverseId}`
    : null;

  useCentrifugoSubscription(universeChannel, handleUniversePulse);

  const refetchInterval = useAdaptiveRefetchInterval(state, 60_000);

  return { connectionState: state, refetchInterval };
}
