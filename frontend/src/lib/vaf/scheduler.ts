// ──────────────────────────────────────────────
// VAF Effect Scheduler
// Determines which effects are active at a given point in time
// ──────────────────────────────────────────────

import type { VAFEffect, VAFScene } from './types';

export class EffectScheduler {
  private scenes: VAFScene[];

  constructor(scenes: VAFScene[]) {
    this.scenes = scenes;
  }

  /**
   * Returns all effects in the given scene that should have
   * triggered by the current scene elapsed time.
   */
  getActiveEffects(sceneIndex: number, sceneElapsedMs: number): VAFEffect[] {
    const scene = this.scenes[sceneIndex];
    if (!scene) return [];

    return scene.effects.filter((e) => sceneElapsedMs >= e.trigger_at_ms);
  }
}
