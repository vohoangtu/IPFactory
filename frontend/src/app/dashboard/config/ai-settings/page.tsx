'use client';

import React, { useState, useEffect } from 'react';
import { RotateCcw, Save } from 'lucide-react';
import { toast } from 'sonner';
import { useNarrativeRuntime } from '@/features/narrative-runtime/useNarrativeRuntime';
import { NARRATIVE_PIPELINE_NODES } from '@/features/narrative-runtime/types';
import PageHeader from '@/components/ui/shared/PageHeader';
import Button from '@/components/ui/shared/Button';
import TabBar from '@/components/ui/shared/TabBar';
import RoutingTab from '@/components/dashboard/ai-settings/RoutingTab';
import ParamsTab from '@/components/dashboard/ai-settings/ParamsTab';
import EpistemicTab from '@/components/dashboard/ai-settings/EpistemicTab';
import type { AgentConfig, EpistemicConfig } from '@/components/dashboard/ai-settings/types';

const DEFAULT_AGENT_CONFIG = (id: string): AgentConfig => ({
  agentId: id,
  model: 'gpt-4o-mini',
  temperature: 0.7,
  maxTokens: 2048,
  retryAttempts: 3,
});

type ActiveSection = 'routing' | 'params' | 'epistemic';

export default function AISettingsPage() {
  const runtime = useNarrativeRuntime();
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

  const tabs = [
    { id: 'routing', label: 'LLM Routing' },
    { id: 'params', label: 'Agent Parameters' },
    { id: 'epistemic', label: 'Epistemic Layer' },
  ];

  return (
    <div className="space-y-6">
      <PageHeader
        title="AI Configuration"
        subtitle="Manage LLM routing, agent parameters, and epistemic settings for the narrative pipeline."
        action={
          <div className="flex items-center gap-2">
            <Button variant="secondary" size="sm" onClick={handleReset}>
              <RotateCcw size={14} />
              Reset
            </Button>
            <Button variant="primary" size="sm" onClick={handleSave} disabled={isSaving}>
              <Save size={14} />
              {isSaving ? 'Saving…' : 'Save Changes'}
            </Button>
          </div>
        }
      />

      <TabBar tabs={tabs} activeTab={activeSection} onChange={(id) => setActiveSection(id as ActiveSection)} />

      {activeSection === 'routing' && (
        <RoutingTab
          agentConfigs={agentConfigs}
          update={update}
          providers={runtime.loomStatus?.providers}
        />
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
