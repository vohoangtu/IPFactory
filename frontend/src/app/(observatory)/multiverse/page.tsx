'use client';
import Link from 'next/link';
import { useUniverses, WorkspaceLayout } from '@/features/universe-workspace';
import { useMultiverseBloom, useMultiverseResonance, ConstellationView } from '@/features/multiverse';
import { Pill } from '@/shared/ui/Pill';
import { routes } from '@/shared/config/routes';

export default function MultiversePage() {
  const { data: universes, isLoading, isError } = useUniverses();
  const { bloom } = useMultiverseBloom();
  const { resonance } = useMultiverseResonance();
  const isEmpty = !isLoading && !isError && (universes ?? []).length === 0;

  return (
    <WorkspaceLayout>
      <div className="mx-auto max-w-6xl">
        <h1 className="mb-4 font-mono text-xs uppercase tracking-[0.3em] text-[var(--color-text-muted)]">
          Đa vũ trụ — chọn một vũ trụ để quan sát
        </h1>
        <p className="mb-6 max-w-xl text-sm text-[var(--color-text-secondary)]">
          Mỗi thẻ là một dòng thời gian đang sống. Chọn một vũ trụ để bước vào Living Chronicle của nó.
        </p>

        <ConstellationView bloom={bloom ?? null} resonance={resonance ?? null} />

        {isLoading && (
          <div
            className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3"
            aria-label="Đang tải danh sách vũ trụ"
          >
            <p className="skeleton h-24 rounded-lg" aria-hidden="true" />
            <p className="skeleton h-24 rounded-lg" aria-hidden="true" />
            <p className="skeleton h-24 rounded-lg" aria-hidden="true" />
          </div>
        )}

        {isError && <p className="text-[var(--color-danger)]">Không tải được danh sách vũ trụ.</p>}

        {isEmpty && (
          <p className="rounded-xl border border-dashed border-[var(--border-subtle)] p-6 text-sm text-[var(--color-text-muted)]">
            Chưa có vũ trụ nào được khởi tạo.
          </p>
        )}

        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {(universes ?? []).map((u) => (
            <Link
              key={u.id}
              href={routes.universe(u.id)}
              className="glass group rounded-xl border border-[var(--border-subtle)] p-4 transition duration-200 ease-out hover:-translate-y-0.5 hover:border-[var(--color-primary)] hover:shadow-[var(--shadow-glow-cyan)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)]/50"
            >
              <div className="flex items-center justify-between gap-2">
                <span className="truncate font-medium group-hover:text-glow-cyan">{u.name}</span>
                <Pill tone={u.status}>{u.status}</Pill>
              </div>
              <p className="mt-2 font-mono text-xs text-[var(--color-text-muted)]">T{u.current_tick}</p>
            </Link>
          ))}
        </div>
      </div>
    </WorkspaceLayout>
  );
}
