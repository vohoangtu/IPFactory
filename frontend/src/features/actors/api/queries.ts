import { queryOptions } from '@tanstack/react-query';
import api from '@/lib/api';
import type { ActorSummary, ActorDetail, ActorEvent, ActorDecision, SupremeEntity } from '@/types/api';

export const actorQueries = {
  list: (universeId: number) =>
    queryOptions({
      queryKey: ['universes', universeId, 'actors'] as const,
      queryFn: (): Promise<ActorSummary[]> =>
        api.get(`/worldos/universes/${universeId}/actors`).then((r) => r.data),
      staleTime: 8_000,
      refetchInterval: 10_000,
      enabled: universeId > 0,
    }),

  detail: (actorId: number) =>
    queryOptions({
      queryKey: ['actors', actorId] as const,
      queryFn: (): Promise<ActorDetail> =>
        api.get(`/worldos/actors/${actorId}`).then((r) => r.data),
      enabled: actorId > 0,
    }),

  events: (actorId: number) =>
    queryOptions({
      queryKey: ['actors', actorId, 'events'] as const,
      queryFn: (): Promise<ActorEvent[]> =>
        api.get(`/worldos/actors/${actorId}/events`).then((r) => r.data),
      enabled: actorId > 0,
    }),

  decisions: (actorId: number) =>
    queryOptions({
      queryKey: ['actors', actorId, 'decisions'] as const,
      queryFn: (): Promise<ActorDecision[]> =>
        api.get(`/worldos/actors/${actorId}/decisions`).then((r) => r.data),
      enabled: actorId > 0,
    }),

  supremeEntities: (universeId: number) =>
    queryOptions({
      queryKey: ['universes', universeId, 'supreme-entities'] as const,
      queryFn: (): Promise<SupremeEntity[]> =>
        api
          .get(`/worldos/universes/${universeId}/supreme-entities`)
          .then((r) => r.data),
      enabled: universeId > 0,
    }),
};
