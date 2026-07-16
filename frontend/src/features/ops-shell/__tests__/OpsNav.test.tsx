import { describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
vi.mock('next/navigation', () => ({ usePathname: () => '/ops/loom' }));
import { OpsNav } from '../components/OpsNav';

describe('OpsNav', () => {
  it('render 6 tab ops, tab hiện tại có aria-current', () => {
    render(<OpsNav />);
    expect(screen.getAllByRole('link')).toHaveLength(6);
    const loom = screen.getByRole('link', { name: 'Loom' });
    expect(loom.getAttribute('href')).toBe('/ops/loom');
    expect(loom.getAttribute('aria-current')).toBe('page');
    expect(screen.getByRole('link', { name: 'Simulation' }).getAttribute('href')).toBe('/ops/simulation');
    expect(screen.getByRole('link', { name: 'Intelligence' }).getAttribute('href')).toBe('/ops/intelligence');
  });
});
