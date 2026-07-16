import { describe, expect, it, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { useSimStore } from '@/shared/store/simStore';

vi.mock('@/features/universe-workspace', () => ({
  useUniverses: () => ({ data: [
    { id: 1, world_id: 1, name: 'Alpha', status: 'active', current_tick: 5, era: 1 },
    { id: 2, world_id: 1, name: 'Beta', status: 'halted', current_tick: 9, era: 2 },
  ], isLoading: false, isError: false }),
}));
import { UniverseSelect } from '../components/UniverseSelect';

describe('UniverseSelect', () => {
  it('chọn universe → set simStore.selectedUniverseId', () => {
    useSimStore.getState().reset();
    render(<UniverseSelect />);
    fireEvent.change(screen.getByRole('combobox', { name: 'Chọn Universe' }), { target: { value: '2' } });
    expect(useSimStore.getState().selectedUniverseId).toBe(2);
  });
});
