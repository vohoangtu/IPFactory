'use client';

import React from 'react';
import { Activity } from 'lucide-react';
import { useMultiverseResonance } from '@/features/multiverse/hooks';
import type { ResonancePollen } from '@/shared/types/api';

function formatTickerItem(event: ResonancePollen): string {
  const parts: string[] = [];
  if (event.headline) {
    parts.push(event.headline);
  }
  if (event.slogan && event.slogan !== event.headline) {
    parts.push(event.slogan);
  }
  if (parts.length === 0) {
    parts.push(`Narrative #${event.id}`);
  }
  return parts.join(' — ');
}

export default function SynthesisTicker() {
  const { resonance, isLoading } = useMultiverseResonance();
  const events = resonance?.resonance_pollen ?? [];

  if (!isLoading && events.length === 0) {
    return null;
  }

  const displayEvents = events
    .slice(0, 12)
    .map((event) => ({
      ...event,
      key: `ticker-${event.id}`,
      label: formatTickerItem(event),
      universeLabel: `U#${event.universe_id}`,
    }));

  // Duplicate for seamless marquee
  const marqueeItems = [...displayEvents, ...displayEvents];

  return (
    <div className="mb-8 flex items-center gap-6 overflow-hidden whitespace-nowrap rounded-2xl border border-violet-500/10 bg-violet-500/5 p-4">
      <div className="flex flex-shrink-0 items-center gap-2 text-[10px] font-black uppercase tracking-widest text-violet-400">
        <Activity size={14} />
        <span>Synthesis</span>
      </div>
      <div className="flex gap-12 animate-marquee">
        {marqueeItems.map((event, i) => (
          <span key={`${event.key}-${i}`} className="text-[11px] font-medium text-slate-400">
            <span className="mr-1.5 rounded bg-violet-500/10 px-1.5 py-0.5 text-[9px] font-bold text-violet-300">
              {event.universeLabel}
            </span>
            <b className="uppercase tracking-tighter text-violet-200">{event.label}</b>
          </span>
        ))}
      </div>
    </div>
  );
}
