'use client';

import { useState, useEffect, useCallback, useRef } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Play, Pause, RotateCcw } from 'lucide-react';
import type { TimelineState } from '../lib/vaf/timeline';

// ──────────────────────────────────────────────
// PlayerControls
// Semi-transparent bottom controls with auto-hide.
// Play/pause, restart, seek bar, time, scene dots.
// ──────────────────────────────────────────────

interface PlayerControlsProps {
  state: TimelineState;
  totalScenes: number;
  onPlay: () => void;
  onPause: () => void;
  onSeek: (ms: number) => void;
  onRestart: () => void;
}

function formatTime(ms: number): string {
  const seconds = Math.floor(ms / 1000);
  const m = Math.floor(seconds / 60);
  const s = seconds % 60;
  return `${m}:${s.toString().padStart(2, '0')}`;
}

const HIDE_DELAY_MS = 3000;

const controlsVariants = {
  visible: {
    opacity: 1,
    y: 0,
    transition: { duration: 0.25, ease: 'easeOut' as const },
  },
  hidden: {
    opacity: 0,
    y: 16,
    transition: { duration: 0.3, ease: 'easeIn' as const },
  },
};

export default function PlayerControls({
  state,
  totalScenes,
  onPlay,
  onPause,
  onSeek,
  onRestart,
}: PlayerControlsProps) {
  const [visible, setVisible] = useState(true);
  const hideTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const containerRef = useRef<HTMLDivElement>(null);

  const handlePlay = useCallback(() => {
    setVisible(true);
    onPlay();
  }, [onPlay]);

  // ── Auto-hide logic ──────────────────────────
  const resetHideTimer = useCallback(() => {
    if (state.status !== 'playing') return;

    setVisible(true);
    if (hideTimerRef.current) clearTimeout(hideTimerRef.current);
    if (state.status === 'playing') {
      hideTimerRef.current = setTimeout(() => setVisible(false), HIDE_DELAY_MS);
    }
  }, [state.status]);

  // Start hide timer when playing
  useEffect(() => {
    if (state.status === 'playing') {
      hideTimerRef.current = setTimeout(() => setVisible(false), HIDE_DELAY_MS);
    } else {
      if (hideTimerRef.current) clearTimeout(hideTimerRef.current);
    }
    return () => {
      if (hideTimerRef.current) clearTimeout(hideTimerRef.current);
    };
  }, [state.status]);

  // Show on mouse move
  useEffect(() => {
    const handler = () => resetHideTimer();
    window.addEventListener('mousemove', handler);
    window.addEventListener('touchstart', handler);
    return () => {
      window.removeEventListener('mousemove', handler);
      window.removeEventListener('touchstart', handler);
    };
  }, [resetHideTimer]);

  // ── Seek handler ─────────────────────────────
  const handleProgressClick = useCallback(
    (e: React.MouseEvent<HTMLDivElement>) => {
      const rect = e.currentTarget.getBoundingClientRect();
      const pct = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
      onSeek(pct * state.totalDurationMs);
    },
    [onSeek, state.totalDurationMs],
  );

  const progress = state.totalDurationMs > 0 ? state.elapsedMs / state.totalDurationMs : 0;
  const isPlaying = state.status === 'playing';

  const showControls = visible || state.status !== 'playing';

  return (
    <AnimatePresence>
      {showControls && (
        <motion.div
          ref={containerRef}
          className="absolute bottom-0 left-0 right-0 z-30 px-4 pb-4 pt-8"
          style={{
            background: 'linear-gradient(transparent, rgba(0,0,0,0.7))',
          }}
          variants={controlsVariants}
          initial="hidden"
          animate="visible"
          exit="hidden"
        >
          {/* Progress bar */}
          <div
            className="w-full h-1.5 bg-white/20 rounded-full cursor-pointer mb-3 group hover:h-2.5 transition-all"
            onClick={handleProgressClick}
            role="slider"
            aria-label="Seek"
            aria-valuemin={0}
            aria-valuemax={state.totalDurationMs}
            aria-valuenow={state.elapsedMs}
            tabIndex={0}
          >
            <div
              className="h-full bg-white/90 rounded-full relative transition-all"
              style={{ width: `${progress * 100}%` }}
            >
              <div className="absolute right-0 top-1/2 -translate-y-1/2 w-3 h-3 bg-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity shadow-lg" />
            </div>
          </div>

          <div className="flex items-center gap-3">
            {/* Play / Pause button */}
            <button
              className="flex items-center justify-center w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur-sm transition-colors"
              onClick={isPlaying ? onPause : handlePlay}
              aria-label={isPlaying ? 'Pause' : 'Play'}
            >
              {isPlaying ? (
                <Pause className="w-5 h-5 text-white" />
              ) : (
                <Play className="w-5 h-5 text-white ml-0.5" />
              )}
            </button>

            {/* Restart button */}
            <button
              className="flex items-center justify-center w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur-sm transition-colors"
              onClick={onRestart}
              aria-label="Restart"
            >
              <RotateCcw className="w-4 h-4 text-white" />
            </button>

            {/* Time display */}
            <span className="text-white/80 text-sm font-mono tabular-nums min-w-[5rem]">
              {formatTime(state.elapsedMs)} / {formatTime(state.totalDurationMs)}
            </span>

            {/* Spacer */}
            <div className="flex-1" />

            {/* Scene dots */}
            <div className="flex items-center gap-1.5" aria-label="Scene progress">
              {Array.from({ length: totalScenes }, (_, i) => {
                const isActive = i === state.currentSceneIndex;
                const isPast = i < state.currentSceneIndex;
                return (
                  <div
                    key={i}
                    className={`rounded-full transition-all ${
                      isActive
                        ? 'w-3 h-3 bg-white shadow-[0_0_6px_rgba(255,255,255,0.6)]'
                        : isPast
                          ? 'w-2 h-2 bg-white/60'
                          : 'w-2 h-2 bg-white/25'
                    }`}
                  />
                );
              })}
            </div>
          </div>
        </motion.div>
      )}
    </AnimatePresence>
  );
}
