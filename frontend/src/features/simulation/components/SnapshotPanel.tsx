'use client';

import { Camera, Plus, RefreshCcw, Clock } from 'lucide-react';

import SectionPanel from '@/shared/ui/SectionPanel';
import EmptyState from '@/shared/ui/EmptyState';
import { useSnapshots, useCreateSnapshot } from '../hooks';

export default function SnapshotPanel({ universeId }: { universeId: number }) {
  const { snapshots, isLoading } = useSnapshots(universeId);
  const createMutation = useCreateSnapshot();

  const handleCreate = () => {
    if (!universeId) return;
    createMutation.mutate(universeId);
  };

  const formatDate = (dateStr: string | null) => {
    if (!dateStr) return '--';
    try {
      return new Date(dateStr).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      });
    } catch {
      return dateStr;
    }
  };

  return (
    <SectionPanel className="flex flex-col">
      {/* Header */}
      <div className="mb-6 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/10">
            <Camera size={18} className="text-amber-400" />
          </div>
          <div>
            <h3 className="text-base font-black tracking-tight text-white">
              Snapshots
            </h3>
            <p className="text-xs text-slate-500">
              {snapshots.length} snapshot{snapshots.length !== 1 ? 's' : ''}
            </p>
          </div>
        </div>
        <button
          onClick={handleCreate}
          disabled={createMutation.isPending || !universeId}
          className="flex items-center gap-1.5 rounded-xl border border-cyan-500/20 bg-cyan-500/10 px-3 py-2 text-xs font-bold text-cyan-200 transition-all hover:bg-cyan-500/20 disabled:cursor-not-allowed disabled:opacity-40"
        >
          {createMutation.isPending ? (
            <RefreshCcw size={12} className="animate-spin" />
          ) : (
            <Plus size={12} />
          )}
          Create
        </button>
      </div>

      {/* List */}
      {isLoading ? (
        <div className="flex items-center justify-center py-12">
          <RefreshCcw size={20} className="animate-spin text-slate-600" />
        </div>
      ) : snapshots.length === 0 ? (
        <EmptyState
          icon={Camera}
          title="No snapshots"
          message="Create a snapshot to capture the current state of the universe."
        />
      ) : (
        <div className="max-h-[480px] overflow-y-auto custom-scrollbar -mr-2 pr-2">
          {/* Table Header */}
          <div className="mb-2 grid grid-cols-[60px_1fr_80px_80px_120px] gap-2 px-3 text-[10px] font-bold uppercase tracking-widest text-slate-600">
            <span>Tick</span>
            <span>Label</span>
            <span className="text-right">Entropy</span>
            <span className="text-right">Stability</span>
            <span className="text-right">Created</span>
          </div>

          {/* Rows */}
          {snapshots.map((snap, idx) => (
            <div
              key={snap.id}
              className={`grid grid-cols-[60px_1fr_80px_80px_120px] gap-2 rounded-xl px-3 py-2.5 text-sm ${
                idx % 2 === 0 ? 'bg-slate-900/30' : 'bg-transparent'
              }`}
            >
              <span className="font-mono text-xs font-bold text-cyan-400">
                {snap.tick}
              </span>
              <span className="truncate text-xs text-slate-300">
                {snap.label || snap.summary || '--'}
              </span>
              <span className="text-right font-mono text-xs text-amber-300">
                {snap.entropy != null ? snap.entropy.toFixed(2) : '--'}
              </span>
              <span className="text-right font-mono text-xs text-emerald-300">
                {snap.stability_index != null
                  ? snap.stability_index.toFixed(2)
                  : '--'}
              </span>
              <span className="flex items-center justify-end gap-1 text-right text-[11px] text-slate-500">
                <Clock size={10} />
                {formatDate(snap.created_at)}
              </span>
            </div>
          ))}
        </div>
      )}
    </SectionPanel>
  );
}
