import type { ReactNode } from 'react';

export function Panel({ title, children, className = '' }: { title?: string; children: ReactNode; className?: string }) {
  return (
    <section className={`rounded-xl border border-white/10 bg-black/30 p-4 ${className}`}>
      {title && <h3 className="mb-2 text-[11px] font-bold uppercase tracking-wider text-gray-500">{title}</h3>}
      {children}
    </section>
  );
}
