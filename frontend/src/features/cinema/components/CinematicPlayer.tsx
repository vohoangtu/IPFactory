"use client";

// ──────────────────────────────────────────────
// CinematicPlayer
// Top-level VAF player that composes SceneCompositor,
// PlayerControls, keyboard shortcuts, and overlays.
// ──────────────────────────────────────────────

import { useState, useEffect, useMemo, useCallback } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { RotateCcw } from 'lucide-react';

import type { AnimationScript } from '../lib/vaf/types';
import { computeSceneElapsedMs } from '../lib/vaf/timeline';
import { useVAFPlayer } from '../hooks/useVAFPlayer';
import SceneCompositor from './SceneCompositor';
import PlayerControls from './PlayerControls';

// ── Props ──────────────────────────────────────

interface CinematicPlayerProps {
  animationScript: AnimationScript;
  chronicleTitle: string;
  chronicleContent?: string;
  onExit?: () => void;
}

// ── Component ──────────────────────────────────

export default function CinematicPlayer({
  animationScript,
  chronicleTitle,
  chronicleContent,
  onExit,
}: CinematicPlayerProps) {
  const { state, play, pause, seek, restart, currentScene, activeEffects } =
    useVAFPlayer(animationScript);

  const [showTitle, setShowTitle] = useState(true);

  // Scene durations for progress calculation
  const sceneDurations = useMemo(
    () => animationScript.scenes.map((s) => s.duration_ms),
    [animationScript],
  );

  // Compute scene-level progress (0-1)
  const sceneElapsedMs = useMemo(
    () => computeSceneElapsedMs(state.elapsedMs, sceneDurations),
    [state.elapsedMs, sceneDurations],
  );

  const sceneProgress = currentScene
    ? Math.min(1, sceneElapsedMs / currentScene.duration_ms)
    : 0;

  // ── Auto-play on mount ──────────────────────
  useEffect(() => {
    play();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // ── Title fade-out after 3s ─────────────────
  useEffect(() => {
    const timer = setTimeout(() => setShowTitle(false), 3000);
    return () => clearTimeout(timer);
  }, []);

  // ── Keyboard shortcuts ──────────────────────
  const handleKeyDown = useCallback(
    (e: KeyboardEvent) => {
      switch (e.key) {
        case ' ':
          e.preventDefault();
          if (state.status === 'playing') {
            pause();
          } else {
            play();
          }
          break;
        case 'ArrowLeft':
          seek(state.elapsedMs - 5000);
          break;
        case 'ArrowRight':
          seek(state.elapsedMs + 5000);
          break;
        case 'r':
        case 'R':
          restart();
          break;
        case 'Escape':
          onExit?.();
          break;
      }
    },
    [state.status, state.elapsedMs, play, pause, seek, restart, onExit],
  );

  useEffect(() => {
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [handleKeyDown]);

  // ── Safety: no current scene ────────────────
  if (!currentScene) {
    return <div className="w-screen h-screen bg-black" />;
  }

  const isPlaying = state.status === 'playing';
  const isEnded = state.status === 'ended';
  const totalScenes = animationScript.scenes.length;

  return (
    <div className="w-screen h-screen bg-black overflow-hidden relative">
      {/* Scene compositor */}
      <SceneCompositor
        scene={currentScene}
        activeEffects={activeEffects}
        isPlaying={isPlaying}
        sceneProgress={sceneProgress}
      />

      {/* Title overlay — fades out after 3s */}
      <AnimatePresence>
        {showTitle && (
          <motion.div
            className="absolute top-6 left-6 z-20 max-w-lg"
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.6 }}
          >
            <h1 className="text-white text-2xl font-bold drop-shadow-lg">
              {chronicleTitle}
            </h1>
            {chronicleContent && (
              <p className="text-white/60 text-sm mt-1 line-clamp-2">
                {chronicleContent}
              </p>
            )}
          </motion.div>
        )}
      </AnimatePresence>

      {/* Replay overlay when ended */}
      <AnimatePresence>
        {isEnded && (
          <motion.div
            className="absolute inset-0 z-20 flex items-center justify-center bg-black/50"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.4 }}
          >
            <button
              className="flex flex-col items-center gap-3 text-white hover:scale-105 transition-transform"
              onClick={() => {
                restart();
                play();
              }}
            >
              <RotateCcw className="w-12 h-12" />
              <span className="text-lg font-medium">Replay</span>
            </button>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Player controls */}
      <PlayerControls
        state={state}
        totalScenes={totalScenes}
        onPlay={play}
        onPause={pause}
        onSeek={seek}
        onRestart={restart}
      />
    </div>
  );
}
