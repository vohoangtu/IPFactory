import { describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';

const mockUse = vi.fn();
vi.mock('../hooks', () => ({ useSupremeEntities: (id: number | null) => mockUse(id) }));
import { NotableActorsPanel } from '../components/NotableActorsPanel';

const entity = (id: number, name: string, power: number) => ({
  id, name, entity_type: 'ascended', domain: 'war', power_level: power, alignment: {}, status: 'active', actor_id: id,
});

describe('NotableActorsPanel', () => {
  it('render top 5 theo power_level DESC + link lens actors', () => {
    mockUse.mockReturnValue({ entities: [entity(1, 'Alpha', 0.2), entity(2, 'Omega', 0.9), ...[3, 4, 5, 6].map((i) => entity(i, `E${i}`, 0.5))], isLoading: false, isError: false });
    render(<NotableActorsPanel universeId={7} />);
    const items = screen.getAllByRole('listitem');
    expect(items).toHaveLength(5);
    expect(items[0].textContent).toContain('Omega');
    expect(screen.getByRole('link', { name: /Xem lens Actors/i }).getAttribute('href')).toBe('/u/7/actors');
  });
  it('empty state', () => {
    mockUse.mockReturnValue({ entities: [], isLoading: false, isError: false });
    render(<NotableActorsPanel universeId={7} />);
    expect(screen.getByText(/Chưa có thực thể nổi bật/i)).toBeTruthy();
  });
});
