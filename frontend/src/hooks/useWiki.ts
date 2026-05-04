'use client';

import { useQuery } from '@tanstack/react-query';

import api from '@/lib/api';

// ── Wiki Search ────────────────────────────────

export interface WikiSearchResult {
  actors: unknown[];
  chronicles: unknown[];
  axioms: unknown[];
  metadata?: Record<string, unknown>;
}

export function useWikiSearch(universeId: number | null, query: string) {
  const { data, error, isLoading } = useQuery<WikiSearchResult>({
    queryKey: ['wiki', universeId, 'search', query],
    queryFn: () =>
      api
        .get(`/wiki/${universeId}/search`, { params: { q: query } })
        .then((res) => res.data),
    enabled: !!universeId && query.length > 2,
    staleTime: 30_000,
    refetchOnWindowFocus: false,
  });

  return {
    results: data ?? { actors: [], chronicles: [], axioms: [] },
    isLoading,
    isError: !!error,
  };
}

// ── Actor Wiki ─────────────────────────────────

export interface ActorWikiData {
  actor: unknown;
  related: unknown[];
  events: unknown[];
}

export function useActorWiki(universeId: number | null, actorId: number | null) {
  const { data, error, isLoading } = useQuery<ActorWikiData>({
    queryKey: ['wiki', universeId, 'actor', actorId],
    queryFn: () =>
      api
        .get(`/wiki/${universeId}/actor/${actorId}`)
        .then((res) => res.data),
    enabled: !!universeId && !!actorId,
    staleTime: 30_000,
    refetchOnWindowFocus: false,
  });

  return {
    wiki: data ?? null,
    isLoading,
    isError: !!error,
  };
}

// ── Axioms ─────────────────────────────────────

export interface Axiom {
  key: string;
  value: unknown;
  category?: string;
}

export function useAxioms() {
  const { data, error, isLoading } = useQuery<Axiom[]>({
    queryKey: ['wiki', 'axioms'],
    queryFn: () =>
      api.get('/wiki/axioms').then((res) => res.data),
    staleTime: 60_000,
    refetchOnWindowFocus: false,
  });

  return {
    axioms: data ?? [],
    isLoading,
    isError: !!error,
  };
}
