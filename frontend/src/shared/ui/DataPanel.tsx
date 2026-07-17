'use client';

import { cn } from '@/shared/lib/utils';

// Port của components/ui/shared/DataPanel.tsx — chỉ đổi import `cn` sang
// `@/shared/lib/utils`. Dùng bởi features/admin/components/ai-settings/*.

interface DataPanelProps {
  title?: string;
  children: React.ReactNode;
  className?: string;
  action?: React.ReactNode;
  padding?: 'none' | 'sm' | 'md';
}

const paddingMap = {
  none: '',
  sm: 'px-4 py-2',
  md: 'px-4 py-3',
};

export default function DataPanel({ title, children, className, action, padding = 'md' }: DataPanelProps) {
  return (
    <div className={cn('rounded-lg border border-border-subtle bg-bg-surface', className)}>
      {(title || action) && (
        <div className="flex items-center justify-between border-b border-border-subtle px-4 py-3">
          {title && <h3 className="text-sm font-semibold text-text-primary">{title}</h3>}
          {action && <div className="shrink-0">{action}</div>}
        </div>
      )}
      <div className={paddingMap[padding]}>{children}</div>
    </div>
  );
}
