import { describe, it, expect } from 'vitest';
import { routes } from '../routes';

describe('routes', () => {
  it('builds login/multiverse/universe URLs', () => {
    expect(routes.login()).toBe('/login');
    expect(routes.multiverse()).toBe('/multiverse');
    expect(routes.universe(2)).toBe('/u/2');
  });
});
