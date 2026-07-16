'use client';

import { motion } from 'framer-motion';
import { Globe2, Sparkles } from 'lucide-react';
import type { MultiverseBloom, MultiverseWorld, MultiverseUniverse } from '@/shared/types/api';
import BadgeLabel from '@/components/ui/shared/BadgeLabel';
import EmptyState from '@/components/ui/shared/EmptyState';

// ── Genre → Badge variant mapping ───────────────

type BadgeVariant = 'cyan' | 'emerald' | 'rose' | 'amber' | 'violet' | 'indigo' | 'slate';

const genreVariant: Record<string, BadgeVariant> = {
  fantasy: 'violet',
  'sci-fi': 'cyan',
  historical: 'amber',
  cyberpunk: 'rose',
  mythology: 'indigo',
};

function getGenreVariant(genre: string): BadgeVariant {
  return genreVariant[genre.toLowerCase()] ?? 'slate';
}

// ── Status dot color ────────────────────────────

function statusDotColor(status: string): string {
  switch (status?.toLowerCase()) {
    case 'active':
    case 'running':
      return 'bg-emerald-400 shadow-emerald-400/50';
    case 'inactive':
    case 'paused':
    case 'stopped':
      return 'bg-rose-400 shadow-rose-400/50';
    default:
      return 'bg-slate-400 shadow-slate-400/50';
  }
}

// ── Animations ──────────────────────────────────

const containerVariants = {
  hidden: { opacity: 0 },
  visible: {
    opacity: 1,
    transition: { staggerChildren: 0.08 },
  },
};

const itemVariants = {
  hidden: { opacity: 0, y: 16 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.35, ease: 'easeOut' as const } },
};

const universeHover = {
  scale: 1.02,
  transition: { duration: 0.2 },
};

// ── Universe Card ───────────────────────────────

interface UniverseCardProps {
  universe: MultiverseUniverse;
  onSelect?: (id: string) => void;
}

function UniverseCard({ universe, onSelect }: UniverseCardProps) {
  return (
    <motion.button
      variants={itemVariants}
      whileHover={universeHover}
      onClick={() => onSelect?.(universe.id)}
      className="group relative flex w-full items-center gap-3 rounded-xl border border-slate-700/60 bg-slate-900/60 px-4 py-3 text-left transition-colors hover:border-cyan-500/40 hover:bg-slate-800/60"
    >
      {/* Status dot */}
      <span
        className={`h-2.5 w-2.5 shrink-0 rounded-full shadow-[0_0_6px] ${statusDotColor(universe.status)}`}
      />

      {/* Info */}
      <div className="min-w-0 flex-1">
        <p className="truncate text-sm font-semibold text-white group-hover:text-cyan-200">
          {universe.label || `Universe ${universe.id}`}
        </p>
        {universe.sub && (
          <p className="truncate text-[11px] text-slate-500">{universe.sub}</p>
        )}
      </div>

      {/* SCI score badge */}
      <BadgeLabel variant="cyan">{universe.sci?.toFixed(2) ?? '—'}</BadgeLabel>

      {/* Hover arrow indicator */}
      <span className="text-slate-600 transition-transform group-hover:translate-x-0.5 group-hover:text-cyan-400">
        →
      </span>
    </motion.button>
  );
}

// ── World Card ──────────────────────────────────

interface WorldCardProps {
  world: MultiverseWorld;
  onSelectUniverse?: (id: string) => void;
}

