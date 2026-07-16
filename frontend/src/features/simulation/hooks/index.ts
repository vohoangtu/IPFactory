'use client';

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { simulationQueries } from '../api/queries';
import { apiClient } from '@/shared/lib/apiClient';
import { takeData } from '@/shared/lib/unwrap';
import { qk } from '@/shared/config/queryKeys';
import type { BranchComparison } from '@/shared/types/api';

// ── Snapshots ───────────────────────────────────────────────────────

export function useSnapshots(universeId: number | null) {
  const { data, error, isLoading } = useQuery({
    ...simulationQueries.snapshots(universeId ?? 0),
    enabled: !!universeId,
  });
  return { snapshots: data ?? [], isLoading, isError: !!error };
}

export function useCreateSnapshot() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) =>
      takeData((await apiClient.post(`/worldos/universes/${id}/snapshots`)).data),
    onSuccess: (_, id) =>
      queryClient.invalidateQueries({
        queryKey: simulationQueries.snapshots(id).queryKey,
      }),
  });
}

// ── Forks / Branches ────────────────────────────────────────────────

export function useForks(universeId: number | null) {
  const { data, error, isLoading } = useQuery({
    ...simulationQueries.forks(universeId ?? 0),
    enabled: !!universeId,
  });
  return { forks: data ?? [], isLoading, isError: !!error };
}

export function useForkUniverse() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({
      universeId,
      tick,
      name,
    }: {
      universeId: number;
      tick?: number;
      name?: string;
    }) =>
      takeData<{ ok: boolean; child_universe_id: number }>(
        (
          await apiClient.post(`/worldos/universes/${universeId}/fork`, {
            tick,
            name,
          })
        ).data,
      ),
    onSuccess: (_, { universeId }) => {
      queryClient.invalidateQueries({
        queryKey: simulationQueries.forks(universeId).queryKey,
      });
      queryClient.invalidateQueries({ queryKey: qk.universes() });
    },
  });
}

export function useCompareBranch(
  universeId: number | null,
  branchId: number | null,
  enabled = true,
) {
  const { data, error, isLoading, isFetching } = useQuery({
    queryKey: ['universes', universeId, 'forks', 'compare', branchId] as const,
    queryFn: async (): Promise<BranchComparison> =>
      takeData<BranchComparison>(
        (
          await apiClient.get(`/worldos/universes/${universeId}/forks/compare`, {
            params: { branch_id: branchId },
          })
        ).data,
      ),
    enabled: enabled && !!universeId && !!branchId,
    staleTime: 15_000,
  });

  return { comparison: data ?? null, isLoading, isFetching, isError: !!error };
}

export const useBranchComparison = useCompareBranch;

// ── Advance / Toggle ─────────────────────────────────────────────────

export function useAdvanceSimulation() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({
      universeId,
      ticks,
    }: {
      universeId: number;
      ticks: number;
    }) =>
      takeData<{ ok: boolean }>(
        (
          await apiClient.post('/worldos/simulation/advance', {
            universe_id: universeId,
            ticks,
          })
        ).data,
      ),
    onSuccess: (_, { universeId }) => {
      queryClient.invalidateQueries({ queryKey: qk.universes() });
      queryClient.invalidateQueries({ queryKey: qk.metrics(universeId) });
      queryClient.invalidateQueries({ queryKey: ['universes', universeId, 'dossier'] });
      queryClient.invalidateQueries({ queryKey: qk.snapshots(universeId) });
    },
  });
}

export function useToggleUniverse() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) =>
      takeData<{ ok: boolean; status: string }>(
        (await apiClient.post(`/worldos/universes/${id}/toggle-status`)).data,
      ),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: qk.universes() }),
  });
}

// ── Create / Delete Universe ─────────────────────────────────────────

export function useCreateUniverse() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({ name, base_genre }: { name: string; base_genre: string }) =>
      takeData((await apiClient.post('/worldos/universes', { name, base_genre })).data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: qk.universes() }),
  });
}

export function useDeleteUniverse() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) =>
      takeData((await apiClient.delete(`/worldos/universes/${id}`)).data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: qk.universes() }),
  });
}
