'use client';

import { useState } from 'react';
import { Play, RefreshCcw, AlertTriangle, CheckCircle2 } from 'lucide-react';

import SectionPanel from '@/components/ui/shared/SectionPanel';
import { useUniverse } from '@/contexts/UniverseContext';
import { useAdvanceSimulation } from '@/features/simulation/hooks';

export default function TickAdvancePanel() {
  const { activeUniverseId, universes } = useUniverse();
  const advanceMutation = useAdvanceSimulation();

  const [ticks, setTicks] = useState(1);
  const [showConfirm, setShowConfirm] = useState(false);
  const [showSuccess, setShowSuccess] = useState(false);

  const activeUniverse = universes.find((u) => u.id === activeUniverseId);

  const handleAdvance = () => {
    if (!activeUniverseId) return;

    if (ticks > 100 && !showConfirm) {
      setShowConfirm(true);
      return;
    }
    setShowConfirm(false);
    setShowSuccess(false);

    advanceMutation.mutate(
      {
        universeId: activeUniverseId,
        ticks,
      },
      {
      onSuccess: () => {
        setShowSuccess(true);
        setTimeout(() => setShowSuccess(false), 3000);
      },
      },
    );
  };

  const handleCancel = () => {
    setShowConfirm(false);
  };

  return (
    <SectionPanel>
      {/* Header */}
      <div className="mb-4 flex items-center justify-between">
        <div>
          <h3 className="text-sm font-semibold text-white">Advance Simulation</h3>
          {activeUniverse && (
            <p className="mt-0.5 text-xs text-slate-500">
              Current tick:{' '}
              <span className="font-mono text-blue-400">{activeUniverse.current_tick ?? 0}</span>
            </p>
          )}
        </div>
      </div>

      {/* Tick Input */}
      <div className="mb-4">
        <label className="mb-1.5 block text-xs font-medium text-slate-400">
          Ticks to advance
        </label>
        <input
          type="number"
          min={1}
          max={1000}
          value={ticks}
          onChange={(e) => setTicks(Math.max(1, Math.min(1000, Number(e.target.value) || 1)))}
          className="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 font-mono text-sm text-white placeholder-slate-600 outline-none focus:border-blue-500"
        />
      </div>

      {/* Confirm Warning */}
      {showConfirm && (
        <div className="mb-4 flex items-start gap-3 rounded border border-amber-500/30 bg-amber-500/5 p-3">
          <AlertTriangle size={14} className="mt-0.5 shrink-0 text-amber-400" />
          <div className="flex-1">
            <p className="text-xs font-semibold text-amber-300">Advancing {ticks} ticks may take a while.</p>
            <div className="mt-2 flex gap-2">
              <button
                onClick={handleAdvance}
                className="rounded bg-amber-600 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-500"
              >
                Confirm
              </button>
              <button
                onClick={handleCancel}
                className="rounded border border-slate-700 bg-slate-800 px-3 py-1 text-xs text-slate-300 hover:bg-slate-700"
              >
                Cancel
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Success Message */}
      {showSuccess && (
        <div className="mb-4 flex items-center gap-2 rounded border border-green-500/20 bg-green-500/5 p-2.5">
          <CheckCircle2 size={13} className="text-green-400" />
          <p className="text-xs font-medium text-green-300">Advanced successfully.</p>
        </div>
      )}

      {/* Advance Button */}
      <button
        onClick={handleAdvance}
        disabled={advanceMutation.isPending || !activeUniverseId}
        className="flex w-full items-center justify-center gap-2 rounded bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-40"
      >
        {advanceMutation.isPending ? (
          <><RefreshCcw size={14} className="animate-spin" />Advancing…</>
        ) : (
          <><Play size={14} />Advance Simulation</>
        )}
      </button>
    </SectionPanel>
  );
}
