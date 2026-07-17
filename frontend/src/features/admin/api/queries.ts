'use client';

import { queryOptions } from '@tanstack/react-query';
import { apiClient } from '@/shared/lib/apiClient';
import { takeData } from '@/shared/lib/unwrap';
import { qk } from '@/shared/config/queryKeys';
import type {
  AiKey,
  AiSettingRecord,
  AiProviderModel,
  DriverName,
  GroupedSimulationSettings,
  LoomAgentRecord,
  ServiceStatusResponse,
} from '../types';

const ADMIN_POLL_MS = 10_000;
const SERVICE_POLL_MS = 30_000;

export const adminQueries = {
  simulationSettings: () =>
    queryOptions({
      queryKey: qk.simulationSettings(),
      queryFn: async (): Promise<GroupedSimulationSettings> =>
        takeData<GroupedSimulationSettings>((await apiClient.get('/apex/settings')).data),
      staleTime: 30_000,
    }),

  serviceStatus: (refetchInterval: number | false = SERVICE_POLL_MS) =>
    queryOptions({
      queryKey: qk.serviceStatus(),
      queryFn: async (): Promise<ServiceStatusResponse> =>
        takeData<ServiceStatusResponse>((await apiClient.get('/worldos/service-status')).data),
      refetchInterval,
      staleTime: 20_000,
      refetchOnWindowFocus: true,
    }),

  aiSettings: () =>
    queryOptions({
      queryKey: qk.aiSettings(),
      queryFn: async (): Promise<AiSettingRecord[]> =>
        takeData<AiSettingRecord[]>((await apiClient.get('/ai-settings')).data),
      staleTime: 30_000,
    }),

  aiDrivers: () =>
    queryOptions({
      queryKey: ['admin', 'ai-drivers'] as const,
      queryFn: async (): Promise<DriverName[]> =>
        takeData<DriverName[]>((await apiClient.get('/ai-settings/drivers')).data),
      staleTime: 30_000,
    }),

  loomAgents: () =>
    queryOptions({
      queryKey: qk.loomAgents(),
      queryFn: async (): Promise<LoomAgentRecord[]> =>
        takeData<LoomAgentRecord[]>((await apiClient.get('/ai-settings/loom-agents')).data),
      staleTime: 30_000,
    }),

  keyPool: (refetchInterval: number | false = ADMIN_POLL_MS) =>
    queryOptions({
      queryKey: qk.keyPool(),
      queryFn: async (): Promise<AiKey[]> =>
        takeData<AiKey[]>((await apiClient.get('/ai-key-pool')).data),
      refetchInterval,
      staleTime: 8_000,
    }),

  providerModels: () =>
    queryOptions({
      queryKey: qk.providerModels(),
      queryFn: async (): Promise<AiProviderModel[]> =>
        takeData<AiProviderModel[]>((await apiClient.get('/ai-provider-models')).data),
      staleTime: 30_000,
    }),
};
