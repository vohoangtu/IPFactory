'use client';

import React, { useState } from 'react';
import { Save } from 'lucide-react';
import type { AiProviderModel, LoomAgentRecord } from '@/features/admin/types';
import { asString } from '@/features/admin/hooks';

interface LoomAgentEditorProps {
  record: LoomAgentRecord;
  providerModels: AiProviderModel[];
  onSave: (value: Record<string, unknown>) => Promise<unknown>;
  isSaving: boolean;
}

export default function LoomAgentEditor({ record, providerModels, onSave, isSaving }: LoomAgentEditorProps) {
  const initialValue =
    record.value && typeof record.value === 'object' && !Array.isArray(record.value)
      ? (record.value as Record<string, unknown>)
      : {};
  const [provider, setProvider] = useState(asString(initialValue.provider));
  const [model, setModel] = useState(asString(initialValue.model));
  const [tier, setTier] = useState(asString(initialValue.tier));

  const modelsForProvider = providerModels.filter(
    (item) => item.provider === provider && item.is_active,
  );

  return (
    <div className="space-y-4 rounded-md border border-border-subtle bg-bg-base/40 p-4">
      <div className="flex items-start justify-between gap-4">
        <div>
          <p className="text-sm font-black text-text-primary">
            {record.agent_name || record.key.replace('loom_agents.', '')}
          </p>
          <p className="mt-1 text-xs text-text-muted">{record.description || 'Loom agent route'}</p>
        </div>
        <span className="rounded-full bg-bg-elevated px-3 py-1 text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary">
          Loom
        </span>
      </div>

      <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
        <label className="space-y-2">
          <span className="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">
            Provider
          </span>
          <input
            value={provider}
            onChange={(event) => setProvider(event.target.value)}
            className="w-full rounded-md border border-border-subtle bg-bg-base px-4 py-3 text-sm text-text-primary outline-none transition focus:border-brand-info"
          />
        </label>
        <label className="space-y-2">
          <span className="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">
            Model
          </span>
          <select
            value={model}
            onChange={(event) => setModel(event.target.value)}
            className="w-full rounded-md border border-border-subtle bg-bg-base px-4 py-3 text-sm text-text-primary outline-none transition focus:border-brand-info"
          >
            <option value="">Select model</option>
            {modelsForProvider.map((item) => (
              <option key={item.id} value={item.model_name}>
                {item.display_name}
              </option>
            ))}
          </select>
        </label>
        <label className="space-y-2">
          <span className="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">
            Tier
          </span>
          <select
            value={tier}
            onChange={(event) => setTier(event.target.value)}
            className="w-full rounded-md border border-border-subtle bg-bg-base px-4 py-3 text-sm text-text-primary outline-none transition focus:border-brand-info"
          >
            <option value="">Any tier</option>
            <option value="free">Free</option>
            <option value="premium">Premium</option>
          </select>
        </label>
      </div>

      <button
        onClick={() =>
          onSave({
            ...(provider ? { provider } : {}),
            ...(model ? { model } : {}),
            ...(tier ? { tier } : {}),
          })
        }
        disabled={isSaving}
        className="inline-flex items-center gap-2 rounded-md bg-brand-info px-4 py-2 text-sm font-black text-text-primary transition hover:bg-brand-info/80 disabled:opacity-50"
      >
        <Save size={16} />
        Save Agent Route
      </button>
    </div>
  );
}
