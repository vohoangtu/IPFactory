'use client';

// Port của app/dashboard/ai-runtime/page.tsx (366 dòng) — khung trang gốc chuyển thành
// component trong feature. `page.tsx` ở (ops)/ops/ai-runtime chỉ render component này.
// Đổi: PageHeader/TabBar (components/ui/shared cũ, không được import trong code mới) ->
// JSX nội tuyến cùng style, theo đúng pattern các trang (ops)/ops/settings, /ops/system.
// Button -> PanelButton (@/shared/ui/PanelButton, cùng API).

import React, { useState } from 'react';
import {
  Brain,
  Cpu,
  Database,
  RefreshCcw,
  Save,
  Settings2,
  ShieldCheck,
} from 'lucide-react';
import { toast } from 'sonner';
import PanelButton from '@/shared/ui/PanelButton';
import KeyForm from '../key-pool/KeyForm';
import PoolRoutingPanel from './PoolRoutingPanel';
import DiagnosticsPanel from './DiagnosticsPanel';
import LoomAgentsPanel from './LoomAgentsPanel';
import ProviderModelsPanel from './ProviderModelsPanel';
import KeyPoolPanel from './KeyPoolPanel';
import {
  asFeatureProfile,
  asString,
  buildDriverOptions,
  toFeaturePayload,
  useAiDrivers,
  useAiSettings,
  useCreateProviderModel,
  useDeleteProviderModel,
  useExportProviderModels,
  useImportAiSettings,
  useImportLoomAgents,
  useImportProviderModels,
  useKeyPool,
  useLoomAgents,
  useProviderModels,
  useRunAiDiagnostics,
  useSyncAiSettings,
  useUpdateAiSetting,
  useUpdateProviderModel,
} from '../../hooks';
import type {
  AiDiagnosticsResult,
  AiFeatureProfile,
  AiKey,
  AiKeyPayload,
  AiProviderModel,
  DriverName,
} from '../../types';

type FeatureKey = 'analytical' | 'narrative' | 'lab' | 'decision';
type RuntimeTab = 'routing' | 'loom' | 'providers' | 'keys';

const featureKeys: FeatureKey[] = ['analytical', 'narrative', 'lab', 'decision'];

const runtimeTabs: Array<{ id: RuntimeTab; label: string; icon: React.ReactNode }> = [
  { id: 'routing', label: 'Routing', icon: <Cpu size={14} /> },
  { id: 'loom', label: 'Loom Agents', icon: <Brain size={14} /> },
  { id: 'providers', label: 'Provider Models', icon: <Settings2 size={14} /> },
  { id: 'keys', label: 'Key Pool', icon: <Database size={14} /> },
];

