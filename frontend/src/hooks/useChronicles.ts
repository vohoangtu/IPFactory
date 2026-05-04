'use client';

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';

import api from '@/lib/api';
import { useCentrifugoConnection, useAdaptiveRefetchInterval } from '@/hooks/useCentrifugo';
import type { Chronicle, MythScar, Artifact } from '@/types/api';

// ── Chronicles ─────────────────────────────────

export function useChronicles(universeId: number | null) {
    const { state } = useCentrifugoConnection();
    const refetchInterval = useAdaptiveRefetchInterval(state, 15_000);

    const { data, error, isLoading } = useQuery<Chronicle[]>({
        queryKey: ['universes', universeId, 'chronicles'],
        queryFn: () =>
            api
                .get(`/worldos/universes/${universeId}/chronicles`)
                .then((res) => res.data),
        enabled: !!universeId,
        refetchInterval,
        refetchOnWindowFocus: true,
    });

    return {
        chronicles: data ?? [],
        isLoading,
        isError: !!error,
    };
}

// ── Generate Chronicle mutation ────────────────

export function useGenerateChronicle() {
    const queryClient = useQueryClient();

    return useMutation<unknown, Error, number>({
        mutationFn: (universeId: number) =>
            api
                .post(`/worldos/universes/${universeId}/generate-chronicle`)
                .then((res) => res.data),
        onSuccess: (_data, universeId) => {
            queryClient.invalidateQueries({
                queryKey: ['universes', universeId, 'chronicles'],
            });
        },
    });
}

// ── Myth Scars ─────────────────────────────────

export function useMythScars(universeId: number | null) {
    const { state } = useCentrifugoConnection();
    const refetchInterval = useAdaptiveRefetchInterval(state, 15_000);

    const { data, error, isLoading } = useQuery<MythScar[]>({
        queryKey: ['universes', universeId, 'myth-scars'],
        queryFn: () =>
            api
                .get(`/worldos/universes/${universeId}/myth-scars`)
                .then((res) => res.data),
        enabled: !!universeId,
        refetchInterval,
        refetchOnWindowFocus: true,
    });

    return {
        mythScars: data ?? [],
        isLoading,
        isError: !!error,
    };
}

// ── Artifacts ──────────────────────────────────

export function useArtifacts(universeId: number | null) {
    const { state } = useCentrifugoConnection();
    const refetchInterval = useAdaptiveRefetchInterval(state, 15_000);

    const { data, error, isLoading } = useQuery<Artifact[]>({
        queryKey: ['universes', universeId, 'artifacts'],
        queryFn: () =>
            api
                .get(`/worldos/universes/${universeId}/artifacts`)
                .then((res) => res.data),
        enabled: !!universeId,
        refetchInterval,
        refetchOnWindowFocus: true,
    });

    return {
        artifacts: data ?? [],
        isLoading,
        isError: !!error,
    };
}
