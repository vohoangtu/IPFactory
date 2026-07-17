'use client';

import React from 'react';
import { Brain, RefreshCcw } from 'lucide-react';
import RuntimeCard from './RuntimeCard';
import LoomAgentEditor from './LoomAgentEditor';
import type { AiProviderModel, LoomAgentRecord } from '../../types';

interface LoomAgentsPanelProps {
  loomAgents: LoomAgentRecord[];
  providerModels: AiProviderModel[];
  isSaving: boolean;
  onSave: (record: LoomAgentRecord, value: Record<string, unknown>) => Promise<unknown>;
  isImporting: boolean;
  onImport: () => void;
}

export default function LoomAgentsPanel({
  loomAgents,
  providerModels,
  isSaving,
  onSave,
  isImporting,
  onImport,
}: LoomAgentsPanelProps) {
  return (
    <RuntimeCard
      title="Loom Agents"
      description="Agent-facing routing remains configurable, but is now managed in one place"
      icon={<Brain size={18} />}
    >
      <div className="mb-5 flex flex-wrap items-center gap-3">
        <button
          onClick={onImport}
          disabled={isImporting}
          className="inline-flex items-center gap-2 rounded-md border border-border-muted/50 bg-bg-elevated/60 px-4 py-3 text-sm font-black text-text-secondary transition hover:text-text-primary disabled:opacity-50"
        >
          <RefreshCcw size={16} />
          Import From JSON
        </button>
      </div>

      <div className="space-y-4">
        {loomAgents.map((record) => (
          <LoomAgentEditor
            key={record.id}
            record={record}
            providerModels={providerModels}
            onSave={(value) => onSave(record, value)}
            isSaving={isSaving}
          />
        ))}
      </div>
    </RuntimeCard>
  );
}
