import { queryOptions } from '@tanstack/react-query';
import { apiClient } from '@/shared/lib/apiClient';
import { takeData } from '@/shared/lib/unwrap';
import { qk } from '@/shared/config/queryKeys';
import type { LoomStatus, LoomTaskStatusPayload, PipelineManifest } from '../types';

const LOOM_MANIFEST_KEY = ['loom', 'pipeline-manifest'] as const;

/** Normalized loom status from /loom-status endpoint */
export const narrativeQueries = {
  loomStatus: () =>
    queryOptions({
      queryKey: qk.loomStatus(),
      queryFn: async (): Promise<LoomStatus> =>
        takeData<LoomStatus>((await apiClient.get('/loom-status')).data),
      staleTime: 10_000,
      refetchInterval: 60_000,
    }),

  loomTaskStatus: (taskId: string | null) =>
    queryOptions({
      queryKey: qk.loomTask(taskId ?? ''),
      queryFn: async (): Promise<LoomTaskStatusPayload> =>
        takeData<LoomTaskStatusPayload>((await apiClient.get(`/loom-tasks/${taskId}/status`)).data),
      staleTime: 5_000,
      refetchInterval: 5_000,
      enabled: !!taskId,
    }),

  pipelineManifest: () =>
    queryOptions({
      queryKey: LOOM_MANIFEST_KEY,
      queryFn: async (): Promise<PipelineManifest> =>
        takeData<PipelineManifest>((await apiClient.get('/loom/pipeline-manifest')).data),
      staleTime: Infinity,
    }),
};

export interface GenerateChronicleResponse {
  task_id?: string;
  world_id?: number;
  content?: string;
  title?: string;
}

export async function generateChronicle(universeId: number): Promise<GenerateChronicleResponse> {
  const response = await apiClient.post(`/worldos/universes/${universeId}/generate-chronicle`);
  const payload =
    response.data && typeof response.data === 'object' && 'data' in response.data
      ? (response.data as { data?: Record<string, unknown> }).data
      : response.data;

  if (!payload || typeof payload !== 'object') {
    return {};
  }

  return {
    task_id: 'task_id' in payload ? String(payload.task_id) : undefined,
    world_id: 'world_id' in payload ? Number(payload.world_id) : undefined,
    content: 'content' in payload ? String(payload.content) : undefined,
    title: 'title' in payload ? String(payload.title) : undefined,
  };
}
