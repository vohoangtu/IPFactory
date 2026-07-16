'use client';
import Link from 'next/link';
import { routes } from '@/shared/config/routes';
import type { MultiverseBloom, MultiverseResonance, MultiverseUniverse } from '@/types/api';

const W = 900;
const H = 360;
const STATUS_COLOR: Record<string, string> = {
  active: 'var(--color-primary)', paused: 'var(--color-amber)', halted: 'var(--color-amber)', archived: 'var(--color-text-disabled)',
};

/** Vị trí sao deterministic: world = cột cụm, universe rải quanh tâm cụm theo index (vòng xoắn vàng). */
function starPosition(worldIdx: number, worldCount: number, uniIdx: number, uniCount: number) {
  const cx = ((worldIdx + 0.5) / worldCount) * W;
  const cy = H / 2;
  const angle = uniIdx * 2.39996; // golden angle
  const radius = 26 + 34 * Math.sqrt(uniCount > 1 ? uniIdx / (uniCount - 1) : 0);
  return { x: cx + radius * Math.cos(angle), y: cy + radius * Math.sin(angle) * 0.75 };
}

function Star({ u, x, y }: { u: MultiverseUniverse; x: number; y: number }) {
  const r = 4 + 8 * Math.max(0, Math.min(1, u.saliency));
  const color = STATUS_COLOR[u.status] ?? 'var(--color-text-muted)';
  const id = Number(u.id);
  return (
    <Link href={routes.universe(id)} aria-label={`${u.label} — vào Living Chronicle`}>
      <g className="cursor-pointer transition-opacity hover:opacity-100" opacity={0.9}>
        <circle cx={x} cy={y} r={r * 2.2} fill={color} opacity={0.12} />
        <circle cx={x} cy={y} r={r} fill={color} />
        <text x={x} y={y + r + 12} textAnchor="middle" className="fill-[var(--color-text-muted)] font-mono text-[10px]">
          {u.label}
        </text>
      </g>
    </Link>
  );
}

interface Props { bloom: MultiverseBloom | null; resonance: MultiverseResonance | null }

export function ConstellationView({ bloom, resonance }: Props) {
  const worlds = bloom?.worlds ?? [];
  if (worlds.length === 0) return null;

  return (
    <figure className="glass mb-8 rounded-2xl border border-[var(--border-subtle)] p-4">
      <figcaption className="mb-2 flex items-center justify-between">
        <span className="font-mono text-xs uppercase tracking-[0.3em] text-[var(--color-text-muted)]">
          Chòm sao đa vũ trụ
        </span>
        {resonance && (
          <span className="font-mono text-[11px] tabular-nums text-[var(--color-accent)]">
            Narrative entropy {resonance.global_narrative_entropy.toFixed(2)}
          </span>
        )}
      </figcaption>
      <svg viewBox={`0 0 ${W} ${H}`} className="h-auto w-full">
        {worlds.map((w, wi) => (
          <g key={w.id}>
            <text
              x={((wi + 0.5) / worlds.length) * W} y={20} textAnchor="middle"
              className="fill-[var(--color-text-disabled)] font-mono text-[10px] uppercase tracking-widest"
            >
              {w.label}
            </text>
            {w.universes.filter((u) => Number.isFinite(Number(u.id))).map((u, ui) => {
              const pos = starPosition(wi, worlds.length, ui, w.universes.length);
              return <Star key={u.id} u={u} x={pos.x} y={pos.y} />;
            })}
          </g>
        ))}
      </svg>
      {resonance && resonance.resonance_pollen.length > 0 && (
        <ul className="mt-2 flex flex-wrap gap-2" role="list" aria-label="Cộng hưởng gần đây">
          {resonance.resonance_pollen.slice(0, 4).map((p) => (
            <li key={p.id} className="rounded-full border border-[var(--border-subtle)] px-3 py-1 text-[11px] text-[var(--color-text-muted)]">
              {p.headline} <span className="font-mono text-[var(--color-accent)]">×{p.intensity.toFixed(1)}</span>
            </li>
          ))}
        </ul>
      )}
    </figure>
  );
}
