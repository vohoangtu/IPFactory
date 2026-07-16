import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { ConstellationView } from '../components/ConstellationView';
import type { MultiverseBloom, MultiverseResonance } from '@/shared/types/api';

const bloom: MultiverseBloom = {
  id: 'm1', label: 'Multiverse', sub: '',
  worlds: [{
    id: 'w1', label: 'Terra', genre: 'fantasy', sci: 0.5, status: 'active',
    universes: [
      { id: '7', label: 'U7', sub: '', status: 'active', sci: 0.6, parentUniverseId: null, saliency: 0.9 },
      { id: '8', label: 'U8', sub: '', status: 'halted', sci: 0.2, parentUniverseId: '7', saliency: 0.3 },
    ],
  }],
};
const resonance: MultiverseResonance = { resonance_pollen: [], global_narrative_entropy: 0.42 };

describe('ConstellationView', () => {
  it('render mỗi universe một link-sao trỏ về hero', () => {
    render(<ConstellationView bloom={bloom} resonance={resonance} />);
    expect(screen.getByRole('link', { name: /U7/ }).getAttribute('href')).toBe('/u/7');
    expect(screen.getByRole('link', { name: /U8/ }).getAttribute('href')).toBe('/u/8');
    expect(screen.getByText(/0.42/)).toBeTruthy(); // global narrative entropy
  });
  it('bloom rỗng → null', () => {
    const { container } = render(<ConstellationView bloom={{ ...bloom, worlds: [] }} resonance={null} />);
    expect(container.firstChild).toBeNull();
  });
});
