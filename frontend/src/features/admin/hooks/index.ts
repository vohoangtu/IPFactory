'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import api from '@/lib/api';
import { useAdaptiveRefetchInterval, useCentrifugoConnection } from '@/hooks/useCentrifugo';
import { adminQueries } from '../api/queries';
import type {
  AiDiagnosticsResult,
  AiFeatureProfile,
  AiKeyPayload,
  AiProviderModel,
  DriverName,
  SimulationSetting,
} from '../types';

const KNOWN_DRIVERS: DriverName[] = [
  'pool',
  'zai',
  'openai',
  'gemini',
  'openrouter',
  'local',
  'qwen',
];

export function asString(value: unknown, fallback = ''): string {
  return typeof value === 'string' ? value : fallback;
}

export function createFeatureProfile(driver: DriverName = 'pool'): AiFeatureProfile {
  return {
    driver,
    model: '',
    max_tokens: '',
    tier: 'any',
    model_group: '',
  };
}

export function asFeatureProfile(
  value: unknown,
  fallbackDriver: DriverName = 'pool',
): AiFeatureProfile {
  if (typeof value === 'string') {
    return createFeatureProfile(value || fallbackDriver);
  }

  if (!value || typeof value !== 'object' || Array.isArray(value)) {
    return createFeatureProfile(fallbackDriver);
  }

  const data = value as Record<string, unknown>;
  const tier = asString(data.tier, 'any');

  return {
    driver: asString(data.driver, fallbackDriver),
    model: asString(data.model),
    max_tokens:
      data.max_tokens === null || data.max_tokens === undefined
        ? ''
        : String(data.max_tokens),
    tier: tier === 'free' || tier === 'premium' ? tier : 'any',
    model_group: asString(data.model_group),
  };
}

export function toFeaturePayload(profile: AiFeatureProfile) {
  return {
    driver: profile.driver,
    ...(profile.model.trim() ? { model: profile.model.trim() } : {}),
    ...(profile.max_tokens.trim()
      ? { max_tokens: Number(profile.max_tokens) }
      : {}),
    ...(profile.tier !== 'any' ? { tier: profile.tier } : {}),
    ...(profile.model_group.trim()
      ? { model_group: profile.model_group.trim() }
      : {}),
  };
}

export function buildDriverOptions(drivers: DriverName[]) {
  return Array.from(new Set<DriverName>(['pool', ...KNOWN_DRIVERS, ...drivers]));
}

export function useSimulationSettings() {
  const queryClient = useQueryClient();
  const query = useQuery(adminQueries.simulationSettings());

  const updateMutation = useMutation({
    mutationFn: (settings: SimulationSetting[]) =>
      api.post('/apex/settings/update', { settings }).then((response) => response.data),
    onSuccess: async () => {
      toast.success('Simulation settings updated.');
      await queryClient.invalidateQueries({
        queryKey: adminQueries.simulationSettings().queryKey,
      });
    },
  });

  const resetMutation = useMutation({
    mutationFn: (group?: string) =>
      api.post('/apex/settings/reset', group ? { group } : {}).then((response) => response.data),
    onSuccess: async () => {
      toast.success('Simulation settings reset.');
      await queryClient.invalidateQueries({
        queryKey: adminQueries.simulationSettings().queryKey,
      });
    },
  });

  return {
    ...query,
    settings: query.data ?? null,
    updateSettings: updateMutation.mutateAsync,
    resetSettings: resetMutation.mutateAsync,
    isUpdating: updateMutation.isPending,
    isResetting: resetMutation.isPending,
  };
}

export function useServiceStatus() {
  const { state } = useCentrifugoConnection();
  const refetchInterval = useAdaptiveRefetchInterval(state, 30_000);
  const query = useQuery(adminQueries.serviceStatus(refetchInterval));

  const healthyCount = query.data
    ? Object.values(query.data.services).filter((service) => service.status === 'ok').length
    : 0;
  const totalCount = query.data ? Object.keys(query.data.services).length : 0;

  return {
    ...query,
    serviceStatus: query.data ?? null,
    isHealthy: query.data?.overall === 'healthy',
    healthyCount,
    totalCount,
  };
}

export function useAiSettings() {
  const query = useQuery(adminQueries.aiSettings());
  return {
    ...query,
    settings: query.data ?? [],
  };
}

export function useAiDrivers() {
  const query = useQuery(adminQueries.aiDrivers());
  return {
    ...query,
    drivers: query.data ?? KNOWN_DRIVERS,
  };
}

