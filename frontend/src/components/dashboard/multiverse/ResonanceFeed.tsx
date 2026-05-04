'use client';

import { motion } from 'framer-motion';
import { Radio } from 'lucide-react';
import type { MultiverseResonance, ResonancePollen } from '@/types/api';
import BadgeLabel from '@/components/ui/shared/BadgeLabel';
import ProgressBar from '@/components/ui/shared/ProgressBar';
import EmptyState from '@/components/ui/shared/EmptyState';

// ── Intensity → ProgressBar color ───────────────

function intensityColor(intensity: number): 'amber' | 'cyan' | 'violet' {
  if (intensity > 0.7) return 'amber';
  if (intensity < 0.3) return 'cyan';
  return 'violet';
}

// ── Distortion badge variant ────────────────────

type BadgeVariant = 'cyan' | 'emerald' | 'rose' | 'amber' | 'violet' | 'indigo' | 'slate';

function distortionVariant(distortion: number): BadgeVariant {
  if (distortion > 0.7) return 'rose';
  if (distortion > 0.4) return 'amber';
  return 'emerald';
}

// ── Tag badge variant cycle ─────────────────────

const tagVariants: BadgeVariant[] = ['cyan', 'violet', 'indigo', 'amber', 'emerald', 'rose'];

function tagVariant(index: number): BadgeVariant {
  return tagVariants[index % tagVariants.length];
}

// ── Animations ──────────────────────────────────

const feedContainerVariants = {
  hidden: { opacity: 0 },
  visible: {
    opacity: 1,
    transition: { staggerChildren: 0.06 },
  },
};

const cardVariants = {
  hidden: { opacity: 0, x: 12 },
  visible: { opacity: 1, x: 0, transition: { duration: 0.3, ease: 'easeOut' as const } },
};

// ── Pollen Card ─────────────────────────────────

interface PollenCardProps {
  pollen: ResonancePollen;
}

function PollenCard({ pollen }: PollenCardProps) {
  return (
    <motion.div
      variants={cardVariants}
      className="relative rounded-3xl border border-slate-800 bg-slate-950/40 p-5"
    >
      {/* Origin tick badge — top right */}
      <span className="absolute right-4 top-4 font-mono text-[10px] text-slate-600">
        tick {pollen.origin_tick}
      </span>

      {/* Headline */}
      <h4 className="pr-16 text-sm font-bold text-white leading-snug">
        {pollen.headline}
      </h4>

      {/* Slogan */}
      {pollen.slogan && (
        <p className="mt-1 text-xs font-medium italic text-cyan-400/80">
          {pollen.slogan}
        </p>
      )}

      {/* Story snippet */}
      {pollen.story_snippet && (
        <p className="mt-2.5 border-l-2 border-slate-700 pl-3 text-xs italic leading-relaxed text-slate-400">
          &ldquo;{pollen.story_snippet}&rdquo;
        </p>
      )}

      {/* Intensity bar */}
      <div className="mt-4">
        <ProgressBar
          value={pollen.intensity}
          max={1}
          label="Intensity"
          color={intensityColor(pollen.intensity)}
          size="sm"
        />
      </div>

      {/* Distortion + Tags row */}
      <div className="mt-3 flex flex-wrap items-center gap-1.5">
        {/* Distortion indicator */}
        <BadgeLabel variant={distortionVariant(pollen.distortion)}>
          ⌁ {pollen.distortion?.toFixed(2) ?? '—'}
        </BadgeLabel>

        {/* Tags */}
        {pollen.tags?.map((tag, i) => (
          <BadgeLabel key={`${tag}-${i}`} variant={tagVariant(i)}>
            {tag}
          </BadgeLabel>
        ))}
      </div>
    </motion.div>
  );
}

// ── Main Component ──────────────────────────────

interface ResonanceFeedProps {
  resonance: MultiverseResonance | undefined;
}

export default function ResonanceFeed({ resonance }: ResonanceFeedProps) {
  const pollen = resonance?.resonance_pollen;

  if (!pollen || pollen.length === 0) {
    return (
      <EmptyState
        icon={Radio}
        title="No Resonance Signals"
        message="The narrative resonance feed is empty."
      />
    );
  }

  return (
    <div className="flex flex-col gap-3">
      {/* Header */}
      <div className="flex items-center gap-2 px-1">
        <Radio size={16} className="text-violet-400" />
        <h3 className="text-xs font-black uppercase tracking-[0.2em] text-slate-400">
          Resonance Feed
        </h3>
        <BadgeLabel variant="violet">{pollen.length}</BadgeLabel>
      </div>

      {/* Scrollable feed */}
      <motion.div
        variants={feedContainerVariants}
        initial="hidden"
        animate="visible"
        className="flex max-h-[calc(100vh-20rem)] flex-col gap-3 overflow-y-auto pr-1 scrollbar-thin scrollbar-track-transparent scrollbar-thumb-slate-700/50"
      >
        {pollen.map((item, i) => (
          <PollenCard key={item.id ?? i} pollen={item} />
        ))}
      </motion.div>
    </div>
  );
}
