import type { ButtonHTMLAttributes, ReactNode } from 'react';

export function Button({ children, className = '', ...rest }: ButtonHTMLAttributes<HTMLButtonElement> & { children: ReactNode }) {
  return (
    <button
      className={`rounded-lg border border-white/15 px-3 py-1.5 text-sm text-gray-200 transition-colors hover:bg-white/10 disabled:opacity-40 ${className}`}
      {...rest}
    >
      {children}
    </button>
  );
}
