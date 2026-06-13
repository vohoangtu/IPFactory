import { describe, it, expect, beforeEach, vi } from 'vitest';
import { renderHook, act, waitFor } from '@testing-library/react';

vi.mock('@/shared/lib/apiClient', () => ({
  TOKEN_KEY: 'worldos_token',
  apiClient: { post: vi.fn().mockResolvedValue({ data: { token: 'tok123' } }) },
}));

import { useAuth } from '../hooks/useAuth';

describe('useAuth', () => {
  beforeEach(() => localStorage.clear());

  it('starts unauthenticated, logs in and stores token', async () => {
    const { result } = renderHook(() => useAuth());
    expect(result.current.isAuthenticated).toBe(false);
    await act(async () => { await result.current.login('a@b.com', 'pw'); });
    await waitFor(() => expect(result.current.isAuthenticated).toBe(true));
    expect(localStorage.getItem('worldos_token')).toBe('tok123');
  });
});
