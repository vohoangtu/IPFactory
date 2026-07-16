'use client';

import { motion } from 'framer-motion';
import type { VAFBackground } from '../lib/vaf/types';

// ──────────────────────────────────────────────
// BackgroundRenderer
// Renders gradient / solid / pattern backgrounds
// with smooth Framer Motion transitions.
// ──────────────────────────────────────────────

interface Props {
  background: VAFBackground;
}

function getBackgroundStyle(bg: VAFBackground): string {
  switch (bg.type) {
    case 'gradient':
      return `linear-gradient(to bottom, ${bg.colors.join(', ')})`;
    case 'solid':
      return bg.colors[0] ?? '#0d0d0d';
    case 'pattern':
      return `repeating-linear-gradient(45deg, ${bg.colors[0] ?? '#1a1a1a'}, ${bg.colors[0] ?? '#1a1a1a'} 10px, ${bg.colors[1] ?? '#2a2a2a'} 10px, ${bg.colors[1] ?? '#2a2a2a'} 20px)`;
    default:
      return '#0d0d0d';
  }
}

export default function BackgroundRenderer({ background }: Props) {
  const bgValue = getBackgroundStyle(background);
  const isColor = background.type === 'solid';

  return (
    <motion.div
      className="absolute inset-0"
      animate={isColor ? { backgroundColor: bgValue } : { background: bgValue }}
      transition={{ duration: 0.8, ease: 'easeInOut' }}
      style={isColor ? { backgroundColor: bgValue } : { background: bgValue }}
    />
  );
}
