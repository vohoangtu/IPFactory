'use client';

import { useState, useCallback } from 'react';
import { ArrowRight, ChevronLeft, ChevronRight, Loader2, Search, Unlink } from 'lucide-react';
import { useCausalLinks } from '../hooks';
import type { CausalLink } from '@/types/api';

interface CausalLinkPanelProps {
  universeId: number | null;
  onHighlight?: (nodeIds: string[]) => void;
}

const typeBadgeColors: Record<string, string> = {
  quantum_trade: 'bg-cyan-500/15 text-cyan-300',
  social: 'bg-amber-500/15 text-amber-300',
  ecological: 'bg-emerald-500/15 text-emerald-300',
  narrative: 'bg-violet-500/15 text-violet-300',
  economic: 'bg-rose-500/15 text-rose-300',
  political: 'bg-indigo-500/15 text-indigo-300',
};

export default function CausalLinkPanel({ universeId, onHighlight }: CausalLinkPanelProps) {
  const [fromTick, setFromTick] = useState<number | undefined>(undefined);
  const [toTick, setToTick] = useState<number | undefined>(undefined);
  const [isCollapsed, setIsCollapsed] = useState(false);

  const { causalLinks, isLoading, refetch } = useCausalLinks(universeId, fromTick, toTick);

  const handleLoad = useCallback(() => {
    if (!universeId) return;
    void refetch();
  }, [universeId, refetch]);

  const handleLinkClick = useCallback(
    (link: CausalLink) => {
      onHighlight?.([link.source, link.target]);
    },
    [onHighlight],
  );

  const links = causalLinks?.links ?? [];

  if (isCollapsed) {
    return (
      <div className="flex flex-col items-center">
        <button
          onClick={() => setIsCollapsed(false)}
          className="flex items-center justify-center w-10 h-10 rounded-xl border border-slate-700/50 bg-slate-900/80 text-slate-400 hover:text-cyan-300 hover:border-cyan-500/30 transition-all"
          title="Expand panel"
        >
          <ChevronLeft size={16} />
        </button>
      </div>
    );
  }

  return (
    <div className="w-80 flex-shrink-0 flex flex-col border-l border-slate-800/50 bg-[#0c0c10]/90 backdrop-blur-sm">
      {/* Header */}
      <div className="flex items-center justify-between px-4 py-3 border-b border-slate-800/50">
        <div>
          <h3 className="text-sm font-bold text-slate-200">Causal Links</h3>
          <p className="text-[10px] text-slate-500 mt-0.5">
            {links.length > 0
              ? `${links.length} link${links.length !== 1 ? 's' : ''} found`
              : 'Query causal connections'}
          </p>
        </div>
        <button
          onClick={() => setIsCollapsed(true)}
          className="p-1.5 rounded-lg text-slate-500 hover:text-slate-300 hover:bg-slate-800/50 transition"
          title="Collapse panel"
        >
          <ChevronRight size={16} />
        </button>
      </div>

      {/* Tick range controls */}
      <div className="px-4 py-3 border-b border-slate-800/30 space-y-2">
        <div className="flex gap-2">
          <div className="flex-1">
            <label className="text-[9px] font-bold uppercase tracking-[0.15em] text-slate-500 mb-1 block">
              From Tick
            </label>
            <input
              type="number"
              min={0}
              placeholder="0"
              value={fromTick ?? ''}
              onChange={(e) => setFromTick(e.target.value ? Number(e.target.value) : undefined)}
              className="w-full rounded-lg border border-slate-700/50 bg-slate-900/60 px-3 py-1.5 text-xs font-mono text-slate-200 placeholder:text-slate-600 focus:border-cyan-500/40 focus:outline-none focus:ring-1 focus:ring-cyan-500/20 transition"
            />
          </div>
          <div className="flex-1">
            <label className="text-[9px] font-bold uppercase tracking-[0.15em] text-slate-500 mb-1 block">
              To Tick
            </label>
            <input
              type="number"
              min={0}
              placeholder="∞"
              value={toTick ?? ''}
              onChange={(e) => setToTick(e.target.value ? Number(e.target.value) : undefined)}
              className="w-full rounded-lg border border-slate-700/50 bg-slate-900/60 px-3 py-1.5 text-xs font-mono text-slate-200 placeholder:text-slate-600 focus:border-cyan-500/40 focus:outline-none focus:ring-1 focus:ring-cyan-500/20 transition"
            />
          </div>
        </div>
        <button
          onClick={handleLoad}
          disabled={!universeId || isLoading}
          className="w-full flex items-center justify-center gap-2 rounded-lg border border-cyan-500/20 bg-cyan-500/10 px-3 py-2 text-xs font-bold uppercase tracking-[0.15em] text-cyan-300 transition hover:bg-cyan-500/20 disabled:opacity-40 disabled:cursor-not-allowed"
        >
          {isLoading ? (
            <Loader2 size={14} className="animate-spin" />
          ) : (
            <Search size={14} />
          )}
          {isLoading ? 'Loading...' : 'Load Links'}
        </button>
      </div>

      {/* Link list */}
      <div className="flex-1 overflow-y-auto custom-scrollbar">
        {links.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-16 px-4 text-center">
            <Unlink size={32} className="text-slate-700 mb-3" />
            <p className="text-xs font-bold text-slate-500">No causal links</p>
            <p className="text-[10px] text-slate-600 mt-1">
              Set a tick range and click Load to discover causal connections.
            </p>
          </div>
        ) : (
          <div className="divide-y divide-slate-800/40">
            {links.map((link) => {
              const badgeColor = typeBadgeColors[link.type] ?? 'bg-slate-500/15 text-slate-400';
              return (
                <button
                  key={link.id}
                  onClick={() => handleLinkClick(link)}
                  className="w-full text-left px-4 py-3 hover:bg-slate-800/30 transition-colors group"
                >
                  <div className="flex items-center gap-1.5 text-xs">
                    <span className="font-mono font-bold text-slate-300 truncate max-w-[90px]">
                      {link.source}
                    </span>
                    <ArrowRight size={12} className="flex-shrink-0 text-slate-600 group-hover:text-cyan-400 transition" />
                    <span className="font-mono font-bold text-slate-300 truncate max-w-[90px]">
                      {link.target}
                    </span>
                  </div>
                  <div className="mt-1.5 flex items-center gap-2">
                    <span
                      className={`inline-flex items-center rounded-md px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider ${badgeColor}`}
                    >
                      {link.type.replace(/_/g, ' ')}
                    </span>
                    <span className="text-[10px] font-mono text-slate-600">
                      tick {link.tick}
                    </span>
                  </div>
                </button>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}
