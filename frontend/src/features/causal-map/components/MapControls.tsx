'use client';

import { Maximize2, PanelRightClose, PanelRightOpen } from 'lucide-react';

interface MapControlsProps {
  onFitView: () => void;
  onTogglePanel: () => void;
  isPanelOpen: boolean;
}

export default function MapControls({ onFitView, onTogglePanel, isPanelOpen }: MapControlsProps) {
  return (
    <div className="absolute top-4 right-4 z-10 flex items-center gap-1.5 rounded-xl border border-slate-700/50 bg-slate-900/80 backdrop-blur-sm p-1">
      <button
        onClick={onFitView}
        className="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.1em] text-slate-400 transition hover:bg-slate-800 hover:text-cyan-300"
        title="Fit view"
      >
        <Maximize2 size={14} />
        Fit
      </button>

      <div className="h-4 w-px bg-slate-700/50" />

      <button
        onClick={onTogglePanel}
        className={`flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.1em] transition ${
          isPanelOpen
            ? 'text-cyan-300 bg-cyan-500/10'
            : 'text-slate-400 hover:bg-slate-800 hover:text-slate-300'
        }`}
        title={isPanelOpen ? 'Hide panel' : 'Show panel'}
      >
        {isPanelOpen ? <PanelRightClose size={14} /> : <PanelRightOpen size={14} />}
        Panel
      </button>
    </div>
  );
}
