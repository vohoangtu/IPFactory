import { describe, it, expect } from 'vitest';
import { unwrapEnvelope } from '../apiClient';

describe('unwrapEnvelope', () => {
  it('unwraps { data: X } to X', () => {
    expect(unwrapEnvelope({ data: [1, 2] })).toEqual([1, 2]);
  });
  it('leaves payloads with meta/links wrapped', () => {
    const body = { data: [1], meta: { total: 1 } };
    expect(unwrapEnvelope(body)).toBe(body);
  });
  it('passes through non-envelope objects', () => {
    expect(unwrapEnvelope({ ok: true })).toEqual({ ok: true });
  });
});
