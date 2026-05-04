'use client';

import { cn } from '@/lib/utils';
import { Loader2 } from 'lucide-react';
import type { ButtonHTMLAttributes, ReactNode } from 'react';

type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger' | 'outline';
type ButtonSize = 'sm' | 'md' | 'lg';

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant;
  size?: ButtonSize;
  loading?: boolean;
  children: ReactNode;
}

const variantStyles: Record<ButtonVariant, string> = {
  primary:
    'border-brand-primary/20 bg-brand-primary/10 text-brand-primary hover:bg-brand-primary/20',
  secondary:
    'border-border-muted bg-bg-surface text-text-secondary hover:bg-bg-elevated hover:text-text-primary',
  ghost:
    'border-transparent bg-transparent text-text-secondary hover:bg-bg-surface hover:text-text-primary',
  danger:
    'border-brand-danger/20 bg-brand-danger/10 text-brand-danger hover:bg-brand-danger/20',
  outline:
    'border-border-muted bg-transparent text-text-secondary hover:border-text-muted hover:text-text-primary',
};

const sizeStyles: Record<ButtonSize, string> = {
  sm: 'h-8 px-3 text-xs gap-1.5 rounded-lg',
  md: 'h-10 px-4 text-sm gap-2 rounded-xl',
  lg: 'h-12 px-6 text-base gap-2 rounded-xl',
};

export default function Button({
  variant = 'primary',
  size = 'md',
  loading = false,
  disabled = false,
  children,
  className,
  type = 'button',
  ...props
}: ButtonProps) {
  return (
    <button
      type={type}
      disabled={disabled || loading}
      className={cn(
        'inline-flex items-center justify-center border font-semibold transition-colors duration-200',
        'disabled:opacity-50 disabled:cursor-not-allowed',
        variantStyles[variant],
        sizeStyles[size],
        className,
      )}
      {...props}
    >
      {loading && (
        <Loader2
          size={size === 'sm' ? 14 : size === 'lg' ? 18 : 16}
          className="animate-spin shrink-0"
        />
      )}
      {children}
    </button>
  );
}
