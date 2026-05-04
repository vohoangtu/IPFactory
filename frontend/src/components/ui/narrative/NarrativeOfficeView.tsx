'use client';

import React from 'react';
import {
  Cpu,
  RadioTower,
  Sparkles,
  Wifi,
  WifiOff,
} from 'lucide-react';
import type { ConnectionState } from '@/hooks/useCentrifugo';
import {
  NARRATIVE_PIPELINE_NODES,
  type LoomStatus,
  type NarrativeRuntimeNodeState,
} from '@/features/narrative-runtime/types';

interface NarrativeOfficeViewProps {
  nodes: Record<string, NarrativeRuntimeNodeState>;
  selectedNode?: string;
  onSelectNode?: (nodeId: string) => void;
  currentAgent?: string | null;
  connectionState: ConnectionState;
  loomStatus: LoomStatus | null;
}

function getNodeSurface(status: NarrativeRuntimeNodeState['status'], accent: string) {
  switch (status) {
    case 'running':
      return {
        background: 'rgba(250, 250, 252, 0.98)',
        border: accent,
        shadow: `0 0 0 1px ${accent}55, 0 12px 24px rgba(15, 23, 42, 0.28)`,
        dot: accent,
      };
    case 'completed':
      return {
        background: 'rgba(245, 255, 248, 0.98)',
        border: '#22c55e',
        shadow: '0 0 0 1px rgba(34,197,94,0.35), 0 10px 20px rgba(15,23,42,0.18)',
        dot: '#22c55e',
      };
    case 'error':
      return {
        background: 'rgba(255, 245, 245, 0.98)',
        border: '#ef4444',
        shadow: '0 0 0 1px rgba(239,68,68,0.35), 0 10px 20px rgba(15,23,42,0.18)',
        dot: '#ef4444',
      };
    default:
      return {
        background: 'rgba(255, 255, 255, 0.94)',
        border: 'rgba(148, 163, 184, 0.35)',
        shadow: '0 8px 18px rgba(15, 23, 42, 0.12)',
        dot: '#94a3b8',
      };
  }
}

function deskDimensions(size: string) {
  if (size === 'boss') return { width: 148, height: 94 };
  if (size === 'feature') return { width: 138, height: 90 };
  if (size === 'compact') return { width: 104, height: 72 };
  return { width: 118, height: 78 };
}

