'use client';

import React, { useState } from 'react';
import PageHeader from '@/components/ui/shared/PageHeader';
import Button from '@/components/ui/shared/Button';
import MetricCard from '@/components/ui/shared/MetricCard';
import { RefreshCcw, Save } from 'lucide-react';
import {
  useServiceStatus,
  useSimulationSettings,
} from '@/features/admin/hooks';
import type { SimulationSetting, SimulationValue } from '@/features/admin/types';
import SettingsGrid from '@/components/dashboard/system/SettingsGrid';
import ServiceHealthPanel from '@/components/dashboard/system/ServiceHealthPanel';
import SystemInfoCard from '@/components/dashboard/system/SystemInfoCard';

export default function SystemPage() {
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

  if (isLoading) {
    return (
      <div className="flex min-h-[60vh] flex-col items-center justify-center gap-4">
        <div className="h-12 w-12 animate-spin rounded-full border-4 border-brand-info/20 border-t-brand-info" />
        <p className="text-[10px] font-black uppercase tracking-[0.3em] text-text-muted">
          Loading system controls
        </p>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-7xl px-4 pb-24 sm:px-6">
      <PageHeader
        title="System Runtime"
        subtitle="Canonical home for simulation settings and service health."
        action={
          <div className="flex flex-wrap items-center gap-2">
            <Button variant="secondary" size="sm" onClick={() => setLocalChanges({})} disabled={isUpdating}>
              Discard Changes
            </Button>
            <Button variant="danger" size="sm" onClick={handleReset} disabled={isResetting || isUpdating}>
              <RefreshCcw size={14} />
              Reset Defaults
            </Button>
            <Button variant="primary" size="sm" onClick={handleSave} disabled={isUpdating}>
              <Save size={14} />
              Save Runtime
            </Button>
          </div>
        }
      />

      <div className="mb-8 grid grid-cols-1 gap-4 md:grid-cols-3">
        <MetricCard label="Runtime Groups" value={String(Object.keys(settings ?? {}).length)} color="cyan" />
        <MetricCard label="Service Health" value={`${healthyCount}/${totalCount || 0}`} color="green" />
        <MetricCard label="Overall Status" value={String(serviceStatus?.overall ?? 'unknown')} color="cyan" />
      </div>

      <div className="grid grid-cols-1 gap-6 xl:grid-cols-[1.3fr_0.7fr]">
        <SettingsGrid getValue={getValue} onChange={handleInputChange} />

        <div className="space-y-6">
          <ServiceHealthPanel serviceStatus={serviceStatus} isLoading={isLoadingStatus} />
          <SystemInfoCard />
        </div>
      </div>
    </div>
  );
}
