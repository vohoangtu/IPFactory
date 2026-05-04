'use client';

import React from 'react';

// ──────────────────────────────────────────────
// FlowDiagram v2
// PRD Enhancement:
// - Phase 1 / Phase 2 section labels
// - Revision loop indicator Critic ↩ Wordsmith
// - Waterfall timeline bar per node
// - Bottleneck highlight (slowest completed node)
// ──────────────────────────────────────────────

interface NodeStatus {
  status: 'idle' | 'running' | 'completed' | 'error';
  startedAt?: number;
  completedAt?: number;
  durationMs?: number;
}

interface FlowDiagramProps {
  nodes: Record<string, NodeStatus>;
  onNodeClick?: (nodeId: string) => void;
  selectedNode?: string;
  /* revision loop metadata — populated from runtime */
  revisionCount?: number;
}

// ── Helpers ────────────────────────────────────

function getStatusColor(status: string) {
  switch (status) {
    case 'running':
      return 'border-violet-500 bg-violet-500/20 shadow-[0_0_12px_rgba(139,92,246,0.6)] animate-pulse';
    case 'completed':
      return 'border-emerald-500 bg-emerald-500/20 shadow-[0_0_8px_rgba(16,185,129,0.4)]';
    case 'error':
      return 'border-rose-500 bg-rose-500/20 shadow-[0_0_8px_rgba(244,63,94,0.4)]';
    default:
      return 'border-gray-700 bg-gray-800/50';
  }
}

function getStatusDot(status: string) {
  switch (status) {
    case 'running': return 'bg-violet-400 animate-pulse';
    case 'completed': return 'bg-emerald-400';
    case 'error': return 'bg-rose-400';
    default: return 'bg-gray-600';
  }
}

function getDuration(node: NodeStatus): string | null {
  if (node.durationMs) return `${(node.durationMs / 1000).toFixed(1)}s`;
  if (node.startedAt && node.completedAt) {
    return `${((node.completedAt - node.startedAt) / 1000).toFixed(2)}s`;
  }
  return null;
}

// ── Waterfall bar ──────────────────────────────

function WaterfallBar({ node, maxMs }: { node: NodeStatus; maxMs: number }) {
  if (!node.durationMs && !(node.startedAt && node.completedAt)) return null;
  const ms = node.durationMs ?? (node.completedAt! - node.startedAt!);
  const pct = maxMs > 0 ? Math.min(100, (ms / maxMs) * 100) : 0;

  let barColor = 'bg-emerald-500/60';
  if (pct > 70) barColor = 'bg-amber-500/60';
  if (pct > 90) barColor = 'bg-rose-500/60';

  return (
    <div className="mt-1 h-0.5 w-full rounded-full bg-slate-800">
      <div
        className={`h-full rounded-full ${barColor} transition-all duration-500`}
        style={{ width: `${pct}%` }}
      />
    </div>
  );
}

// ── Node card ─────────────────────────────────

interface NodeCardProps {
  id: string;
  label: string;
  status: NodeStatus;
  allNodes: Record<string, NodeStatus>;
  onNodeClick?: (id: string) => void;
  isSelected?: boolean;
  isCritic?: boolean;
  isWordsmith?: boolean;
}

function NodeCard({ id, label, status, allNodes, onNodeClick, isSelected, isCritic, isWordsmith }: NodeCardProps) {
  // Compute max duration across all nodes for waterfall context
  const maxMs = Object.values(allNodes).reduce((max, n) => {
    const ms = n.durationMs ?? (n.startedAt && n.completedAt ? n.completedAt - n.startedAt : 0);
    return Math.max(max, ms);
  }, 0);

  const duration = getDuration(status);

  return (
    <div
      className={`relative cursor-pointer rounded-lg border px-4 py-2.5 transition-all duration-300 ${getStatusColor(status.status)} ${
        isSelected ? 'ring-2 ring-cyan-400 ring-offset-1 ring-offset-black' : ''
      } ${isCritic ? 'border-rose-500/60' : ''} ${isWordsmith ? 'border-emerald-500/60' : ''}`}
      onClick={() => onNodeClick?.(id)}
      title={id}
    >
      <div className="flex items-center gap-2.5">
        <div className={`h-2 w-2 rounded-full flex-shrink-0 ${getStatusDot(status.status)}`} />
        <span className="text-xs font-bold text-gray-200 truncate">{label}</span>
        {duration && (
          <span className="ml-auto text-[10px] text-gray-400 tabular-nums">{duration}</span>
        )}
      </div>
      <WaterfallBar node={status} maxMs={maxMs} />
    </div>
  );
}

