// ──────────────────────────────────────────────
// VAF Timeline State Machine
// Manages playback state via a pure reducer
// ──────────────────────────────────────────────

// ── Types ───────────────────────────────────────

export type TimelineStatus = 'idle' | 'playing' | 'paused' | 'ended';

export interface TimelineState {
  status: TimelineStatus;
  currentSceneIndex: number;
  elapsedMs: number;
  totalDurationMs: number;
}

export type TimelineAction =
  | { type: 'PLAY' }
  | { type: 'PAUSE' }
  | { type: 'SEEK'; ms: number }
  | { type: 'TICK'; deltaMs: number }
  | { type: 'RESTART' };

// ── Helpers ─────────────────────────────────────

/**
 * Given an elapsed time and an array of scene durations,
 * returns the index of the scene that should be active.
 */
export function computeSceneIndex(elapsedMs: number, sceneDurations: number[]): number {
  if (sceneDurations.length === 0) return 0;

  let accumulated = 0;
  for (let i = 0; i < sceneDurations.length; i++) {
    accumulated += sceneDurations[i];
    if (elapsedMs < accumulated) return i;
  }

  // Past the end — return last scene index
  return sceneDurations.length - 1;
}

/**
 * Given an elapsed time and an array of scene durations,
 * returns how many ms have elapsed within the current scene.
 */
export function computeSceneElapsedMs(elapsedMs: number, sceneDurations: number[]): number {
  if (sceneDurations.length === 0) return 0;

  let accumulated = 0;
  for (let i = 0; i < sceneDurations.length; i++) {
    const sceneEnd = accumulated + sceneDurations[i];
    if (elapsedMs < sceneEnd) {
      return elapsedMs - accumulated;
    }
    accumulated = sceneEnd;
  }

  // Past the end — return duration of last scene
  return sceneDurations[sceneDurations.length - 1];
}

// ── Reducer ─────────────────────────────────────

export function timelineReducer(state: TimelineState, action: TimelineAction): TimelineState {
  switch (action.type) {
    case 'PLAY': {
      if (state.status === 'ended') {
        // Restart from beginning if ended
        return { ...state, status: 'playing', elapsedMs: 0, currentSceneIndex: 0 };
      }
      if (state.status === 'playing') return state;
      return { ...state, status: 'playing' };
    }

    case 'PAUSE': {
      if (state.status !== 'playing') return state;
      return { ...state, status: 'paused' };
    }

    case 'SEEK': {
      const ms = Math.max(0, Math.min(action.ms, state.totalDurationMs));
      const ended = ms >= state.totalDurationMs;
      return {
        ...state,
        elapsedMs: ms,
        status: ended ? 'ended' : (state.status === 'idle' ? 'idle' : state.status),
      };
    }

    case 'TICK': {
      if (state.status !== 'playing') return state;

      const newElapsed = state.elapsedMs + action.deltaMs;
      if (newElapsed >= state.totalDurationMs) {
        return {
          ...state,
          elapsedMs: state.totalDurationMs,
          status: 'ended',
        };
      }
      return { ...state, elapsedMs: newElapsed };
    }

    case 'RESTART': {
      return {
        ...state,
        status: 'idle',
        elapsedMs: 0,
        currentSceneIndex: 0,
      };
    }

    default:
      return state;
  }
}
