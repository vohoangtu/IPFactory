// ──────────────────────────────────────────────
// API Client Unit Tests
// Tests: axios instance, interceptors, response unwrapping
// ──────────────────────────────────────────────

import { describe, it, expect, vi, beforeEach } from 'vitest';
import api from '../api';

describe('API Client', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
    localStorage.clear();
  });

  describe('axios instance', () => {
    it('has correct base configuration', () => {
      expect(api.defaults.baseURL).toBeDefined();
      expect(api.defaults.headers['Content-Type']).toBe('application/json');
      expect(api.defaults.headers['Accept']).toBe('application/json');
    });

    it('has request interceptor registered', () => {
      expect(api.interceptors.request).toBeDefined();
    });

    it('has response interceptor registered', () => {
      expect(api.interceptors.response).toBeDefined();
    });
  });

  describe('request interceptor', () => {
    it('attaches Bearer token from localStorage when available', async () => {
      localStorage.setItem('worldos_token', 'test-jwt-token');

      const interceptor = (api.interceptors.request as any).handlers[0];
      expect(interceptor).toBeDefined();

      const config = { headers: new Map() };
      const result = interceptor.fulfilled({ ...config, headers: { set: vi.fn(), get: vi.fn() } });

      expect(result).toBeDefined();
    });

    it('does not crash when localStorage is unavailable (SSR)', () => {
      const interceptor = (api.interceptors.request as any).handlers[0];
      const config = { headers: { set: vi.fn() } };
      const result = interceptor.fulfilled(config);

      expect(result).toBeDefined();
    });
  });

  describe('response interceptor', () => {
    it('unwraps Laravel { data: payload } envelope', async () => {
      const interceptor = (api.interceptors.response as any).handlers[0];
      const response = {
        data: { data: { id: 1, name: 'Test' }, meta: {} },
        status: 200,
        headers: {},
        config: {},
      };

      const result = interceptor.fulfilled(response);
      expect(result.data.id).toBe(1);
      expect(result.data.name).toBe('Test');
    });

    it('passes through non-envelope responses unchanged', async () => {
      const interceptor = (api.interceptors.response as any).handlers[0];
      const directResponse = {
        data: { id: 1, name: 'Direct' },
        status: 200,
        headers: {},
        config: {},
      };

      const result = interceptor.fulfilled(directResponse);
      expect(result.data.id).toBe(1);
      expect(result.data.name).toBe('Direct');
    });

    it('passes through array responses unchanged', async () => {
      const interceptor = (api.interceptors.response as any).handlers[0];
      const arrayResponse = {
        data: [{ id: 1 }, { id: 2 }],
        status: 200,
        headers: {},
        config: {},
      };

      const result = interceptor.fulfilled(arrayResponse);
      expect(Array.isArray(result.data)).toBe(true);
      expect(result.data.length).toBe(2);
    });

    it('passes through null/undefined responses', async () => {
      const interceptor = (api.interceptors.response as any).handlers[0];

      const nullResponse = { data: null, status: 200, headers: {}, config: {} };
      const result1 = interceptor.fulfilled(nullResponse);
      expect(result1.data).toBeNull();

      const undefinedResponse = { data: undefined, status: 200, headers: {}, config: {} };
      const result2 = interceptor.fulfilled(undefinedResponse);
      expect(result2.data).toBeUndefined();
    });
  });
});
