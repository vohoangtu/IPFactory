'use client';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { routes } from '@/shared/config/routes';

const TABS = [
  { label: 'Simulation', href: routes.opsSimulation() },
  { label: 'Loom', href: routes.opsLoom() },
  { label: 'AI Runtime', href: routes.opsAiRuntime() },
  { label: 'Settings', href: routes.opsSettings() },
  { label: 'System', href: routes.opsSystem() },
  { label: 'Intelligence', href: routes.opsIntelligence() },
] as const;

export function OpsNav() {
  const pathname = usePathname();
  return (
    <nav aria-label="Vận hành" className="flex items-center gap-1 overflow-x-auto px-4">
      {TABS.map((tab) => {
        const active = pathname === tab.href;
        return (
          <Link
            key={tab.label}
            href={tab.href}
            aria-current={active ? 'page' : undefined}
            className={`shrink-0 border-b-2 px-3 py-2 font-mono text-xs uppercase tracking-wider transition-colors duration-200 ${
              active
                ? 'border-[var(--color-primary)] text-[var(--color-primary)]'
                : 'border-transparent text-[var(--color-text-muted)] hover:text-[var(--color-text-secondary)]'
            }`}
          >
            {tab.label}
          </Link>
        );
      })}
    </nav>
  );
}
