'use client';
import type { CSSProperties, ReactNode } from 'react';
import {
  Activity, AlertTriangle, BookOpen, Crown, Dna, Gem, Landmark, ScrollText,
} from 'lucide-react';
import type { FeedItem } from '@/shared/realtime/envelope';

type Tone = 'accent' | 'primary' | 'danger' | 'amber' | 'emerald' | 'info' | 'muted';

/** Sắc thái theo loại sự kiện — viền trái + chip icon đồng bộ màu, mirror rgba của --shadow-glow-* trong globals.css. */
const TONE: Record<Tone, { border: string; chipBg: string; chipBorder: string; iconColor: string }> = {
  accent: {
    border: 'var(--color-accent)', chipBg: 'rgba(167, 139, 250, 0.14)',
    chipBorder: 'rgba(167, 139, 250, 0.32)', iconColor: 'var(--color-accent)',
  },
  primary: {
    border: 'var(--color-primary)', chipBg: 'rgba(110, 231, 247, 0.14)',
    chipBorder: 'rgba(110, 231, 247, 0.32)', iconColor: 'var(--color-primary)',
  },
  danger: {
    border: 'var(--color-danger)', chipBg: 'rgba(248, 113, 113, 0.14)',
    chipBorder: 'rgba(248, 113, 113, 0.32)', iconColor: 'var(--color-danger)',
  },
  amber: {
    border: 'var(--color-amber)', chipBg: 'rgba(245, 158, 11, 0.14)',
    chipBorder: 'rgba(245, 158, 11, 0.32)', iconColor: 'var(--color-amber)',
  },
  emerald: {
    border: 'var(--color-emerald)', chipBg: 'rgba(52, 211, 153, 0.14)',
    chipBorder: 'rgba(52, 211, 153, 0.32)', iconColor: 'var(--color-emerald)',
  },
  info: {
    border: 'var(--color-info)', chipBg: 'rgba(96, 165, 250, 0.14)',
    chipBorder: 'rgba(96, 165, 250, 0.32)', iconColor: 'var(--color-info)',
  },
  muted: {
    border: 'var(--border-muted)', chipBg: 'rgba(255, 255, 255, 0.05)',
    chipBorder: 'var(--border-muted)', iconColor: 'var(--color-text-muted)',
  },
};

interface Visual { icon: ReactNode; tone: Tone; body: ReactNode }

function visualFor(item: FeedItem): Visual {
  const p = item.payload as Record<string, unknown>;
  switch (item.type) {
    case 'chronicle':
      return {
        icon: <ScrollText size={15} strokeWidth={1.75} />,
        tone: 'accent',
        body: (
          <p className="leading-relaxed text-[var(--color-text-primary)]">
            {(p.content as string) ?? '(chưa có nội dung tường thuật)'}
          </p>
        ),
      };
    case 'epoch.transitioned': {
      const oldName = (p.old_epoch as { name?: string } | undefined)?.name ?? '?';
      const newName = (p.new_epoch as { name?: string } | undefined)?.name ?? '?';
      return {
        icon: <Landmark size={15} strokeWidth={1.75} />,
        tone: 'primary',
        body: (
          <p className="text-glow-cyan font-medium tracking-wide">
            Kỷ nguyên chuyển mình: {oldName} → {newName}
          </p>
        ),
      };
    }
    case 'anomaly.detected':
      return {
        icon: <AlertTriangle size={15} strokeWidth={1.75} />,
        tone: item.severity === 'critical' ? 'danger' : 'amber',
        body: (
          <div>
            <p className="font-medium text-[var(--color-text-primary)]">{(p.title as string) ?? 'Dị thường'}</p>
            {typeof p.description === 'string' && (
              <p className="mt-0.5 text-sm text-[var(--color-text-muted)]">{p.description}</p>
            )}
          </div>
        ),
      };
    case 'celebrity.emerged':
      return {
        icon: <Crown size={15} strokeWidth={1.75} />,
        tone: 'amber',
        body: <p>Nhân vật #{String(p.agent_id ?? '?')} nổi lên ({String(p.vocation ?? '?')})</p>,
      };
    case 'artifact.discovered':
      return {
        icon: <Gem size={15} strokeWidth={1.75} />,
        tone: 'accent',
        body: <p>Cổ vật #{String(p.artifact_id ?? '?')} được phát hiện</p>,
      };
    case 'autopoiesis.mutation':
      return {
        icon: <Dna size={15} strokeWidth={1.75} />,
        tone: 'emerald',
        body: <p>Luật thế giới tự biến đổi</p>,
      };
    case 'history.shifted':
      return {
        icon: <BookOpen size={15} strokeWidth={1.75} />,
        tone: 'info',
        body: <p>{String(p.event_type ?? 'Biến cố lịch sử')}</p>,
      };
    default:
      return {
        icon: <Activity size={15} strokeWidth={1.75} />,
        tone: 'muted',
        body: <p className="text-[var(--color-text-muted)]">{item.type}</p>,
      };
  }
}

export function ChronicleEntry({ item }: { item: FeedItem }) {
  const v = visualFor(item);
  const t = TONE[v.tone];
  const cardStyle: CSSProperties = { borderLeft: `2px solid ${t.border}` };
  const chipStyle: CSSProperties = { background: t.chipBg, border: `1px solid ${t.chipBorder}`, color: t.iconColor };

  return (
    <article
      className="glass animate-fade-in-up flex items-start gap-3 rounded-lg p-3 transition-colors duration-200 hover:bg-white/[0.03]"
      style={cardStyle}
    >
      <span
        className="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full"
        style={chipStyle}
        aria-hidden="true"
      >
        {v.icon}
      </span>
      <div className="min-w-0 flex-1 pt-0.5">{v.body}</div>
      <span className="mt-1 shrink-0 rounded-full border border-[var(--border-subtle)] px-2 py-0.5 font-mono text-[11px] tabular-nums text-[var(--color-text-disabled)]">
        T{item.tick}
      </span>
    </article>
  );
}
