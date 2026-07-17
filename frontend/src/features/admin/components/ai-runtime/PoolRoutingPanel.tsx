'use client';

import React from 'react';
import { Cpu } from 'lucide-react';
import RuntimeCard from './RuntimeCard';
import type { AiFeatureProfile, DriverName } from '../../types';

type FeatureKey = 'analytical' | 'narrative' | 'lab' | 'decision';

const featureKeys: FeatureKey[] = ['analytical', 'narrative', 'lab', 'decision'];

interface PoolRoutingPanelProps {
  defaultDriver: DriverName;
  driverOptions: DriverName[];
  features: Record<FeatureKey, AiFeatureProfile>;
  onDriverChange: (driver: DriverName) => void;
  onFeatureChange: (feature: FeatureKey, patch: Partial<AiFeatureProfile>) => void;
}

export default function PoolRoutingPanel({
  defaultDriver,
  driverOptions,
  features,
  onDriverChange,
  onFeatureChange,
}: PoolRoutingPanelProps) {
  return (
    <RuntimeCard
      title="Pool Routing"
      description="Default route and feature-level provider preferences"
      icon={<Cpu size={18} />}
    >
      <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
        <label className="space-y-2">
          <span className="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">
            Default Route
          </span>
          <select
            value={defaultDriver}
            onChange={(event) => onDriverChange(event.target.value)}
            className="w-full rounded-md border border-border-subtle bg-bg-base/60 px-4 py-3 text-sm text-text-primary outline-none"
          >
            {driverOptions.map((driver) => (
              <option key={driver} value={driver}>
                {driver.toUpperCase()}
              </option>
            ))}
          </select>
        </label>

        <div className="space-y-2">
          <span className="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">
            Pool Status
          </span>
          <div className="rounded-md border border-brand-info/30 bg-brand-info/10 px-4 py-3 text-sm font-bold text-cyan-300">
            Enabled permanently through AiGateway
          </div>
        </div>
      </div>

      <div className="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2">
        {featureKeys.map((feature) => (
          <div
            key={feature}
            className="space-y-4 rounded-md border border-border-subtle/70 bg-bg-base/40 p-4"
          >
            <div>
              <span className="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">
                Feature: {feature}
              </span>
              <p className="mt-1 text-xs leading-relaxed text-text-muted">
                Filter which providers and model tiers each runtime feature can use.
              </p>
            </div>

            <label className="space-y-2 block">
              <span className="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">
                Provider Filter
              </span>
              <select
                value={features[feature].driver}
                onChange={(event) =>
                  onFeatureChange(feature, { driver: event.target.value })
                }
                className="w-full rounded-md border border-border-subtle bg-bg-base/60 px-4 py-3 text-sm text-text-primary outline-none"
              >
                {driverOptions.map((driver) => (
                  <option key={driver} value={driver}>
                    {driver.toUpperCase()}
                  </option>
                ))}
              </select>
            </label>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <label className="space-y-2">
                <span className="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">
                  Model Override
                </span>
                <input
                  value={features[feature].model}
                  onChange={(event) =>
                    onFeatureChange(feature, { model: event.target.value })
                  }
                  className="w-full rounded-md border border-border-subtle bg-bg-base/60 px-4 py-3 text-sm text-text-primary outline-none"
                />
              </label>
              <label className="space-y-2">
                <span className="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">
                  Max Tokens
                </span>
                <input
                  value={features[feature].max_tokens}
                  onChange={(event) =>
                    onFeatureChange(feature, {
                      max_tokens: event.target.value.replace(/[^\d]/g, ''),
                    })
                  }
                  className="w-full rounded-md border border-border-subtle bg-bg-base/60 px-4 py-3 text-sm text-text-primary outline-none"
                />
              </label>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <label className="space-y-2">
                <span className="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">
                  Tier
                </span>
                <select
                  value={features[feature].tier}
                  onChange={(event) =>
                    onFeatureChange(feature, {
                      tier: event.target.value as AiFeatureProfile['tier'],
                    })
                  }
                  className="w-full rounded-md border border-border-subtle bg-bg-base/60 px-4 py-3 text-sm text-text-primary outline-none"
                >
                  <option value="any">Any Tier</option>
                  <option value="free">Free</option>
                  <option value="premium">Premium</option>
                </select>
              </label>
              <label className="space-y-2">
                <span className="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">
                  Model Group
                </span>
                <input
                  value={features[feature].model_group}
                  onChange={(event) =>
                    onFeatureChange(feature, { model_group: event.target.value })
                  }
                  className="w-full rounded-md border border-border-subtle bg-bg-base/60 px-4 py-3 text-sm text-text-primary outline-none"
                />
              </label>
            </div>
          </div>
        ))}
      </div>
    </RuntimeCard>
  );
}
