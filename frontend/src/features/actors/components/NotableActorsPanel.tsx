'use client';
import Link from 'next/link';
import { routes } from '@/shared/config/routes';
import { useSupremeEntities } from '../hooks';

const TOP_N = 5;

export function NotableActorsPanel({ universeId }: { universeId: number }) {
  const { entities, isLoading } = useSupremeEntities(universeId);
  const top = [...entities].sort((a, b) => b.power_level - a.power_level).slice(0, TOP_N);

  return (
    <div className="flex flex-col gap-2">
      {isLoading && <p className="skeleton h-16 rounded-lg" aria-hidden="true" />}
      {!isLoading && top.length === 0 && (
        <p className="text-xs text-[var(--color-text-disabled)]">Chưa có thực thể nổi bật — lịch sử còn đang chờ vĩ nhân.</p>
      )}
      {top.length > 0 && (
        <ul className="flex flex-col gap-1.5" role="list">
          {top.map((e) => (
            <li key={e.id} className="flex items-center justify-between gap-2 rounded border border-[var(--border-subtle)] px-2 py-1.5">
              <div className="min-w-0">
                <p className="truncate text-sm font-medium">{e.name}</p>
                <p className="font-mono text-[10px] uppercase tracking-wider text-[var(--color-text-muted)]">
                  {e.entity_type} · {e.domain}
                </p>
              </div>
              <span className="shrink-0 font-mono text-[11px] tabular-nums text-[var(--color-amber)]">
                ⚡{e.power_level.toFixed(2)}
              </span>
            </li>
          ))}
        </ul>
      )}
      <Link
        href={routes.universeActors(universeId)}
        className="mt-1 text-xs text-[var(--color-text-secondary)] underline-offset-2 hover:text-[var(--color-primary)] hover:underline"
      >
        Xem lens Actors →
      </Link>
    </div>
  );
}
