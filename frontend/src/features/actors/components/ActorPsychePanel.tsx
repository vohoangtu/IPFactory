'use client';
import type { ActorPsyche } from '../types';

const EMOTION_LABELS: Record<string, string> = {
  fear: 'Sợ hãi', anger: 'Giận dữ', sadness: 'Buồn bã', joy: 'Hân hoan', stress: 'Căng thẳng', trust: 'Tin tưởng',
};

function EmotionMeter({ name, value }: { name: string; value: number }) {
  const pct = Math.round(Math.max(0, Math.min(1, value)) * 100);
  return (
    <div className="flex items-center gap-2">
      <span className="w-20 shrink-0 text-xs text-[var(--color-text-muted)]">{EMOTION_LABELS[name] ?? name}</span>
      <div
        role="meter" aria-label={name} aria-valuenow={pct} aria-valuemin={0} aria-valuemax={100}
        className="h-1.5 flex-1 overflow-hidden rounded-full bg-white/[0.06]"
      >
        <div className="h-full rounded-full bg-[var(--color-primary)]" style={{ width: `${pct}%` }} />
      </div>
      <span className="w-9 shrink-0 text-right font-mono text-[11px] tabular-nums text-[var(--color-text-disabled)]">
        {value.toFixed(2)}
      </span>
    </div>
  );
}

interface Props { psyche: ActorPsyche | null; isLoading: boolean }

export function ActorPsychePanel({ psyche, isLoading }: Props) {
  if (isLoading) return <p className="p-4 text-sm text-[var(--color-text-muted)]">Đang đọc tâm trí…</p>;
  if (!psyche) {
    return <p className="p-4 text-sm text-[var(--color-text-muted)]">Chọn một actor để soi chiếu tâm lý.</p>;
  }
  return (
    <div className="flex flex-col gap-4 p-1">
      <div>
        <h3 className="font-medium">{psyche.actor.name}</h3>
        <p className="font-mono text-[11px] uppercase tracking-wider text-[var(--color-text-muted)]">
          {psyche.actor.archetype ?? '—'} · {psyche.actor.life_stage ?? '—'}
        </p>
      </div>
      <section aria-label="Cảm xúc" className="flex flex-col gap-1.5">
        {Object.entries(psyche.emotions).map(([k, v]) => <EmotionMeter key={k} name={k} value={v} />)}
      </section>
      <section aria-label="Mục tiêu">
        <h4 className="mb-1 font-mono text-[11px] uppercase tracking-wider text-[var(--color-text-muted)]">Mục tiêu (Maslow)</h4>
        {psyche.goals.length === 0
          ? <p className="text-xs text-[var(--color-text-disabled)]">Tâm trí tĩnh lặng — chưa có nhu cầu vượt ngưỡng.</p>
          : (
            <ol className="flex flex-col gap-1">
              {psyche.goals.map((g) => (
                <li key={g.type} className="flex items-center justify-between rounded border border-[var(--border-subtle)] px-2 py-1 text-xs">
                  <span>{g.type}</span>
                  <span className="font-mono tabular-nums text-[var(--color-accent)]">{g.priority.toFixed(2)}</span>
                </li>
              ))}
            </ol>
          )}
      </section>
      <section aria-label="Quyết định gần nhất">
        <h4 className="mb-1 font-mono text-[11px] uppercase tracking-wider text-[var(--color-text-muted)]">Quyết định gần nhất</h4>
        {psyche.recent_decisions.length === 0
          ? <p className="text-xs text-[var(--color-text-disabled)]">Chưa có quyết định nào được ghi.</p>
          : (
            <ul className="flex flex-col gap-1">
              {psyche.recent_decisions.map((d) => (
                <li key={d.id} className="rounded border-l-2 border-[var(--color-info)] bg-white/[0.02] px-2 py-1 text-xs">
                  <span className="font-mono text-[var(--color-text-disabled)]">T{d.tick}</span>{' '}
                  <span className="font-medium">{d.action_type ?? '?'}</span>
                  {d.reasoning && <span className="text-[var(--color-text-muted)]"> — {d.reasoning}</span>}
                </li>
              ))}
            </ul>
          )}
      </section>
    </div>
  );
}
