'use client';
import type { ActorSummary } from '@/types/api';

interface Props { actors: ActorSummary[]; selectedId: number | null; onSelect: (id: number) => void }

export function ActorGrid({ actors, selectedId, onSelect }: Props) {
  if (actors.length === 0) {
    return (
      <p className="rounded-xl border border-dashed border-[var(--border-subtle)] p-6 text-sm text-[var(--color-text-muted)]">
        Vũ trụ chưa có actor nào — hãy chạy tick để sự sống xuất hiện.
      </p>
    );
  }
  return (
    <ul className="grid grid-cols-1 gap-2 sm:grid-cols-2" role="list">
      {actors.map((a) => (
        <li key={a.id}>
          <button
            type="button"
            onClick={() => onSelect(a.id)}
            aria-pressed={selectedId === a.id}
            className={`glass w-full rounded-lg border p-3 text-left transition-colors duration-200 ${
              selectedId === a.id
                ? 'border-[var(--color-primary)]'
                : 'border-[var(--border-subtle)] hover:border-[var(--border-muted)]'
            }`}
          >
            <div className="flex items-center justify-between gap-2">
              <span className={`truncate font-medium ${a.is_alive ? '' : 'line-through opacity-60'}`}>{a.name}</span>
              <span className="shrink-0 font-mono text-[11px] text-[var(--color-text-disabled)]">#{a.id}</span>
            </div>
            <p className="mt-1 font-mono text-[11px] uppercase tracking-wider text-[var(--color-text-muted)]">
              {a.archetype} · {a.life_stage}{a.is_alive ? '' : ' · đã mất'}
            </p>
          </button>
        </li>
      ))}
    </ul>
  );
}
