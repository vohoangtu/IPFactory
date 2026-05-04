'use client';

import { motion } from 'framer-motion';
import { Activity } from 'lucide-react';

// ── Entropy helpers ─────────────────────────────

function formatEntropy(entropy: number | undefined): string {
  if (entropy === undefined || entropy === null) return '—';
  return entropy.toFixed(3);
}

interface EntropyLevel {
  label: string;
  color: 'emerald' | 'amber' | 'rose';
  textColor: string;
}

function getEntropyLevel(entropy: number | undefined): EntropyLevel {
  if (entropy === undefined || entropy === null) {
    return { label: 'Unknown', color: 'emerald', textColor: 'text-slate-500' };
  }
  if (entropy < 0.25) {
    return { label: 'Low', color: 'emerald', textColor: 'text-emerald-400' };
  }
  if (entropy < 0.5) {
    return { label: 'Moderate', color: 'emerald', textColor: 'text-emerald-300' };
  }
  if (entropy < 0.75) {
    return { label: 'High', color: 'amber', textColor: 'text-amber-400' };
  }
  return { label: 'Critical', color: 'rose', textColor: 'text-rose-400' };
}

// ── Gradient bar (custom visual, not the shared ProgressBar) ──

function EntropyGradientBar({ entropy }: { entropy: number | undefined }) {
  const value = entropy ?? 0;
  const pct = Math.min(100, Math.max(0, value * 100));

  return (
    <div className="relative h-3 w-full overflow-hidden rounded-full bg-slate-800/60">
      {/* Gradient background track */}
      <div
        className="absolute inset-0 rounded-full opacity-30"
        style={{
          background: 'linear-gradient(to right, #10b981, #f59e0b, #f43f5e)',
        }}
      />
      {/* Filled portion */}
      <div
        className="relative h-full rounded-full transition-all duration-700 ease-out"
        style={{
          width: `${pct}%`,
          background: 'linear-gradient(to right, #10b981, #f59e0b, #f43f5e)',
        }}
      />
      {/* Indicator needle */}
      <div
        className="absolute top-0 h-full w-0.5 bg-white shadow-[0_0_4px_rgba(255,255,255,0.6)] transition-all duration-700"
        style={{ left: `${pct}%` }}
      />
    </div>
  );
}

// ── Main Component ──────────────────────────────

interface NarrativeEntropyGaugeProps {
  entropy: number | undefined;
}

export default function NarrativeEntropyGauge({ entropy }: NarrativeEntropyGaugeProps) {
  const level = getEntropyLevel(entropy);

  return (
    <motion.div
      initial={{ opacity: 0, y: -10 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.4 }}
      className="rounded-3xl border border-slate-800 bg-slate-950/40 px-6 py-5"
    >
      <div className="flex items-center gap-6">
        {/* Icon + label */}
        <div className="flex items-center gap-2.5 shrink-0">
          <Activity size={18} className="text-slate-400" />
          <span className="text-[10px] font-black uppercase tracking-[0.25em] text-slate-400">
            Global Narrative Entropy
          </span>
        </div>

        {/* Large number */}
        <div className="shrink-0">
          <span className="font-mono text-3xl font-black tracking-tight text-white">
            {formatEntropy(entropy)}
          </span>
        </div>

        {/* Gradient bar — fills remaining space */}
        <div className="min-w-0 flex-1">
          <EntropyGradientBar entropy={entropy} />
          {/* Scale labels */}
          <div className="mt-1 flex justify-between">
            <span className="text-[9px] text-emerald-500/60">0</span>
            <span className="text-[9px] text-amber-500/60">0.5</span>
            <span className="text-[9px] text-rose-500/60">1.0</span>
          </div>
        </div>

        {/* Level indicator */}
        <div className="shrink-0 text-right">
          <span
            className={`text-xs font-bold uppercase tracking-wider ${level.textColor}`}
          >
            {level.label}
          </span>
        </div>
      </div>
    </motion.div>
  );
}
