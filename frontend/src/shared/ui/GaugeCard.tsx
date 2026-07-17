'use client';

import { motion } from 'framer-motion';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/shared/lib/utils';

interface GaugeCardProps {
  label: string;
  value: string;
  meta?: string;
  icon: LucideIcon;
  /** Progress fill 0‒1 (optional). Renders a bottom bar when supplied. */
  progress?: number;
  tone?: 'cyan' | 'violet' | 'amber' | 'emerald' | 'danger' | 'custom';
  /** Override classes when tone="custom" */
  customTone?: string;
  index?: number;
  className?: string;
}

const toneMap: Record<NonNullable<GaugeCardProps['tone']>, { gradient: string; bar: string; border: string; icon: string }> = {
  cyan:    { gradient: 'from-cyan-500/10 to-blue-500/5',    bar: 'bg-cyan-400',    border: 'border-cyan-500/20',    icon: 'text-cyan-400'    },
  violet:  { gradient: 'from-violet-500/10 to-purple-500/5',bar: 'bg-violet-400',  border: 'border-violet-500/20',  icon: 'text-violet-400'  },
  amber:   { gradient: 'from-amber-500/10 to-orange-500/5', bar: 'bg-amber-400',   border: 'border-amber-500/20',   icon: 'text-amber-400'   },
  emerald: { gradient: 'from-emerald-500/10 to-teal-500/5', bar: 'bg-emerald-400', border: 'border-emerald-500/20', icon: 'text-emerald-400' },
  danger:  { gradient: 'from-red-500/10 to-rose-500/5',     bar: 'bg-red-400',     border: 'border-red-500/20',     icon: 'text-red-400'     },
  custom:  { gradient: '', bar: '', border: '', icon: '' },
};

export default function GaugeCard({
  label,
  value,
  meta,
  icon: Icon,
  progress,
  tone = 'cyan',
  customTone,
  index = 0,
  className,
}: GaugeCardProps) {
  const t = toneMap[tone];
  const pct = progress !== undefined ? Math.min(100, Math.max(0, progress * 100)) : null;

  return (
    <motion.div
      initial={{ opacity: 0, y: 18 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay: index * 0.06, duration: 0.4 }}
      className={cn(
        'group relative overflow-hidden rounded-2xl border bg-gradient-to-br p-5 transition-all duration-300',
        tone === 'custom' ? customTone : `${t.gradient} ${t.border}`,
        className,
      )}
    >
      {/* subtle glow orb */}
      <div className="pointer-events-none absolute -right-6 -top-6 h-20 w-20 rounded-full bg-current opacity-[0.04] blur-2xl" />

      {/* Header */}
      <div className="mb-4 flex items-center justify-between">
        <span className="text-[10px] font-black uppercase tracking-[0.25em] text-text-muted">
          {label}
        </span>
        <Icon
          size={16}
          className={cn(
            'transition-transform duration-300 group-hover:scale-110',
            tone === 'custom' ? 'text-white/70' : t.icon,
          )}
        />
      </div>

      {/* Value */}
      <div className="font-mono text-3xl font-black tracking-[-0.04em] text-text-primary">
        {value}
      </div>

      {/* Meta */}
      {meta && (
        <p className="mt-2 text-[11px] font-semibold uppercase tracking-[0.15em] text-text-muted/80">
          {meta}
        </p>
      )}

      {/* Progress bar */}
      {pct !== null && (
        <div className="mt-4">
          <div className="h-1 w-full rounded-full bg-white/5">
            <motion.div
              initial={{ width: 0 }}
              animate={{ width: `${pct}%` }}
              transition={{ delay: index * 0.06 + 0.2, duration: 0.8, ease: 'easeOut' }}
              className={cn('h-1 rounded-full', tone === 'custom' ? 'bg-white' : t.bar)}
            />
          </div>
        </div>
      )}
    </motion.div>
  );
}