// ── Connector ─────────────────────────────────

function HArrow() {
  return <div className="w-8 h-0.5 bg-slate-700 flex-shrink-0" />;
}

function VConnector() {
  return <div className="mx-auto w-0.5 h-5 bg-slate-700" />;
}

// ── Revision Loop Badge ────────────────────────

function RevisionLoopIndicator({ revisionCount, criticDone }: {
  revisionCount: number;
  criticDone: boolean;
}) {
  if (!criticDone && revisionCount === 0) return null;
  return (
    <div className={`inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-[10px] font-black uppercase tracking-widest ${
      revisionCount > 0
        ? 'border-rose-500/30 bg-rose-500/10 text-rose-400'
        : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
    }`}>
      ↩ {revisionCount > 0 ? `${revisionCount} revision${revisionCount > 1 ? 's' : ''}` : 'Critic passed'}
    </div>
  );
}

// ── Phase Label ───────────────────────────────

function PhaseLabel({ label, color }: { label: string; color: 'amber' | 'violet' }) {
  return (
    <div className={`mb-3 inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-[0.28em] ${
      color === 'amber'
        ? 'border-amber-500/20 bg-amber-500/10 text-amber-400'
        : 'border-violet-500/20 bg-violet-500/10 text-violet-400'
    }`}>
      <span className={`h-1.5 w-1.5 rounded-full ${color === 'amber' ? 'bg-amber-400' : 'bg-violet-400'}`} />
      {label}
    </div>
  );
}

// ── Main Component ─────────────────────────────

