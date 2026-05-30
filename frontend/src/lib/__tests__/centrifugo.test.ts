// ──────────────────────────────────────────────
// Centrifugo Client Unit Tests
// Tests: singleton pattern, token fetching, cleanup
// ──────────────────────────────────────────────

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

// Mock the centrifuge library
vi.mock('centrifuge', () => ({
  Centrifuge: vi.fn().mockImplementation(function (url: string, opts: Record<string, unknown>) {
    return {
      _url: url,
      _opts: opts,
      disconnect: vi.fn(),
      connect: vi.fn(),
      on: vi.fn(),
    };
  }),
}));

// Mock api
vi.mock('@/lib/api', () => ({
  default: {
    post: vi.fn().mockResolvedValue({ data: { token: 'test-centrifugo-jwt' } }),
  },
}));

import { getCentrifuge, resetCentrifuge } from '../centrifugo';
import { Centrifuge } from 'centrifuge';
import api from '@/lib/api';

describe('Centrifugo Client', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    resetCentrifuge();
  });

  afterEach(() => {
    resetCentrifuge();
  });

  describe('getCentrifuge', () => {
    it('creates a Centrifuge instance with correct URL', () => {
      const client = getCentrifuge();
      expect(Centrifuge).toHaveBeenCalledTimes(1);
      const callArgs = (Centrifuge as unknown as vi.Mock).mock.calls[0];
      expect(callArgs[0]).toContain('ws://');
      expect(client).toBeDefined();
    });

    it('returns the same singleton instance on consecutive calls', () => {
      const client1 = getCentrifuge();
      const client2 = getCentrifuge();
      expect(client1).toBe(client2);
      expect(Centrifuge).toHaveBeenCalledTimes(1);
    });
  });

  describe('resetCentrifuge', () => {
    it('disconnects and clears the singleton', () => {
      const client = getCentrifuge();
      resetCentrifuge();
      expect(client.disconnect).toHaveBeenCalledTimes(1);

      // Second call should create a new instance.
      const client2 = getCentrifuge();
      expect(Centrifuge).toHaveBeenCalledTimes(2);
      expect(client2).not.toBe(client);
    });

    it('is safe to call when no instance exists', () => {
      expect(() => resetCentrifuge()).not.toThrow();
    });
  });

  describe('token fetching', () => {
    it('provides getToken callback that calls the backend API', async () => {
      const client = getCentrifuge();
      const callArgs = (Centrifuge as unknown as vi.Mock).mock.calls[0];
      const getTokenFn = callArgs[1]?.getToken;

      expect(getTokenFn).toBeDefined();

      const token = await getTokenFn();
      expect(token).toBe('test-centrifugo-jwt');
      expect(api.post).toHaveBeenCalledWith('/worldos/centrifugo/token');
    });

    it('returns empty string when token API fails', async () => {
      vi.mocked(api.post).mockRejectedValueOnce(new Error('Network error'));

      const client = getCentrifuge();
      const callArgs = (Centrifuge as unknown as vi.Mock).mock.calls[0];
      const getTokenFn = callArgs[1]?.getToken;

      const token = await getTokenFn();
      expect(token).toBe('');
    });
  });
});
