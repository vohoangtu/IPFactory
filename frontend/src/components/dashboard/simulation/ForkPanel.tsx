'use client';

import { useState } from 'react';
import {
  GitBranch,
  GitCompareArrows,
  Plus,
  RefreshCcw,
  ArrowRightLeft,
  ChevronDown,
  ChevronUp,
} from 'lucide-react';

import SectionPanel from '@/components/ui/shared/SectionPanel';
import BadgeLabel from '@/components/ui/shared/BadgeLabel';
import EmptyState from '@/components/ui/shared/EmptyState';
import { useUniverse } from '@/contexts/UniverseContext';
import {
  useForks,
  useForkUniverse,
  useCompareBranch,
} from '@/features/simulation/hooks';
import type { BranchComparison } from '@/shared/types/api';

export default function ForkPanel() {
  const { activeUniverseId } = useUniverse();
  const { forks, isLoading } = useForks(activeUniverseId);
  const forkMutation = useForkUniverse();

  const [forkTick, setForkTick] = useState<string>('');
  const [forkName, setForkName] = useState('');
  const [expandedBranch, setExpandedBranch] = useState<number | null>(null);

  const handleFork = () => {
    if (!activeUniverseId) return;
    forkMutation.mutate(
      {
        universeId: activeUniverseId,
        tick: forkTick ? Number(forkTick) : undefined,
        name: forkName || undefined,
      },
      {
        onSuccess: () => {
          setForkTick('');
          setForkName('');
        },
      },
    );
  };

  const handleCompare = (branchId: number) => {
    if (!activeUniverseId) return;

    // Toggle expanded
    if (expandedBranch === branchId) {
      setExpandedBranch(null);
      return;
    }

    setExpandedBranch(branchId);
  };

  const statusVariant = (status: string) => {
    switch (status) {
      case 'active':
        return 'emerald' as const;
      case 'paused':
        return 'amber' as const;
      case 'archived':
        return 'slate' as const;
      default:
        return 'slate' as const;
    }
  };

  return (
    <SectionPanel>
      {/* Header */}
      <div className="mb-6 flex items-center gap-3">
        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-500/10">
          <GitBranch size={18} className="text-indigo-400" />
        </div>
        <div>
          <h3 className="text-base font-black tracking-tight text-white">
            Branches & Forks
          </h3>
          <p className="text-xs text-slate-500">
            {forks.length} branch{forks.length !== 1 ? 'es' : ''}
          </p>
        </div>
      </div>

      {/* Fork Form */}
      <div className="mb-6 rounded-xl border border-slate-800 bg-slate-900/30 p-4">
        <p className="mb-3 text-xs font-bold uppercase tracking-widest text-slate-500">
          Create Fork
        </p>
        <div className="flex flex-wrap items-end gap-3">
          <div className="min-w-[120px] flex-1">
            <label className="mb-1 block text-[11px] text-slate-500">
              Name (optional)
            </label>
            <input
              type="text"
              value={forkName}
              onChange={(e) => setForkName(e.target.value)}
              placeholder="Branch name..."
              className="w-full rounded-lg border border-slate-800 bg-slate-900/60 px-3 py-2 text-xs text-white placeholder-slate-600 outline-none transition-colors focus:border-cyan-500/40"
            />
          </div>
          <div className="w-[100px]">
            <label className="mb-1 block text-[11px] text-slate-500">
              At tick
            </label>
            <input
              type="number"
              value={forkTick}
              onChange={(e) => setForkTick(e.target.value)}
              placeholder="Latest"
              min={0}
              className="w-full rounded-lg border border-slate-800 bg-slate-900/60 px-3 py-2 text-xs font-mono text-white placeholder-slate-600 outline-none transition-colors focus:border-cyan-500/40"
            />
          </div>
          <button
            onClick={handleFork}
            disabled={forkMutation.isPending || !activeUniverseId}
            className="flex items-center gap-1.5 rounded-lg border border-cyan-500/20 bg-cyan-500/10 px-4 py-2 text-xs font-bold text-cyan-200 transition-all hover:bg-cyan-500/20 disabled:cursor-not-allowed disabled:opacity-40"
          >
            {forkMutation.isPending ? (
              <RefreshCcw size={12} className="animate-spin" />
            ) : (
              <Plus size={12} />
            )}
            Fork
          </button>
        </div>
      </div>

      {/* Fork List */}
      {isLoading ? (
        <div className="flex items-center justify-center py-12">
          <RefreshCcw size={20} className="animate-spin text-slate-600" />
        </div>
      ) : forks.length === 0 ? (
        <EmptyState
          icon={GitBranch}
          title="No branches"
          message="Fork the universe to create parallel timelines."
        />
      ) : (
        <div className="space-y-2">
          {forks.map((fork) => (
            <div key={fork.id}>
              {/* Fork Row */}
              <div className="flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-900/30 px-4 py-3">
                <GitCompareArrows size={14} className="shrink-0 text-indigo-400" />
                <div className="flex-1 min-w-0">
                  <p className="truncate text-sm font-bold text-white">
                    {fork.name || fork.label || `Fork #${fork.id}`}
                  </p>
                  <div className="mt-1 flex items-center gap-3 text-[11px] text-slate-500">
                    <span>
                      Diverged at tick{' '}
                      <span className="font-mono text-cyan-400">
                        {fork.divergence_tick}
                      </span>
                    </span>
                    <span>
                      Current:{' '}
                      <span className="font-mono text-white">
                        {fork.current_tick}
                      </span>
                    </span>
                  </div>
                </div>
                <BadgeLabel variant={statusVariant(fork.status)}>
                  {fork.status}
                </BadgeLabel>
                <button
                  onClick={() => handleCompare(fork.id)}
                  className="flex items-center gap-1 rounded-lg border border-slate-700 bg-slate-800/50 px-2.5 py-1.5 text-[11px] font-bold text-slate-300 transition-colors hover:bg-slate-800 hover:text-white"
                >
                  <ArrowRightLeft size={10} />
                  Compare
                  {expandedBranch === fork.id ? (
                    <ChevronUp size={10} />
                  ) : (
                    <ChevronDown size={10} />
                  )}
                </button>
              </div>

              {/* Comparison Panel */}
              {expandedBranch === fork.id && (
                <div className="ml-6 mt-1 rounded-xl border border-slate-800/60 bg-slate-950/60 p-4">
                  <BranchComparisonPanel
                    universeId={activeUniverseId}
                    branchId={fork.id}
                  />
                </div>
              )}
            </div>
          ))}
        </div>
      )}
    </SectionPanel>
  );
}

