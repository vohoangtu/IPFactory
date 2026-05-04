'use client';

import { FileText, PlayCircle, RefreshCcw } from 'lucide-react';
import { useNarrativeRuntime } from '@/features/narrative-runtime/useNarrativeRuntime';
import { NARRATIVE_NODE_MAP } from '@/features/narrative-runtime/types';
import FlowDiagram from '@/components/ui/narrative/FlowDiagram';
import StatCard from './StatCard';

interface RunTabProps {
  runtime: ReturnType<typeof useNarrativeRuntime>;
  activeUniverseId: string | null;
}

export default function RunTab({ runtime, activeUniverseId }: RunTabProps) {
  return (
    <div className="space-y-5">
      {/* Stat row */}
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <StatCard
          label="Loom Status"
          value={(runtime.loomStatus?.status ?? 'unknown').toUpperCase()}
          status={runtime.loomStatus?.status === 'online' ? 'ok' : 'warn'}
        />
        <StatCard
          label="Connection"
          value={runtime.connectionState.toUpperCase()}
          status={runtime.connectionState === 'connected' ? 'ok' : 'warn'}
        />
        <StatCard label="Agents" value={String(runtime.agentCount)} />
        <StatCard label="Providers" value={String(runtime.providerCount)} />
        <StatCard
          label="Progress"
          value={`${runtime.progress.completed}/${runtime.progress.total}`}
          status={runtime.errorCount > 0 ? 'error' : runtime.progress.completed > 0 ? 'ok' : 'neutral'}
        />
      </div>

      {/* Main run panel */}
      <div className="rounded-lg border border-border-subtle bg-bg-surface">
        <div className="flex items-center justify-between border-b border-border-subtle px-5 py-4">
          <div>
            <h2 className="text-sm font-semibold text-text-primary">Run Chronicle Weave</h2>
            <p className="mt-0.5 text-xs text-text-disabled">
              Submit and monitor a pipeline run for universe{' '}
              <span className="text-text-secondary">{activeUniverseId ?? '—'}</span>
            </p>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={() => void runtime.refreshLoomStatus()}
              disabled={runtime.isLoadingLoomStatus}
              className="flex items-center gap-1.5 rounded border border-border-muted bg-bg-elevated px-3 py-1.5 text-xs text-text-secondary transition hover:bg-bg-base disabled:opacity-50"
            >
              <RefreshCcw size={13} />
              Refresh
            </button>
            <button
              onClick={() => runtime.clearTrackedSession()}
              disabled={!runtime.activeTaskId}
              className="flex items-center gap-1.5 rounded border border-border-muted bg-bg-elevated px-3 py-1.5 text-xs text-text-secondary transition hover:bg-bg-base disabled:opacity-50"
            >
              <FileText size={13} />
              Clear Session
            </button>
            <button
              onClick={() => void runtime.startWeave()}
              disabled={runtime.isSubmitting || runtime.isWeaving || !activeUniverseId}
              className="flex items-center gap-1.5 rounded bg-brand-info px-4 py-1.5 text-xs font-semibold text-text-primary transition hover:bg-brand-info/80 disabled:opacity-50"
            >
              <PlayCircle size={13} />
              {runtime.isSubmitting ? 'Submitting…' : runtime.isWeaving ? 'Running…' : 'Start Weave'}
            </button>
          </div>
        </div>

        {/* Progress bar + session info */}
        <div className="border-b border-border-subtle px-5 py-3">
          <div className="mb-2 flex items-center justify-between text-xs text-text-disabled">
            <span>{runtime.activeTaskId ? `Task: ${runtime.activeTaskId}` : 'No active task'}</span>
            <span>
              Current agent:{' '}
              <span className="font-medium text-text-secondary">
                {runtime.currentAgent
                  ? (NARRATIVE_NODE_MAP[runtime.currentAgent]?.label ?? runtime.currentAgent)
                  : 'Idle'}
              </span>
            </span>
          </div>
          <div className="h-2 overflow-hidden rounded-full bg-bg-elevated">
            <div
              className="h-full rounded-full bg-brand-info transition-all duration-500"
              style={{ width: `${runtime.progress.pct}%` }}
            />
          </div>
          <div className="mt-1.5 flex justify-between text-[11px] text-text-disabled">
            <span>
              {runtime.progress.completed} completed · {runtime.runningCount} running · {runtime.errorCount} errors
            </span>
            <span>
              {runtime.connectionState === 'connected' ? 'Real-time via Centrifugo' : 'Fallback polling'}
            </span>
          </div>
        </div>

        {(runtime.isRestoredSession || runtime.lastError) && (
          <div className="space-y-2 px-5 py-3">
            {runtime.isRestoredSession && (
              <p className="text-xs text-brand-info">Session restored from previous navigation.</p>
            )}
            {runtime.lastError && <p className="text-xs text-brand-danger">{runtime.lastError}</p>}
          </div>
        )}
      </div>

      {/* Flow + Inspector */}
      <div className="grid grid-cols-1 gap-5 xl:grid-cols-[1fr_380px]">
        <div className="rounded-lg border border-border-subtle bg-bg-surface">
          <div className="border-b border-border-subtle px-5 py-3">
            <h3 className="text-sm font-semibold text-text-primary">Pipeline Graph</h3>
          </div>
          <div className="p-5">
            <FlowDiagram
              nodes={runtime.pipelineNodes}
              selectedNode={runtime.selectedNode}
              onNodeClick={runtime.setSelectedNode}
              revisionCount={runtime.revisionCount ?? 0}
            />
          </div>
        </div>

        <div className="space-y-5">
          {/* Node inspector */}
          <div className="rounded-lg border border-border-subtle bg-bg-surface">
            <div className="border-b border-border-subtle px-5 py-3">
              <h3 className="text-sm font-semibold text-text-primary">
                {runtime.selectedNode
                  ? (NARRATIVE_NODE_MAP[runtime.selectedNode]?.label ?? runtime.selectedNode)
                  : 'Node Inspector'}
              </h3>
            </div>
            <div className="p-4">
              {runtime.selectedNode && NARRATIVE_NODE_MAP[runtime.selectedNode] ? (
                <div className="space-y-3 text-sm">
                  <div>
                    <p className="text-xs text-text-disabled">Role</p>
                    <p className="mt-0.5 text-text-secondary">{NARRATIVE_NODE_MAP[runtime.selectedNode].role}</p>
                  </div>
                  <div>
                    <p className="text-xs text-text-disabled">Description</p>
                    <p className="mt-0.5 text-xs leading-relaxed text-text-muted">
                      {NARRATIVE_NODE_MAP[runtime.selectedNode].description}
                    </p>
                  </div>
                  {runtime.selectedNodeDetails?.stage && (
                    <div>
                      <p className="text-xs text-text-disabled">Stage</p>
                      <p className="mt-0.5 text-xs font-mono text-brand-info">
                        {runtime.selectedNodeDetails.stage}
                      </p>
                    </div>
                  )}
                  {runtime.selectedNodeDetails?.output !== undefined && (
                    <div>
                      <p className="text-xs text-text-disabled">Output</p>
                      <pre className="mt-1 max-h-48 overflow-auto whitespace-pre-wrap rounded border border-border-muted bg-bg-elevated p-2 text-[11px] text-text-secondary">
                        {JSON.stringify(runtime.selectedNodeDetails.output as Record<string, unknown>, null, 2)}
                      </pre>
                    </div>
                  )}
                </div>
              ) : (
                <p className="text-xs text-text-disabled">Select a node from the graph to inspect it.</p>
              )}
            </div>
          </div>

          {/* Event stream */}
          <div className="rounded-lg border border-border-subtle bg-bg-surface">
            <div className="flex items-center justify-between border-b border-border-subtle px-5 py-3">
              <h3 className="text-sm font-semibold text-text-primary">Event Log</h3>
              <span className="text-xs text-text-disabled">{runtime.logs.length} lines</span>
            </div>
            <div className="max-h-64 overflow-y-auto bg-bg-base px-4 py-3 font-mono text-[11px]">
              {runtime.logs.map((line, i) => {
                let color = 'text-text-muted';
                if (line.includes('[Error]')) color = 'text-brand-danger';
                if (line.includes('[Pipeline]')) color = 'text-brand-accent';
                if (line.includes('[Centrifugo]') || line.includes('[Polling]')) color = 'text-brand-info';
                return (
                  <p key={`${i}-${line}`} className={color}>
                    <span className="mr-2 text-text-disabled">{String(i + 1).padStart(3, '0')}</span>
                    {line}
                  </p>
                );
              })}
              {runtime.logs.length === 0 && <p className="text-text-disabled">No events yet.</p>}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
