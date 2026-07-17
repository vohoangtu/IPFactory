'use client';

import { useState } from 'react';
import { RefreshCcw, Save } from 'lucide-react';
import {
  useServiceStatus,
  useSimulationSettings,
  SettingsGrid,
  ServiceHealthPanel,
  SystemInfoCard,
} from '@/features/admin';
import type { SimulationSetting, SimulationValue } from '@/features/admin';
import PanelButton from '@/shared/ui/PanelButton';

export default function OpsSystemPage() {
  const { settings, isLoading, updateSettings, resetSettings, isUpdating, isResetting } =
    useSimulationSettings();
  const { serviceStatus, healthyCount, totalCount, isLoading: isLoadingStatus } =
    useServiceStatus();
  const [localChanges, setLocalChanges] = useState<Record<string, SimulationValue>>({});

  const handleInputChange = (key: string, value: SimulationValue) => {
    setLocalChanges((current) => ({ ...current, [key]: value }));
  };

  const findSetting = (key: string): SimulationSetting | undefined =>
    settings ? Object.values(settings).flat().find((item) => item.key === key) : undefined;

  const getValue = (key: string, fallback: SimulationValue): SimulationValue => {
    if (localChanges[key] !== undefined) {
      return localChanges[key];
    }
    return findSetting(key)?.value ?? fallback;
  };

  const handleSave = async () => {
    if (!settings) return;

    const changedSettings: SimulationSetting[] = [];

    Object.keys(localChanges).forEach((key) => {
      const original = findSetting(key);
      if (!original || original.value === localChanges[key]) {
        return;
      }
      changedSettings.push({
        key,
        value: localChanges[key],
        group: original.group,
        description: original.description,
      });
    });

    if (changedSettings.length === 0) {
      return;
    }

    await updateSettings(changedSettings);
    setLocalChanges({});
  };

  const handleReset = async () => {
    await resetSettings(undefined);
    setLocalChanges({});
  };

  return (
    <div className="mx-auto flex max-w-6xl flex-col gap-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="font-mono text-xs uppercase tracking-[0.3em] text-[var(--color-text-muted)]">Hệ thống</h1>
        <div className="flex flex-wrap items-center gap-2">
          <PanelButton variant="secondary" size="sm" onClick={() => setLocalChanges({})} disabled={isUpdating}>
            Discard Changes
          </PanelButton>
          <PanelButton variant="danger" size="sm" onClick={handleReset} disabled={isResetting || isUpdating}>
            <RefreshCcw size={14} />
            Reset Defaults
          </PanelButton>
          <PanelButton variant="primary" size="sm" onClick={handleSave} disabled={isUpdating}>
            <Save size={14} />
            Save Runtime
          </PanelButton>
        </div>
      </div>

      {isLoading ? (
        <div className="flex min-h-[40vh] flex-col items-center justify-center gap-4">
          <div className="h-12 w-12 animate-spin rounded-full border-4 border-brand-info/20 border-t-brand-info" />
          <p className="text-[10px] font-black uppercase tracking-[0.3em] text-text-muted">
            Loading system controls
          </p>
        </div>
      ) : (
        <>
          <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div className="rounded-lg border border-border-subtle bg-bg-surface p-4">
              <p className="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">Runtime Groups</p>
              <p className="mt-1 text-2xl font-black text-text-primary">{Object.keys(settings ?? {}).length}</p>
            </div>
            <div className="rounded-lg border border-border-subtle bg-bg-surface p-4">
              <p className="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">Service Health</p>
              <p className="mt-1 text-2xl font-black text-text-primary">{`${healthyCount}/${totalCount || 0}`}</p>
            </div>
            <div className="rounded-lg border border-border-subtle bg-bg-surface p-4">
              <p className="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">Overall Status</p>
              <p className="mt-1 text-2xl font-black text-text-primary">{String(serviceStatus?.overall ?? 'unknown')}</p>
            </div>
          </div>

          <div className="grid grid-cols-1 gap-6 xl:grid-cols-[1.3fr_0.7fr]">
            <SettingsGrid getValue={getValue} onChange={handleInputChange} />

            <div className="space-y-6">
              <ServiceHealthPanel serviceStatus={serviceStatus} isLoading={isLoadingStatus} />
              <SystemInfoCard />
            </div>
          </div>
        </>
      )}
    </div>
  );
}
