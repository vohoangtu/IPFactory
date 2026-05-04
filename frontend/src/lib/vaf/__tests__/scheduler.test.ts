// ──────────────────────────────────────────────
// VAF Effect Scheduler Unit Tests
// Tests: EffectScheduler.getActiveEffects
// ──────────────────────────────────────────────

import { EffectScheduler } from '../scheduler';
import type { VAFScene, VAFEffect } from '../types';

// ── Helpers ──────────────────────────────────

function makeEffect(overrides: Partial<VAFEffect> = {}): VAFEffect {
  return {
    type: 'particles',
    intensity: 0.5,
    color: null,
    trigger_at_ms: 0,
    ...overrides,
  };
}

function makeScene(effects: VAFEffect[] = []): VAFScene {
  return {
    id: `scene-${Math.random().toString(36).slice(2)}`,
    type: 'establishing',
    duration_ms: 5000,
    background: { type: 'gradient', colors: ['#000'], description: '' },
    atmosphere: { filter: 'none', intensity: 0.5, weather: null },
    camera: { type: 'static', speed: 1.0, easing: 'linear' },
    effects,
    narration: '',
    transition: { type: 'fade', duration_ms: 500 },
  };
}

// ── Tests ─────────────────────────────────────

describe('EffectScheduler', () => {
  describe('getActiveEffects', () => {
    it('returns empty array for out-of-bounds scene index', () => {
      const scheduler = new EffectScheduler([makeScene()]);
      expect(scheduler.getActiveEffects(5, 0)).toEqual([]);
    });

    it('returns empty array for empty scenes list', () => {
      const scheduler = new EffectScheduler([]);
      expect(scheduler.getActiveEffects(0, 0)).toEqual([]);
    });

    it('returns effects with trigger_at_ms = 0 at elapsed = 0', () => {
      const effect = makeEffect({ trigger_at_ms: 0 });
      const scheduler = new EffectScheduler([makeScene([effect])]);
      const active = scheduler.getActiveEffects(0, 0);
      expect(active).toHaveLength(1);
      expect(active[0].type).toBe('particles');
    });

    it('does NOT return effects whose trigger_at_ms > elapsed', () => {
      const effect = makeEffect({ trigger_at_ms: 3000 });
      const scheduler = new EffectScheduler([makeScene([effect])]);
      expect(scheduler.getActiveEffects(0, 1000)).toHaveLength(0);
    });

    it('returns effect when elapsed >= trigger_at_ms', () => {
      const effect = makeEffect({ trigger_at_ms: 3000 });
      const scheduler = new EffectScheduler([makeScene([effect])]);
      expect(scheduler.getActiveEffects(0, 3000)).toHaveLength(1);
      expect(scheduler.getActiveEffects(0, 5000)).toHaveLength(1);
    });

    it('returns multiple effects that have all triggered by elapsed', () => {
      const effects = [
        makeEffect({ type: 'particles', trigger_at_ms: 0 }),
        makeEffect({ type: 'flash', trigger_at_ms: 1000 }),
        makeEffect({ type: 'glow', trigger_at_ms: 2000 }),
        makeEffect({ type: 'screen_shake', trigger_at_ms: 4000 }),
      ];
      const scheduler = new EffectScheduler([makeScene(effects)]);

      // Only first 3 should be active at 2000ms
      expect(scheduler.getActiveEffects(0, 2000)).toHaveLength(3);

      // All 4 active at 4000ms
      expect(scheduler.getActiveEffects(0, 4000)).toHaveLength(4);
    });

    it('treats each scene independently', () => {
      const scene0 = makeScene([makeEffect({ trigger_at_ms: 0 })]);
      const scene1 = makeScene([
        makeEffect({ trigger_at_ms: 2000 }),
        makeEffect({ trigger_at_ms: 4000 }),
      ]);
      const scheduler = new EffectScheduler([scene0, scene1]);

      // scene0 at 0ms: 1 effect
      expect(scheduler.getActiveEffects(0, 0)).toHaveLength(1);

      // scene1 at 1000ms: 0 effects (none triggered yet)
      expect(scheduler.getActiveEffects(1, 1000)).toHaveLength(0);

      // scene1 at 2000ms: 1 effect
      expect(scheduler.getActiveEffects(1, 2000)).toHaveLength(1);

      // scene1 at 4500ms: 2 effects
      expect(scheduler.getActiveEffects(1, 4500)).toHaveLength(2);
    });

    it('returns no effects if scene has empty effects array', () => {
      const scheduler = new EffectScheduler([makeScene([])]);
      expect(scheduler.getActiveEffects(0, 9999)).toHaveLength(0);
    });

    it('handles negative sceneElapsedMs gracefully', () => {
      const effect = makeEffect({ trigger_at_ms: 0 });
      const scheduler = new EffectScheduler([makeScene([effect])]);
      // trigger_at_ms=0, elapsed=-100 → 0 >= -100 is true, but:
      // effect.trigger_at_ms (0) >= sceneElapsedMs (-100) → active
      expect(scheduler.getActiveEffects(0, -100)).toHaveLength(0);
    });
  });
});
