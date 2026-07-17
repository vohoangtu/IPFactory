import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen, waitFor, fireEvent } from '@testing-library/react';

const { mockMutateAsync, toastSuccess, toastError, toastInfo } = vi.hoisted(() => ({
  mockMutateAsync: vi.fn(),
  toastSuccess: vi.fn(),
  toastError: vi.fn(),
  toastInfo: vi.fn(),
}));

vi.mock('sonner', () => ({
  toast: { success: toastSuccess, error: toastError, info: toastInfo },
}));

vi.mock('@/features/ops-shell', () => ({
  UniverseSelect: () => null,
}));

vi.mock('@/features/narrative-runtime', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@/features/narrative-runtime')>();
  return {
    ...actual,
    NARRATIVE_PIPELINE_NODES: actual.NARRATIVE_PIPELINE_NODES.slice(0, 2),
    useNarrativeRuntime: () => ({ loomStatus: null }),
  };
});

vi.mock('@/features/admin', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@/features/admin')>();
  return {
    ...actual,
    useUpdateAiSetting: () => ({ mutateAsync: mockMutateAsync }),
    useLoomAgents: () => ({ data: [] }),
    useAiSettings: () => ({ data: [] }),
  };
});

// Import sau khi mock để component nhận đúng module đã mock.
import OpsSettingsPage from '@/app/(ops)/ops/settings/page';

describe('/ops/settings — save that qua useUpdateAiSetting', () => {
  beforeEach(() => {
    mockMutateAsync.mockReset();
    toastSuccess.mockReset();
    toastError.mockReset();
    toastInfo.mockReset();
  });

  it('click Save gọi mutateAsync 3 lần (2 agent + 1 epistemic) với key/value đúng', async () => {
    mockMutateAsync.mockResolvedValue({});

    render(<OpsSettingsPage />);
    fireEvent.click(screen.getByRole('button', { name: /save changes/i }));

    await waitFor(() => expect(mockMutateAsync).toHaveBeenCalledTimes(3));

    const calledKeys = mockMutateAsync.mock.calls.map(([payload]) => payload.key).sort();
    expect(calledKeys).toEqual(
      ['loom_agents.Event_Normalizer', 'loom_agents.Universe_Bridge', 'narrative.epistemic'].sort(),
    );

    const agentCall = mockMutateAsync.mock.calls.find(
      ([payload]) => payload.key === 'loom_agents.Event_Normalizer',
    )?.[0];
    expect(agentCall).toMatchObject({
      key: 'loom_agents.Event_Normalizer',
      group: 'loom_agents',
      value: {
        model: 'gpt-4o-mini',
        temperature: 0.7,
        max_tokens: 2048,
        retry_attempts: 3,
      },
    });

    const epistemicCall = mockMutateAsync.mock.calls.find(
      ([payload]) => payload.key === 'narrative.epistemic',
    )?.[0];
    expect(epistemicCall).toMatchObject({
      key: 'narrative.epistemic',
      group: 'narrative',
      value: { noise_level: 0.3, tier: 'historian', strict_mode: false },
    });

    await waitFor(() => expect(toastSuccess).toHaveBeenCalled());
    expect(toastError).not.toHaveBeenCalled();
  });

  it('mutateAsync reject -> hiện toast.error', async () => {
    mockMutateAsync.mockRejectedValue(new Error('network down'));

    render(<OpsSettingsPage />);
    fireEvent.click(screen.getByRole('button', { name: /save changes/i }));

    await waitFor(() => expect(toastError).toHaveBeenCalled());
    expect(toastSuccess).not.toHaveBeenCalled();
  });
});
