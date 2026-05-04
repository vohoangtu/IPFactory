'use client';

import {
  Activity,
  AlertTriangle,
  Brain,
  Globe,
  Zap,
} from 'lucide-react';
import ConfigCard from './ConfigCard';
import SettingRow from './SettingRow';
import type { SimulationValue } from '@/features/admin/types';

interface SettingsGridProps {
  getValue: (key: string, fallback: SimulationValue) => SimulationValue;
  onChange: (key: string, value: SimulationValue) => void;
}

export default function SettingsGrid({ getValue, onChange }: SettingsGridProps) {
  return (
    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
      <ConfigCard
        title="General"
        description="Universe identity and bootstrap controls"
        icon={<Globe size={20} />}
      >
        <SettingRow label="Universe Name">
          <input
            type="text"
            value={String(getValue('general.name', 'Standard Multiverse'))}
            onChange={(e) => onChange('general.name', e.target.value)}
            className="w-full rounded-md border border-border-muted bg-bg-base px-4 py-3 text-text-primary outline-none transition focus:border-brand-info"
          />
        </SettingRow>
        <SettingRow label="Observation Seed" detail="Primacy Value">
          <input
            type="text"
            value={String(getValue('general.seed', '0x99AFA'))}
            onChange={(e) => onChange('general.seed', e.target.value)}
            className="w-full rounded-md border border-border-muted bg-bg-base px-4 py-3 font-mono text-text-primary outline-none transition focus:border-brand-info"
          />
        </SettingRow>
      </ConfigCard>

      <ConfigCard
        title="Simulation Kernel"
        description="Tick cadence and actor density"
        icon={<Activity size={20} />}
      >
        <SettingRow label="Tick Rate" detail="ms per tick">
          <input
            type="number"
            value={Number(getValue('simulation.tick_rate', 1000))}
            onChange={(e) => onChange('simulation.tick_rate', Number(e.target.value) || 1)}
            className="w-full rounded-md border border-border-muted bg-bg-base px-4 py-3 text-text-primary outline-none transition focus:border-brand-emerald"
          />
        </SettingRow>
        <SettingRow label="Population Ceiling" detail="Actor limit">
          <input
            type="number"
            value={Number(getValue('simulation.actor_limit', 50))}
            onChange={(e) => onChange('simulation.actor_limit', Number(e.target.value) || 1)}
            className="w-full rounded-md border border-border-muted bg-bg-base px-4 py-3 text-text-primary outline-none transition focus:border-brand-emerald"
          />
        </SettingRow>
      </ConfigCard>

      <ConfigCard
        title="World Physics"
        description="Environmental stability and regeneration"
        icon={<Zap size={20} />}
      >
        <SettingRow
          label="Stability Factor"
          detail={`${Math.round(Number(getValue('chaos.dampening_stability_factor', 0.6)) * 100)}%`}
        >
          <input
            type="range"
            min="0"
            max="1"
            step="0.05"
            value={Number(getValue('chaos.dampening_stability_factor', 0.6))}
            onChange={(e) => onChange('chaos.dampening_stability_factor', Number(e.target.value))}
            className="h-1 w-full cursor-pointer appearance-none rounded-full bg-bg-elevated accent-brand-info"
          />
        </SettingRow>
        <SettingRow label="Resource Regeneration">
          <select
            value={Number(getValue('intelligence.resource_regen_rate', 2))}
            onChange={(e) => onChange('intelligence.resource_regen_rate', Number(e.target.value))}
            className="w-full rounded-md border border-border-muted bg-bg-base px-4 py-3 text-text-primary outline-none transition focus:border-brand-info"
          >
            <option value={1}>Abundant</option>
            <option value={2}>Standard</option>
            <option value={3}>Scarce</option>
            <option value={5}>Catastrophic</option>
          </select>
        </SettingRow>
      </ConfigCard>

      <ConfigCard
        title="Psychology & Entropy"
        description="Behavioral thresholds and collapse pressure"
        icon={<Brain size={20} />}
      >
        <SettingRow label="Trauma Threshold">
          <input
            type="range"
            min="0"
            max="100"
            step="1"
            value={Number(getValue('psychology.trauma_threshold', 75))}
            onChange={(e) => onChange('psychology.trauma_threshold', Number(e.target.value))}
            className="h-1 w-full cursor-pointer appearance-none rounded-full bg-bg-elevated accent-brand-danger"
          />
        </SettingRow>
        <SettingRow label="Entropy Floor">
          <input
            type="range"
            min="0"
            max="0.01"
            step="0.001"
            value={Number(getValue('worldos.entropy_floor', 0.001))}
            onChange={(e) => onChange('worldos.entropy_floor', Number(e.target.value))}
            className="h-1 w-full cursor-pointer appearance-none rounded-full bg-bg-elevated accent-brand-amber"
          />
        </SettingRow>
        <div className="flex gap-3 rounded-md border border-brand-amber/10 bg-brand-amber/5 p-4">
          <AlertTriangle size={18} className="mt-0.5 shrink-0 text-brand-amber" />
          <p className="text-xs leading-relaxed text-text-secondary">
            High tick rate and actor ceiling changes affect runtime stability immediately.
            Keep these in sync with service health before aggressive advances.
          </p>
        </div>
      </ConfigCard>
    </div>
  );
}
