'use client';

import { useState, useEffect } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { RotateCcw, Save } from 'lucide-react';
import { toast } from 'sonner';
import { UniverseSelect } from '@/features/ops-shell';
import { useSimStore } from '@/shared/store/simStore';
import { useNarrativeRuntime, NARRATIVE_PIPELINE_NODES } from '@/features/narrative-runtime';
import { RoutingTab, ParamsTab, EpistemicTab, useUpdateAiSetting, useLoomAgents, useAiSettings } from '@/features/admin';
import type { AgentConfig, EpistemicConfig } from '@/features/admin';
import PanelButton from '@/shared/ui/PanelButton';
import { qk } from '@/shared/config/queryKeys';

const DEFAULT_AGENT_CONFIG = (id: string): AgentConfig => ({
  agentId: id,
  model: 'gpt-4o-mini',
  temperature: 0.7,
  maxTokens: 2048,
  retryAttempts: 3,
});

type ActiveSection = 'routing' | 'params' | 'epistemic';

const TABS: Array<{ id: ActiveSection; label: string }> = [
  { id: 'routing', label: 'LLM Routing' },
  { id: 'params', label: 'Agent Parameters' },
  { id: 'epistemic', label: 'Epistemic Layer' },
];

export default function OpsSettingsPage() {
  const queryClient = useQueryClient();
  const universeId = useSimStore((s) => s.selectedUniverseId);
  const runtime = useNarrativeRuntime(universeId);
  const updateSetting = useUpdateAiSetting();
  const { data: loomAgentRecords } = useLoomAgents();
  const { data: aiSettings } = useAiSettings();
  const [activeSection, setActiveSection] = useState<ActiveSection>('routing');
  const [expandedAgent, setExpandedAgent] = useState<string | null>(null);
  const [isSaving, setIsSaving] = useState(false);

  const [agentConfigs, setAgentConfigs] = useState<AgentConfig[]>(
    NARRATIVE_PIPELINE_NODES.map((n) => DEFAULT_AGENT_CONFIG(n.id)),
  );

  const [epistemic, setEpistemic] = useState<EpistemicConfig>({
    noiseLevel: 0.3,
    tier: 'historian',
    strictMode: false,
  });

  // Sync models from loom status
  useEffect(() => {
    if (!runtime.loomStatus?.agents) return;
    setAgentConfigs((prev) =>
      prev.map((cfg) => {
        const loomAgent = runtime.loomStatus!.agents[cfg.agentId];
        if (loomAgent?.model && loomAgent.model !== cfg.model) {
          return { ...cfg, model: loomAgent.model };
        }
        return cfg;
      }),
    );
  }, [runtime.loomStatus]);

  // Hydrate agentConfigs từ bản đã lưu (một lần khi data về; loomStatus sync vẫn giữ nguyên effect cũ)
  useEffect(() => {
    if (!loomAgentRecords?.length) return;
    setAgentConfigs((prev) =>
      prev.map((cfg) => {
        const saved = loomAgentRecords.find((r) => r.key === `loom_agents.${cfg.agentId}`)?.value as
          | { model?: string; temperature?: number; max_tokens?: number; retry_attempts?: number }
          | undefined;
        return saved
          ? {
              ...cfg,
              model: saved.model ?? cfg.model,
              temperature: saved.temperature ?? cfg.temperature,
              maxTokens: saved.max_tokens ?? cfg.maxTokens,
              retryAttempts: saved.retry_attempts ?? cfg.retryAttempts,
            }
          : cfg;
      }),
    );
  }, [loomAgentRecords]);

  // Hydrate epistemic từ key narrative.epistemic
  useEffect(() => {
    const rec = aiSettings?.find((s) => s.key === 'narrative.epistemic');
    if (!rec) return;
    const v = rec.value as { noise_level?: number; tier?: EpistemicConfig['tier']; strict_mode?: boolean };
    setEpistemic((prev) => ({
      noiseLevel: v.noise_level ?? prev.noiseLevel,
      tier: v.tier ?? prev.tier,
      strictMode: v.strict_mode ?? prev.strictMode,
    }));
  }, [aiSettings]);

  const update = (agentId: string, patch: Partial<AgentConfig>) => {
    setAgentConfigs((prev) => prev.map((c) => (c.agentId === agentId ? { ...c, ...patch } : c)));
  };

  const handleSave = async () => {
    setIsSaving(true);
    try {
      await Promise.all([
        ...agentConfigs.map((cfg) => {
          const existing = loomAgentRecords?.find((r) => r.key === `loom_agents.${cfg.agentId}`)?.value;
          const preserved = typeof existing === 'object' && existing !== null ? existing : {};
          return updateSetting.mutateAsync({
            key: `loom_agents.${cfg.agentId}`,
            group: 'loom_agents',
            value: {
              ...preserved,
              model: cfg.model,
              temperature: cfg.temperature,
              max_tokens: cfg.maxTokens,
              retry_attempts: cfg.retryAttempts,
            },
            silent: true,
          });
        }),
        updateSetting.mutateAsync({
          key: 'narrative.epistemic',
          group: 'narrative',
          value: { noise_level: epistemic.noiseLevel, tier: epistemic.tier, strict_mode: epistemic.strictMode },
          silent: true,
        }),
      ]);
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: qk.aiSettings() }),
        queryClient.invalidateQueries({ queryKey: qk.loomAgents() }),
      ]);
      toast.success('Đã lưu cấu hình.');
    } catch {
      toast.error('Lưu thất bại.');
    } finally {
      setIsSaving(false);
    }
  };

  const handleReset = () => {
    setAgentConfigs(NARRATIVE_PIPELINE_NODES.map((n) => DEFAULT_AGENT_CONFIG(n.id)));
    setEpistemic({ noiseLevel: 0.3, tier: 'historian', strictMode: false });
    toast.info('Reset to defaults.');
  };

  return (
    <div className="mx-auto flex max-w-5xl flex-col gap-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="font-mono text-xs uppercase tracking-[0.3em] text-[var(--color-text-muted)]">Cấu hình AI</h1>
        <div className="flex flex-wrap items-center gap-2">
          <UniverseSelect />
          <PanelButton variant="secondary" size="sm" onClick={handleReset}>
            <RotateCcw size={14} />
            Reset
          </PanelButton>
          <PanelButton variant="primary" size="sm" onClick={handleSave} disabled={isSaving}>
            <Save size={14} />
            {isSaving ? 'Saving…' : 'Save Changes'}
          </PanelButton>
        </div>
      </div>

      <div role="tablist" aria-label="AI Configuration" className="flex border-b border-border-subtle">
        {TABS.map((tab) => (
          <button
            key={tab.id}
            type="button"
            role="tab"
            aria-selected={activeSection === tab.id}
            onClick={() => setActiveSection(tab.id)}
            className={`border-b-2 px-4 py-2.5 text-sm font-medium transition ${
              activeSection === tab.id
                ? 'border-brand-info text-brand-info'
                : 'border-transparent text-text-disabled hover:text-text-secondary'
            }`}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {activeSection === 'routing' && (
        <RoutingTab agentConfigs={agentConfigs} update={update} providers={runtime.loomStatus?.providers} />
      )}

      {activeSection === 'params' && (
        <ParamsTab
          agentConfigs={agentConfigs}
          update={update}
          expandedAgent={expandedAgent}
          setExpandedAgent={setExpandedAgent}
        />
      )}

      {activeSection === 'epistemic' && (
        <EpistemicTab config={epistemic} onChange={(patch) => setEpistemic((e) => ({ ...e, ...patch }))} />
      )}
    </div>
  );
}
