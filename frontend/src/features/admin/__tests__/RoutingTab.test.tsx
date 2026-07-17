import { describe, expect, it, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import RoutingTab from '../components/ai-settings/RoutingTab';
import { NARRATIVE_PIPELINE_NODES } from '@/features/narrative-runtime';
import type { AgentConfig } from '../components/ai-settings/types';

const fixtureConfigs: AgentConfig[] = NARRATIVE_PIPELINE_NODES.map((node) => ({
  agentId: node.id,
  model: 'gpt-4o-mini',
  temperature: 0.7,
  maxTokens: 2048,
  retryAttempts: 3,
}));

describe('RoutingTab', () => {
  it('hiển thị danh sách agent theo phase (engine/agent) kèm role', () => {
    render(<RoutingTab agentConfigs={fixtureConfigs} update={vi.fn()} />);

    expect(screen.getByText('Event Normalizer')).toBeDefined();
    expect(screen.getByText('Normalize raw chronicles')).toBeDefined();
  });

  it('đổi model của một agent gọi update với agentId + patch tương ứng', () => {
    const update = vi.fn();
    render(<RoutingTab agentConfigs={fixtureConfigs} update={update} />);

    const selects = screen.getAllByRole('combobox');
    fireEvent.change(selects[0], { target: { value: 'gpt-4o' } });

    expect(update).toHaveBeenCalledWith('Event_Normalizer', { model: 'gpt-4o' });
  });

  it('hiển thị Provider Status khi có prop providers', () => {
    render(
      <RoutingTab
        agentConfigs={fixtureConfigs}
        update={vi.fn()}
        providers={{ openai: { status: 'ok' }, anthropic: { key_present: false } }}
      />,
    );

    expect(screen.getByText('Provider Status')).toBeDefined();
    expect(screen.getByText('openai')).toBeDefined();
    expect(screen.getByText('anthropic')).toBeDefined();
  });

  it('không hiển thị Provider Status khi thiếu prop providers', () => {
    render(<RoutingTab agentConfigs={fixtureConfigs} update={vi.fn()} />);
    expect(screen.queryByText('Provider Status')).toBeNull();
  });
});
