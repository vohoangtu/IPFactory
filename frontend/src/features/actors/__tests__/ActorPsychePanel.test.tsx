import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { ActorPsychePanel } from '../components/ActorPsychePanel';
import type { ActorPsyche } from '../types';

const psycheFixture: ActorPsyche = {
  actor: { id: 3, universe_id: 1, name: 'Aria', archetype: 'sage', is_alive: true, life_stage: 'adult' },
  emotions: { fear: 0.9, anger: 0.1, sadness: 0.2, joy: 0.3, stress: 0.8, trust: 0.4 },
  needs: { survive: 1.03, safety: 0.64, belong: 0.48, esteem: 0.26 },
  goals: [{ type: 'survive', priority: 1.03 }],
  trait_vector: [0.5],
  recent_decisions: [],
};

describe('ActorPsychePanel', () => {
  it('render emotions dạng meter + goals + decisions', () => {
    render(<ActorPsychePanel psyche={psycheFixture} isLoading={false} />);
    expect(screen.getByText('Aria')).toBeTruthy();
    expect(screen.getByRole('meter', { name: /fear/i })).toBeTruthy();
    expect(screen.getByText('survive')).toBeTruthy();
  });

  it('loading state', () => {
    render(<ActorPsychePanel psyche={null} isLoading />);
    expect(screen.getByText(/Đang đọc tâm trí/i)).toBeTruthy();
  });
});
