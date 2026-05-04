'use client';

import { memo } from 'react';
import { Handle, Position, type NodeProps } from '@xyflow/react';

interface ZoneNodeData {
  label: string;
  type: string;
  metrics: Record<string, number>;
  highlighted?: boolean;
  [key: string]: unknown;
}

const borderColorMap: Record<string, string> = {
  zone: 'border-cyan-500/60',
  settlement: 'border-amber-500/60',
  territory: 'border-emerald-500/60',
};

const badgeColorMap: Record<string, string> = {
  zone: 'bg-cyan-500/15 text-cyan-300 ring-cyan-500/30',
  settlement: 'bg-amber-500/15 text-amber-300 ring-amber-500/30',
  territory: 'bg-emerald-500/15 text-emerald-300 ring-emerald-500/30',
};

const glowColorMap: Record<string, string> = {
  zone: 'shadow-[0_0_20px_rgba(6,182,212,0.4)]',
  settlement: 'shadow-[0_0_20px_rgba(245,158,11,0.4)]',
  territory: 'shadow-[0_0_20px_rgba(16,185,129,0.4)]',
};

function ZoneNode({ data }: NodeProps) {
  const nodeData = data as ZoneNodeData;
  const { label, type, metrics, highlighted } = nodeData;

  const borderColor = borderColorMap[type] ?? 'border-slate-600/60';
  const badgeColor = badgeColorMap[type] ?? 'bg-slate-500/15 text-slate-300 ring-slate-500/30';
  const glowEffect = highlighted ? (glowColorMap[type] ?? 'shadow-[0_0_20px_rgba(148,163,184,0.4)]') : '';

  // Pick the first numeric metric to display
  const metricEntries = Object.entries(metrics ?? {});
  const primaryMetric = metricEntries[0];

  return (
    <>
      <Handle
        type="target"
        position={Position.Top}
        className="!w-2 !h-2 !bg-slate-600 !border-slate-500"
      />

      <div
        className={`
          relative min-w-[180px] max-w-[220px] rounded-xl border-2 ${borderColor}
          bg-slate-900/90 backdrop-blur-sm px-4 py-3
          transition-all duration-300
          ${highlighted ? `ring-2 ring-offset-1 ring-offset-transparent ring-cyan-400/40 ${glowEffect}` : ''}
        `}
      >
        {/* Clipped corner accents */}
        <div className="absolute top-0 left-0 w-3 h-3 border-t-2 border-l-2 border-inherit rounded-tl-xl opacity-60" />
        <div className="absolute top-0 right-0 w-3 h-3 border-t-2 border-r-2 border-inherit rounded-tr-xl opacity-60" />
        <div className="absolute bottom-0 left-0 w-3 h-3 border-b-2 border-l-2 border-inherit rounded-bl-xl opacity-60" />
        <div className="absolute bottom-0 right-0 w-3 h-3 border-b-2 border-r-2 border-inherit rounded-br-xl opacity-60" />

        {/* Label */}
        <div className="text-sm font-bold text-slate-100 truncate mb-1.5">
          {label}
        </div>

        {/* Type badge */}
        <span
          className={`inline-flex items-center rounded-md px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.12em] ring-1 ring-inset ${badgeColor}`}
        >
          {type}
        </span>

        {/* Key metric */}
        {primaryMetric && (
          <div className="mt-2 flex items-center justify-between text-[11px]">
            <span className="text-slate-500 capitalize">
              {primaryMetric[0].replace(/_/g, ' ')}
            </span>
            <span className="font-mono font-bold text-slate-300">
              {typeof primaryMetric[1] === 'number'
                ? primaryMetric[1] % 1 === 0
                  ? primaryMetric[1]
                  : primaryMetric[1].toFixed(2)
                : primaryMetric[1]}
            </span>
          </div>
        )}
      </div>

      <Handle
        type="source"
        position={Position.Bottom}
        className="!w-2 !h-2 !bg-slate-600 !border-slate-500"
      />
    </>
  );
}

export default memo(ZoneNode);
