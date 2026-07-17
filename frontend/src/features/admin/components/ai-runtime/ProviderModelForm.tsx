'use client';

import React from 'react';
import { Save } from 'lucide-react';
import type { AiProviderModel } from '../../types';

interface ProviderModelFormProps {
  model: Partial<AiProviderModel>;
  onChange: (model: Partial<AiProviderModel>) => void;
  onSave: () => void;
  onCancel: () => void;
}

export default function ProviderModelForm({
  model,
  onChange,
  onSave,
  onCancel,
}: ProviderModelFormProps) {
  return (
    <div className="mb-6 rounded-lg border border-brand-info/30 bg-brand-info/10 p-6">
      <h3 className="mb-4 text-lg font-black text-text-primary">
        {model.id ? 'Edit Provider Model' : 'Add Provider Model'}
      </h3>
      <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
        <label className="space-y-2">
          <span className="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">
            Provider
          </span>
          <input
            value={model.provider || ''}
            onChange={(event) =>
              onChange({ ...model, provider: event.target.value })
            }
            className="w-full rounded-md border border-border-subtle bg-bg-base px-4 py-3 text-text-primary outline-none transition focus:border-brand-info"
          />
        </label>
        <label className="space-y-2">
          <span className="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">
            Model Name
          </span>
          <input
            value={model.model_name || ''}
            onChange={(event) =>
              onChange({ ...model, model_name: event.target.value })
            }
            className="w-full rounded-md border border-border-subtle bg-bg-base px-4 py-3 text-text-primary outline-none transition focus:border-brand-info"
          />
        </label>
        <label className="space-y-2">
          <span className="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">
            Display Name
          </span>
          <input
            value={model.display_name || ''}
            onChange={(event) =>
              onChange({ ...model, display_name: event.target.value })
            }
            className="w-full rounded-md border border-border-subtle bg-bg-base px-4 py-3 text-text-primary outline-none transition focus:border-brand-info"
          />
        </label>
        <label className="space-y-2">
          <span className="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">
            Tier
          </span>
          <select
            value={String(model.metadata?.tier || '')}
            onChange={(event) =>
              onChange({
                ...model,
                metadata: {
                  ...(model.metadata || {}),
                  tier: event.target.value,
                },
              })
            }
            className="w-full rounded-md border border-border-subtle bg-bg-base px-4 py-3 text-text-primary outline-none transition focus:border-brand-info"
          >
            <option value="">Select tier</option>
            <option value="pro">Pro</option>
            <option value="mini">Mini</option>
            <option value="free">Free</option>
          </select>
        </label>
        <label className="inline-flex items-center gap-2">
          <input
            type="checkbox"
            checked={model.is_active ?? true}
            onChange={(event) =>
              onChange({ ...model, is_active: event.target.checked })
            }
            className="h-4 w-4 rounded border-border-muted bg-bg-elevated text-cyan-500"
          />
          <span className="text-sm text-text-primary">Active</span>
        </label>
      </div>
      <div className="mt-4 flex gap-3">
        <button
          onClick={onSave}
          className="inline-flex items-center gap-2 rounded-md bg-white px-4 py-2 text-sm font-black text-black transition hover:bg-brand-info/80"
        >
          <Save size={16} />
          Save
        </button>
        <button
          onClick={onCancel}
          className="rounded-md border border-border-muted/50 bg-bg-elevated/60 px-4 py-2 text-sm font-black text-text-secondary transition hover:text-text-primary"
        >
          Cancel
        </button>
      </div>
    </div>
  );
}
