'use client';

import { useState } from 'react';
import { Calendar, Loader2, PlayCircle } from 'lucide-react';
import { toast } from 'sonner';

const ERAS = [
  { value: 'genesis', label: 'Genesis' },
  { value: 'ancient', label: 'Ancient' },
  { value: 'medieval', label: 'Medieval' },
  { value: 'renaissance', label: 'Renaissance' },
  { value: 'industrial', label: 'Industrial' },
  { value: 'modern', label: 'Modern' },
  { value: 'transcendent', label: 'Transcendent' },
];

interface WeaveParametersPanelProps {
  activeUniverseId: number | null;
  isSubmitting: boolean;
  isWeaving: boolean;
  lastError: string | null;
  onSubmit: (params: { era: string; tickStart: number; tickEnd: number; customContext: string }) => void;
}

export default function WeaveParametersPanel({
  activeUniverseId,
  isSubmitting,
  isWeaving,
  lastError,
  onSubmit,
}: WeaveParametersPanelProps) {
  const [selectedEra, setSelectedEra] = useState('genesis');
  const [tickStart, setTickStart] = useState(1);
  const [tickEnd, setTickEnd] = useState(100);
  const [customContext, setCustomContext] = useState('');

  const handleSubmit = () => {
    if (!activeUniverseId) {
      toast.error('No universe selected.');
      return;
    }
    if (tickEnd <= tickStart) {
      toast.error('Tick end must be greater than tick start.');
      return;
    }
    onSubmit({ era: selectedEra, tickStart, tickEnd, customContext });
  };

  return (
    <div className="rounded-lg border border-border-subtle bg-bg-surface">
      <div className="border-b border-border-subtle px-4 py-3">
        <h2 className="text-sm font-semibold text-text-primary">Weave Parameters</h2>
      </div>
      <div className="p-4 space-y-5">
        {/* Era */}
        <div>
          <label className="mb-2 block text-xs font-medium text-text-muted">World Era</label>
          <select
            value={selectedEra}
            onChange={e => setSelectedEra(e.target.value)}
            className="w-full rounded border border-border-muted bg-bg-elevated px-3 py-2 text-sm text-text-primary focus:border-brand-info focus:outline-none"
          >
            {ERAS.map(era => (
              <option key={era.value} value={era.value}>{era.label}</option>
            ))}
          </select>
        </div>

        {/* Tick range */}
        <div>
          <label className="mb-2 block text-xs font-medium text-text-muted">
            <Calendar size={11} className="mr-1 inline" />
            Tick Range
          </label>
          <div className="grid grid-cols-2 gap-2">
            <div>
              <p className="mb-1 text-[11px] text-text-disabled">Start</p>
              <input
                type="number"
                min={0}
                max={tickEnd - 1}
                value={tickStart}
                onChange={e => setTickStart(Math.max(0, parseInt(e.target.value) || 0))}
                className="w-full rounded border border-border-muted bg-bg-elevated px-3 py-2 text-sm text-text-primary focus:border-brand-info focus:outline-none"
              />
            </div>
            <div>
              <p className="mb-1 text-[11px] text-text-disabled">End</p>
              <input
                type="number"
                min={tickStart + 1}
                value={tickEnd}
                onChange={e => setTickEnd(Math.max(tickStart + 1, parseInt(e.target.value) || tickStart + 1))}
                className="w-full rounded border border-border-muted bg-bg-elevated px-3 py-2 text-sm text-text-primary focus:border-brand-info focus:outline-none"
              />
            </div>
          </div>
          <p className="mt-1.5 text-[11px] text-text-disabled">
            {tickEnd - tickStart} ticks · Era: {ERAS.find(e => e.value === selectedEra)?.label}
          </p>
        </div>

        {/* Custom context */}
        <div>
          <label className="mb-2 block text-xs font-medium text-text-muted">Custom Context <span className="text-text-disabled">(optional)</span></label>
          <textarea
            value={customContext}
            onChange={e => setCustomContext(e.target.value)}
            placeholder="Add narrative hints or special instructions…"
            rows={3}
            className="w-full resize-none rounded border border-border-muted bg-bg-elevated px-3 py-2 text-sm text-text-secondary placeholder-slate-600 focus:border-brand-info focus:outline-none"
          />
        </div>

        {/* Submit */}
        <button
          onClick={() => void handleSubmit()}
          disabled={isSubmitting || isWeaving || !activeUniverseId}
          className="flex w-full items-center justify-center gap-2 rounded bg-brand-info py-2.5 text-sm font-semibold text-text-primary transition hover:bg-brand-info/80 disabled:opacity-50"
        >
          {isSubmitting || isWeaving
            ? <><Loader2 size={14} className="animate-spin" />{isSubmitting ? 'Submitting…' : 'Running…'}</>
            : <><PlayCircle size={14} />Start Weave</>
          }
        </button>

        {lastError && (
          <p className="rounded border border-brand-danger/20 bg-brand-danger/10 px-3 py-2 text-xs text-brand-danger">
            {lastError}
          </p>
        )}
      </div>
    </div>
  );
}
