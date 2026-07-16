'use client';

import { useRef, useEffect, useCallback } from 'react';
import type { VAFEffect } from '../lib/vaf/types';

// ──────────────────────────────────────────────
// ParticleRenderer
// Canvas 2D particle system for 'particles' effects.
// Uses an object pool of 200 particles with rAF loop.
// H3 fix: Responsive canvas via ResizeObserver (no more fixed 960x540).
// ──────────────────────────────────────────────

interface Particle {
  x: number;
  y: number;
  vx: number;
  vy: number;
  alpha: number;
  size: number;
  lifetime: number;
  maxLifetime: number;
  active: boolean;
  color: string;
}

const MAX_PARTICLES = 200;

interface Props {
  effects: VAFEffect[];
  isPlaying: boolean;
  // width/height props giữ lại để backward-compat nhưng không dùng cho canvas attr
  width?: number;
  height?: number;
}

export default function ParticleRenderer({ effects, isPlaying }: Props) {
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const containerRef = useRef<HTMLDivElement>(null);
  const particlesRef = useRef<Particle[]>(
    Array.from({ length: MAX_PARTICLES }, () => ({
      x: 0, y: 0, vx: 0, vy: 0,
      alpha: 0, size: 0, lifetime: 0, maxLifetime: 0,
      active: false, color: '#ff6b35',
    })),
  );
  const rafRef = useRef<number>(0);
  const lastTimeRef = useRef<number>(0);
  const spawnAccRef = useRef<number>(0);

  const particleEffects = effects.filter((e) => e.type === 'particles');
  const avgIntensity =
    particleEffects.length > 0
      ? particleEffects.reduce((s, e) => s + e.intensity, 0) / particleEffects.length
      : 0;
  const effectColor = particleEffects[0]?.color ?? '#ff6b35';
  const spawnRate = 5 + avgIntensity * 45; // 5-50 per second

  // H3 fix: ResizeObserver sync canvas attrs voi container size + DPR
  useEffect(() => {
    const canvas = canvasRef.current;
    const container = containerRef.current;
    if (!canvas || !container) return;

    const observer = new ResizeObserver((entries) => {
      const entry = entries[0];
      if (!entry) return;
      const { width, height } = entry.contentRect;
      const dpr = window.devicePixelRatio || 1;
      canvas.width = Math.round(width * dpr);
      canvas.height = Math.round(height * dpr);
      const ctx = canvas.getContext('2d');
      if (ctx) {
        ctx.scale(dpr, dpr);
      }
    });

    observer.observe(container);
    return () => observer.disconnect();
  }, []);

  const spawnOne = useCallback(
    (w: number, h: number) => {
      const pool = particlesRef.current;
      for (let i = 0; i < MAX_PARTICLES; i++) {
        if (!pool[i].active) {
          const p = pool[i];
          p.x = Math.random() * w;
          p.y = h + 4;
          p.vx = (Math.random() - 0.5) * 20;
          p.vy = -(30 + Math.random() * 60);
          p.alpha = 0.6 + Math.random() * 0.4;
          p.size = 1.5 + Math.random() * 3;
          p.lifetime = 0;
          p.maxLifetime = 1.5 + Math.random() * 2;
          p.active = true;
          p.color = effectColor;
          return;
        }
      }
    },
    [effectColor],
  );

  useEffect(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    let running = true;

    const tick = (time: number) => {
      if (!running) return;
      const dt = lastTimeRef.current ? Math.min((time - lastTimeRef.current) / 1000, 0.1) : 0.016;
      lastTimeRef.current = time;

      // Doc logical size (canvas.width da nhan DPR)
      const dpr = window.devicePixelRatio || 1;
      const w = canvas.width / dpr;
      const h = canvas.height / dpr;
      ctx.clearRect(0, 0, w, h);

      // spawn
      if (isPlaying && avgIntensity > 0) {
        spawnAccRef.current += spawnRate * dt;
        while (spawnAccRef.current >= 1) {
          spawnOne(w, h);
          spawnAccRef.current -= 1;
        }
      }

      // update + draw
      const pool = particlesRef.current;
      for (let i = 0; i < MAX_PARTICLES; i++) {
        const p = pool[i];
        if (!p.active) continue;
        p.lifetime += dt;
        if (p.lifetime >= p.maxLifetime) {
          p.active = false;
          continue;
        }
        p.x += p.vx * dt;
        p.y += p.vy * dt;
        const fade = 1 - p.lifetime / p.maxLifetime;
        ctx.globalAlpha = p.alpha * fade;
        ctx.fillStyle = p.color;
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.size * fade, 0, Math.PI * 2);
        ctx.fill();
      }
      ctx.globalAlpha = 1;

      rafRef.current = requestAnimationFrame(tick);
    };

    rafRef.current = requestAnimationFrame(tick);

    return () => {
      running = false;
      cancelAnimationFrame(rafRef.current);
    };
  }, [isPlaying, avgIntensity, spawnRate, spawnOne]);

  return (
    <div ref={containerRef} className="absolute inset-0 pointer-events-none">
      <canvas
        ref={canvasRef}
        className="absolute inset-0 w-full h-full"
      />
    </div>
  );
}
