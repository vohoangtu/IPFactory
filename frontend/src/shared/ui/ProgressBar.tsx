'use client';

import { motion } from 'framer-motion';
import { cn } from '@/shared/lib/utils';

interface ProgressBarProps {
  value: number;
  max?: number;
  label?: string;
  showPercent?: boolean;
  /** Auto-select color based on value ratio when set to 'auto' */
  color?: 'auto' | 'cyan' | 'emerald' | 'rose' | 'amber' | 'violet' | 'indigo' | 'slate' | 'danger';
  size?: 'xs' | 'sm' | 'md';
  animated?: boolean;
  className?: string;
}

const explicitColorMap: Record<string, { bar: string; bg: string }> = {
  cyan:    { bar: 'bg-cyan-400',    bg: 'bg-cyan-500/10'    },
  emerald: { bar: 'bg-emerald-400', bg: 'bg-emerald-500/10' },
  rose:    { bar: 'bg-rose-400',    bg: 'bg-rose-500/10'    },
  amber:   { bar: 'bg-amber-400',   bg: 'bg-amber-500/10'   },
  violet:  { bar: 'bg-violet-400',  bg: 'bg-violet-500/10'  },
  indigo:  { bar: 'bg-indigo-400',  bg: 'bg-indigo-500/10'  },
  slate:   { bar: 'bg-slate-500',   bg: 'bg-slate-500/10'   },
  danger:  { bar: 'bg-red-400',     bg: 'bg-red-500/10'     },
};

function autoColor(pct: number): { bar: string; bg: string } {
  if (pct >= 80) return explicitColorMap.danger;
  if (pct >= 60) return explicitColorMap.amber;
  if (pct >= 30) return explicitColorMap.cyan;
  return explicitColorMap.emerald;
}

const sizeMap = { xs: 'h-1', sm: 'h-1.5', md: 'h-2.5' };

export default function ProgressBar({
  value,
  max = 1,
  label,
  showPercent = true,
  color = 'cyan',
  size = 'md',
  animated = true,
  className,
}: ProgressBarProps) {
  const pct = Math.min(100, Math.max(0, (value / max) * 100));
  const colors = color === 'auto' ? autoColor(pct) : (explicitColorMap[color] ?? explicitColorMap.cyan);
  const h = sizeMap[size];

  const barStyle = { width: `${pct}%` };

  return (
    <div className={cn('w-full', className)}>
      {(label || showPercent) && (
        <div className="mb-1.5 flex items-center justify-between">
          {label && <span className="text-xs text-text-muted">{label}</span>}
          {showPercent && (
            <span className="font-mono text-xs text-text-disabled">{pct.toFixed(0)}%</span>
          )}
        </div>
      )}
      <div className={cn('w-full rounded-full', h, colors.bg)}>
        {animated ? (
          <motion.div
            initial={{ width: 0 }}
            animate={barStyle}
            transition={{ duration: 0.7, ease: 'easeOut' }}
            className={cn(h, 'rounded-full', colors.bar)}
          />
        ) : (
          <div className={cn(h, 'rounded-full transition-all duration-500', colors.bar)} style={barStyle} />
        )}
      </div>
    </div>
  );
}
