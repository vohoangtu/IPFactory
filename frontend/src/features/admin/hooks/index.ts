'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { apiClient } from '@/shared/lib/apiClient';
import { takeData } from '@/shared/lib/unwrap';
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
    mutationFn: async (settings: SimulationSetting[]) =>
      takeData((await apiClient.post('/apex/settings/update', { settings })).data),
    onSuccess: async () => {
      toast.success('Simulation settings updated.');
      await queryClient.invalidateQueries({
        queryKey: adminQueries.simulationSettings().queryKey,
      });
    },
  });

  const resetMutation = useMutation({
    mutationFn: async (group?: string) =>
      takeData(
        (await apiClient.post('/apex/settings/reset', group ? { group } : {})).data,
      ),
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
  const query = useQuery(adminQueries.serviceStatus());

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
    mutationFn: async (payload: {
      key: string;
      value: unknown;
      group?: string;
      is_secret?: boolean;
      silent?: boolean;
    }) => {
      const body = {
        key: payload.key,
        value: payload.value,
        ...(payload.group !== undefined ? { group: payload.group } : {}),
        ...(payload.is_secret !== undefined ? { is_secret: payload.is_secret } : {}),
      };
      return takeData((await apiClient.post('/ai-settings/update', body)).data);
    },
    onSuccess: async (_data, variables) => {
      if (variables.silent) return;
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
    mutationFn: async () => takeData((await apiClient.post('/ai-settings/sync')).data),
    onSuccess: async () => {
      toast.success('AI runtime cache synchronized.');
      await queryClient.invalidateQueries({ queryKey: adminQueries.aiSettings().queryKey });
    },
  });
}

export function useImportAiSettings() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async () => takeData((await apiClient.post('/ai-settings/import')).data),
    onSuccess: async () => {
      toast.success('Imported AI runtime defaults.');
      await queryClient.invalidateQueries({ queryKey: adminQueries.aiSettings().queryKey });
    },
  });
}

export function useImportLoomAgents() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async () =>
      takeData((await apiClient.post('/ai-settings/import-loom-agents')).data),
    onSuccess: async () => {
      toast.success('Imported Loom agent routing.');
      await queryClient.invalidateQueries({ queryKey: adminQueries.loomAgents().queryKey });
    },
  });
}

export function useRunAiDiagnostics() {
  return useMutation({
    mutationFn: async (payload: { driver?: string; prompt?: string }) =>
      takeData<AiDiagnosticsResult>(
        (await apiClient.post('/ai-settings/diagnostics', payload)).data,
      ),
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
    mutationFn: async (payload: Partial<AiProviderModel>) =>
      takeData((await apiClient.post('/ai-provider-models', payload)).data),
    onSuccess: async () => {
      toast.success('Provider model created.');
      await queryClient.invalidateQueries({ queryKey: adminQueries.providerModels().queryKey });
    },
  });
}

export function useUpdateProviderModel() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, data }: { id: number; data: Partial<AiProviderModel> }) =>
      takeData((await apiClient.put(`/ai-provider-models/${id}`, data)).data),
    onSuccess: async () => {
      toast.success('Provider model updated.');
      await queryClient.invalidateQueries({ queryKey: adminQueries.providerModels().queryKey });
    },
  });
}

export function useDeleteProviderModel() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (id: number) =>
      takeData((await apiClient.delete(`/ai-provider-models/${id}`)).data),
    onSuccess: async () => {
      toast.success('Provider model deleted.');
      await queryClient.invalidateQueries({ queryKey: adminQueries.providerModels().queryKey });
    },
  });
}

export function useExportProviderModels() {
  return useMutation({
    mutationFn: async () => takeData((await apiClient.get('/ai-provider-models/export')).data),
  });
}

export function useImportProviderModels() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: unknown) =>
      takeData((await apiClient.post('/ai-provider-models/import', { data })).data),
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
    mutationFn: async (newKey: AiKeyPayload & { key: string }) =>
      takeData((await apiClient.post('/ai-key-pool', newKey)).data),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: adminQueries.keyPool().queryKey });
    },
  });

  const updateMutation = useMutation({
    mutationFn: async ({ id, data }: { id: number; data: AiKeyPayload }) =>
      takeData((await apiClient.put(`/ai-key-pool/${id}`, data)).data),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: adminQueries.keyPool().queryKey });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async (id: number) =>
      takeData((await apiClient.delete(`/ai-key-pool/${id}`)).data),
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
