'use client';

import { motion, AnimatePresence } from 'framer-motion';
import { useMemo } from 'react';

// ──────────────────────────────────────────────
// NarrationOverlay
// Sentence-level narration text at bottom of screen.
// L1 fix: Animate per sentence (not per character) to reduce
// framer-motion node count from ~500 to ~5, improving CPU perf.
// ──────────────────────────────────────────────

interface Props {
  text: string;
  isPlaying: boolean;
}

// ── Container variants ─────────────────────────

const containerVariants = {
  hidden: { opacity: 0, y: 20 },
  visible: {
    opacity: 1,
    y: 0,
    transition: {
      duration: 0.4,
      ease: 'easeOut' as const,
      staggerChildren: 0.12,
    },
  },
  exit: {
    opacity: 0,
    y: 10,
    transition: { duration: 0.3 },
  },
};

// ── Sentence variants ───────────────────────────
// L1 fix: animate per sentence instead of per character

const sentenceVariants = {
  hidden: { opacity: 0, y: 6, filter: 'blur(2px)' },
  visible: {
    opacity: 1,
    y: 0,
    filter: 'blur(0px)',
    transition: { duration: 0.35, ease: 'easeOut' as const },
  },
};

// ── Helpers ────────────────────────────────────

/**
 * Chia text thanh cac cau theo dau cau (., !, ?).
 * Neu khong co dau cau, tra ve toan bo text nhu 1 sentence.
 */
function splitIntoSentences(str: string): string[] {
  const parts = str.match(/[^.!?]+[.!?]+/g) ?? [str];
  return parts.map((s) => s.trim()).filter(Boolean);
}

// ── Main component ─────────────────────────────

export default function NarrationOverlay({ text, isPlaying }: Props) {
  const sentences = useMemo(() => splitIntoSentences(text), [text]);

  if (!text) return null;

  return (
    <div className="absolute bottom-0 left-0 right-0 flex justify-center pointer-events-none z-20 pb-6 px-4">
      <AnimatePresence mode="wait">
        <motion.div
          key={text}
          className="max-w-3xl w-full bg-black/60 backdrop-blur-md rounded-t-2xl px-8 py-6 pointer-events-auto"
          variants={containerVariants}
          initial="hidden"
          animate="visible"
          exit="exit"
        >
          <p className="text-white/90 text-lg leading-relaxed font-serif">
            {isPlaying ? (
              sentences.map((sentence, i) => (
                <motion.span
                  key={`${i}-${sentence.slice(0, 20)}`}
                  variants={sentenceVariants}
                  className="inline"
                >
                  {sentence}{' '}
                </motion.span>
              ))
            ) : (
              text
            )}
          </p>
        </motion.div>
      </AnimatePresence>
    </div>
  );
}
