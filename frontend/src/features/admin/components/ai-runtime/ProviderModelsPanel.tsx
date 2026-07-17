'use client';

import React from 'react';
import { Download, Plus, Settings2, Upload } from 'lucide-react';
import RuntimeCard from './RuntimeCard';
import ProviderModelForm from './ProviderModelForm';
import ProviderModelCard from './ProviderModelCard';
import type { AiProviderModel } from '../../types';

interface ProviderModelsPanelProps {
  providerModels: AiProviderModel[];
  editingModel: Partial<AiProviderModel> | null;
  isExporting: boolean;
  onStartAdd: () => void;
  onEdit: (model: AiProviderModel) => void;
  onCancelEdit: () => void;
  onSave: () => void;
  onChange: (model: Partial<AiProviderModel>) => void;
  onExport: () => void;
  onImport: (event: React.ChangeEvent<HTMLInputElement>) => void;
  onDelete: (id: number) => void;
}

export default function ProviderModelsPanel({
  providerModels,
  editingModel,
  isExporting,
  onStartAdd,
  onEdit,
  onCancelEdit,
  onSave,
  onChange,
  onExport,
  onImport,
  onDelete,
}: ProviderModelsPanelProps) {
  return (
    <RuntimeCard
      title="Provider Models"
      description="CRUD and import/export for provider model registry"
      icon={<Settings2 size={18} />}
    >
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <button
          onClick={onStartAdd}
          className="inline-flex items-center gap-2 rounded-md bg-brand-info px-4 py-3 text-sm font-black text-text-primary transition hover:bg-brand-info/80"
        >
          <Plus size={16} />
          Add Model
        </button>
        <div className="flex flex-wrap items-center gap-2">
          <button
            onClick={onExport}
            disabled={isExporting}
            className="inline-flex items-center gap-2 rounded-md border border-border-muted/50 bg-bg-elevated/60 px-4 py-3 text-sm font-black text-text-secondary transition hover:text-text-primary disabled:opacity-50"
          >
            <Download size={16} />
            Export
          </button>
          <label className="inline-flex cursor-pointer items-center gap-2 rounded-md border border-border-muted/50 bg-bg-elevated/60 px-4 py-3 text-sm font-black text-text-secondary transition hover:text-text-primary">
            <Upload size={16} />
            Import
            <input
              type="file"
              accept=".json"
              onChange={onImport}
              className="hidden"
            />
          </label>
        </div>
      </div>

      {editingModel ? (
        <ProviderModelForm
          model={editingModel}
          onChange={onChange}
          onSave={onSave}
          onCancel={onCancelEdit}
        />
      ) : null}

      <div className="space-y-4">
        {providerModels.map((model) => (
          <ProviderModelCard
            key={model.id}
            model={model}
            onEdit={onEdit}
            onDelete={onDelete}
          />
        ))}
      </div>
    </RuntimeCard>
  );
}
