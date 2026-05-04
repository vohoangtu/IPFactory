import { cn } from '@/lib/utils';

interface LoadingSpinnerProps {
  size?: 'xs' | 'sm' | 'md' | 'lg';
  className?: string;
  label?: string;
}

const sizeMap = {
  xs: 'h-3 w-3 border',
  sm: 'h-5 w-5 border-2',
  md: 'h-8 w-8 border-2',
  lg: 'h-12 w-12 border-2',
};

export default function LoadingSpinner({
  size = 'md',
  className,
  label,
}: LoadingSpinnerProps) {
  return (
    <div className={cn('flex flex-col items-center justify-center gap-3', className)}>
      <div
        className={cn(
          'animate-spin rounded-full border-brand-primary/20 border-t-brand-primary',
          sizeMap[size],
        )}
        role="status"
        aria-label={label ?? 'Loading…'}
      />
      {label && (
        <span className="font-mono text-[10px] uppercase tracking-[0.25em] text-text-disabled">
          {label}
        </span>
      )}
    </div>
  );
}
