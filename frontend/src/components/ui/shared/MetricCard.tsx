'use client';

import { cn } from '@/lib/utils';

interface MetricCardProps {
  label: string;
  value: string | number;
  subLabel?: string;
  subValue?: string;
  hint?: string;
  color?: 'red' | 'amber' | 'violet' | 'green' | 'cyan' | 'emerald' | 'info' | 'default';
  className?: string;
}

const borderMap: Record<string, string> = {
  red: 'border-red-500/20',
  amber: 'border-amber-500/20',
  violet: 'border-violet-500/20',
  green: 'border-green-500/20',
  cyan: 'border-cyan-500/20',
  emerald: 'border-emerald-500/20',
  info: 'border-brand-info/20',
  default: 'border-border-subtle',
};

const valueMap: Record<string, string> = {
  red: 'text-red-300',
  amber: 'text-amber-300',
  violet: 'text-violet-300',
  green: 'text-green-300',
  cyan: 'text-cyan-300',
  emerald: 'text-emerald-300',
  info: 'text-brand-info',
  default: 'text-text-primary',
};

export default function MetricCard({ label, value, subLabel, subValue, hint, color = 'default', className }: MetricCardProps) {
  return (
    <div className={cn('rounded-lg border bg-bg-surface px-4 py-4', borderMap[color], className)} title={hint}>
      <p className="text-xs text-text-disabled">{label}</p>
      <p className={cn('mt-1 text-2xl font-bold tabular-nums', valueMap[color])}>{value}</p>
      {subLabel && (
        <p className="mt-2 text-[11px] text-text-disabled">
          <span className="text-text-muted">{subLabel}:</span> {subValue}
        </p>
      )}
    </div>
  );
}
