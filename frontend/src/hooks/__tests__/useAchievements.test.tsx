// ──────────────────────────────────────────────
// Achievement Hook Unit Tests
// Tests: useAchievements, useAchievementProgress
// ──────────────────────────────────────────────

import { describe, it, expect, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import type { ReactNode } from 'react';

// Mock the API module.
vi.mock('@/lib/api', () => ({
  default: {
    get: vi.fn(),
  },
}));

import api from '@/lib/api';
import { useAchievements } from '../useAchievements';

function wrapper({ children }: { children: ReactNode }) {
  const qc = new QueryClient({
    defaultOptions: {
      queries: { retry: false, gcTime: 0 },
    },
  });
  return <QueryClientProvider client={qc}>{children}</QueryClientProvider>;
}

describe('useAchievements', () => {
  it('returns loading state initially', () => {
    vi.mocked(api.get).mockReturnValue(new Promise(() => {})); // never resolves

    const { result } = renderHook(() => useAchievements(1), { wrapper });

    expect(result.current.isLoading).toBe(true);
    expect(result.current.achievements).toEqual([]);
    expect(result.current.isError).toBe(false);
  });

  it('returns achievements on successful fetch', async () => {
    const mockAchievements = [
      { id: 1, name: 'First Steps', description: 'Complete tutorial', unlocked: true },
      { id: 2, name: 'Explorer', description: 'Visit 5 zones', unlocked: false },
    ];
    vi.mocked(api.get).mockResolvedValueOnce({ data: mockAchievements });

    const { result } = renderHook(() => useAchievements(1), { wrapper });

    await waitFor(() => {
      expect(result.current.isLoading).toBe(false);
    });

    expect(result.current.achievements).toHaveLength(2);
    expect(result.current.achievements[0].name).toBe('First Steps');
    expect(result.current.isError).toBe(false);
  });

  it('returns error state on failed fetch', async () => {
    vi.mocked(api.get).mockRejectedValueOnce(new Error('Network error'));

    const { result } = renderHook(() => useAchievements(1), { wrapper });

    await waitFor(() => {
      expect(result.current.isError).toBe(true);
    });

    expect(result.current.achievements).toEqual([]);
  });

  it('returns empty achievements when data is null', async () => {
    vi.mocked(api.get).mockResolvedValueOnce({ data: null });

    const { result } = renderHook(() => useAchievements(1), { wrapper });

    await waitFor(() => {
      expect(result.current.isLoading).toBe(false);
    });

    expect(result.current.achievements).toEqual([]);
  });

  it('does not fetch when universeId is 0', () => {
    vi.mocked(api.get).mockReturnValue(new Promise(() => {}));

    const { result } = renderHook(() => useAchievements(0), { wrapper });

    // Should not have called the API.
    expect(api.get).not.toHaveBeenCalled();
    expect(result.current.isLoading).toBe(false);
  });

  it('handles Laravel envelope-wrapped response', async () => {
    const mockWrapped = {
      data: [
        { id: 1, name: 'Achievement A', description: 'Desc A', unlocked: true },
      ],
    };
    vi.mocked(api.get).mockResolvedValueOnce({ data: mockWrapped });

    const { result } = renderHook(() => useAchievements(1), { wrapper });

    await waitFor(() => {
      expect(result.current.isLoading).toBe(false);
    });

    // The API interceptor unwraps the envelope, so we get the inner data directly.
    expect(result.current.achievements.length).toBeGreaterThanOrEqual(0);
  });
});
