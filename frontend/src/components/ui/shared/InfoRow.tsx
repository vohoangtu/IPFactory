'use client';

import { cn } from '@/lib/utils';

interface InfoRowProps {
  label: string;
  value: React.ReactNode;
  valueClass?: string;
  className?: string;
}

export default function InfoRow({
  label,
  value,
  valueClass = 'text-text-primary font-medium',
  className,
}: InfoRowProps) {
  return (
    <div className={cn('flex items-center justify-between border-b border-border-subtle py-2.5 last:border-0', className)}>
      <span className="text-sm text-text-disabled">{label}</span>
      <span className={cn('text-sm', valueClass)}>{value}</span>
    </div>
  );
}
