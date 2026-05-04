import { cn } from '@/lib/utils';
import { TrendingUp, TrendingDown, Minus } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

interface StatCardProps {
  label: string;
  value: string | number;
  subValue?: string;
  icon?: LucideIcon;
  trend?: number;          // positive = up, negative = down, 0 = flat
  variant?: 'default' | 'cyan' | 'violet' | 'amber' | 'emerald' | 'danger';
  className?: string;
  loading?: boolean;
}

const variantStyles: Record<
  NonNullable<StatCardProps['variant']>,
  { border: string; icon: string; glow: string }
> = {
  default:  { border: 'border-border-muted',      icon: 'text-text-muted',  glow: '' },
  cyan:     { border: 'border-cyan-500/20',       icon: 'text-cyan-400',    glow: 'shadow-glow-cyan' },
  violet:   { border: 'border-violet-500/20',     icon: 'text-violet-400',  glow: 'shadow-glow-violet' },
  amber:    { border: 'border-amber-500/20',      icon: 'text-amber-400',   glow: 'shadow-glow-amber' },
  emerald:  { border: 'border-emerald-500/20',    icon: 'text-emerald-400', glow: 'shadow-glow-emerald' },
  danger:   { border: 'border-red-500/20',        icon: 'text-red-400',     glow: 'shadow-glow-danger' },
};

export default function StatCard({
  label,
  value,
  subValue,
  icon: Icon,
  trend,
  variant = 'default',
  className,
  loading = false,
}: StatCardProps) {
  const styles = variantStyles[variant];

  const TrendIcon =
    trend === undefined
      ? null
      : trend > 0
        ? TrendingUp
        : trend < 0
          ? TrendingDown
          : Minus;

  const trendColor =
    trend === undefined
      ? ''
      : trend > 0
        ? 'text-brand-emerald'
        : trend < 0
          ? 'text-brand-danger'
          : 'text-text-disabled';

  if (loading) {
    return (
      <div
        className={cn(
          'rounded-2xl border p-5',
          styles.border,
          'bg-bg-surface',
          className,
        )}
      >
        <div className="skeleton mb-3 h-3 w-20 rounded" />
        <div className="skeleton h-6 w-28 rounded" />
      </div>
    );
  }

  return (
    <div
      className={cn(
        'group relative overflow-hidden rounded-2xl border p-5 transition-all duration-300',
        'bg-bg-surface hover:bg-bg-elevated',
        styles.border,
        styles.glow,
        className,
      )}
    >
      {/* background glow orb */}
      <div className="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-current opacity-[0.03] blur-2xl" />

      {/* Header */}
      <div className="mb-3 flex items-center justify-between">
        <span className="text-xs font-semibold uppercase tracking-widest text-text-disabled">
          {label}
        </span>
        {Icon && (
          <Icon
            size={16}
            className={cn('transition-transform duration-300 group-hover:scale-110', styles.icon)}
          />
        )}
      </div>

      {/* Value */}
      <div className="flex items-end gap-2">
        <span className="font-mono text-2xl font-bold text-text-primary">{value}</span>
        {TrendIcon && (
          <span className={cn('mb-0.5 flex items-center gap-0.5 text-xs font-semibold', trendColor)}>
            <TrendIcon size={12} />
          </span>
        )}
      </div>

      {/* Sub-value */}
      {subValue && (
        <p className="mt-1 truncate text-xs text-text-disabled">{subValue}</p>
      )}
    </div>
  );
}
