'use client';

interface StatCardProps {
  label: string;
  value: string;
  status?: 'ok' | 'warn' | 'error' | 'neutral';
}

export default function StatCard({ label, value, status }: StatCardProps) {
  const dot =
    status === 'ok'
      ? 'bg-brand-emerald'
      : status === 'warn'
        ? 'bg-brand-amber'
        : status === 'error'
          ? 'bg-brand-danger'
          : 'bg-slate-600';

  return (
    <div className="rounded-lg border border-border-subtle bg-bg-surface px-4 py-3">
      <p className="text-xs text-text-disabled">{label}</p>
      <div className="mt-1 flex items-center gap-2">
        {status && <span className={`h-2 w-2 flex-shrink-0 rounded-full ${dot}`} />}
        <p className="truncate text-sm font-semibold text-text-primary">{value}</p>
      </div>
    </div>
  );
}
