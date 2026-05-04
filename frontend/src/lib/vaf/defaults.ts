// ──────────────────────────────────────────────
// VAF Default Constants
// Used as fallbacks when animation script fields are missing
// ──────────────────────────────────────────────

import type {
  VAFAtmosphere,
  VAFBackground,
  VAFCameraMovement,
  VAFTransition,
} from './types';

export const DEFAULT_ATMOSPHERE: VAFAtmosphere = {
  filter: 'none',
  intensity: 0,
  weather: null,
};

export const DEFAULT_CAMERA: VAFCameraMovement = {
  type: 'static',
  speed: 0.5,
  easing: 'linear',
};

export const DEFAULT_TRANSITION: VAFTransition = {
  type: 'fade',
  duration_ms: 800,
};

export const DEFAULT_BACKGROUND: VAFBackground = {
  type: 'solid',
  colors: ['#0d0d0d'],
  description: '',
};
