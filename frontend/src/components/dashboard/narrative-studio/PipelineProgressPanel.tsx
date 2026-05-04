'use client';

import { CheckCircle2, Loader2, XCircle } from 'lucide-react';
import { NARRATIVE_PIPELINE_NODES } from '@/features/narrative-runtime/types';
import type { NarrativeRuntimeNodeState } from '@/features/narrative-runtime/types';

function StatusBadge({ status }: { status: string }) {
  const map: Record<string, string> = {
    running: 'bg-brand-info/10 text-brand-info border border-brand-info/20',
    completed: 'bg-brand-emerald/10 text-brand-emerald border border-brand-emerald/20',
    error: 'bg-brand-danger/10 text-brand-danger border border-brand-danger/20',
    idle: 'bg-bg-elevated text-text-disabled border border-border-muted',
  };
  return (
    <span className={`inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase ${map[status] ?? map.idle}`}>
      {status === 'running' && <Loader2 size={9} className="animate-spin" />}
      {status === 'completed' && <CheckCircle2 size={9} />}
      {status === 'error' && <XCircle size={9} />}
      {status}
    </span>
  );
}

interface PipelineProgressPanelProps {
  pipelineNodes: Record<string, NarrativeRuntimeNodeState>;
  currentAgent: string | null;
  progressPct: number;
  connectionState: string;
}

export default function PipelineProgressPanel({
  pipelineNodes,
  currentAgent,
  progressPct,
  connectionState,
}: PipelineProgressPanelProps) {
  const completedNodes = Object.values(pipelineNodes).filter(n => n.status === 'completed').length;
  const totalNodes = NARRATIVE_PIPELINE_NODES.length;

  return (
    <div className="rounded-lg border border-border-subtle bg-bg-surface">
      <div className="flex items-center justify-between border-b border-border-subtle px-4 py-3">
        <h2 className="text-sm font-semibold text-text-primary">Pipeline</h2>
        <div className="flex items-center gap-3">
          <span className="text-xs text-text-disabled">{completedNodes}/{totalNodes} nodes</span>
          <span className={`inline-flex items-center gap-1 rounded px-2 py-0.5 text-[10px] font-semibold uppercase ${
            connectionState === 'connected'
              ? 'bg-brand-emerald/10 text-brand-emerald'
              : 'bg-brand-amber/10 text-brand-amber'
          }`}>
            {connectionState}
          </span>
        </div>
      </div>

      {/* Progress bar */}
      <div className="border-b border-border-subtle px-4 py-2">
        <div className="h-1.5 overflow-hidden rounded-full bg-bg-elevated">
          <div
            className="h-full rounded-full bg-brand-info transition-all duration-500"
            style={{ width: `${progressPct}%` }}
          />
        </div>
      </div>

      {/* Node table */}
      <div className="overflow-y-auto" style={{ maxHeight: '520px' }}>
        <table className="w-full text-xs">
          <thead>
            <tr className="border-b border-border-subtle text-left">
              <th className="px-4 py-2 font-medium text-text-disabled">Node</th>
              <th className="px-4 py-2 font-medium text-text-disabled">Phase</th>
              <th className="px-4 py-2 font-medium text-text-disabled">Status</th>
              <th className="px-4 py-2 text-right font-medium text-text-disabled">Duration</th>
            </tr>
          </thead>
          <tbody>
            {NARRATIVE_PIPELINE_NODES.map((node, i) => {
              const ns = pipelineNodes[node.id];
              const status = ns?.status ?? 'idle';
              const isCurrent = currentAgent === node.id;
              const ms = ns?.durationMs ?? (ns?.startedAt && ns?.completedAt ? ns.completedAt - ns.startedAt : null);

              return (
                <tr
                  key={node.id}
                  className={`border-b border-border-subtle/60 transition ${isCurrent ? 'bg-brand-info/5' : i % 2 === 0 ? '' : 'bg-bg-elevated/20'}`}
                >
                  <td className="px-4 py-2.5">
                    <div className="flex items-center gap-2">
                      <span className="h-1.5 w-1.5 rounded-full flex-shrink-0" style={{ backgroundColor: node.accent }} />
                      <span className={`font-medium ${isCurrent ? 'text-brand-info' : 'text-text-secondary'}`}>{node.label}</span>
                    </div>
                  </td>
                  <td className="px-4 py-2.5">
                    <span className={`text-[10px] uppercase font-semibold ${node.phase === 'engine' ? 'text-brand-amber' : 'text-brand-accent'}`}>
                      {node.phase}
                    </span>
                  </td>
                  <td className="px-4 py-2.5">
                    <StatusBadge status={status} />
                  </td>
                  <td className="px-4 py-2.5 text-right tabular-nums text-text-disabled">
                    {ms ? `${(ms / 1000).toFixed(1)}s` : '—'}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    </div>
  );
}
