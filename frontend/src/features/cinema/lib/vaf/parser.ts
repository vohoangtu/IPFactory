// ──────────────────────────────────────────────
// VAF Animation Script Parser
// Validates and normalizes raw animation script data
// ──────────────────────────────────────────────

import type {
  AnimationScript,
  VAFScene,
  VAFBackground,
  VAFAtmosphere,
  VAFCameraMovement,
  VAFEffect,
  VAFTransition,
  VAFSceneType,
  VAFBackgroundType,
  VAFAtmosphereFilter,
  VAFWeather,
  VAFCameraType,
  VAFEasing,
  VAFEffectType,
  VAFTransitionType,
} from './types';

import {
  DEFAULT_ATMOSPHERE,
  DEFAULT_BACKGROUND,
  DEFAULT_CAMERA,
  DEFAULT_TRANSITION,
} from './defaults';

// ── Validation Sets ─────────────────────────────

const SCENE_TYPES = new Set<VAFSceneType>([
  'establishing', 'action', 'tension', 'climax', 'resolution',
]);

const BACKGROUND_TYPES = new Set<VAFBackgroundType>([
  'gradient', 'solid', 'pattern',
]);

const ATMOSPHERE_FILTERS = new Set<VAFAtmosphereFilter>([
  'mist', 'sepia', 'grain', 'glitch', 'aurora', 'dust', 'none',
]);

const WEATHER_TYPES = new Set<string>([
  'rain', 'snow', 'fire_embers', 'sandstorm',
]);

const CAMERA_TYPES = new Set<VAFCameraType>([
  'static', 'zoom_in', 'zoom_out', 'pan_left', 'pan_right', 'dolly', 'shake',
]);

const EASING_TYPES = new Set<VAFEasing>([
  'ease-in', 'ease-out', 'ease-in-out', 'linear',
]);

const EFFECT_TYPES = new Set<VAFEffectType>([
  'particles', 'screen_shake', 'flash', 'ripple', 'energy_burst', 'glow',
]);

const TRANSITION_TYPES = new Set<VAFTransitionType>([
  'fade', 'dissolve', 'wipe_left', 'wipe_right', 'zoom_through', 'cut',
]);

// ── Helpers ─────────────────────────────────────

function clamp(value: number, min: number, max: number): number {
  return Math.max(min, Math.min(max, value));
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

// ── Sub-Parsers ─────────────────────────────────

function parseBackground(raw: unknown): VAFBackground {
  if (!isRecord(raw)) return { ...DEFAULT_BACKGROUND };

  const type = BACKGROUND_TYPES.has(raw.type as VAFBackgroundType)
    ? (raw.type as VAFBackgroundType)
    : DEFAULT_BACKGROUND.type;

  const colors = Array.isArray(raw.colors) && raw.colors.every((c: unknown) => typeof c === 'string')
    ? (raw.colors as string[])
    : [...DEFAULT_BACKGROUND.colors];

  const description = typeof raw.description === 'string' ? raw.description : '';

  return { type, colors, description };
}

function parseAtmosphere(raw: unknown): VAFAtmosphere {
  if (!isRecord(raw)) return { ...DEFAULT_ATMOSPHERE };

  const filter = ATMOSPHERE_FILTERS.has(raw.filter as VAFAtmosphereFilter)
    ? (raw.filter as VAFAtmosphereFilter)
    : DEFAULT_ATMOSPHERE.filter;

  const intensity = typeof raw.intensity === 'number'
    ? clamp(raw.intensity, 0, 1)
    : DEFAULT_ATMOSPHERE.intensity;

  const weather = raw.weather === null || WEATHER_TYPES.has(raw.weather as string)
    ? (raw.weather as VAFWeather)
    : DEFAULT_ATMOSPHERE.weather;

  return { filter, intensity, weather };
}

function parseCamera(raw: unknown): VAFCameraMovement {
  if (!isRecord(raw)) return { ...DEFAULT_CAMERA };

  const type = CAMERA_TYPES.has(raw.type as VAFCameraType)
    ? (raw.type as VAFCameraType)
    : DEFAULT_CAMERA.type;

  const speed = typeof raw.speed === 'number'
    ? clamp(raw.speed, 0.1, 2.0)
    : DEFAULT_CAMERA.speed;

  const easing = EASING_TYPES.has(raw.easing as VAFEasing)
    ? (raw.easing as VAFEasing)
    : DEFAULT_CAMERA.easing;

  return { type, speed, easing };
}

function parseEffect(raw: unknown): VAFEffect | null {
  if (!isRecord(raw)) return null;

  const type = raw.type as VAFEffectType;
  if (!EFFECT_TYPES.has(type)) return null;

  const intensity = typeof raw.intensity === 'number' ? clamp(raw.intensity, 0, 1) : 0.5;
  const color = typeof raw.color === 'string' ? raw.color : null;
  const trigger_at_ms = typeof raw.trigger_at_ms === 'number' ? Math.max(0, raw.trigger_at_ms) : 0;

  return { type, intensity, color, trigger_at_ms };
}

function parseTransition(raw: unknown): VAFTransition {
  if (!isRecord(raw)) return { ...DEFAULT_TRANSITION };

  const type = TRANSITION_TYPES.has(raw.type as VAFTransitionType)
    ? (raw.type as VAFTransitionType)
    : DEFAULT_TRANSITION.type;

  const duration_ms = typeof raw.duration_ms === 'number'
    ? clamp(raw.duration_ms, 300, 1500)
    : DEFAULT_TRANSITION.duration_ms;

  return { type, duration_ms };
}

function parseScene(raw: unknown): VAFScene | null {
  if (!isRecord(raw)) return null;

  const id = typeof raw.id === 'string' ? raw.id : '';
  if (!id) return null;

  const type = SCENE_TYPES.has(raw.type as VAFSceneType)
    ? (raw.type as VAFSceneType)
    : 'establishing';

  const duration_ms = typeof raw.duration_ms === 'number'
    ? clamp(raw.duration_ms, 3000, 15000)
    : 5000;

  const background = parseBackground(raw.background);
  const atmosphere = parseAtmosphere(raw.atmosphere);
  const camera = parseCamera(raw.camera);

  const effects: VAFEffect[] = Array.isArray(raw.effects)
    ? (raw.effects.map(parseEffect).filter((e): e is VAFEffect => e !== null))
    : [];

  const narration = typeof raw.narration === 'string' ? raw.narration : '';
  const transition = parseTransition(raw.transition);

  return { id, type, duration_ms, background, atmosphere, camera, effects, narration, transition };
}

// ── Main Parser ─────────────────────────────────

export function parseAnimationScript(raw: unknown): AnimationScript | null {
  if (raw == null) return null;

  if (!isRecord(raw)) {
    console.warn('[VAF] Invalid animation script: expected object, got', typeof raw);
    return null;
  }

  const total_duration_ms = typeof raw.total_duration_ms === 'number'
    ? clamp(raw.total_duration_ms, 15000, 60000)
    : 30000;

  if (!Array.isArray(raw.scenes)) {
    console.warn('[VAF] Invalid animation script: scenes is not an array');
    return null;
  }

  const scenes = raw.scenes.map(parseScene).filter((s): s is VAFScene => s !== null);

  if (scenes.length < 2) {
    console.warn('[VAF] Invalid animation script: need at least 2 valid scenes, got', scenes.length);
    return null;
  }

  // Cap at 8 scenes maximum
  const cappedScenes = scenes.slice(0, 8);

  return { total_duration_ms, scenes: cappedScenes };
}
