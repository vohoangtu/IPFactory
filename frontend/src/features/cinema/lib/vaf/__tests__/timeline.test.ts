// ──────────────────────────────────────────────
// VAF Timeline Reducer Unit Tests
// Tests: timelineReducer + computeSceneIndex + computeSceneElapsedMs
// ──────────────────────────────────────────────

import {
  timelineReducer,
  computeSceneIndex,
  computeSceneElapsedMs,
  type TimelineState,
} from '../timeline';

// ── Helpers ──────────────────────────────────

function makeState(overrides: Partial<TimelineState> = {}): TimelineState {
  return {
    status: 'idle',
    currentSceneIndex: 0,
    elapsedMs: 0,
    totalDurationMs: 10000,
    ...overrides,
  };
}

// ── timelineReducer Tests ─────────────────────

describe('timelineReducer', () => {
  describe('PLAY', () => {
    it('changes status from idle → playing', () => {
      const state = makeState({ status: 'idle' });
      const next = timelineReducer(state, { type: 'PLAY' });
      expect(next.status).toBe('playing');
    });

    it('is a no-op when already playing', () => {
      const state = makeState({ status: 'playing' });
      const next = timelineReducer(state, { type: 'PLAY' });
      expect(next).toBe(state); // same reference
    });

    it('restarts from 0 when status is ended', () => {
      const state = makeState({ status: 'ended', elapsedMs: 10000 });
      const next = timelineReducer(state, { type: 'PLAY' });
      expect(next.status).toBe('playing');
      expect(next.elapsedMs).toBe(0);
      expect(next.currentSceneIndex).toBe(0);
    });

    it('resumes from paused', () => {
      const state = makeState({ status: 'paused', elapsedMs: 3000 });
      const next = timelineReducer(state, { type: 'PLAY' });
      expect(next.status).toBe('playing');
      expect(next.elapsedMs).toBe(3000); // does not reset
    });
  });

  describe('PAUSE', () => {
    it('pauses from playing', () => {
      const state = makeState({ status: 'playing' });
      const next = timelineReducer(state, { type: 'PAUSE' });
      expect(next.status).toBe('paused');
    });

    it('is a no-op when already paused', () => {
      const state = makeState({ status: 'paused' });
      const next = timelineReducer(state, { type: 'PAUSE' });
      expect(next).toBe(state);
    });

    it('is a no-op when idle', () => {
      const state = makeState({ status: 'idle' });
      const next = timelineReducer(state, { type: 'PAUSE' });
      expect(next).toBe(state);
    });
  });

  describe('TICK', () => {
    it('advances elapsedMs when playing', () => {
      const state = makeState({ status: 'playing', elapsedMs: 0 });
      const next = timelineReducer(state, { type: 'TICK', deltaMs: 500 });
      expect(next.elapsedMs).toBe(500);
      expect(next.status).toBe('playing');
    });

    it('is a no-op when paused', () => {
      const state = makeState({ status: 'paused', elapsedMs: 3000 });
      const next = timelineReducer(state, { type: 'TICK', deltaMs: 500 });
      expect(next).toBe(state);
    });

    it('is a no-op when idle', () => {
      const state = makeState({ status: 'idle' });
      const next = timelineReducer(state, { type: 'TICK', deltaMs: 500 });
      expect(next).toBe(state);
    });

    it('transitions to ended when elapsed reaches totalDuration', () => {
      const state = makeState({ status: 'playing', elapsedMs: 9700, totalDurationMs: 10000 });
      const next = timelineReducer(state, { type: 'TICK', deltaMs: 500 });
      expect(next.status).toBe('ended');
      expect(next.elapsedMs).toBe(10000);
    });

    it('caps elapsedMs at totalDurationMs (no overshoot)', () => {
      const state = makeState({ status: 'playing', elapsedMs: 9900, totalDurationMs: 10000 });
      const next = timelineReducer(state, { type: 'TICK', deltaMs: 1000 });
      expect(next.elapsedMs).toBe(10000);
    });
  });

  describe('SEEK', () => {
    it('updates elapsedMs to target', () => {
      const state = makeState({ status: 'playing', elapsedMs: 0 });
      const next = timelineReducer(state, { type: 'SEEK', ms: 5000 });
      expect(next.elapsedMs).toBe(5000);
    });

    it('clamps seek to 0 minimum', () => {
      const state = makeState({ status: 'playing', elapsedMs: 2000 });
      const next = timelineReducer(state, { type: 'SEEK', ms: -500 });
      expect(next.elapsedMs).toBe(0);
    });

    it('clamps seek to totalDurationMs maximum', () => {
      const state = makeState({ status: 'playing', elapsedMs: 0, totalDurationMs: 10000 });
      const next = timelineReducer(state, { type: 'SEEK', ms: 99999 });
      expect(next.elapsedMs).toBe(10000);
    });

    it('marks status as ended when seeking to end', () => {
      const state = makeState({ status: 'playing', totalDurationMs: 10000 });
      const next = timelineReducer(state, { type: 'SEEK', ms: 10000 });
      expect(next.status).toBe('ended');
    });
  });

  describe('RESTART', () => {
    it('resets to idle from any state', () => {
      const state = makeState({ status: 'ended', elapsedMs: 10000, currentSceneIndex: 3 });
      const next = timelineReducer(state, { type: 'RESTART' });
      expect(next.status).toBe('idle');
      expect(next.elapsedMs).toBe(0);
      expect(next.currentSceneIndex).toBe(0);
    });
  });
});

// ── computeSceneIndex Tests ───────────────────

describe('computeSceneIndex', () => {
  const durations = [3000, 4000, 3000]; // total 10000ms

  it('returns 0 at the start', () => {
    expect(computeSceneIndex(0, durations)).toBe(0);
  });

  it('returns 0 during first scene', () => {
    expect(computeSceneIndex(1500, durations)).toBe(0);
  });

  it('returns 1 at transition boundary', () => {
    expect(computeSceneIndex(3000, durations)).toBe(1);
  });

  it('returns 1 during second scene', () => {
    expect(computeSceneIndex(5000, durations)).toBe(1);
  });

  it('returns last index when past end', () => {
    expect(computeSceneIndex(12000, durations)).toBe(2);
  });

  it('returns 0 for empty durations', () => {
    expect(computeSceneIndex(5000, [])).toBe(0);
  });
});

// ── computeSceneElapsedMs Tests ───────────────

describe('computeSceneElapsedMs', () => {
  const durations = [3000, 4000, 3000];

  it('returns elapsed within first scene', () => {
    expect(computeSceneElapsedMs(1500, durations)).toBe(1500);
  });

  it('returns elapsed within second scene (offset by first)', () => {
    expect(computeSceneElapsedMs(4000, durations)).toBe(1000); // 4000 - 3000 = 1000
  });

  it('returns 0 for empty durations', () => {
    expect(computeSceneElapsedMs(5000, [])).toBe(0);
  });

  it('returns last scene duration when past end', () => {
    expect(computeSceneElapsedMs(15000, durations)).toBe(3000); // last scene duration
  });
});