function WorldCard({ world, onSelectUniverse }: WorldCardProps) {
  return (
    <motion.div variants={itemVariants} className="relative">
      {/* World container */}
      <div className="rounded-3xl border border-slate-800 bg-slate-950/40 p-6">
        {/* World header */}
        <div className="mb-4 flex items-center gap-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-800/60">
            <Globe2 size={20} className="text-cyan-400" />
          </div>
          <div className="min-w-0 flex-1">
            <h3 className="truncate text-base font-bold text-white">
              {world.label || `World ${world.id}`}
            </h3>
            <div className="mt-1 flex items-center gap-2">
              <BadgeLabel variant={getGenreVariant(world.genre)}>
                {world.genre}
              </BadgeLabel>
              <span className="text-[11px] font-mono text-slate-500">
                SCI {world.sci?.toFixed(2) ?? '—'}
              </span>
            </div>
          </div>
          {/* Status */}
          <span
            className={`h-2.5 w-2.5 rounded-full shadow-[0_0_6px] ${statusDotColor(world.status)}`}
          />
        </div>

        {/* Connection line from world to universes */}
        {world.universes?.length > 0 && (
          <div className="relative ml-5 border-l-2 border-dashed border-slate-700/50 pl-6">
            {/* Vertical connector dot at top */}
            <div className="absolute -left-[5px] top-0 h-2 w-2 rounded-full bg-slate-600" />

            <motion.div
              variants={containerVariants}
              initial="hidden"
              animate="visible"
              className="flex flex-col gap-2"
            >
              {world.universes.map((universe) => (
                <div key={universe.id} className="relative">
                  {/* Horizontal connector line */}
                  <div className="absolute -left-6 top-1/2 h-px w-5 bg-slate-700/50" />
                  <UniverseCard universe={universe} onSelect={onSelectUniverse} />
                </div>
              ))}
            </motion.div>

            {/* Vertical connector dot at bottom */}
            <div className="absolute -left-[5px] bottom-0 h-2 w-2 rounded-full bg-slate-600" />
          </div>
        )}

        {/* No universes */}
        {(!world.universes || world.universes.length === 0) && (
          <p className="ml-5 text-xs italic text-slate-600">No universes in this world</p>
        )}
      </div>
    </motion.div>
  );
}

// ── Main Component ──────────────────────────────

interface MultiverseTreeProps {
  bloom: MultiverseBloom | undefined;
  onSelectUniverse?: (universeId: string) => void;
}

export default function MultiverseTree({ bloom, onSelectUniverse }: MultiverseTreeProps) {
  if (!bloom || !bloom.worlds || bloom.worlds.length === 0) {
    return (
      <EmptyState
        icon={Globe2}
        title="No Multiverse Data"
        message="The multiverse bloom has not been initialized yet."
      />
    );
  }

  return (
    <div className="space-y-4">
      {/* Root label */}
      <motion.div
        initial={{ opacity: 0, scale: 0.95 }}
        animate={{ opacity: 1, scale: 1 }}
        transition={{ duration: 0.4 }}
        className="flex items-center gap-3"
      >
        <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500/20 to-cyan-500/20 ring-1 ring-violet-500/30">
          <Sparkles size={22} className="text-violet-300" />
        </div>
        <div>
          <h2 className="text-lg font-bold text-white">
            {bloom.label || 'Multiverse'}
          </h2>
          {bloom.sub && (
            <p className="text-xs text-slate-500">{bloom.sub}</p>
          )}
        </div>
        <BadgeLabel variant="violet">
          {bloom.worlds.length} world{bloom.worlds.length !== 1 ? 's' : ''}
        </BadgeLabel>
      </motion.div>

      {/* Connection from root to worlds */}
      <div className="relative ml-6 border-l-2 border-dashed border-violet-500/25 pl-8">
        <div className="absolute -left-[5px] top-0 h-2 w-2 rounded-full bg-violet-500/50" />

        <motion.div
          variants={containerVariants}
          initial="hidden"
          animate="visible"
          className="flex flex-col gap-6"
        >
          {bloom.worlds.map((world) => (
            <div key={world.id} className="relative">
              {/* Horizontal connector */}
              <div className="absolute -left-8 top-8 h-px w-7 bg-violet-500/25" />
              <WorldCard
                world={world}
                onSelectUniverse={onSelectUniverse}
              />
            </div>
          ))}
        </motion.div>

        <div className="absolute -left-[5px] bottom-0 h-2 w-2 rounded-full bg-violet-500/50" />
      </div>
    </div>
  );
}
