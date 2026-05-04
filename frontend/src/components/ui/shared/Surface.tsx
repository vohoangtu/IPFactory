'use client';

import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';

type SurfaceVariant = 'default' | 'elevated' | 'glass';
type SurfaceRadius = 'none' | 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl';
type SurfacePadding = 'none' | 'sm' | 'md' | 'lg';

interface SurfaceProps {
  children: ReactNode;
  variant?: SurfaceVariant;
  radius?: SurfaceRadius;
  padding?: SurfacePadding;
  border?: boolean;
  className?: string;
}

const variantStyles: Record<SurfaceVariant, string> = {
  default:  'bg-bg-surface',
  elevated: 'bg-bg-elevated',
  glass:    'glass',
};

const radiusStyles: Record<SurfaceRadius, string> = {
  none:  '',
  sm:    'rounded-sm',
  md:    'rounded-md',
  lg:    'rounded-lg',
  xl:    'rounded-xl',
  '2xl': 'rounded-2xl',
  '3xl': 'rounded-3xl',
};

const paddingStyles: Record<SurfacePadding, string> = {
  none: '',
  sm:   'p-4',
  md:   'p-6',
  lg:   'p-8',
};

export default function Surface({
  children,
  variant = 'default',
  radius = 'xl',
  padding = 'md',
  border = true,
  className,
}: SurfaceProps) {
  const needsBorder = border && variant !== 'glass';

  return (
    <div
      className={cn(
        variantStyles[variant],
        radiusStyles[radius],
        paddingStyles[padding],
        needsBorder && 'border border-border-subtle',
        className,
      )}
    >
      {children}
    </div>
  );
}
