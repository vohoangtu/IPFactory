'use client';

import { queryOptions } from '@tanstack/react-query';
import api from '@/lib/api';
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
      queryKey: ['admin', 'simulation-settings'] as const,
      queryFn: (): Promise<GroupedSimulationSettings> =>
        api.get('/apex/settings').then((response) => response.data),
      staleTime: 30_000,
    }),

  serviceStatus: (refetchInterval: number | false = SERVICE_POLL_MS) =>
    queryOptions({
      queryKey: ['admin', 'service-status'] as const,
      queryFn: (): Promise<ServiceStatusResponse> =>
        api.get('/worldos/service-status').then((response) => response.data),
      refetchInterval,
      staleTime: 20_000,
      refetchOnWindowFocus: true,
    }),

  aiSettings: () =>
    queryOptions({
      queryKey: ['admin', 'ai-settings'] as const,
      queryFn: (): Promise<AiSettingRecord[]> =>
        api.get('/ai-settings').then((response) => response.data),
      staleTime: 30_000,
    }),

  aiDrivers: () =>
    queryOptions({
      queryKey: ['admin', 'ai-drivers'] as const,
      queryFn: (): Promise<DriverName[]> =>
        api.get('/ai-settings/drivers').then((response) => response.data),
      staleTime: 30_000,
    }),

  loomAgents: () =>
    queryOptions({
      queryKey: ['admin', 'loom-agents'] as const,
      queryFn: (): Promise<LoomAgentRecord[]> =>
        api.get('/ai-settings/loom-agents').then((response) => response.data),
      staleTime: 30_000,
    }),

  keyPool: (refetchInterval: number | false = ADMIN_POLL_MS) =>
    queryOptions({
      queryKey: ['admin', 'ai-key-pool'] as const,
      queryFn: (): Promise<AiKey[]> =>
        api.get('/ai-key-pool').then((response) => response.data),
      refetchInterval,
      staleTime: 8_000,
    }),

  providerModels: () =>
    queryOptions({
      queryKey: ['admin', 'ai-provider-models'] as const,
      queryFn: (): Promise<AiProviderModel[]> =>
        api.get('/ai-provider-models').then((response) => response.data),
      staleTime: 30_000,
    }),
};
