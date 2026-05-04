import { queryOptions } from '@tanstack/react-query';
import api from '@/lib/api';
import type { LoomStatus, LoomTaskStatusPayload, PipelineManifest } from '../types';

const LOOM_STATUS_KEY = ['loom', 'status'] as const;
const LOOM_TASK_KEY = (taskId: string) => ['loom', 'tasks', taskId] as const;
const LOOM_MANIFEST_KEY = ['loom', 'pipeline-manifest'] as const;

/** Normalized loom status from /loom-status endpoint */
export const narrativeQueries = {
  loomStatus: () =>
    queryOptions({
      queryKey: LOOM_STATUS_KEY,
      queryFn: (): Promise<LoomStatus> =>
        api.get('/loom-status').then((r) => r.data),
      staleTime: 10_000,
      refetchInterval: 60_000,
    }),

  loomTaskStatus: (taskId: string | null) =>
    queryOptions({
      queryKey: LOOM_TASK_KEY(taskId ?? ''),
      queryFn: (): Promise<LoomTaskStatusPayload> =>
        api.get(`/loom-tasks/${taskId}/status`).then((r) => r.data),
      staleTime: 5_000,
      refetchInterval: 5_000,
      enabled: !!taskId,
    }),

  pipelineManifest: () =>
    queryOptions({
      queryKey: LOOM_MANIFEST_KEY,
      queryFn: (): Promise<PipelineManifest> =>
        api.get('/loom/pipeline-manifest').then((r) => r.data),
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
  const response = await api.post(`/worldos/universes/${universeId}/generate-chronicle`);
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
