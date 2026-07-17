'use client';

import type { LucideIcon } from 'lucide-react';
import { PackageOpen } from 'lucide-react';
import { cn } from '@/shared/lib/utils';
import PanelButton from './PanelButton';

interface EmptyStateProps {
  icon?: LucideIcon;
  title?: string;
  message?: string;
  action?: {
    label: string;
    onClick: () => void;
  };
  className?: string;
}

export default function EmptyState({
  icon: Icon = PackageOpen,
  title = 'No data yet',
  message = 'There is nothing to display at this time.',
  action,
  className,
}: EmptyStateProps) {
  return (
    <div
      className={cn(
        'flex flex-col items-center justify-center rounded-2xl border border-dashed',
        'border-border-subtle bg-bg-surface/40 px-8 py-20 text-center',
        className,
      )}
    >
      <div className="mb-5 flex h-16 w-16 items-center justify-center rounded-2xl border border-border-subtle bg-bg-surface/60">
        <Icon size={28} className="text-text-disabled" />
      </div>
      <h3 className="text-lg font-bold tracking-tight text-text-muted">{title}</h3>
      <p className="mt-2 max-w-xs text-sm leading-relaxed text-text-disabled">{message}</p>
      {action && (
        <PanelButton variant="primary" size="md" onClick={action.onClick} className="mt-6">
          {action.label}
        </PanelButton>
      )}
    </div>
  );
}
