import { describe, it, expect } from 'vitest';
import { routes } from '../routes';

describe('routes', () => {
  it('builds live/replay/multiverse/actor URLs', () => {
    expect(routes.live(2)).toBe('/u/2/live');
    expect(routes.replay(2, 15)).toBe('/u/2/replay?tick=15');
    expect(routes.replay(2)).toBe('/u/2/replay');
    expect(routes.multiverse()).toBe('/multiverse');
    expect(routes.actor(2, 9)).toBe('/u/2/actor/9');
  });
});