export default function NarrativeOfficeView({
  nodes,
  selectedNode,
  onSelectNode,
  currentAgent,
  connectionState,
  loomStatus,
}: NarrativeOfficeViewProps) {
  const selectedDefinition = selectedNode
    ? NARRATIVE_PIPELINE_NODES.find((node) => node.id === selectedNode)
    : undefined;
  const selectedState = selectedNode ? nodes[selectedNode] : undefined;

  return (
    <div className="overflow-hidden rounded-3xl border border-slate-800 bg-[#f4efe5]">
      <div className="flex items-center justify-between border-b border-[#d9d0c3] bg-[#fbf7ef] px-5 py-4">
        <div>
          <p className="text-[10px] font-black uppercase tracking-[0.28em] text-slate-500">
            Narrative Loom Office
          </p>
          <h3 className="mt-1 text-lg font-black text-slate-900">
            Phase 1 Engines to Phase 2 Agents
          </h3>
        </div>
        <div className="flex items-center gap-3">
          <div className="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white/80 px-3 py-1.5 text-[11px] font-bold text-slate-600">
            {connectionState === 'connected' ? <Wifi size={14} /> : <WifiOff size={14} />}
            {connectionState}
          </div>
          <div className="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white/80 px-3 py-1.5 text-[11px] font-bold text-slate-600">
            <Sparkles size={14} />
            {loomStatus?.status ?? 'unknown'}
          </div>
        </div>
      </div>

      <div
        className="relative min-h-[760px] overflow-hidden"
        style={{
          background:
            'linear-gradient(90deg, rgba(224,219,208,1) 0%, rgba(224,219,208,1) 44%, rgba(204,204,204,1) 44%, rgba(204,204,204,1) 46%, rgba(237,232,252,1) 46%, rgba(237,232,252,1) 100%)',
        }}
      >
        <div
          className="pointer-events-none absolute inset-0 opacity-30"
          style={{
            backgroundImage:
              'linear-gradient(rgba(15,23,42,0.08) 1px, transparent 1px), linear-gradient(90deg, rgba(15,23,42,0.08) 1px, transparent 1px)',
            backgroundSize: '46px 46px',
          }}
        />

        <div className="absolute left-[22px] top-[18px] rounded-full bg-[#f8f3e7] px-4 py-2 text-[11px] font-black uppercase tracking-[0.25em] text-slate-700 shadow-sm">
          Engine Zone
        </div>
        <div className="absolute right-[22px] top-[18px] rounded-full bg-[#f7f1ff] px-4 py-2 text-[11px] font-black uppercase tracking-[0.25em] text-slate-700 shadow-sm">
          Agent Zone
        </div>

        <div className="absolute left-[45.2%] top-0 h-full w-px border-l border-dashed border-slate-400/80" />

        <div className="absolute right-[28px] top-[92px] w-[160px] rounded-2xl border border-slate-300 bg-white/75 p-4 shadow-sm backdrop-blur">
          <div className="mb-3 flex items-center gap-2 text-sm font-black text-slate-800">
            <RadioTower size={16} />
            Server Corner
          </div>
          <div className="space-y-2 text-xs text-slate-600">
            <div className="flex items-center justify-between rounded-xl bg-slate-100 px-3 py-2">
              <span>Centrifugo</span>
              <span className="font-black text-violet-500">
                {connectionState === 'connected' ? 'LIVE' : 'RETRY'}
              </span>
            </div>
            <div className="flex items-center justify-between rounded-xl bg-slate-100 px-3 py-2">
              <span>Providers</span>
              <span className="font-black text-slate-800">
                {Object.keys(loomStatus?.providers ?? {}).length}
              </span>
            </div>
            <div className="flex items-center justify-between rounded-xl bg-slate-100 px-3 py-2">
              <span>Agents</span>
              <span className="font-black text-slate-800">
                {Object.keys(loomStatus?.agents ?? {}).length}
              </span>
            </div>
          </div>
        </div>

        {NARRATIVE_PIPELINE_NODES.map((node) => {
          const state = nodes[node.id] ?? { status: 'idle' };
          const surface = getNodeSurface(state.status, node.accent);
          const { width, height } = deskDimensions(node.deskSize);
          const isSelected = node.id === selectedNode;
          const isCurrent = node.id === currentAgent;

          return (
            <button
              key={node.id}
              type="button"
              onClick={() => onSelectNode?.(node.id)}
              className="absolute rounded-2xl text-left transition-transform duration-200 hover:-translate-y-1"
              style={{
                left: `${node.officeX}%`,
                top: `${node.officeY}%`,
                width,
                height,
                background: surface.background,
                boxShadow: isSelected
                  ? `${surface.shadow}, 0 0 0 3px rgba(14, 165, 233, 0.28)`
                  : surface.shadow,
                border: `1px solid ${surface.border}`,
              }}
            >
              <div
                className="rounded-t-[20px] px-3 py-2 text-[10px] font-black uppercase tracking-[0.25em] text-slate-900"
                style={{ backgroundColor: node.accent }}
              >
                {node.phase}
              </div>
              <div className="flex h-[calc(100%-34px)] flex-col justify-between px-3 py-3">
                <div className="flex items-start justify-between gap-2">
                  <div>
                    <p className="text-xs font-black leading-tight text-slate-900">{node.shortLabel}</p>
                    <p className="mt-1 text-[10px] text-slate-600">{node.role}</p>
                  </div>
                  <span
                    className="mt-0.5 inline-flex h-2.5 w-2.5 rounded-full"
                    style={{
                      backgroundColor: surface.dot,
                      boxShadow: isCurrent ? `0 0 0 5px ${surface.dot}33` : 'none',
                    }}
                  />
                </div>

                <div className="flex items-center justify-between text-[10px] text-slate-500">
                  <span>{state.durationMs ? `${state.durationMs}ms` : 'standby'}</span>
                  <span className="font-black uppercase">{state.status}</span>
                </div>
              </div>
            </button>
          );
        })}

        <div className="absolute bottom-0 left-0 right-0 border-t border-[#d9d0c3] bg-[#fbf7ef]/95 px-5 py-4 backdrop-blur">
          <div className="mb-3 flex items-center justify-between">
            <div>
              <p className="text-[10px] font-black uppercase tracking-[0.25em] text-slate-500">
                Pipeline Bar
              </p>
              <p className="text-sm font-bold text-slate-800">
                {selectedDefinition ? selectedDefinition.label : 'Select a desk to inspect its role'}
              </p>
            </div>
            {selectedDefinition ? (
              <div className="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-bold text-slate-600">
                <Cpu size={14} />
                {selectedState?.status ?? 'idle'}
              </div>
            ) : null}
          </div>

          <div className="mb-4 flex flex-wrap gap-2">
            {NARRATIVE_PIPELINE_NODES.map((node) => {
              const state = nodes[node.id] ?? { status: 'idle' };
              const surface = getNodeSurface(state.status, node.accent);

              return (
                <button
                  key={`${node.id}-dot`}
                  type="button"
                  onClick={() => onSelectNode?.(node.id)}
                  className="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-[11px] font-bold text-slate-700 transition hover:-translate-y-0.5"
                  style={{
                    borderColor: node.id === selectedNode ? '#0ea5e9' : 'rgba(148, 163, 184, 0.35)',
                    backgroundColor: 'rgba(255,255,255,0.92)',
                  }}
                >
                  <span
                    className="inline-flex h-2.5 w-2.5 rounded-full"
                    style={{ backgroundColor: surface.dot }}
                  />
                  {node.shortLabel}
                </button>
              );
            })}
          </div>

          {selectedDefinition ? (
            <div className="rounded-2xl border border-slate-200 bg-white/85 p-4 shadow-sm">
              <p className="text-sm font-black text-slate-900">{selectedDefinition.label}</p>
              <p className="mt-1 text-sm leading-relaxed text-slate-600">
                {selectedDefinition.description}
              </p>
              {selectedState?.error ? (
                <p className="mt-3 text-xs font-bold text-rose-500">{selectedState.error}</p>
              ) : null}
            </div>
          ) : null}
        </div>
      </div>
    </div>
  );
}