export function useLoomAgents() {
  const query = useQuery(adminQueries.loomAgents());
  return {
    ...query,
    loomAgents: query.data ?? [],
  };
}

export function useUpdateAiSetting() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: {
      key: string;
      value: unknown;
      group?: string;
      is_secret?: boolean;
    }) => api.post('/ai-settings/update', payload).then((response) => response.data),
    onSuccess: async (_data, variables) => {
      toast.success('AI runtime setting updated.');
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: adminQueries.aiSettings().queryKey }),
        variables.key.startsWith('loom_agents.')
          ? queryClient.invalidateQueries({ queryKey: adminQueries.loomAgents().queryKey })
          : Promise.resolve(),
      ]);
    },
  });
}

export function useSyncAiSettings() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: () => api.post('/ai-settings/sync').then((response) => response.data),
    onSuccess: async () => {
      toast.success('AI runtime cache synchronized.');
      await queryClient.invalidateQueries({ queryKey: adminQueries.aiSettings().queryKey });
    },
  });
}

export function useImportAiSettings() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: () => api.post('/ai-settings/import').then((response) => response.data),
    onSuccess: async () => {
      toast.success('Imported AI runtime defaults.');
      await queryClient.invalidateQueries({ queryKey: adminQueries.aiSettings().queryKey });
    },
  });
}

export function useImportLoomAgents() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: () =>
      api.post('/ai-settings/import-loom-agents').then((response) => response.data),
    onSuccess: async () => {
      toast.success('Imported Loom agent routing.');
      await queryClient.invalidateQueries({ queryKey: adminQueries.loomAgents().queryKey });
    },
  });
}

export function useRunAiDiagnostics() {
  return useMutation({
    mutationFn: (payload: { driver?: string; prompt?: string }) =>
      api.post<AiDiagnosticsResult>('/ai-settings/diagnostics', payload).then((response) => response.data),
  });
}

export function useProviderModels() {
  const query = useQuery(adminQueries.providerModels());
  return {
    ...query,
    providerModels: query.data ?? [],
  };
}

export function useCreateProviderModel() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: Partial<AiProviderModel>) =>
      api.post('/ai-provider-models', payload).then((response) => response.data),
    onSuccess: async () => {
      toast.success('Provider model created.');
      await queryClient.invalidateQueries({ queryKey: adminQueries.providerModels().queryKey });
    },
  });
}

export function useUpdateProviderModel() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<AiProviderModel> }) =>
      api.put(`/ai-provider-models/${id}`, data).then((response) => response.data),
    onSuccess: async () => {
      toast.success('Provider model updated.');
      await queryClient.invalidateQueries({ queryKey: adminQueries.providerModels().queryKey });
    },
  });
}

export function useDeleteProviderModel() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => api.delete(`/ai-provider-models/${id}`).then((response) => response.data),
    onSuccess: async () => {
      toast.success('Provider model deleted.');
      await queryClient.invalidateQueries({ queryKey: adminQueries.providerModels().queryKey });
    },
  });
}

export function useExportProviderModels() {
  return useMutation({
    mutationFn: () => api.get('/ai-provider-models/export').then((response) => response.data),
  });
}

export function useImportProviderModels() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: unknown) =>
      api.post('/ai-provider-models/import', { data }).then((response) => response.data),
    onSuccess: async () => {
      toast.success('Provider models imported.');
      await queryClient.invalidateQueries({ queryKey: adminQueries.providerModels().queryKey });
    },
  });
}

export function useKeyPool() {
  const queryClient = useQueryClient();
  const query = useQuery(adminQueries.keyPool());

  const addMutation = useMutation({
    mutationFn: (newKey: AiKeyPayload & { key: string }) =>
      api.post('/ai-key-pool', newKey).then((response) => response.data),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: adminQueries.keyPool().queryKey });
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: AiKeyPayload }) =>
      api.put(`/ai-key-pool/${id}`, data).then((response) => response.data),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: adminQueries.keyPool().queryKey });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/ai-key-pool/${id}`).then((response) => response.data),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: adminQueries.keyPool().queryKey });
    },
  });

  return {
    ...query,
    keys: query.data ?? [],
    addKey: addMutation.mutateAsync,
    updateKey: updateMutation.mutateAsync,
    deleteKey: deleteMutation.mutateAsync,
    isAdding: addMutation.isPending,
    isUpdating: updateMutation.isPending,
    isDeleting: deleteMutation.isPending,
  };
}
