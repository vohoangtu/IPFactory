'use client';

import React from 'react';
import { Settings2, Trash2 } from 'lucide-react';
import type { AiProviderModel } from '../../types';

interface ProviderModelCardProps {
  model: AiProviderModel;
  onEdit: (model: AiProviderModel) => void;
  onDelete: (id: number) => void;
}

export default function ProviderModelCard({ model, onEdit, onDelete }: ProviderModelCardProps) {
  return (
    <div className="rounded-lg border border-border-muted/50 bg-bg-elevated/40 p-5">
      <div className="flex items-start justify-between gap-4">
        <div>
          <div className="mb-2 flex flex-wrap items-center gap-2">
            <h3 className="text-lg font-black text-text-primary">
              {model.display_name}
            </h3>
            <span className="rounded-full bg-bg-elevated/60 px-3 py-1 text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary">
              {model.provider}
            </span>
            <span
              className={`rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-[0.2em] ${
                model.is_active
                  ? 'bg-brand-emerald/10 text-brand-emerald'
                  : 'bg-brand-danger/10 text-brand-danger'
              }`}
            >
              {model.is_active ? 'active' : 'inactive'}
            </span>
          </div>
          <p className="text-sm text-text-secondary">Model: {model.model_name}</p>
          {model.metadata?.tier ? (
            <p className="mt-1 text-sm text-text-muted">
              Tier: {String(model.metadata.tier)}
            </p>
          ) : null}
        </div>
        <div className="flex items-center gap-2">
          <button
            onClick={() => onEdit(model)}
            className="rounded-xl bg-bg-elevated/50 p-2 text-text-secondary transition hover:text-text-primary"
          >
            <Settings2 size={16} />
          </button>
          <button
            onClick={() => onDelete(model.id)}
            className="rounded-xl bg-brand-danger/10 p-2 text-brand-danger transition hover:bg-brand-danger/20"
          >
            <Trash2 size={16} />
          </button>
        </div>
      </div>
    </div>
  );
}
