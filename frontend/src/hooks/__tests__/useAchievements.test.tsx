// ──────────────────────────────────────────────
// Achievement Hook Unit Tests — useAchievements
// Hook trả thẳng kết quả useQuery; consumer dùng `data?.achievements ?? []`
// (xem AchievementGrid.tsx). Test bám đúng API đó.
// ──────────────────────────────────────────────

import { describe, it, expect, vi, beforeEach } from 'vitest';
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

// queryFn của hook trả về `res.data`, nên mock theo dạng { data: AchievementsResponse }.
function ok(achievements: Array<Record<string, unknown>>) {
  return {
    data: {
      achievements,
      stats: { total: achievements.length, unlocked: 0, claimed: 0 },
    },
  };
}

describe('useAchievements', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('returns loading state initially', () => {
    vi.mocked(api.get).mockReturnValue(new Promise(() => {})); // never resolves
    const { result } = renderHook(() => useAchievements(1), { wrapper });

    expect(result.current.isLoading).toBe(true);
    expect(result.current.data).toBeUndefined();
    expect(result.current.isError).toBe(false);
  });

  it('returns achievements on successful fetch', async () => {
    vi.mocked(api.get).mockResolvedValueOnce(
      ok([
        { id: 1, name: 'First Steps' },
        { id: 2, name: 'Explorer' },
      ]),
    );
    const { result } = renderHook(() => useAchievements(1), { wrapper });

    await waitFor(() => expect(result.current.isLoading).toBe(false));

    expect(result.current.data?.achievements).toHaveLength(2);
    expect(result.current.data?.achievements?.[0].name).toBe('First Steps');
    expect(result.current.isError).toBe(false);
  });

  it('returns error state on failed fetch', async () => {
    vi.mocked(api.get).mockRejectedValueOnce(new Error('Network error'));
    const { result } = renderHook(() => useAchievements(1), { wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.data).toBeUndefined();
  });

  it('returns empty achievements list when payload is empty', async () => {
    vi.mocked(api.get).mockResolvedValueOnce(ok([]));
    const { result } = renderHook(() => useAchievements(1), { wrapper });

    await waitFor(() => expect(result.current.isLoading).toBe(false));
    expect(result.current.data?.achievements).toEqual([]);
  });

  it('does not fetch when universeId is 0', () => {
    vi.mocked(api.get).mockReturnValue(new Promise(() => {}));
    const { result } = renderHook(() => useAchievements(0), { wrapper });

    expect(api.get).not.toHaveBeenCalled();
    expect(result.current.isLoading).toBe(false);
  });

  it('does not fetch when universeId is null', () => {
    vi.mocked(api.get).mockReturnValue(new Promise(() => {}));
    const { result } = renderHook(() => useAchievements(null), { wrapper });

    expect(api.get).not.toHaveBeenCalled();
    expect(result.current.isLoading).toBe(false);
  });
});
