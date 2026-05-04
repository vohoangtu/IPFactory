'use client';

import { motion } from 'framer-motion';

import type { ActorSummary } from '@/types/api';
import BadgeLabel from '@/components/ui/shared/BadgeLabel';
import ProgressBar from '@/components/ui/shared/ProgressBar';

interface ActorCardProps {
  actor: ActorSummary;
  onClick: () => void;
}

const alignmentColor: Record<string, string> = {
  good: 'text-emerald-400',
  neutral: 'text-slate-400',
  evil: 'text-rose-400',
  chaotic: 'text-amber-400',
  lawful: 'text-cyan-400',
};

export default function ActorCard({ actor, onClick }: ActorCardProps) {
  const alignClass =
    alignmentColor[actor.alignment?.toLowerCase()] ?? 'text-slate-400';

  return (
    <motion.div
      whileHover={{ y: -4 }}
      onClick={onClick}
      className="group relative cursor-pointer rounded-3xl border border-slate-800 bg-slate-950/40 p-6 transition-all duration-300 hover:border-cyan-500/30 hover:shadow-[0_0_40px_-12px_rgba(6,182,212,0.15)]"
    >
      {/* Header */}
      <div className="mb-4 flex items-start justify-between gap-3">
        <div className="min-w-0 flex-1">
          <div className="flex items-center gap-2">
            <h3 className="truncate text-base font-black tracking-tight text-white">
              {actor.name}
            </h3>
            {/* Alive indicator */}
            <span
              className={`inline-block h-2 w-2 flex-shrink-0 rounded-full ${
                actor.is_alive ? 'bg-emerald-400 shadow-[0_0_6px_rgba(52,211,153,0.5)]' : 'bg-red-400 shadow-[0_0_6px_rgba(248,113,113,0.5)]'
              }`}
              title={actor.is_alive ? 'Alive' : `Dead at tick ${actor.death_tick}`}
            />
          </div>
          {!actor.is_alive && actor.death_tick !== null && (
            <span className="mt-0.5 block text-[10px] font-mono text-red-400/70">
              Died tick #{actor.death_tick}
            </span>
          )}
        </div>
      </div>

      {/* Badges */}
      <div className="mb-4 flex flex-wrap gap-1.5">
        <BadgeLabel variant="cyan">{actor.role}</BadgeLabel>
        <BadgeLabel variant="violet">{actor.archetype}</BadgeLabel>
      </div>

      {/* Influence */}
      <div className="mb-4">
        <ProgressBar
          value={actor.influence}
          max={100}
          label="Influence"
          color="cyan"
          size="sm"
        />
      </div>

      {/* Footer: alignment + life_stage */}
      <div className="flex items-center justify-between">
        <span className={`text-xs font-bold uppercase tracking-wider ${alignClass}`}>
          {actor.alignment}
        </span>
        <span className="text-xs text-slate-500">{actor.life_stage}</span>
      </div>
    </motion.div>
  );
}
