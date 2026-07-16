'use client';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { routes } from '@/shared/config/routes';

const LENSES = [
  { label: 'Biên niên sử', href: (id: number) => routes.universe(id) },
  { label: 'Actors', href: (id: number) => routes.universeActors(id) },
  { label: 'Văn minh', href: (id: number) => routes.universeCivilization(id) },
  { label: 'Nhân quả', href: (id: number) => routes.universeCausality(id) },
  { label: 'Wavefunction', href: (id: number) => routes.universeWavefunction(id) },
] as const;

export function LensNav({ universeId }: { universeId: number }) {
  const pathname = usePathname();
  return (
    <nav aria-label="Lens quan sát" className="flex items-center gap-1 overflow-x-auto px-4">
      {LENSES.map((lens) => {
        const href = lens.href(universeId);
        const active = pathname === href;
        return (
          <Link
            key={lens.label}
            href={href}
            aria-current={active ? 'page' : undefined}
            className={`shrink-0 border-b-2 px-3 py-2 font-mono text-xs uppercase tracking-wider transition-colors duration-200 ${
              active
                ? 'border-[var(--color-primary)] text-[var(--color-primary)]'
                : 'border-transparent text-[var(--color-text-muted)] hover:text-[var(--color-text-secondary)]'
            }`}
          >
            {lens.label}
          </Link>
        );
      })}
    </nav>
  );
}
