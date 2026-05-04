'use client';

import { useMemo } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import type { VAFEffect } from '@/lib/vaf/types';

// ──────────────────────────────────────────────
// EffectOverlay
// Renders non-particle visual effects (flash,
// ripple, energy burst, glow, screen shake)
// using Framer Motion AnimatePresence.
// ──────────────────────────────────────────────

interface Props {
  effects: VAFEffect[];
  primaryColor?: string;
}

function EffectLayer({ effect }: { effect: VAFEffect }) {
  const color = effect.color ?? '#ffffff';
  const intensity = effect.intensity;

  switch (effect.type) {
    case 'screen_shake':
      return (
        <motion.div
          className="absolute inset-0 pointer-events-none"
          animate={{
            x: [0, -3, 3, -2, 2, 0],
            y: [0, 2, -2, 3, -1, 0],
          }}
          transition={{ duration: 0.3, ease: 'linear' }}
        />
      );

    case 'flash':
      return (
        <motion.div
          className="absolute inset-0 pointer-events-none"
          style={{ backgroundColor: color }}
          initial={{ opacity: 0.8 * intensity }}
          animate={{ opacity: 0 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.4, ease: 'easeOut' }}
        />
      );

    case 'ripple':
      return (
        <motion.div
          className="absolute inset-0 pointer-events-none flex items-center justify-center"
        >
          <motion.div
            className="rounded-full"
            style={{
              width: 120,
              height: 120,
              background: `radial-gradient(circle, ${color}88 0%, transparent 70%)`,
            }}
            initial={{ scale: 0, opacity: 1 }}
            animate={{ scale: 2, opacity: 0 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.6, ease: 'easeOut' }}
          />
        </motion.div>
      );

    case 'energy_burst':
      return (
        <motion.div
          className="absolute inset-0 pointer-events-none flex items-center justify-center"
        >
          <motion.div
            className="rounded-full"
            style={{
              width: 160,
              height: 160,
              background: `radial-gradient(circle, ${color}aa 0%, ${color}44 40%, transparent 70%)`,
            }}
            initial={{ scale: 0.5, opacity: 1 }}
            animate={{ scale: 1.5, opacity: 0 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.5, ease: 'easeOut' }}
          />
        </motion.div>
      );

    case 'glow': {
      const spread = 20 + intensity * 40;
      return (
        <motion.div
          className="absolute inset-0 pointer-events-none"
          initial={{ opacity: 0 }}
          animate={{ opacity: [0.4, 0.7, 0.4] }}
          exit={{ opacity: 0 }}
          transition={{ duration: 1.5, repeat: Infinity, ease: 'easeInOut' }}
          style={{
            boxShadow: `inset 0 0 ${spread}px ${spread / 2}px ${color}66`,
          }}
        />
      );
    }

    default:
      return null;
  }
}

export default function EffectOverlay({ effects, primaryColor }: Props) {
  const nonParticleEffects = useMemo(
    () => effects.filter((e) => e.type !== 'particles'),
    [effects],
  );

  return (
    <div className="absolute inset-0 pointer-events-none">
      <AnimatePresence>
        {nonParticleEffects.map((effect) => (
          <EffectLayer
            key={`${effect.type}-${effect.trigger_at_ms}`}
            effect={{
              ...effect,
              color: effect.color ?? primaryColor ?? '#ffffff',
            }}
          />
        ))}
      </AnimatePresence>
    </div>
  );
}
