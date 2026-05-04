// ──────────────────────────────────────────────
// VAF Parser Unit Tests
// Tests: parseAnimationScript edge cases
// ──────────────────────────────────────────────

import { parseAnimationScript } from '../parser';

// ── Helpers ──────────────────────────────────

function makeScene(overrides: Partial<Record<string, unknown>> = {}): Record<string, unknown> {
  return {
    id: `scene-${Math.random().toString(36).slice(2)}`,
    type: 'establishing',
    duration_ms: 5000,
    background: { type: 'gradient', colors: ['#000', '#111'], description: '' },
    atmosphere: { filter: 'none', intensity: 0.5, weather: null },
    camera: { type: 'static', speed: 1.0, easing: 'linear' },
    effects: [],
    narration: 'Test narration',
    transition: { type: 'fade', duration_ms: 500 },
    ...overrides,
  };
}

// ── Tests ────────────────────────────────────

describe('parseAnimationScript', () => {
  // Null / falsy inputs
  it('returns null for null input', () => {
    expect(parseAnimationScript(null)).toBeNull();
  });

  it('returns null for undefined', () => {
    expect(parseAnimationScript(undefined)).toBeNull();
  });

  it('returns null for empty string', () => {
    expect(parseAnimationScript('')).toBeNull();
  });

  it('returns null for non-object (number)', () => {
    expect(parseAnimationScript(42)).toBeNull();
  });

  it('returns null for empty object (no scenes)', () => {
    expect(parseAnimationScript({})).toBeNull();
  });

  // Array validation
  it('returns null when scenes is not an array', () => {
    expect(parseAnimationScript({ scenes: 'invalid' })).toBeNull();
  });

  it('returns null for empty scenes array', () => {
    expect(parseAnimationScript({ scenes: [] })).toBeNull();
  });

  it('returns null for single valid scene (< 2 required)', () => {
    expect(parseAnimationScript({ scenes: [makeScene()] })).toBeNull();
  });

  // Valid cases
  it('returns valid script with 2 scenes', () => {
    const result = parseAnimationScript({ scenes: [makeScene(), makeScene()] });
    expect(result).not.toBeNull();
    expect(result?.scenes).toHaveLength(2);
  });

  it('returns valid script with 8 scenes (max cap)', () => {
    const scenes = Array.from({ length: 8 }, () => makeScene());
    const result = parseAnimationScript({ scenes });
    expect(result).not.toBeNull();
    expect(result?.scenes).toHaveLength(8);
  });

  it('caps scenes at 8 when 10 are provided', () => {
    const scenes = Array.from({ length: 10 }, () => makeScene());
    const result = parseAnimationScript({ scenes });
    expect(result).not.toBeNull();
    expect(result?.scenes).toHaveLength(8);
  });

  // total_duration_ms clamping
  it('uses default 30000 when total_duration_ms is missing', () => {
    const result = parseAnimationScript({ scenes: [makeScene(), makeScene()] });
    expect(result?.total_duration_ms).toBe(30000);
  });

  it('clamps total_duration_ms to 15000 minimum', () => {
    const result = parseAnimationScript({
      scenes: [makeScene(), makeScene()],
      total_duration_ms: 100,
    });
    expect(result?.total_duration_ms).toBe(15000);
  });

  it('clamps total_duration_ms to 60000 maximum', () => {
    const result = parseAnimationScript({
      scenes: [makeScene(), makeScene()],
      total_duration_ms: 999999,
    });
    expect(result?.total_duration_ms).toBe(60000);
  });

  it('accepts valid total_duration_ms within range', () => {
    const result = parseAnimationScript({
      scenes: [makeScene(), makeScene()],
      total_duration_ms: 25000,
    });
    expect(result?.total_duration_ms).toBe(25000);
  });

  // Scene filtering — invalid scenes are dropped, not fatal
  it('filters out invalid scenes (no id) and succeeds if >= 2 remain', () => {
    const scenes = [
      makeScene(),
      { ...makeScene(), id: '' },  // invalid: empty id
      makeScene(),
    ];
    const result = parseAnimationScript({ scenes });
    expect(result).not.toBeNull();
    expect(result?.scenes).toHaveLength(2);
  });

  it('returns null if all scenes become invalid after filtering', () => {
    const scenes = [
      { ...makeScene(), id: '' },
      { ...makeScene(), id: '' },
    ];
    const result = parseAnimationScript({ scenes });
    expect(result).toBeNull();
  });

  // Effect intensity clamping inside scene
  it('clamps effect intensity to [0, 1]', () => {
    const scene = makeScene({
      effects: [{ type: 'particles', intensity: 5.0, color: null, trigger_at_ms: 0 }],
    });
    const result = parseAnimationScript({ scenes: [scene, makeScene()] });
    expect(result?.scenes[0].effects[0].intensity).toBe(1);
  });

  // Camera speed clamping
  it('clamps camera speed to [0.1, 2.0]', () => {
    const scene = makeScene({ camera: { type: 'zoom_in', speed: 99, easing: 'linear' } });
    const result = parseAnimationScript({ scenes: [scene, makeScene()] });
    expect(result?.scenes[0].camera.speed).toBe(2.0);
  });
});
