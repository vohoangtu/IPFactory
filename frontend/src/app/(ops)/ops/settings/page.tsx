'use client';

import { useState, useEffect } from 'react';
import { RotateCcw, Save } from 'lucide-react';
import { toast } from 'sonner';
import { UniverseSelect } from '@/features/ops-shell';
import { useSimStore } from '@/shared/store/simStore';
import { useNarrativeRuntime, NARRATIVE_PIPELINE_NODES } from '@/features/narrative-runtime';
import { RoutingTab, ParamsTab, EpistemicTab } from '@/features/admin';
import type { AgentConfig, EpistemicConfig } from '@/features/admin';
import PanelButton from '@/shared/ui/PanelButton';

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
  const universeId = useSimStore((s) => s.selectedUniverseId);
  const runtime = useNarrativeRuntime(universeId);
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

  const update = (agentId: string, patch: Partial<AgentConfig>) => {
    setAgentConfigs((prev) => prev.map((c) => (c.agentId === agentId ? { ...c, ...patch } : c)));
  };

  const handleSave = async () => {
    setIsSaving(true);
    try {
      await new Promise((r) => setTimeout(r, 600));
      toast.success('Configuration saved.');
    } catch {
      toast.error('Failed to save.');
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
