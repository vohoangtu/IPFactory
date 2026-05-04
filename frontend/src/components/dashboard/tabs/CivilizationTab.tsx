'use client';

import { Layers3, Shapes } from 'lucide-react';
import { useUniverseDossier, useUniverseMetrics } from '@/features/universe/hooks';
import { useUniverse } from '@/contexts/UniverseContext';
import { formatMetric, sentenceCase, getRecord, getEntries } from '@/lib/utils';
import DataPanel from '@/components/ui/shared/DataPanel';
import InfoRow from '@/components/ui/shared/InfoRow';

type Dictionary = Record<string, unknown>;

interface DossierGovernance extends Dictionary {
  stability?: number;
  legitimacy?: number;
  elite_power?: number;
  total_population?: number;
  authority_intensity?: number;
}

interface DossierEconomy extends Dictionary {
  prosperity_index?: number;
  prosperity_trend?: number;
  food_price?: number;
  market_surplus?: number;
  resource_biases?: Record<string, number>;
}

interface DossierCivilizationProfile extends Dictionary {
  identity?: Dictionary;
  governance?: DossierGovernance;
  economy?: DossierEconomy;
  belief_order?: { average_cohesion?: number };
  core_regions?: Dictionary[];
}

export default function CivilizationTab() {
  const { activeUniverseId } = useUniverse();
  const { metrics } = useUniverseMetrics(activeUniverseId);
  const { dossier } = useUniverseDossier(activeUniverseId);

  const materialIdentity = getRecord(dossier?.material_identity);
  const cultureIdentity = getRecord(dossier?.culture_identity);
  const civilizationProfile = getRecord<DossierCivilizationProfile>(dossier?.civilization_profile);
  const governance = getRecord<DossierGovernance>(civilizationProfile.governance);
  const economy = getRecord<DossierEconomy>(civilizationProfile.economy);
  const beliefOrder = getRecord(civilizationProfile.belief_order);
  const coreRegions = Array.isArray(civilizationProfile.core_regions) ? civilizationProfile.core_regions : [];
  return (
    <div className="grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
      <div className="space-y-4">
        <DataPanel title="Material & Ecosystem Identity" action={<Layers3 size={14} className="text-brand-amber" />}>
          <div className="grid gap-3 md:grid-cols-3">
            <div className="rounded-lg border border-border-subtle bg-bg-base p-4">
              <div className="text-[10px] font-black uppercase tracking-widest text-text-disabled">Primary Material</div>
              <div className="mt-1 text-lg font-black text-brand-amber">{sentenceCase(String(materialIdentity.primary_material || 'unknown'))}</div>
            </div>
            <div className="rounded-lg border border-border-subtle bg-bg-base p-4">
              <div className="text-[10px] font-black uppercase tracking-widest text-text-disabled">Main Livelihood</div>
              <div className="mt-1 text-lg font-black text-brand-emerald">{sentenceCase(String(materialIdentity.primary_livelihood || 'unknown'))}</div>
            </div>
            <div className="rounded-lg border border-border-subtle bg-bg-base p-4">
              <div className="text-[10px] font-black uppercase tracking-widest text-text-disabled">Settlement Code</div>
              <div className="mt-1 text-lg font-black text-brand-info">{sentenceCase(String(materialIdentity.primary_settlement_style || 'unknown'))}</div>
            </div>
          </div>

          <div className="mt-4 grid gap-4 border-t border-border-subtle pt-4 md:grid-cols-2">
            <div>
              <div className="mb-3 flex items-center justify-between text-xs font-black uppercase tracking-widest text-text-disabled">
                <span>Core Settlement Zones</span>
                <span className="text-brand-info/60 lowercase font-mono">Top {coreRegions.length} Active</span>
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                {coreRegions.map((region, i) => (
                  <div key={i} className="rounded-lg border border-border-subtle bg-bg-base p-3">
                    <div className="font-bold text-text-primary mb-1 flex justify-between items-center text-sm">
                      <span className="truncate">{String(region.name)}</span>
                      <div className="flex items-center gap-1.5 shrink-0">
                        <div className="h-1.5 w-1.5 rounded-full bg-brand-info" />
                        <span className="text-[9px] text-brand-info opacity-60">ID {String(region.zone_id)}</span>
                      </div>
                    </div>
                    <div className="flex flex-wrap gap-1.5 mb-2">
                      <span className="text-[9px] bg-bg-elevated text-text-secondary px-2 py-0.5 rounded-full border border-border-subtle">
                        {sentenceCase(String(region.climate_signature))}
                      </span>
                      <span className="text-[9px] bg-brand-amber/10 text-brand-amber px-2 py-0.5 rounded-full border border-brand-amber/20">
                        {sentenceCase(String(region.settlement_style))}
                      </span>
                    </div>
                    <div className="space-y-1">
                      <div className="flex items-center justify-between text-[9px] uppercase tracking-tighter text-text-disabled">
                        <span>Cohesion</span>
                        <span className="text-brand-emerald">{formatMetric(Number(region.cohesion), 2)}</span>
                      </div>
                      <div className="h-1 w-full bg-bg-elevated rounded-full overflow-hidden">
                        <div className="h-full bg-brand-emerald" style={{ width: `${Number(region.cohesion) * 100}%` }} />
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
            <div className="space-y-4">
              <div>
                <div className="mb-2 text-[10px] font-bold uppercase tracking-widest text-text-disabled">Cultural Artifacts</div>
                <div className="flex flex-wrap gap-2">
                  {(() => {
                    const artifacts = getRecord(coreRegions[0]?.cultural_artifacts);
                    return Object.entries(artifacts).map(([key, value]) => (
                      <div key={key} className="rounded-lg border border-brand-accent/10 bg-brand-accent/5 px-3 py-2">
                        <div className="text-[8px] font-black uppercase tracking-widest text-brand-accent/60 mb-0.5">{key}</div>
                        <div className="text-xs font-bold text-text-secondary">{String(value)}</div>
                      </div>
                    ));
                  })()}
                </div>
              </div>
              <div className="rounded-lg border border-border-subtle bg-bg-base p-4">
                <div className="mb-3 text-xs font-black uppercase tracking-widest text-text-disabled">Resource Bias Profile</div>
                <div className="flex flex-wrap gap-2">
                  {getEntries(economy.resource_biases).map(([resource, score]) => (
                    <div key={resource} className="flex items-center gap-2 rounded-md bg-bg-elevated px-3 py-1.5 border border-border-subtle">
                      <span className="text-[10px] font-bold text-text-secondary">{sentenceCase(resource)}</span>
                      <span className="text-[10px] font-mono text-brand-info">{(Number(score) * 100).toFixed(0)}%</span>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </DataPanel>

        <DataPanel title="Culture Grouping & Social Class" action={<Shapes size={14} className="text-brand-accent" />}>
          <div className="grid gap-4 md:grid-cols-2">
            <div>
              <div className="mb-3 text-[10px] font-bold uppercase tracking-widest text-text-disabled">Cultural Mix</div>
              <div className="space-y-2">
                {getEntries(cultureIdentity.dominant_memes).slice(0, 4).map(([label, count]) => (
                  <div key={label} className="flex items-center justify-between rounded-lg border border-border-subtle bg-bg-base px-4 py-3">
                    <span className="text-sm font-semibold text-text-primary">{sentenceCase(label)}</span>
                    <div className="flex items-center gap-3">
                      <div className="h-1.5 w-16 overflow-hidden rounded-full bg-bg-elevated">
                        <div className="h-full bg-brand-accent" style={{ width: `${(Number(count) * 100)}%` }} />
                      </div>
                      <span className="font-mono text-xs text-brand-accent">{Number(count).toFixed(3)}</span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
            <div className="rounded-lg border border-border-subtle bg-bg-base p-4">
              <div className="mb-3 text-[10px] font-bold uppercase tracking-widest text-text-disabled">Demographic Data</div>
              <InfoRow label="Total Population" value={String(governance?.total_population || metrics?.actor_count || 0)} />
              <InfoRow label="Culture Diversity" value={formatMetric(Number(cultureIdentity.group_diversity || 0), 2)} />
              <InfoRow label="Avg. Cohesion" value={formatMetric(Number(beliefOrder.average_cohesion || 0), 3)} valueClass="text-brand-emerald font-medium" />
            </div>
          </div>
        </DataPanel>
      </div>

      <div className="space-y-4">
        <DataPanel title="Governance & Logic">
          <div className="space-y-4">
            <div className="rounded-lg border border-border-subtle bg-bg-base p-4">
              <div className="text-[10px] font-black uppercase tracking-widest text-text-disabled mb-3">Governance Vitality</div>
              <div className="space-y-3">
                <div>
                  <div className="flex justify-between text-xs mb-1">
                    <span className="text-text-muted">Stability</span>
                    <span className="text-text-primary font-bold">{formatMetric(Number(governance?.stability), 3)}</span>
                  </div>
                  <div className="h-1.5 w-full bg-bg-elevated rounded-full overflow-hidden">
                    <div className="h-full bg-brand-danger" style={{ width: `${Number(governance?.stability) * 100}%` }} />
                  </div>
                </div>
                <div>
                  <div className="flex justify-between text-xs mb-1">
                    <span className="text-text-muted">Legitimacy</span>
                    <span className="text-text-primary font-bold">{formatMetric(Number(governance?.legitimacy), 3)}</span>
                  </div>
                  <div className="h-1.5 w-full bg-bg-elevated rounded-full overflow-hidden">
                    <div className="h-full bg-brand-emerald" style={{ width: `${Number(governance?.legitimacy) * 100}%` }} />
                  </div>
                </div>
                <div>
                  <div className="flex justify-between text-xs mb-1">
                    <span className="text-text-muted">Elite Power</span>
                    <span className="text-text-primary font-bold">{formatMetric(Number(governance?.elite_power), 3)}</span>
                  </div>
                  <div className="h-1.5 w-full bg-bg-elevated rounded-full overflow-hidden">
                    <div className="h-full bg-brand-accent" style={{ width: `${Number(governance?.elite_power) * 100}%` }} />
                  </div>
                </div>
              </div>
            </div>
            <div className="rounded-lg border border-border-subtle bg-bg-base p-4">
              <div className="text-[10px] font-black uppercase tracking-widest text-text-disabled mb-3">Economic Market</div>
              <div className="grid grid-cols-2 gap-3">
                <div className="bg-bg-elevated p-3 rounded-md border border-border-subtle">
                  <div className="text-[9px] text-text-disabled uppercase font-black mb-1">Food Price</div>
                  <div className="text-lg font-black text-brand-danger">{formatMetric(Number(economy.food_price), 2)}</div>
                </div>
                <div className="bg-bg-elevated p-3 rounded-md border border-border-subtle">
                  <div className="text-[9px] text-text-disabled uppercase font-black mb-1">Surplus</div>
                  <div className="text-lg font-black text-brand-emerald">{formatMetric(Number(economy.market_surplus), 2)}</div>
                </div>
              </div>
            </div>
          </div>
        </DataPanel>
      </div>
    </div>
  );
}
