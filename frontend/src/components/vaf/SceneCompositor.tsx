'use client';

import { useMemo } from 'react';
import { AnimatePresence, motion } from 'framer-motion';
import type { VAFScene, VAFEffect } from '@/lib/vaf/types';
import BackgroundRenderer from './BackgroundRenderer';
import AtmosphereRenderer from './AtmosphereRenderer';
import CameraRenderer from './CameraRenderer';
import ParticleRenderer from './ParticleRenderer';
import EffectOverlay from './EffectOverlay';
import NarrationOverlay from './NarrationOverlay';

// ──────────────────────────────────────────────
// SceneCompositor
// Composes all visual layers for a single scene:
// background, atmosphere, camera, particles,
// effects, and narration.
// ──────────────────────────────────────────────

interface SceneCompositorProps {
  scene: VAFScene;
  activeEffects: VAFEffect[];
  isPlaying: boolean;
  sceneProgress: number; // 0-1 within current scene
}

export default function SceneCompositor({
  scene,
  activeEffects,
  isPlaying,
  sceneProgress,
}: SceneCompositorProps) {
  const particleEffects = useMemo(
    () => activeEffects.filter((e) => e.type === 'particles'),
    [activeEffects],
  );

  const hasParticles = particleEffects.length > 0;
  const primaryColor = scene.background.colors[0] ?? '#ffffff';

  return (
    <AnimatePresence mode="sync">
      <motion.div
        key={scene.id}
        className="absolute inset-0 overflow-hidden"
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        transition={{ duration: scene.transition.duration_ms / 1000 }}
      >
        {/* Layer 1: Background */}
        <BackgroundRenderer background={scene.background} />

        {/* Layer 2: Camera wraps atmosphere + content */}
        <CameraRenderer camera={scene.camera} progress={sceneProgress}>
          {/* Layer 3: Atmosphere filters + weather */}
          <AtmosphereRenderer atmosphere={scene.atmosphere} />
        </CameraRenderer>

        {/* Layer 4: Particle canvas */}
        {hasParticles && (
          <ParticleRenderer
            effects={particleEffects}
            isPlaying={isPlaying}
          />
        )}

        {/* Layer 5: Non-particle effects overlay */}
        <EffectOverlay effects={activeEffects} primaryColor={primaryColor} />

        {/* Layer 6: Narration text */}
        <NarrationOverlay text={scene.narration} isPlaying={isPlaying} />
      </motion.div>
    </AnimatePresence>
  );
}
