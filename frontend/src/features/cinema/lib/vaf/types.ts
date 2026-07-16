// ──────────────────────────────────────────────
// VAF (Visual Animation Framework) Type Definitions
// Mirrors backend Pydantic models for animation scripts
// ──────────────────────────────────────────────

// ── Scene Types ─────────────────────────────────

export type VAFSceneType =
  | 'establishing'
  | 'action'
  | 'tension'
  | 'climax'
  | 'resolution';

// ── Background ──────────────────────────────────

export type VAFBackgroundType = 'gradient' | 'solid' | 'pattern';

export interface VAFBackground {
  type: VAFBackgroundType;
  colors: string[];
  description: string;
}

// ── Atmosphere ──────────────────────────────────

export type VAFAtmosphereFilter =
  | 'mist'
  | 'sepia'
  | 'grain'
  | 'glitch'
  | 'aurora'
  | 'dust'
  | 'none';

export type VAFWeather =
  | 'rain'
  | 'snow'
  | 'fire_embers'
  | 'sandstorm'
  | null;

export interface VAFAtmosphere {
  filter: VAFAtmosphereFilter;
  intensity: number;
  weather: VAFWeather;
}

// ── Camera Movement ─────────────────────────────

export type VAFCameraType =
  | 'static'
  | 'zoom_in'
  | 'zoom_out'
  | 'pan_left'
  | 'pan_right'
  | 'dolly'
  | 'shake';

export type VAFEasing =
  | 'ease-in'
  | 'ease-out'
  | 'ease-in-out'
  | 'linear';

export interface VAFCameraMovement {
  type: VAFCameraType;
  speed: number;
  easing: VAFEasing;
}

// ── Effects ─────────────────────────────────────

export type VAFEffectType =
  | 'particles'
  | 'screen_shake'
  | 'flash'
  | 'ripple'
  | 'energy_burst'
  | 'glow';

export interface VAFEffect {
  type: VAFEffectType;
  intensity: number;
  color: string | null;
  trigger_at_ms: number;
}

// ── Transitions ─────────────────────────────────

export type VAFTransitionType =
  | 'fade'
  | 'dissolve'
  | 'wipe_left'
  | 'wipe_right'
  | 'zoom_through'
  | 'cut';

export interface VAFTransition {
  type: VAFTransitionType;
  duration_ms: number;
}

// ── Scene ───────────────────────────────────────

export interface VAFScene {
  id: string;
  type: VAFSceneType;
  duration_ms: number;
  background: VAFBackground;
  atmosphere: VAFAtmosphere;
  camera: VAFCameraMovement;
  effects: VAFEffect[];
  narration: string;
  transition: VAFTransition;
}

// ── Animation Script ────────────────────────────

export interface AnimationScript {
  total_duration_ms: number;
  scenes: VAFScene[];
}
