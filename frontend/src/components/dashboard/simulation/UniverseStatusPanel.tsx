'use client';

import { useState } from 'react';
import { Power, Trash2, RefreshCcw } from 'lucide-react';

import SectionPanel from '@/components/ui/shared/SectionPanel';
import ModalShell from '@/components/ui/shared/ModalShell';
import { useUniverse } from '@/contexts/UniverseContext';
import { useUniverseMetrics } from '@/features/universe/hooks';
import { useToggleUniverse, useDeleteUniverse } from '@/features/simulation/hooks';

export default function UniverseStatusPanel() {
  const { activeUniverseId, universes } = useUniverse();
  const { metrics } = useUniverseMetrics(activeUniverseId);
  const toggleMutation = useToggleUniverse();
  const deleteMutation = useDeleteUniverse();

  const [showDeleteModal, setShowDeleteModal] = useState(false);

  const activeUniverse = universes.find((u) => u.id === activeUniverseId);
  const status = metrics?.status ?? activeUniverse?.status ?? 'unknown';
  const isActive = status === 'active';

  const handleToggle = () => {
    if (!activeUniverseId) return;
    toggleMutation.mutate(activeUniverseId);
  };

  const handleDelete = () => {
    if (!activeUniverseId) return;
    deleteMutation.mutate(activeUniverseId, {
      onSuccess: () => {
        setShowDeleteModal(false);
      },
    });
  };

  return (
    <>
      <SectionPanel>
        {/* Header */}
        <div className="mb-4 flex items-center justify-between">
          <div>
            <h3 className="text-sm font-semibold text-white">
              {activeUniverse?.name ?? 'Universe'}
            </h3>
            <div className="mt-1">
              <span className={`inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-semibold ${
                isActive ? 'bg-green-500/10 text-green-400' : 'bg-slate-800 text-slate-500'
              }`}>
                <span className={`h-1.5 w-1.5 rounded-full ${isActive ? 'bg-green-400' : 'bg-slate-600'}`} />
                {status}
              </span>
            </div>
          </div>
          <div className="flex gap-2">
            <button
              onClick={handleToggle}
              disabled={toggleMutation.isPending || !activeUniverseId}
              className="flex items-center gap-1.5 rounded border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-medium text-slate-300 transition hover:bg-slate-700 disabled:opacity-40"
            >
              {toggleMutation.isPending ? <RefreshCcw size={11} className="animate-spin" /> : <Power size={11} />}
              Toggle
            </button>
            <button
              onClick={() => setShowDeleteModal(true)}
              disabled={!activeUniverseId}
              className="flex items-center gap-1.5 rounded border border-rose-500/20 bg-rose-500/10 px-3 py-1.5 text-xs font-medium text-rose-400 transition hover:bg-rose-500/20 disabled:opacity-40"
            >
              <Trash2 size={11} />
              Delete
            </button>
          </div>
        </div>

        {/* Metrics */}
        <div className="space-y-0 divide-y divide-slate-800/60">
          {[
            { label: 'Tick', value: String(metrics?.current_tick ?? activeUniverse?.current_tick ?? 0), mono: true },
            { label: 'Entropy', value: (metrics?.entropy != null ? metrics.entropy.toFixed(3) : '--') },
            { label: 'Stability', value: (metrics?.stability != null ? metrics.stability.toFixed(3) : '--') },
            { label: 'Actors', value: String(metrics?.actor_count ?? 0) },
            { label: 'Anomalies', value: String(metrics?.anomaly_count ?? 0) },
          ].map((row) => (
            <div key={row.label} className="flex items-center justify-between py-2.5">
              <span className="text-xs text-slate-500">{row.label}</span>
              <span className={`text-sm font-medium text-white ${row.mono ? 'font-mono text-blue-400' : ''}`}>
                {row.value}
              </span>
            </div>
          ))}
        </div>
      </SectionPanel>

      {/* Delete Confirmation Modal */}
      <ModalShell
        open={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        title="Delete Universe"
        maxWidth="max-w-md"
      >
        <div className="text-center">
          <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-500/10">
            <Trash2 size={24} className="text-rose-400" />
          </div>
          <p className="text-sm text-slate-300">
            Are you sure you want to delete{' '}
            <span className="font-bold text-white">
              {activeUniverse?.name ?? `Universe #${activeUniverseId}`}
            </span>
            ? This action cannot be undone.
          </p>
          <div className="mt-4 flex gap-2">
            <button
              onClick={() => setShowDeleteModal(false)}
              className="flex-1 rounded border border-slate-700 bg-slate-800 px-4 py-2 text-sm text-slate-300 transition hover:bg-slate-700"
            >
              Cancel
            </button>
            <button
              onClick={handleDelete}
              disabled={deleteMutation.isPending}
              className="flex flex-1 items-center justify-center gap-2 rounded bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-500 disabled:opacity-40"
            >
              {deleteMutation.isPending ? <RefreshCcw size={13} className="animate-spin" /> : <Trash2 size={13} />}
              Delete
            </button>
          </div>
        </div>
      </ModalShell>
    </>
  );
}
