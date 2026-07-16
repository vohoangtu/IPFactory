'use client';
import type { UniverseCivilization, UniverseWorldState } from '../types';

function StatTile({ label, value, tone = 'primary' }: { label: string; value: number | null; tone?: 'primary' | 'danger' | 'emerald' | 'accent' }) {
  const color = { primary: 'var(--color-primary)', danger: 'var(--color-danger)', emerald: 'var(--color-emerald)', accent: 'var(--color-accent)' }[tone];
  return (
    <div className="glass rounded-xl border border-[var(--border-subtle)] p-4">
      <p className="font-mono text-[11px] uppercase tracking-wider text-[var(--color-text-muted)]">{label}</p>
      <p className="mt-1 font-mono text-2xl tabular-nums" style={{ color }}>{value == null ? '—' : value.toFixed(2)}</p>
    </div>
  );
}

function EmptyNote({ children }: { children: string }) {
  return <p className="text-xs text-[var(--color-text-disabled)]">{children}</p>;
}

interface Props { civilization: UniverseCivilization | null; world: UniverseWorldState | null }

export function CivilizationLens({ civilization, world }: Props) {
  return (
    <div className="flex flex-col gap-6">
      <section aria-label="Chỉ số văn minh" className="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <StatTile label="Entropy" value={civilization?.metrics.entropy ?? null} tone="danger" />
        <StatTile label="Stability" value={civilization?.metrics.stability_index ?? null} tone="primary" />
        <StatTile label="Coherence" value={civilization?.metrics.structural_coherence ?? null} tone="emerald" />
        <StatTile label="Fitness" value={civilization?.metrics.fitness_score ?? null} tone="accent" />
      </section>

      <section aria-label="Kỷ nguyên" className="glass rounded-xl border border-[var(--border-subtle)] p-4">
        <h3 className="mb-1 font-mono text-[11px] uppercase tracking-wider text-[var(--color-text-muted)]">Kỷ nguyên hiện tại</h3>
        {world?.epoch ? (
          <div>
            <p className="text-glow-cyan text-lg font-medium">{world.epoch.name}</p>
            {world.epoch.description && <p className="mt-1 text-sm text-[var(--color-text-muted)]">{world.epoch.description}</p>}
            <p className="mt-1 font-mono text-[11px] text-[var(--color-text-disabled)]">
              T{world.epoch.start_tick ?? '?'} → {world.epoch.end_tick != null ? `T${world.epoch.end_tick}` : 'nay'}
            </p>
          </div>
        ) : <EmptyNote>Chưa ghi nhận kỷ nguyên.</EmptyNote>}
        {civilization && (
          <p className="mt-3 border-t border-[var(--border-subtle)] pt-2 font-mono text-[11px] text-[var(--color-text-muted)]">
            {civilization.complexity.living_actor_count}/{civilization.complexity.actor_count} actor còn sống · {civilization.complexity.supreme_entity_count} thực thể tối cao
          </p>
        )}
      </section>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <section aria-label="Tôn giáo" className="glass rounded-xl border border-[var(--border-subtle)] p-4">
          <h3 className="mb-2 font-mono text-[11px] uppercase tracking-wider text-[var(--color-text-muted)]">Tôn giáo</h3>
          {(world?.religions ?? []).length === 0 ? <EmptyNote>Chưa ghi nhận tôn giáo.</EmptyNote> : (
            <ul className="flex flex-col gap-1.5" role="list">
              {world!.religions.map((r) => (
                <li key={r.id} className="flex items-center justify-between text-sm">
                  <span className="truncate">{r.name}</span>
                  <span className="font-mono text-[11px] tabular-nums text-[var(--color-text-muted)]">{r.followers} tín đồ</span>
                </li>
              ))}
            </ul>
          )}
        </section>
        <section aria-label="Hiệp ước" className="glass rounded-xl border border-[var(--border-subtle)] p-4">
          <h3 className="mb-2 font-mono text-[11px] uppercase tracking-wider text-[var(--color-text-muted)]">Hiệp ước hiệu lực</h3>
          {(world?.treaties ?? []).length === 0 ? <EmptyNote>Chưa ghi nhận hiệp ước.</EmptyNote> : (
            <ul className="flex flex-col gap-1.5" role="list">
              {world!.treaties.map((t) => (
                <li key={t.id} className="text-sm">
                  <span className="font-medium">{t.treaty_type}</span>{' '}
                  <span className="font-mono text-[11px] text-[var(--color-text-muted)]">civ {t.source_civ_id} ↔ civ {t.target_civ_id} · từ T{t.started_at_tick}</span>
                </li>
              ))}
            </ul>
          )}
        </section>
        <section aria-label="Công nghệ" className="glass rounded-xl border border-[var(--border-subtle)] p-4">
          <h3 className="mb-2 font-mono text-[11px] uppercase tracking-wider text-[var(--color-text-muted)]">Công nghệ</h3>
          {(world?.technologies ?? []).length === 0 ? <EmptyNote>Chưa ghi nhận công nghệ.</EmptyNote> : (
            <ul className="flex flex-col gap-1.5" role="list">
              {world!.technologies.map((t) => (
                <li key={t.id} className="flex items-center justify-between text-sm">
                  <span className="truncate">{t.name}</span>
                  <span className="font-mono text-[11px] tabular-nums text-[var(--color-text-muted)]">{t.adopters} actor · lv {t.avg_level.toFixed(2)}</span>
                </li>
              ))}
            </ul>
          )}
        </section>
      </div>
    </div>
  );
}
