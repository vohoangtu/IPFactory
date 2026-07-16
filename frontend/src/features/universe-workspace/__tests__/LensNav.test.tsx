import { describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import { LensNav } from '../components/LensNav';

vi.mock('next/navigation', () => ({ usePathname: () => '/u/7/actors' }));

describe('LensNav', () => {
  it('render 5 lens, đánh dấu lens hiện tại bằng aria-current', () => {
    render(<LensNav universeId={7} />);
    const nav = screen.getByRole('navigation', { name: /lens/i });
    expect(nav).toBeTruthy();
    expect(screen.getByRole('link', { name: 'Biên niên sử' }).getAttribute('href')).toBe('/u/7');
    const actors = screen.getByRole('link', { name: 'Actors' });
    expect(actors.getAttribute('href')).toBe('/u/7/actors');
    expect(actors.getAttribute('aria-current')).toBe('page');
    expect(screen.getByRole('link', { name: 'Văn minh' }).getAttribute('href')).toBe('/u/7/civilization');
    expect(screen.getByRole('link', { name: 'Nhân quả' }).getAttribute('href')).toBe('/u/7/causality');
    expect(screen.getByRole('link', { name: 'Wavefunction' }).getAttribute('href')).toBe('/u/7/wavefunction');
  });
});
