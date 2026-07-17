'use client';

import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { getCentrifuge } from '@/shared/lib/centrifugo';
import { useCentrifugoConnection } from './hooks/useCentrifugoConnection';
import type {
  AgentDetails,
  IntermediateOutputs,
  LoomStatus,
  NarrativeEvent,
  NarrativeResult,
  NarrativeRuntimeNodeState,
  PipelineProgress,
} from './types';
import { NARRATIVE_NODE_MAP, NARRATIVE_PIPELINE_NODES } from './types';
import { useLoomStatus, useLoomTaskStatus, useGenerateChronicle } from './hooks';
import { narrativeQueries } from './api/queries';

const SESSION_STORAGE_KEY = 'worldos:narrative-runtime:session';
const DEFAULT_LOGS = [
  'Canonical Loom Workshop ready.',
  'Awaiting chronicle generation request from the active universe.',
];

function createEmptyNodeState(): Record<string, NarrativeRuntimeNodeState> {
  return Object.fromEntries(
    NARRATIVE_PIPELINE_NODES.map((node) => [node.id, { status: 'idle' }]),
  ) as Record<string, NarrativeRuntimeNodeState>;
}

function deriveProgress(
  nodes: Record<string, NarrativeRuntimeNodeState>,
  total = NARRATIVE_PIPELINE_NODES.length,
): PipelineProgress {
  const completed = Object.values(nodes).filter((node) => node.status === 'completed').length;

  return {
    completed,
    total,
    pct: total > 0 ? Math.round((completed / total) * 100) : 0,
  };
}

function normalizeLoomStatus(value: unknown): LoomStatus | null {
  if (!value || typeof value !== 'object' || Array.isArray(value)) {
    return null;
  }

  const payload = value as Record<string, unknown>;

  return {
    status:
      payload.status === 'online' ||
      payload.status === 'offline' ||
      payload.status === 'degraded' ||
      payload.status === 'error'
        ? payload.status
        : 'error',
    agents:
      payload.agents && typeof payload.agents === 'object' && !Array.isArray(payload.agents)
        ? (payload.agents as LoomStatus['agents'])
        : {},
    providers:
      payload.providers &&
      typeof payload.providers === 'object' &&
      !Array.isArray(payload.providers)
        ? (payload.providers as LoomStatus['providers'])
        : {},
    version: typeof payload.version === 'string' ? payload.version : undefined,
    message: typeof payload.message === 'string' ? payload.message : undefined,
  };
}

function inferLegacyEventType(event: NarrativeEvent): string {
  if (event.type) return event.type;
  if (event.stage === 'complete') return 'pipeline_done';
  if (event.stage === 'completed') return 'agent_done';
  if (event.error) return 'agent_error';
  if (event.agent) return 'agent_started';
  return 'unknown';
}

