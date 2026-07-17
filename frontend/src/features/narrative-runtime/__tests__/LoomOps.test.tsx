import { describe, expect, it, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';

const mockRuntime = vi.hoisted(() => vi.fn());

vi.mock('../useNarrativeRuntime', () => ({
  useNarrativeRuntime: mockRuntime,
}));

import { LoomOps } from '../components/LoomOps';

function idleRuntime() {
  return {
    activeTaskId: null,
    worldId: null,
    isSubmitting: false,
    isWeaving: false,
    isRestoredSession: false,
    connectionState: 'connected',
    loomStatus: { status: 'online', agents: {}, providers: {} },
    isLoadingLoomStatus: false,
    currentAgent: null,
    progress: { completed: 0, total: 18, pct: 0 },
    pipelineNodes: {},
    logs: [],
    narrativeResult: null,
    intermediateOutputs: {},
    selectedNode: undefined,
    selectedNodeDetails: undefined,
    lastError: null,
    providerCount: 0,
    agentCount: 0,
    completedCount: 0,
    runningCount: 0,
    errorCount: 0,
    revisionCount: 0,
    startWeave: vi.fn(),
    clearTrackedSession: vi.fn(),
    chronicleId: null,
    refreshLoomStatus: vi.fn(),
    setSelectedNode: vi.fn(),
  };
}

describe('LoomOps', () => {
  it('render đủ 7 tab và tab Run mặc định active', () => {
    mockRuntime.mockReturnValue(idleRuntime());
    render(<LoomOps universeId={1} />);

    const expectedTabs = ['Run', 'Review', 'Monitor', 'Actor Intent', 'Scribe', 'Asset Forge', 'System'];
    for (const label of expectedTabs) {
      expect(screen.getByRole('tab', { name: label })).toBeTruthy();
    }
    expect(screen.getByRole('tab', { name: 'Run' }).getAttribute('aria-selected')).toBe('true');
    expect(screen.getByText('Run Chronicle Weave')).toBeTruthy();
  });

  it('đổi tab: click Review hiển thị nội dung Review, ẩn nội dung Run', () => {
    mockRuntime.mockReturnValue(idleRuntime());
    render(<LoomOps universeId={1} />);

    fireEvent.click(screen.getByRole('tab', { name: 'Review' }));

    expect(screen.getByText('No output yet. Start a weave from the Run tab.')).toBeTruthy();
    expect(screen.queryByText('Run Chronicle Weave')).toBeNull();
    expect(screen.getByRole('tab', { name: 'Review' }).getAttribute('aria-selected')).toBe('true');
    expect(screen.getByRole('tab', { name: 'Run' }).getAttribute('aria-selected')).toBe('false');
  });

  it('đổi tab: click Actor Intent hiển thị form actor', () => {
    mockRuntime.mockReturnValue(idleRuntime());
    render(<LoomOps universeId={1} />);

    fireEvent.click(screen.getByRole('tab', { name: 'Actor Intent' }));

    expect(screen.getByText('Actor Profile')).toBeTruthy();
  });

  it('universeId=null → hiển thị empty note, không render tab', () => {
    render(<LoomOps universeId={null} />);

    expect(screen.getByText('Chọn một universe để dệt biên niên sử.')).toBeTruthy();
    expect(screen.queryByRole('tab', { name: 'Run' })).toBeNull();
  });
});
