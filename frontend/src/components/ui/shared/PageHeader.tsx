'use client';

import { cn } from '@/lib/utils';

interface PageHeaderProps {
  title: string;
  subtitle?: React.ReactNode;
  action?: React.ReactNode;
  className?: string;
}

export default function PageHeader({ title, subtitle, action, className }: PageHeaderProps) {
  return (
    <div className={cn('flex items-center justify-between', className)}>
      <div>
        <h1 className="text-xl font-semibold text-text-primary">{title}</h1>
        {subtitle && <div className="mt-1 text-sm text-text-muted">{subtitle}</div>}
      </div>
      {action && <div className="shrink-0">{action}</div>}
    </div>
  );
}