export function useNarrativeRuntime(universeId: number | null) {
  const activeUniverseId = universeId;
  const { state: connectionState } = useCentrifugoConnection();
  const queryClient = useQueryClient();

  const {
    loomStatus: queriedLoomStatus,
    isLoading: isLoadingLoomStatus,
    refresh: refreshLoomStatus,
  } = useLoomStatus();

  const [loomStatus, setLoomStatus] = useState<LoomStatus | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isWeaving, setIsWeaving] = useState(false);
  const [activeTaskId, setActiveTaskId] = useState<string | null>(null);
  const [worldId, setWorldId] = useState<number | null>(null);
  const [logs, setLogs] = useState<string[]>(DEFAULT_LOGS);
  const [currentAgent, setCurrentAgent] = useState<string | null>(null);
  const [pipelineNodes, setPipelineNodes] =
    useState<Record<string, NarrativeRuntimeNodeState>>(createEmptyNodeState);
  const [progress, setProgress] = useState<PipelineProgress>({
    completed: 0,
    total: NARRATIVE_PIPELINE_NODES.length,
    pct: 0,
  });
  const [narrativeResult, setNarrativeResult] = useState<NarrativeResult | null>(null);
  const [chronicleId, setChronicleId] = useState<string | null>(null);
  const [intermediateOutputs, setIntermediateOutputs] = useState<IntermediateOutputs>({});
  const [agentDetails, setAgentDetails] = useState<Record<string, AgentDetails>>({});
  const [selectedNode, setSelectedNode] = useState<string | undefined>(undefined);
  const [lastError, setLastError] = useState<string | null>(null);
  const [isRestoredSession, setIsRestoredSession] = useState(false);
  const hasHydratedSession = useRef(false);

  // Sync queried loom status into local state (initial + fallback)
  useEffect(() => {
    if (queriedLoomStatus) {
      setLoomStatus(queriedLoomStatus);
    }
  }, [queriedLoomStatus]);

  const { taskStatus } = useLoomTaskStatus(activeTaskId);

  const addLog = useCallback((message: string) => {
    const timestamp = new Date().toLocaleTimeString([], {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
    });

    setLogs((current) => [...current, `[${timestamp}] ${message}`].slice(-120));
  }, []);

  const resetRuntimeView = useCallback(() => {
    setCurrentAgent(null);
    setPipelineNodes(createEmptyNodeState());
    setProgress({
      completed: 0,
      total: NARRATIVE_PIPELINE_NODES.length,
      pct: 0,
    });
    setNarrativeResult(null);
    setIntermediateOutputs({});
    setAgentDetails({});
    setSelectedNode(undefined);
    setLastError(null);
  }, []);

  const persistSession = useCallback((taskId: string | null, nextWorldId: number | null) => {
    if (typeof window === 'undefined') return;

    if (!taskId || !nextWorldId) {
      window.localStorage.removeItem(SESSION_STORAGE_KEY);
      return;
    }

    window.localStorage.setItem(
      SESSION_STORAGE_KEY,
      JSON.stringify({
        taskId,
        worldId: nextWorldId,
      }),
    );
  }, []);

  const clearTrackedSession = useCallback(() => {
    setActiveTaskId(null);
    setWorldId(null);
    setIsWeaving(false);
    setIsRestoredSession(false);
    persistSession(null, null);
  }, [persistSession]);

  const updateNodeState = useCallback(
    (
      nodeId: string,
      updater: (previous: NarrativeRuntimeNodeState) => NarrativeRuntimeNodeState,
      total = NARRATIVE_PIPELINE_NODES.length,
    ) => {
      setPipelineNodes((current) => {
        const previous = current[nodeId] ?? { status: 'idle' };
        const next = {
          ...current,
          [nodeId]: updater(previous),
        };
        setProgress(deriveProgress(next, total));
        return next;
      });
    },
    [],
  );

  const finalizeTaskFromResult = useCallback(
    (payload: NarrativeResult & IntermediateOutputs & { chronicle_id?: string }) => {
      const total = NARRATIVE_PIPELINE_NODES.length;
      setProgress({
        completed: total,
        total,
        pct: 100,
      });
      setNarrativeResult({
        headline: payload.headline,
        prose: payload.prose,
        newsSlogan: payload.newsSlogan,
      });
      setIntermediateOutputs({
        historical_outline: payload.historical_outline,
        storyboard: payload.storyboard,
        final_prose: payload.prose,
        vfx_config: payload.vfx_config,
      });
      if (payload.chronicle_id) {
        setChronicleId(payload.chronicle_id);
      }
      setCurrentAgent(null);
      setIsWeaving(false);
      clearTrackedSession();
    },
    [clearTrackedSession],
  );

  // React to polled task status when WebSocket is offline
  useEffect(() => {
    if (!taskStatus || connectionState === 'connected') return;

    if (taskStatus.status === 'SUCCESS' && taskStatus.result) {
      addLog('[Polling] WebSocket offline, recovered final task result via status endpoint.');
      finalizeTaskFromResult({
        headline: taskStatus.result.news_headline,
        prose: taskStatus.result.final_prose,
        newsSlogan: taskStatus.result.news_slogan,
        historical_outline: taskStatus.result.historical_outline,
        storyboard: taskStatus.result.storyboard,
        vfx_config: taskStatus.result.vfx_config,
      });
    }

    if (taskStatus.status === 'FAILURE') {
      const message = taskStatus.error || taskStatus.message || 'Narrative Loom task failed.';
      setLastError(message);
      setIsWeaving(false);
      addLog(`[Polling] ${message}`);
      clearTrackedSession();
    }
  }, [taskStatus, connectionState, addLog, clearTrackedSession, finalizeTaskFromResult]);

  const handleRuntimeEvent = useCallback(
    (rawEvent: NarrativeEvent) => {
      const eventType = inferLegacyEventType(rawEvent);
      const agentId = rawEvent.agent && NARRATIVE_NODE_MAP[rawEvent.agent] ? rawEvent.agent : undefined;
      const total = rawEvent.progress?.total || rawEvent.total_agents || NARRATIVE_PIPELINE_NODES.length;

      switch (eventType) {
        case 'pipeline_started': {
          setIsWeaving(true);
          setProgress({
            completed: 0,
            total,
            pct: 0,
          });
          addLog(`[Pipeline] Started with ${total} nodes in scope.`);
          return;
        }
        case 'agent_started': {
          if (!agentId) return;
          setCurrentAgent(agentId);
          setSelectedNode((current) => current ?? agentId);
          updateNodeState(agentId, (previous) => ({
            ...previous,
            status: 'running',
            startedAt: Date.now(),
            error: undefined,
          }), total);

          if (rawEvent.input || rawEvent.stage) {
            setAgentDetails((current) => ({
              ...current,
              [agentId]: {
                ...current[agentId],
                input: rawEvent.input ?? current[agentId]?.input,
                stage: rawEvent.stage ?? current[agentId]?.stage,
              },
            }));
          }

          addLog(`[Agent] ${NARRATIVE_NODE_MAP[agentId].label} started.`);
          return;
        }
        case 'agent_done': {
          if (!agentId) return;
          setCurrentAgent(null);
          updateNodeState(agentId, (previous) => ({
            ...previous,
            status: 'completed',
            completedAt: Date.now(),
            durationMs: rawEvent.duration_ms ?? previous.durationMs,
            error: undefined,
          }), total);

          if (rawEvent.output || rawEvent.stage) {
            setAgentDetails((current) => ({
              ...current,
              [agentId]: {
                ...current[agentId],
                output: rawEvent.output ?? current[agentId]?.output,
                stage: rawEvent.stage ?? current[agentId]?.stage,
              },
            }));
          }

          addLog(
            `[Agent] ${NARRATIVE_NODE_MAP[agentId].label} completed${
              rawEvent.duration_ms ? ` in ${rawEvent.duration_ms}ms` : ''
            }.`,
          );
          return;
        }
        case 'agent_error': {
          if (!agentId) return;
          setCurrentAgent(null);
          const message = rawEvent.error || 'Unknown pipeline error';
          setLastError(message);
          updateNodeState(agentId, (previous) => ({
            ...previous,
            status: 'error',
            completedAt: Date.now(),
            error: message,
          }), total);
          setAgentDetails((current) => ({
            ...current,
            [agentId]: {
              ...current[agentId],
              error: message,
              output: rawEvent.output ?? current[agentId]?.output,
              stage: rawEvent.stage ?? current[agentId]?.stage,
            },
          }));
          addLog(`[Error] ${NARRATIVE_NODE_MAP[agentId].label} failed: ${message}`);
          return;
        }
        case 'pipeline_done': {
          addLog('[Pipeline] Completed successfully.');
          finalizeTaskFromResult({
            headline: rawEvent.news_headline,
            prose: rawEvent.final_prose,
            newsSlogan: rawEvent.news_slogan,
            historical_outline: rawEvent.historical_outline,
            storyboard: rawEvent.storyboard,
            vfx_config: rawEvent.vfx_config,
            chronicle_id: rawEvent.chronicle_id,
          });
          return;
        }
        case 'pipeline_error': {
          const message = rawEvent.error || 'Narrative Loom pipeline failed.';
          setLastError(message);
          setCurrentAgent(null);
          setIsWeaving(false);
          addLog(`[Error] ${message}`);
          clearTrackedSession();
          return;
        }
        default:
          return;
      }
    },
    [addLog, clearTrackedSession, finalizeTaskFromResult, updateNodeState],
  );

  const generateChronicleMutation = useGenerateChronicle();

  const startWeave = useCallback(async () => {
    if (!activeUniverseId) {
      addLog('[Error] Select an active universe before starting Narrative Loom.');
      return false;
    }

    setIsSubmitting(true);
    setIsRestoredSession(false);
    resetRuntimeView();

    try {
      const payload = await generateChronicleMutation.mutateAsync(activeUniverseId);

      if (payload.task_id) {
        const taskId = payload.task_id;
        const nextWorldId = payload.world_id ?? activeUniverseId;

        setActiveTaskId(taskId);
        setWorldId(nextWorldId);
        setIsWeaving(true);
        persistSession(taskId, nextWorldId);
        addLog(`[Pipeline] Submitted task ${taskId} for universe ${activeUniverseId}.`);
        return true;
      }

      if (payload.content) {
        addLog('[Pipeline] Fallback PHP chronicle completed immediately.');
        finalizeTaskFromResult({
          prose: payload.content,
          headline: payload.title || 'Fallback chronicle generated',
        });
        return true;
      }

      addLog('[Error] Narrative Loom returned an unexpected payload.');
      setIsWeaving(false);
      return false;
    } catch {
      addLog('[Error] Failed to submit chronicle generation task.');
      setIsWeaving(false);
      return false;
    } finally {
      setIsSubmitting(false);
    }
  }, [
    activeUniverseId,
    addLog,
    finalizeTaskFromResult,
    generateChronicleMutation,
    persistSession,
    resetRuntimeView,
  ]);

  // Centrifugo system status subscription
  useEffect(() => {
    const centrifuge = getCentrifuge();
    const sub = centrifuge.getSubscription('loom:system:status') || centrifuge.newSubscription('loom:system:status');
    sub.on('publication', (ctx: { data?: unknown }) => {
      const status = normalizeLoomStatus(ctx.data);
      if (status) {
        setLoomStatus(status);
        queryClient.setQueryData(narrativeQueries.loomStatus().queryKey, status);
      }
    });
    sub.subscribe();
    const interval = window.setInterval(() => {
      void refreshLoomStatus();
    }, 60_000);
    return () => {
      sub.unsubscribe();
      sub.removeAllListeners();
      window.clearInterval(interval);
    };
  }, [refreshLoomStatus, queryClient]);

  // Session restoration from localStorage
  useEffect(() => {
    if (typeof window === 'undefined' || hasHydratedSession.current) return;

    hasHydratedSession.current = true;
    const raw = window.localStorage.getItem(SESSION_STORAGE_KEY);

    if (!raw) return;

    try {
      const payload = JSON.parse(raw) as { taskId?: string; worldId?: number };
      if (payload.taskId && payload.worldId) {
        setActiveTaskId(payload.taskId);
        setWorldId(payload.worldId);
        setIsWeaving(true);
        setIsRestoredSession(true);
        addLog(`[Pipeline] Restored active session ${payload.taskId}.`);
      }
    } catch {
      window.localStorage.removeItem(SESSION_STORAGE_KEY);
    }
  }, [addLog]);

  // Centrifugo task-specific subscription
  useEffect(() => {
    if (!activeTaskId || !worldId) return;

    const channel = `narrative:${worldId}:${activeTaskId}`;
    const centrifuge = getCentrifuge();
    const subscription = centrifuge.getSubscription(channel) || centrifuge.newSubscription(channel);

    subscription.on('publication', (ctx) => {
      handleRuntimeEvent(ctx.data as NarrativeEvent);
    });

    subscription.subscribe();
    addLog(`[Centrifugo] Subscribed to ${channel}.`);

    return () => {
      subscription.unsubscribe();
      subscription.removeAllListeners();
    };
  }, [activeTaskId, addLog, handleRuntimeEvent, worldId]);

  const providerCount = useMemo(
    () => Object.keys(loomStatus?.providers ?? {}).length,
    [loomStatus],
  );
  const agentCount = useMemo(
    () => Object.keys(loomStatus?.agents ?? {}).length,
    [loomStatus],
  );
  const completedCount = useMemo(
    () => Object.values(pipelineNodes).filter((node) => node.status === 'completed').length,
    [pipelineNodes],
  );
  const runningCount = useMemo(
    () => Object.values(pipelineNodes).filter((node) => node.status === 'running').length,
    [pipelineNodes],
  );
  const errorCount = useMemo(
    () => Object.values(pipelineNodes).filter((node) => node.status === 'error').length,
    [pipelineNodes],
  );
  const revisionCount = useMemo(() => {
    const revisions = Object.values(agentDetails).reduce((acc, detail) => {
      return acc + (detail?.revisions ?? 0);
    }, 0);
    return revisions;
  }, [agentDetails]);

  const selectedNodeDetails = selectedNode ? agentDetails[selectedNode] : undefined;

  return {
    activeTaskId,
    worldId,
    isSubmitting,
    isWeaving,
    isRestoredSession,
    connectionState,
    loomStatus,
    isLoadingLoomStatus,
    currentAgent,
    progress,
    pipelineNodes,
    logs,
    narrativeResult,
    intermediateOutputs,
    selectedNode,
    selectedNodeDetails,
    lastError,
    providerCount,
    agentCount,
    completedCount,
    runningCount,
    errorCount,
    revisionCount,
    startWeave,
    clearTrackedSession,
    chronicleId,
    refreshLoomStatus,
    setSelectedNode,
  };
}
