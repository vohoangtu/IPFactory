'use client';

import { useQuery, useMutation } from '@tanstack/react-query';
import { actorQueries } from '../api/queries';
import api from '@/lib/api';
import { useCentrifugoConnection, useAdaptiveRefetchInterval } from '@/hooks/useCentrifugo';

export function useActors(universeId: number | null) {
  const { state } = useCentrifugoConnection();
  const refetchInterval = useAdaptiveRefetchInterval(state, 10_000);
  const { data, error, isLoading } = useQuery({
    ...actorQueries.list(universeId ?? 0),
    enabled: !!universeId,
    refetchInterval,
  });
  return { actors: data ?? [], isLoading, isError: !!error };
}

export function useActorDetail(actorId: number | null) {
  const { data, error, isLoading } = useQuery({
    ...actorQueries.detail(actorId ?? 0),
    enabled: !!actorId,
  });
  return { actor: data ?? null, isLoading, isError: !!error };
}

export function useActorEvents(actorId: number | null) {
  const { data, error, isLoading } = useQuery({
    ...actorQueries.events(actorId ?? 0),
    enabled: !!actorId,
  });
  return { events: data ?? [], isLoading, isError: !!error };
}

export function useActorDecisions(actorId: number | null) {
  const { data, error, isLoading } = useQuery({
    ...actorQueries.decisions(actorId ?? 0),
    enabled: !!actorId,
  });
  return { decisions: data ?? [], isLoading, isError: !!error };
}

export function useSupremeEntities(universeId: number | null) {
  const { data, error, isLoading } = useQuery({
    ...actorQueries.supremeEntities(universeId ?? 0),
    enabled: !!universeId,
  });
  return { entities: data ?? [], isLoading, isError: !!error };
}

interface MindMeldResult {
  action: string;
  confidence: number;
}

export function useMindMeld() {
  return useMutation<MindMeldResult, Error, number>({
    mutationFn: (actorId: number) =>
      api.post(`/worldos/actors/${actorId}/mind-meld`).then((r) => r.data),
  });
}
