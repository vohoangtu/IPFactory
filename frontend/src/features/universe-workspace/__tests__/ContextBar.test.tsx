import { describe, it, expect, beforeEach, vi } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import { renderWithClient } from '@/test/render';
import { useSimStore } from '@/shared/store/simStore';

vi.mock('@/shared/lib/apiClient', () => ({
  TOKEN_KEY: 'worldos_token',
  apiClient: { get: vi.fn().mockResolvedValue({ data: [{ id: 2, world_id: 1, name: 'Demo World', status: 'active', current_tick: 15, era: 3 }] }) },
}));
vi.mock('next/navigation', () => ({ useRouter: () => ({ push: vi.fn(), replace: vi.fn() }) }));

import { ContextBar } from '../components/ContextBar';

describe('ContextBar', () => {
  beforeEach(() => useSimStore.getState().reset());

  it('shows selected universe + live tick from store', async () => {
    useSimStore.getState().selectUniverse(2);
    useSimStore.getState().applyTick({ tick: 42, status: 'active' });
    renderWithClient(<ContextBar />);
    await waitFor(() => expect(screen.getByText('Demo World')).toBeTruthy());
    expect(screen.getByText(/Tick 42/)).toBeTruthy();
  });
});
