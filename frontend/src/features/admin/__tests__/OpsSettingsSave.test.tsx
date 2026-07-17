import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen, waitFor, fireEvent } from '@testing-library/react';

const { mockMutateAsync, mockUseLoomAgents, mockUseAiSettings, toastSuccess, toastError, toastInfo } = vi.hoisted(
  () => ({
    mockMutateAsync: vi.fn(),
    mockUseLoomAgents: vi.fn(),
    mockUseAiSettings: vi.fn(),
    toastSuccess: vi.fn(),
    toastError: vi.fn(),
    toastInfo: vi.fn(),
  }),
);

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
    useLoomAgents: mockUseLoomAgents,
    useAiSettings: mockUseAiSettings,
  };
});

// Import sau khi mock để component nhận đúng module đã mock.
import OpsSettingsPage from '@/app/(ops)/ops/settings/page';

describe('/ops/settings — save that qua useUpdateAiSetting', () => {
  beforeEach(() => {
    mockMutateAsync.mockReset();
    mockUseLoomAgents.mockReset().mockReturnValue({ data: [] });
    mockUseAiSettings.mockReset().mockReturnValue({ data: [] });
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

  it('merge-preserve: giu tier/provider cu tu loomAgentRecords khi save, chi ghi de field UI so huu (model/temperature/...)', async () => {
    mockUseLoomAgents.mockReturnValue({
      data: [
        {
          id: 1,
          key: 'loom_agents.Event_Normalizer',
          agent_name: 'Event_Normalizer',
          value: { tier: 'pro', provider: 'openrouter', model: 'old-model' },
          group: 'loom_agents',
          is_secret: false,
          created_at: '2026-01-01T00:00:00Z',
          updated_at: '2026-01-01T00:00:00Z',
        },
      ],
    });
    mockMutateAsync.mockResolvedValue({});

    render(<OpsSettingsPage />);

    // Doi model tren UI (routing tab la default) de mo phong nguoi dung sua sau khi hydrate.
    const selects = screen.getAllByRole('combobox');
    fireEvent.change(selects[0], { target: { value: 'gpt-4o' } });

    fireEvent.click(screen.getByRole('button', { name: /save changes/i }));

    await waitFor(() => expect(mockMutateAsync).toHaveBeenCalledTimes(3));

    const agentCall = mockMutateAsync.mock.calls.find(
      ([payload]) => payload.key === 'loom_agents.Event_Normalizer',
    )?.[0];

    // tier/provider tu record cu duoc giu nguyen (khong bi merge-drop).
    expect(agentCall.value).toMatchObject({ tier: 'pro', provider: 'openrouter' });
    // model la gia tri hien tai cua form (da doi qua UI), khong phai gia tri cu 'old-model'.
    expect(agentCall.value.model).toBe('gpt-4o');
    expect(agentCall.value.temperature).toBe(0.7);
    expect(agentCall.value.max_tokens).toBe(2048);
    expect(agentCall.value.retry_attempts).toBe(3);
  });
});
