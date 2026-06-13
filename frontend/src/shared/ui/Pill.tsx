import type { ReactNode } from 'react';

const TONES: Record<string, string> = {
  active: 'bg-teal-500/20 text-teal-300 border-teal-500/40',
  paused: 'bg-amber-500/20 text-amber-300 border-amber-500/40',
  halted: 'bg-rose-500/20 text-rose-300 border-rose-500/40',
  neutral: 'bg-white/10 text-gray-300 border-white/20',
};

export function Pill({ tone = 'neutral', children }: { tone?: keyof typeof TONES | string; children: ReactNode }) {
  return (
    <span className={`inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-medium ${TONES[tone] ?? TONES.neutral}`}>
      {children}
    </span>
  );
}
