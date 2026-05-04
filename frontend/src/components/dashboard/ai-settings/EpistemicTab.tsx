'use client';

import DataPanel from '@/components/ui/shared/DataPanel';
import Slider from './Slider';
import type { EpistemicConfig } from './types';

interface EpistemicTabProps {
  config: EpistemicConfig;
  onChange: (patch: Partial<EpistemicConfig>) => void;
}

const TIERS = [
  { value: 'oracle' as const, label: 'Oracle', desc: 'Omniscient — highest quality, highest cost' },
  { value: 'historian' as const, label: 'Historian', desc: 'Balanced factual + narrative blend' },
  { value: 'myth' as const, label: 'Myth', desc: 'Mythic resonance, poetic license' },
];

export default function EpistemicTab({ config, onChange }: EpistemicTabProps) {
  return (
    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <DataPanel title="World Noise Level" action={<span className="text-xs text-text-muted">Narrative tension</span>}>
        <div className="space-y-4">
          <Slider
            label="Noise Intensity"
            value={config.noiseLevel}
            min={0}
            max={1}
            step={0.05}
            onChange={(v) => onChange({ noiseLevel: v })}
          />
          <table className="w-full text-xs">
            <tbody>
              {[
                { range: '0.0 – 0.3', label: 'Stable', tone: 'text-brand-emerald' },
                { range: '0.3 – 0.7', label: 'Moderate tension', tone: 'text-brand-amber' },
                { range: '0.7 – 1.0', label: 'Chaotic / Epic crisis', tone: 'text-brand-danger' },
              ].map((row) => (
                <tr key={row.range} className="border-b border-border-subtle">
                  <td className="py-2 text-text-muted">{row.range}</td>
                  <td className={`py-2 font-medium ${row.tone}`}>{row.label}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </DataPanel>

      <DataPanel title="Epistemic Tier" action={<span className="text-xs text-text-muted">Narrator scope</span>}>
        <div className="space-y-2">
          {TIERS.map((tier) => (
            <label
              key={tier.value}
              className={`flex cursor-pointer items-start gap-3 rounded border p-3 transition ${
                config.tier === tier.value
                  ? 'border-brand-info/40 bg-brand-info/5'
                  : 'border-border-subtle hover:border-border-muted'
              }`}
            >
              <input
                type="radio"
                name="tier"
                value={tier.value}
                checked={config.tier === tier.value}
                onChange={() => onChange({ tier: tier.value })}
                className="mt-0.5 accent-brand-info"
              />
              <div>
                <p className="text-sm font-medium text-text-primary">{tier.label}</p>
                <p className="text-xs text-text-muted">{tier.desc}</p>
              </div>
            </label>
          ))}
        </div>
      </DataPanel>

      <div className="lg:col-span-2">
        <DataPanel title="Strict Mode" action={<span className="text-xs text-text-muted">Epistemic coherence</span>}>
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-text-secondary">Enable Epistemic Strict Mode</p>
              <p className="mt-1 text-xs text-text-muted">
                Increases revision loop probability. Rejects prose that contradicts world axioms.
              </p>
            </div>
            <button
              onClick={() => onChange({ strictMode: !config.strictMode })}
              className={`relative h-6 w-10 rounded-full transition ${
                config.strictMode ? 'bg-brand-info' : 'bg-bg-elevated'
              }`}
            >
              <span
                className={`absolute top-1 h-4 w-4 rounded-full bg-white shadow transition-all ${
                  config.strictMode ? 'left-5' : 'left-1'
                }`}
              />
            </button>
          </div>

          <div className="mt-5 border-t border-border-subtle pt-4">
            <p className="mb-2 text-xs font-medium text-text-muted">
              Resonance Scars <span className="text-text-disabled">(read-only)</span>
            </p>
            <div className="rounded border border-dashed border-border-subtle px-4 py-6 text-center text-xs text-text-disabled">
              No resonance scars recorded. Run the pipeline to populate.
            </div>
          </div>
        </DataPanel>
      </div>
    </div>
  );
}