function BranchComparisonPanel({
  universeId,
  branchId,
}: {
  universeId: number | null;
  branchId: number;
}) {
  const { comparison, isLoading } = useCompareBranch(universeId, branchId);

  if (isLoading && !comparison) {
    return (
      <div className="flex items-center justify-center py-4">
        <RefreshCcw size={14} className="animate-spin text-slate-600" />
      </div>
    );
  }

  if (!comparison) {
    return <p className="text-xs text-slate-500">No comparison data available.</p>;
  }

  return <ComparisonDisplay data={comparison} />;
}

// ── Comparison inline display ───────────────────

function ComparisonDisplay({ data }: { data: BranchComparison }) {
  const formatDelta = (val: number) => {
    const sign = val >= 0 ? '+' : '';
    return `${sign}${val.toFixed(3)}`;
  };

  const deltaColor = (val: number) =>
    val > 0 ? 'text-emerald-400' : val < 0 ? 'text-rose-400' : 'text-slate-400';

  return (
    <div>
      {/* Header */}
      <div className="mb-3 flex items-center gap-2 text-xs text-slate-400">
        <span className="font-bold text-white">{data.source.name}</span>
        <ArrowRightLeft size={10} />
        <span className="font-bold text-white">{data.branch.name}</span>
        <span className="ml-auto font-mono text-slate-600">
          {data.tick_span} tick span
        </span>
      </div>

      {/* Side-by-side Metrics */}
      <div className="mb-3 grid grid-cols-2 gap-3">
        <div className="rounded-lg border border-slate-800 bg-slate-900/30 p-2.5">
          <p className="text-[10px] uppercase tracking-widest text-slate-600">
            Source
          </p>
          <p className="mt-1 text-xs text-slate-400">
            Entropy:{' '}
            <span className="font-mono text-amber-300">
              {data.source.entropy.toFixed(3)}
            </span>
          </p>
          <p className="text-xs text-slate-400">
            Stability:{' '}
            <span className="font-mono text-emerald-300">
              {data.source.stability_index.toFixed(3)}
            </span>
          </p>
        </div>
        <div className="rounded-lg border border-slate-800 bg-slate-900/30 p-2.5">
          <p className="text-[10px] uppercase tracking-widest text-slate-600">
            Branch
          </p>
          <p className="mt-1 text-xs text-slate-400">
            Entropy:{' '}
            <span className="font-mono text-amber-300">
              {data.branch.entropy.toFixed(3)}
            </span>
          </p>
          <p className="text-xs text-slate-400">
            Stability:{' '}
            <span className="font-mono text-emerald-300">
              {data.branch.stability_index.toFixed(3)}
            </span>
          </p>
        </div>
      </div>

      {/* Deltas */}
      {Object.keys(data.deltas).length > 0 && (
        <div>
          <p className="mb-1.5 text-[10px] font-bold uppercase tracking-widest text-slate-600">
            Deltas
          </p>
          <div className="flex flex-wrap gap-2">
            {Object.entries(data.deltas).map(([key, val]) => (
              <span
                key={key}
                className="inline-flex items-center gap-1 rounded-lg bg-slate-900/50 px-2 py-1 text-[11px]"
              >
                <span className="text-slate-500">{key}:</span>
                <span className={`font-mono font-bold ${deltaColor(Number(val))}`}>
                  {formatDelta(Number(val))}
                </span>
              </span>
            ))}
          </div>
        </div>
      )}

      {/* Metric Deltas */}
      {Object.keys(data.metric_deltas).length > 0 && (
        <div className="mt-2">
          <p className="mb-1.5 text-[10px] font-bold uppercase tracking-widest text-slate-600">
            Metric Deltas
          </p>
          <div className="flex flex-wrap gap-2">
            {Object.entries(data.metric_deltas).map(([key, val]) => (
              <span
                key={key}
                className="inline-flex items-center gap-1 rounded-lg bg-slate-900/50 px-2 py-1 text-[11px]"
              >
                <span className="text-slate-500">{key}:</span>
                <span className={`font-mono font-bold ${deltaColor(Number(val))}`}>
                  {formatDelta(Number(val))}
                </span>
              </span>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
