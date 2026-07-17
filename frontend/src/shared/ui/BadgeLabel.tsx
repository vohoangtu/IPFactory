'use client';

type BadgeVariant = 'cyan' | 'emerald' | 'rose' | 'amber' | 'violet' | 'indigo' | 'slate' | 'red' | 'danger' | 'success' | 'default';

interface BadgeLabelProps {
  children: React.ReactNode;
  variant?: BadgeVariant;
}

const variantMap: Record<BadgeVariant, string> = {
  cyan:    'bg-cyan-500/10 text-cyan-300 ring-cyan-500/20',
  emerald: 'bg-emerald-500/10 text-emerald-300 ring-emerald-500/20',
  rose:    'bg-rose-500/10 text-rose-300 ring-rose-500/20',
  amber:   'bg-amber-500/10 text-amber-300 ring-amber-500/20',
  violet:  'bg-violet-500/10 text-violet-300 ring-violet-500/20',
  indigo:  'bg-indigo-500/10 text-indigo-300 ring-indigo-500/20',
  slate:   'bg-slate-500/10 text-slate-300 ring-slate-500/20',
  red:     'bg-red-500/10 text-red-300 ring-red-500/20',
  // Semantic aliases
  danger:  'bg-rose-500/10 text-rose-300 ring-rose-500/20',
  success: 'bg-emerald-500/10 text-emerald-300 ring-emerald-500/20',
  default: 'bg-slate-500/10 text-slate-300 ring-slate-500/20',
};

export default function BadgeLabel({ children, variant = 'cyan' }: BadgeLabelProps) {
  return (
    <span className={`inline-flex items-center rounded-lg px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.12em] ring-1 ring-inset ${variantMap[variant]}`}>
      {children}
    </span>
  );
}
