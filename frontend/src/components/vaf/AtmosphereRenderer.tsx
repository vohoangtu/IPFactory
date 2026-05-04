'use client';

import { motion, AnimatePresence } from 'framer-motion';
import { useMemo } from 'react';
import type { VAFAtmosphere, VAFAtmosphereFilter, VAFWeather } from '@/lib/vaf/types';

// ──────────────────────────────────────────────
// AtmosphereRenderer
// Renders CSS overlay filters and weather effects.
// All layers are pointer-events-none.
// ──────────────────────────────────────────────

interface Props {
  atmosphere: VAFAtmosphere;
}

// ── Filter layer styles ────────────────────────

function useFilterStyle(filter: VAFAtmosphereFilter, intensity: number) {
  return useMemo(() => {
    const i = Math.max(0, Math.min(1, intensity));

    switch (filter) {
      case 'mist':
        return {
          backdropFilter: `blur(${i * 6}px)`,
          WebkitBackdropFilter: `blur(${i * 6}px)`,
          backgroundColor: `rgba(255, 255, 255, ${i * 0.15})`,
        };

      case 'sepia':
        return {
          filter: `sepia(${i})`,
          backgroundColor: 'transparent',
        };

      case 'grain':
        return {
          filter: `url(#vaf-grain) opacity(${i})`,
          backgroundColor: 'transparent',
        };

      case 'glitch':
        return {
          backgroundColor: `rgba(255, 0, 80, ${i * 0.08})`,
          mixBlendMode: 'screen' as const,
        };

      case 'aurora':
        return {
          background: `linear-gradient(135deg, rgba(0,255,128,${i * 0.15}), rgba(0,128,255,${i * 0.15}), rgba(128,0,255,${i * 0.15}))`,
          mixBlendMode: 'screen' as const,
        };

      case 'dust':
        return {
          backgroundColor: `rgba(180, 130, 60, ${i * 0.12})`,
        };

      case 'none':
      default:
        return { opacity: 0 };
    }
  }, [filter, intensity]);
}

// ── Weather overlay styles ─────────────────────

function useWeatherStyle(weather: VAFWeather, intensity: number) {
  return useMemo(() => {
    if (!weather) return null;
    const i = Math.max(0, Math.min(1, intensity));

    switch (weather) {
      case 'rain':
        return {
          background: `repeating-linear-gradient(
            180deg,
            transparent,
            transparent 4px,
            rgba(174, 194, 224, ${i * 0.3}) 4px,
            rgba(174, 194, 224, ${i * 0.3}) 5px
          )`,
          backgroundSize: '20px 100%',
          animation: 'vaf-rain 0.4s linear infinite',
        };

      case 'snow':
        return {
          background: `radial-gradient(1px 1px at 20% 30%, rgba(255,255,255,${i * 0.8}), transparent),
            radial-gradient(1px 1px at 40% 70%, rgba(255,255,255,${i * 0.6}), transparent),
            radial-gradient(1.5px 1.5px at 60% 20%, rgba(255,255,255,${i * 0.7}), transparent),
            radial-gradient(1px 1px at 80% 50%, rgba(255,255,255,${i * 0.5}), transparent)`,
          backgroundSize: '100px 100px',
          animation: 'vaf-snow 3s linear infinite',
        };

      case 'fire_embers':
        return {
          background: `radial-gradient(ellipse at center, rgba(255,100,0,${i * 0.1}), transparent 70%)`,
          animation: 'vaf-ember-pulse 2s ease-in-out infinite',
        };

      case 'sandstorm':
        return {
          backgroundColor: `rgba(180, 150, 80, ${i * 0.15})`,
          filter: `blur(${i * 2}px)`,
          animation: 'vaf-sandstorm 1.5s ease-in-out infinite',
        };

      default:
        return null;
    }
  }, [weather, intensity]);
}

// ── Glitch animation variants ──────────────────

const glitchVariants = {
  animate: {
    clipPath: [
      'inset(0 0 0 0)',
      'inset(20% 0 40% 0)',
      'inset(60% 0 10% 0)',
      'inset(0 0 0 0)',
      'inset(40% 0 20% 0)',
      'inset(0 0 0 0)',
    ],
    transition: {
      duration: 0.6,
      repeat: Infinity,
      repeatDelay: 2,
      ease: 'linear' as const,
    },
  },
};

// ── Aurora hue-rotate variants ─────────────────

const auroraVariants = {
  animate: {
    filter: [
      'hue-rotate(0deg)',
      'hue-rotate(60deg)',
      'hue-rotate(120deg)',
      'hue-rotate(180deg)',
      'hue-rotate(240deg)',
      'hue-rotate(300deg)',
      'hue-rotate(360deg)',
    ],
    transition: {
      duration: 8,
      repeat: Infinity,
      ease: 'linear' as const,
    },
  },
};

// ── CSS Keyframes (injected once) ──────────────

const keyframesCSS = `
@keyframes vaf-rain {
  0%   { background-position: 0 0; }
  100% { background-position: 0 100%; }
}
@keyframes vaf-snow {
  0%   { background-position: 0 0, 0 0, 0 0, 0 0; }
  100% { background-position: 0 300px, 0 200px, 0 250px, 0 350px; }
}
@keyframes vaf-ember-pulse {
  0%, 100% { opacity: 0.6; transform: scale(1); }
  50%      { opacity: 1;   transform: scale(1.05); }
}
@keyframes vaf-sandstorm {
  0%, 100% { transform: translateX(0); }
  25%      { transform: translateX(4px); }
  75%      { transform: translateX(-4px); }
}
`;

// ── Inline SVG grain filter ────────────────────

function GrainSVG() {
  return (
    <svg className="absolute" width="0" height="0" aria-hidden="true">
      <defs>
        <filter id="vaf-grain">
          <feTurbulence
            type="fractalNoise"
            baseFrequency="0.65"
            numOctaves="3"
            stitchTiles="stitch"
          />
          <feColorMatrix type="saturate" values="0" />
        </filter>
      </defs>
    </svg>
  );
}

// ── Main component ─────────────────────────────

export default function AtmosphereRenderer({ atmosphere }: Props) {
  const { filter, intensity, weather } = atmosphere;
  const filterStyle = useFilterStyle(filter, intensity);
  const weatherStyle = useWeatherStyle(weather, intensity);

  const useGlitch = filter === 'glitch';
  const useAurora = filter === 'aurora';

  return (
    <>
      {/* Inject keyframes */}
      <style dangerouslySetInnerHTML={{ __html: keyframesCSS }} />

      {/* SVG filter defs (for grain) */}
      {filter === 'grain' && <GrainSVG />}

      {/* Atmosphere filter layer */}
      <AnimatePresence mode="wait">
        <motion.div
          key={filter}
          className="absolute inset-0 pointer-events-none"
          initial={{ opacity: 0 }}
          animate={useGlitch || useAurora ? 'animate' : { opacity: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.5 }}
          style={filterStyle}
          variants={useGlitch ? glitchVariants : useAurora ? auroraVariants : undefined}
        />
      </AnimatePresence>

      {/* Weather overlay layer */}
      <AnimatePresence>
        {weatherStyle && (
          <motion.div
            key={weather}
            className="absolute inset-0 pointer-events-none"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.6 }}
            style={weatherStyle as React.CSSProperties}
          />
        )}
      </AnimatePresence>
    </>
  );
}
