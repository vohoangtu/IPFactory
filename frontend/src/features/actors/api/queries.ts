import { queryOptions } from '@tanstack/react-query';
import { apiClient } from '@/shared/lib/apiClient';
import { takeData } from '@/shared/lib/unwrap';
import { qk } from '@/shared/config/queryKeys';
import type { ActorSummary, ActorDetail, ActorEvent, ActorDecision, SupremeEntity } from '@/types/api';
import type { ActorPsyche } from '../types';

export const actorQueries = {
  list: (universeId: number) =>
    queryOptions({
      queryKey: qk.actors(universeId),
      queryFn: async (): Promise<ActorSummary[]> =>
        takeData<ActorSummary[]>((await apiClient.get(`/worldos/universes/${universeId}/actors`)).data),
      staleTime: 8_000,
      refetchInterval: 10_000,
      enabled: universeId > 0,
    }),
  detail: (actorId: number) =>
    queryOptions({
      queryKey: ['actors', actorId] as const,
      queryFn: async (): Promise<ActorDetail> =>
        takeData<ActorDetail>((await apiClient.get(`/worldos/actors/${actorId}`)).data),
      enabled: actorId > 0,
    }),
  events: (actorId: number) =>
    queryOptions({
      queryKey: ['actors', actorId, 'events'] as const,
      queryFn: async (): Promise<ActorEvent[]> =>
        takeData<ActorEvent[]>((await apiClient.get(`/worldos/actors/${actorId}/events`)).data),
      enabled: actorId > 0,
    }),
  decisions: (actorId: number) =>
    queryOptions({
      queryKey: ['actors', actorId, 'decisions'] as const,
      queryFn: async (): Promise<ActorDecision[]> =>
        takeData<ActorDecision[]>((await apiClient.get(`/worldos/actors/${actorId}/decisions`)).data),
      enabled: actorId > 0,
    }),
  psyche: (actorId: number) =>
    queryOptions({
      queryKey: qk.actorPsyche(actorId),
      queryFn: async (): Promise<ActorPsyche> =>
        (await apiClient.get(`/worldos/observatory/actors/${actorId}/psyche`)).data as ActorPsyche,
      staleTime: 5_000,
      refetchInterval: 10_000,
      enabled: actorId > 0,
    }),
  supremeEntities: (universeId: number) =>
    queryOptions({
      queryKey: qk.supremeEntities(universeId),
      queryFn: async (): Promise<SupremeEntity[]> =>
        takeData<SupremeEntity[]>((await apiClient.get(`/worldos/universes/${universeId}/supreme-entities`)).data),
      staleTime: 15_000,
      refetchInterval: 20_000,
      enabled: universeId > 0,
    }),
};
