'use client';
import { FEED_FILTERS } from '../lib/feedFilters';

interface Props { active: string[]; onToggle: (key: string) => void }

export function FeedFilterChips({ active, onToggle }: Props) {
  return (
    <div className="flex flex-wrap items-center gap-1.5" role="group" aria-label="Lọc sự kiện theo loại">
      {FEED_FILTERS.map((f) => {
        const on = active.includes(f.key);
        return (
          <button
            key={f.key}
            type="button"
            aria-pressed={on}
            onClick={() => onToggle(f.key)}
            className={`rounded-full border px-3 py-1 font-mono text-[11px] uppercase tracking-wider transition-colors duration-200 ${
              on
                ? 'border-[var(--color-primary)] bg-[rgba(110,231,247,0.12)] text-[var(--color-primary)]'
                : 'border-[var(--border-subtle)] text-[var(--color-text-muted)] hover:border-[var(--border-muted)] hover:text-[var(--color-text-secondary)]'
            }`}
          >
            {f.label}
          </button>
        );
      })}
    </div>
  );
}
