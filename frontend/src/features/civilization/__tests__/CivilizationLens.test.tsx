import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { CivilizationLens } from '../components/CivilizationLens';
import type { UniverseCivilization, UniverseWorldState } from '../types';

const civFixture: UniverseCivilization = {
  universe_id: 1,
  status: 'active',
  current_tick: 42,
  epoch: 2,
  metrics: { entropy: 0.42, stability_index: 0.7, structural_coherence: 0.6, fitness_score: 0.8 },
  complexity: { actor_count: 10, living_actor_count: 8, supreme_entity_count: 1 },
  snapshot: null,
};

const worldFixture: UniverseWorldState = {
  universe_id: 1,
  world_id: 5,
  epoch: { id: 2, name: 'Iron', theme: 'war', description: 'Kỷ nguyên sắt', start_tick: 10, end_tick: null, status: 'active' },
  religions: [{ id: 1, name: 'Solism', followers: 100, spread_rate: 0.1, doctrine: null }],
  treaties: [{ id: 1, treaty_type: 'peace', source_civ_id: 1, target_civ_id: 2, started_at_tick: 5, ends_at_tick: null }],
  technologies: [{ id: 1, name: 'fire', code: 'fire', adopters: 5, avg_level: 1.2 }],
};

const emptyWorld: UniverseWorldState = {
  universe_id: 1,
  world_id: 5,
  epoch: null,
  religions: [],
  treaties: [],
  technologies: [],
};

describe('CivilizationLens', () => {
  it('render metrics tiles, epoch, religion, technology', () => {
    render(<CivilizationLens civilization={civFixture} world={worldFixture} />);
    expect(screen.getByText('Entropy')).toBeTruthy();
    expect(screen.getByText('0.42')).toBeTruthy();
    expect(screen.getByText('Iron')).toBeTruthy();
    expect(screen.getByText('Solism')).toBeTruthy();
    expect(screen.getByText('fire')).toBeTruthy();
  });

  it('empty state khi religions/treaties/technologies rỗng', () => {
    render(<CivilizationLens civilization={civFixture} world={emptyWorld} />);
    expect(screen.getByText('Chưa ghi nhận tôn giáo.')).toBeTruthy();
    expect(screen.getByText('Chưa ghi nhận hiệp ước.')).toBeTruthy();
    expect(screen.getByText('Chưa ghi nhận công nghệ.')).toBeTruthy();
    expect(screen.getByText('Chưa ghi nhận kỷ nguyên.')).toBeTruthy();
  });

  it('null data → tiles hiện dấu gạch ngang, không crash', () => {
    render(<CivilizationLens civilization={null} world={null} />);
    expect(screen.getAllByText('—').length).toBeGreaterThan(0);
  });
});