const FlowDiagram: React.FC<FlowDiagramProps> = ({
  nodes,
  onNodeClick,
  selectedNode,
  revisionCount = 0,
}) => {
  const getNode = (id: string): NodeStatus => nodes[id] ?? { status: 'idle' };

  const criticDone = getNode('The_Critic').status === 'completed';

  return (
    <div className="space-y-5 overflow-x-auto pb-2">

      {/* ── Phase 1: Engines ── */}
      <div>
        <PhaseLabel label="Phase 1 — Engines (Data Analysis)" color="amber" />
        <div className="space-y-2.5">
          {/* Row 1: sequential pair */}
          <div className="flex items-center gap-2">
            <NodeCard id="Event_Normalizer" label="Event Normalizer" status={getNode('Event_Normalizer')} allNodes={nodes} onNodeClick={onNodeClick} isSelected={selectedNode === 'Event_Normalizer'} />
            <HArrow />
            <NodeCard id="Universe_Bridge" label="Universe Bridge" status={getNode('Universe_Bridge')} allNodes={nodes} onNodeClick={onNodeClick} isSelected={selectedNode === 'Universe_Bridge'} />
          </div>

          <VConnector />

          {/* Row 2: parallel engines */}
          <div className="flex flex-wrap items-center gap-2">
            <NodeCard id="Entropy_Engine" label="Entropy Engine" status={getNode('Entropy_Engine')} allNodes={nodes} onNodeClick={onNodeClick} isSelected={selectedNode === 'Entropy_Engine'} />
            <HArrow />
            <NodeCard id="Style_Analyzer" label="Style Analyzer" status={getNode('Style_Analyzer')} allNodes={nodes} onNodeClick={onNodeClick} isSelected={selectedNode === 'Style_Analyzer'} />
            <HArrow />
            <NodeCard id="Attractor_Engine" label="Attractor Engine" status={getNode('Attractor_Engine')} allNodes={nodes} onNodeClick={onNodeClick} isSelected={selectedNode === 'Attractor_Engine'} />
            <HArrow />
            <NodeCard id="Dramatic_Arc" label="Dramatic Arc" status={getNode('Dramatic_Arc')} allNodes={nodes} onNodeClick={onNodeClick} isSelected={selectedNode === 'Dramatic_Arc'} />
          </div>

          <VConnector />

          <div className="flex flex-wrap items-center gap-2">
            <NodeCard id="Phase_Engine" label="Phase Engine" status={getNode('Phase_Engine')} allNodes={nodes} onNodeClick={onNodeClick} isSelected={selectedNode === 'Phase_Engine'} />
            <HArrow />
            <NodeCard id="Singularity_Engine" label="Singularity Engine" status={getNode('Singularity_Engine')} allNodes={nodes} onNodeClick={onNodeClick} isSelected={selectedNode === 'Singularity_Engine'} />
          </div>
        </div>
      </div>

      {/* Divider */}
      <div className="border-t border-dashed border-slate-800 pt-4">
        <PhaseLabel label="Phase 2 — Agents (Content Creation)" color="violet" />
      </div>

      {/* ── Phase 2: Agents ── */}
      <div className="space-y-2.5">
        {/* Chief Editor */}
        <div className="flex items-center gap-2">
          <NodeCard id="Chief_Editor" label="Chief Editor" status={getNode('Chief_Editor')} allNodes={nodes} onNodeClick={onNodeClick} isSelected={selectedNode === 'Chief_Editor'} />
        </div>

        <VConnector />

        {/* Parallel: Historian + Mythologist */}
        <div className="flex flex-wrap items-center gap-2">
          <NodeCard id="The_Historian" label="Historian" status={getNode('The_Historian')} allNodes={nodes} onNodeClick={onNodeClick} isSelected={selectedNode === 'The_Historian'} />
          <HArrow />
          <NodeCard id="The_Mythologist" label="Mythologist" status={getNode('The_Mythologist')} allNodes={nodes} onNodeClick={onNodeClick} isSelected={selectedNode === 'The_Mythologist'} />
        </div>

        <VConnector />

        {/* Parallel: Psychologist + Director */}
        <div className="flex flex-wrap items-center gap-2">
          <NodeCard id="The_Psychologist" label="Psychologist" status={getNode('The_Psychologist')} allNodes={nodes} onNodeClick={onNodeClick} isSelected={selectedNode === 'The_Psychologist'} />
          <HArrow />
          <NodeCard id="The_Director" label="Director" status={getNode('The_Director')} allNodes={nodes} onNodeClick={onNodeClick} isSelected={selectedNode === 'The_Director'} />
        </div>

        <VConnector />

        {/* ── REVISION LOOP: Wordsmith ↔ Critic ── */}
        <div className="rounded-2xl border border-slate-800/60 bg-slate-950/40 p-3">
          <div className="mb-2 flex items-center justify-between">
            <p className="text-[9px] font-black uppercase tracking-widest text-slate-500">Revision Loop</p>
            <RevisionLoopIndicator
              revisionCount={revisionCount}
              criticDone={criticDone}
            />
          </div>
          <div className="flex items-center gap-2">
            <NodeCard
              id="The_Wordsmith"
              label="Wordsmith"
              status={getNode('The_Wordsmith')}
              allNodes={nodes}
              onNodeClick={onNodeClick}
              isSelected={selectedNode === 'The_Wordsmith'}
              isWordsmith
            />
            {/* Bidirectional arrow */}
            <div className="flex flex-col items-center gap-0.5 px-1">
              <span className="text-[10px] text-rose-400">↔</span>
              {revisionCount > 0 && (
                <span className="whitespace-nowrap text-[9px] font-black text-rose-500">
                  ×{revisionCount}
                </span>
              )}
            </div>
            <NodeCard
              id="The_Critic"
              label="Critic"
              status={getNode('The_Critic')}
              allNodes={nodes}
              onNodeClick={onNodeClick}
              isSelected={selectedNode === 'The_Critic'}
              isCritic
            />
          </div>
        </div>

        <VConnector />

        {/* Final: VFX Director → Archivist → News Anchor */}
        <div className="flex flex-wrap items-center gap-2">
          <NodeCard id="VFX_Director" label="VFX Director" status={getNode('VFX_Director')} allNodes={nodes} onNodeClick={onNodeClick} isSelected={selectedNode === 'VFX_Director'} />
          <HArrow />
          <NodeCard id="The_Archivist" label="Archivist" status={getNode('The_Archivist')} allNodes={nodes} onNodeClick={onNodeClick} isSelected={selectedNode === 'The_Archivist'} />
          <HArrow />
          <NodeCard id="News_Anchor" label="News Anchor" status={getNode('News_Anchor')} allNodes={nodes} onNodeClick={onNodeClick} isSelected={selectedNode === 'News_Anchor'} />
        </div>
      </div>
    </div>
  );
};

export default FlowDiagram;
