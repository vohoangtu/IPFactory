import { beforeEach, describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { renderWithClient } from '@/test/render';
import { makeFakeCentrifugeMulti } from '@/test/fakeCentrifuge';

const fake = makeFakeCentrifugeMulti();
vi.mock('@/shared/lib/centrifugo', () => ({ getCentrifuge: () => fake.centrifuge }));
vi.mock('next/navigation', () => ({
  useRouter: () => ({ push: vi.fn(), replace: vi.fn() }),
  useParams: () => ({ id: '5' }),
}));
vi.mock('@/shared/lib/apiClient', () => ({
  TOKEN_KEY: 'worldos_token',
  apiClient: {
    get: vi.fn().mockImplementation((url: string) => {
      if (url.includes('/feed')) {
        return Promise.resolve({ data: { data: [{
          id: 'h1', kind: 'chronicle', type: 'chronicle', tick: 5, universe_id: 5,
          severity: 'notable', occurred_at: '2026-07-15T00:00:00+00:00',
          payload: { chronicle_id: 1, content: 'Khởi nguyên của Aurora', importance: 0.8, has_animation: false },
        }], meta: { count: 1, next_before_tick: null } } });
      }
      return Promise.resolve({ data: [] });
    }),
  },
}));

import UniverseHeroPage from '../u/[id]/page';
import { useSimStore } from '@/shared/store/simStore';
import { useFeedStore } from '@/shared/store/feedStore';

describe('Hero Living Chronicle', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    useSimStore.getState().reset();
    useFeedStore.getState().clear();
  });

  it('render chronicle từ feed và nhận sự kiện realtime mới', async () => {
    renderWithClient(<UniverseHeroPage />);
    expect(await screen.findByText('Khởi nguyên của Aurora')).toBeTruthy();

    fake.emit('universes:5:anomaly', {
      id: 'live-a1', type: 'anomaly.detected', tick: 9, universe_id: 5, world_id: 3,
      severity: 'critical', occurred_at: '2026-07-15T00:01:00+00:00',
      payload: { title: 'Entropy spike', description: 'x' },
    });
    expect(await screen.findByText('Entropy spike')).toBeTruthy();
  });

  it('chọn universe từ params vào store', async () => {
    renderWithClient(<UniverseHeroPage />);
    await screen.findByText('Khởi nguyên của Aurora');
    expect(useSimStore.getState().selectedUniverseId).toBe(5);
  });
});
