'use client';

import { cn } from '@/lib/utils';

interface SectionPanelProps {
  children: React.ReactNode;
  className?: string;
}

export default function SectionPanel({ children, className = '' }: SectionPanelProps) {
  return (
    <div className={cn('rounded-3xl border border-border-subtle bg-bg-base/40 p-8', className)}>
      {children}
    </div>
  );
}