export default function AiRuntimeOps() {
  const [activeTab, setActiveTab] = useState<RuntimeTab>('routing');
  const [defaultDriverOverride, setDefaultDriverOverride] = useState<DriverName | null>(null);
  const [featureOverrides, setFeatureOverrides] = useState<
    Record<FeatureKey, Partial<AiFeatureProfile>>
  >({
    analytical: {},
    narrative: {},
    lab: {},
    decision: {},
  });
  const [diagnostics, setDiagnostics] = useState<AiDiagnosticsResult | null>(null);
  const [diagnosticsDriverOverride, setDiagnosticsDriverOverride] =
    useState<DriverName | null>(null);
  const [diagnosticsPrompt, setDiagnosticsPrompt] = useState(
    'Ping AI diagnostics. Reply with one short readiness sentence.',
  );
  const [editingProviderModel, setEditingProviderModel] = useState<Partial<AiProviderModel> | null>(null);
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingKey, setEditingKey] = useState<AiKey | null>(null);

  const { settings, isLoading: isLoadingSettings } = useAiSettings();
  const { drivers } = useAiDrivers();
  const { loomAgents, isLoading: isLoadingLoom } = useLoomAgents();
  const { providerModels, isLoading: isLoadingProviderModels } = useProviderModels();
  const {
    keys,
    isLoading: isLoadingKeys,
    addKey,
    updateKey,
    deleteKey,
  } = useKeyPool();

  const updateAiSetting = useUpdateAiSetting();
  const syncAiSettings = useSyncAiSettings();
  const importAiSettings = useImportAiSettings();
  const importLoomAgents = useImportLoomAgents();
  const diagnosticsMutation = useRunAiDiagnostics();
  const createProviderModel = useCreateProviderModel();
  const updateProviderModel = useUpdateProviderModel();
  const deleteProviderModel = useDeleteProviderModel();
  const exportProviderModels = useExportProviderModels();
  const importProviderModels = useImportProviderModels();

  const driverOptions = buildDriverOptions(drivers);
  const isLoading = isLoadingSettings || isLoadingLoom || isLoadingProviderModels || isLoadingKeys;
  const recordMap = new Map(settings.map((record) => [record.key, record.value]));
  const baseDefaultDriver = asString(recordMap.get('default'), 'pool');
  const baseFeatures: Record<FeatureKey, AiFeatureProfile> = {
    analytical: asFeatureProfile(recordMap.get('features.analytical'), 'pool'),
    narrative: asFeatureProfile(recordMap.get('features.narrative'), 'pool'),
    lab: asFeatureProfile(recordMap.get('features.lab'), 'pool'),
    decision: asFeatureProfile(recordMap.get('features.decision'), 'pool'),
  };
  const defaultDriver = defaultDriverOverride ?? baseDefaultDriver;
  const diagnosticsDriver = diagnosticsDriverOverride ?? baseDefaultDriver;
  const features: Record<FeatureKey, AiFeatureProfile> = {
    analytical: { ...baseFeatures.analytical, ...featureOverrides.analytical },
    narrative: { ...baseFeatures.narrative, ...featureOverrides.narrative },
    lab: { ...baseFeatures.lab, ...featureOverrides.lab },
    decision: { ...baseFeatures.decision, ...featureOverrides.decision },
  };

  const updateFeature = (feature: FeatureKey, patch: Partial<AiFeatureProfile>) => {
    setFeatureOverrides((current) => ({
      ...current,
      [feature]: { ...(current[feature] ?? {}), ...patch },
    }));
  };

  const handleSaveRouting = async () => {
    await updateAiSetting.mutateAsync({
      key: 'use_pool',
      value: true,
      group: 'general',
    });
    await updateAiSetting.mutateAsync({
      key: 'default',
      value: defaultDriver,
      group: 'general',
    });

    for (const feature of featureKeys) {
      await updateAiSetting.mutateAsync({
        key: `features.${feature}`,
        value: toFeaturePayload(features[feature]),
        group: 'feature',
      });
    }

    setDefaultDriverOverride(null);
    setDiagnosticsDriverOverride(null);
    setFeatureOverrides({
      analytical: {},
      narrative: {},
      lab: {},
      decision: {},
    });
    await syncAiSettings.mutateAsync();
  };

  const handleDiagnostics = async () => {
    try {
      const result = await diagnosticsMutation.mutateAsync({
        driver: diagnosticsDriver,
        prompt: diagnosticsPrompt,
      });
      setDiagnostics(result);
      toast.success(`${diagnosticsDriver.toUpperCase()} diagnostics completed.`);
    } catch (error: unknown) {
      const payload = (error as { response?: { data?: AiDiagnosticsResult } }).response?.data;
      if (payload) {
        setDiagnostics(payload);
      }
    }
  };

  const handleProviderModelSave = async () => {
    if (!editingProviderModel) return;

    if (editingProviderModel.id) {
      await updateProviderModel.mutateAsync({
        id: editingProviderModel.id,
        data: editingProviderModel,
      });
    } else {
      await createProviderModel.mutateAsync(editingProviderModel);
    }

    setEditingProviderModel(null);
  };

  const handleExportProviderModels = async () => {
    const data = await exportProviderModels.mutateAsync();
    const blob = new Blob([JSON.stringify(data, null, 2)], {
      type: 'application/json',
    });
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = 'provider-models.json';
    anchor.click();
    URL.revokeObjectURL(url);
    toast.success('Provider models exported.');
  };

  const handleImportProviderModels = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = async (loadEvent) => {
      try {
        const payload = JSON.parse(String(loadEvent.target?.result ?? '{}'));
        await importProviderModels.mutateAsync(payload);
      } catch {
        toast.error('Failed to parse provider model JSON.');
      }
    };
    reader.readAsText(file);
  };

  const handleAddKeyClick = () => {
    setEditingKey(null);
    setIsFormOpen(true);
  };

  const handleEditKeyClick = (key: AiKey) => {
    setEditingKey(key);
    setIsFormOpen(true);
  };

  const handleDeleteKeyClick = async (id: number) => {
    if (!confirm('Delete this key pool entry?')) {
      return;
    }
    await deleteKey(id);
    toast.success('Key pool entry deleted.');
  };

  const handleKeyFormSubmit = async (data: AiKeyPayload) => {
    if (editingKey) {
      await updateKey({ id: editingKey.id, data });
      toast.success('Key pool entry updated.');
      return;
    }

    if (!data.key) {
      throw new Error('API key is required when creating a new pool entry.');
    }

    await addKey({ ...data, key: data.key });
    toast.success('Key pool entry created.');
  };

  return (
    <div className="mx-auto max-w-7xl pb-24">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-semibold text-text-primary">AI Runtime</h1>
          <p className="mt-1 text-sm text-text-muted">
            Canonical home for AI routing, diagnostics, Loom agent config, provider models, and
            key pool operations.
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <PanelButton
            variant="secondary"
            size="sm"
            onClick={() => importAiSettings.mutate()}
            disabled={importAiSettings.isPending}
          >
            <RefreshCcw size={14} />
            Import Defaults
          </PanelButton>
          <PanelButton
            variant="primary"
            size="sm"
            onClick={() => syncAiSettings.mutate()}
            disabled={syncAiSettings.isPending}
          >
            <ShieldCheck size={14} />
            Sync Cache
          </PanelButton>
          <PanelButton
            variant="primary"
            size="sm"
            onClick={handleSaveRouting}
            disabled={updateAiSetting.isPending || syncAiSettings.isPending}
          >
            <Save size={14} />
            Save Routing
          </PanelButton>
        </div>
      </div>

      <div role="tablist" aria-label="AI Runtime" className="mt-6 flex border-b border-border-subtle">
        {runtimeTabs.map((tab) => (
          <button
            key={tab.id}
            type="button"
            role="tab"
            aria-selected={activeTab === tab.id}
            onClick={() => setActiveTab(tab.id)}
            className={`flex items-center gap-1.5 border-b-2 px-4 py-2.5 text-sm font-medium transition ${
              activeTab === tab.id
                ? 'border-brand-info text-brand-info'
                : 'border-transparent text-text-disabled hover:text-text-secondary'
            }`}
          >
            {tab.icon}
            {tab.label}
          </button>
        ))}
      </div>

      {isLoading ? (
        <div className="flex min-h-[40vh] items-center justify-center">
          <RefreshCcw size={22} className="animate-spin text-text-disabled" />
        </div>
      ) : null}

      {!isLoading && activeTab === 'routing' ? (
        <div className="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[1.2fr_0.8fr]">
          <PoolRoutingPanel
            defaultDriver={defaultDriver}
            driverOptions={driverOptions}
            features={features}
            onDriverChange={setDefaultDriverOverride}
            onFeatureChange={updateFeature}
          />
          <DiagnosticsPanel
            diagnosticsDriver={diagnosticsDriver}
            driverOptions={driverOptions}
            diagnosticsPrompt={diagnosticsPrompt}
            diagnostics={diagnostics}
            isPending={diagnosticsMutation.isPending}
            onDriverChange={setDiagnosticsDriverOverride}
            onPromptChange={setDiagnosticsPrompt}
            onRun={handleDiagnostics}
          />
        </div>
      ) : null}

      {!isLoading && activeTab === 'loom' ? (
        <div className="mt-6">
          <LoomAgentsPanel
            loomAgents={loomAgents}
            providerModels={providerModels}
            isSaving={updateAiSetting.isPending}
            onSave={(_record, value) =>
              updateAiSetting.mutateAsync({
                key: _record.key,
                value,
                group: _record.group,
              })
            }
            isImporting={importLoomAgents.isPending}
            onImport={() => importLoomAgents.mutate()}
          />
        </div>
      ) : null}

      {!isLoading && activeTab === 'providers' ? (
        <div className="mt-6">
          <ProviderModelsPanel
            providerModels={providerModels}
            editingModel={editingProviderModel}
            isExporting={exportProviderModels.isPending}
            onStartAdd={() => setEditingProviderModel({ provider: 'openai', is_active: true })}
            onEdit={(model) => setEditingProviderModel(model)}
            onCancelEdit={() => setEditingProviderModel(null)}
            onSave={handleProviderModelSave}
            onChange={setEditingProviderModel}
            onExport={handleExportProviderModels}
            onImport={handleImportProviderModels}
            onDelete={(id) => deleteProviderModel.mutate(id)}
          />
        </div>
      ) : null}

      {!isLoading && activeTab === 'keys' ? (
        <div className="mt-6">
          <KeyPoolPanel
            keys={keys}
            onAdd={handleAddKeyClick}
            onEdit={handleEditKeyClick}
            onDelete={handleDeleteKeyClick}
          />
        </div>
      ) : null}

      <KeyForm
        key={editingKey ? `edit-${editingKey.id}` : isFormOpen ? 'new-open' : 'new-closed'}
        isOpen={isFormOpen}
        onClose={() => setIsFormOpen(false)}
        onSubmit={handleKeyFormSubmit}
        initialData={editingKey}
      />
    </div>
  );
}
