'use client';

import { Globe } from 'lucide-react';
import { useUniverseDossier, useUniverseMetrics } from '@/features/universe/hooks';
import { useUniverse } from '@/contexts/UniverseContext';
import { formatMetric, sentenceCase, getRecord } from '@/lib/utils';
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

export default function OverviewTab() {
  const { activeUniverseId } = useUniverse();
  const { metrics } = useUniverseMetrics(activeUniverseId);
  const { dossier } = useUniverseDossier(activeUniverseId);

  const materialIdentity = getRecord(dossier?.material_identity);
  const civilizationProfile = getRecord<DossierCivilizationProfile>(dossier?.civilization_profile);
  const civilizationIdentity = getRecord(civilizationProfile.identity);
  const governance = getRecord<DossierGovernance>(civilizationProfile.governance);
  const economy = getRecord<DossierEconomy>(civilizationProfile.economy);
  const religions = getRecord(dossier?.religions);
  const dominantReligion = getRecord(religions.dominant);
  const prosperityTrend = Number(economy?.prosperity_trend ?? 0);
  const era = getRecord(civilizationIdentity.era);

  return (
    <div className="grid gap-4 xl:grid-cols-2">
      <DataPanel title="Civilization State">
        <InfoRow
          label="Name"
          value={sentenceCase(String(civilizationIdentity.civilization_name || 'Unknown'))}
        />
        <InfoRow
          label="Governance"
          value={sentenceCase(String(civilizationIdentity.governance_type || 'Unknown'))}
        />
        <InfoRow
          label="Phase"
          value={String(civilizationIdentity.phase || 'Unknown').toUpperCase()}
          valueClass="text-brand-info font-medium"
        />
        <InfoRow
          label="Material Base"
          value={sentenceCase(String(civilizationIdentity.primary_material || materialIdentity.primary_material || 'Stone'))}
          valueClass="text-brand-amber font-medium"
        />
        <InfoRow
          label="Dominant Faith"
          value={sentenceCase(String(civilizationIdentity.dominant_religion || dominantReligion.name || 'None'))}
          valueClass="text-brand-accent font-medium"
        />
      </DataPanel>

      <DataPanel title="Governance & Economy">
        <InfoRow
          label="Population"
          value={(Number(governance?.total_population || metrics?.actor_count || 0)).toLocaleString()}
        />
        <InfoRow
          label="Legitimacy"
          value={formatMetric(Number(governance?.legitimacy || 0), 2)}
          valueClass={Number(governance?.legitimacy || 0) > 0.5 ? 'text-brand-emerald font-medium' : 'text-brand-danger font-medium'}
        />
        <InfoRow
          label="Elite Power"
          value={formatMetric(Number(governance?.elite_power || 0), 2)}
        />
        <InfoRow
          label="Prosperity Index"
          value={formatMetric(Number(economy?.prosperity_index ?? 0), 2)}
        />
        <InfoRow
          label="Prosperity Trend"
          value={`${prosperityTrend >= 0 ? '↑' : '↓'} ${formatMetric(Math.abs(prosperityTrend), 3)}`}
          valueClass={prosperityTrend >= 0 ? 'text-brand-emerald font-medium' : 'text-brand-danger font-medium'}
        />
        <InfoRow
          label="Anomalies"
          value={String(metrics?.anomaly_count ?? 0)}
          valueClass={Number(metrics?.anomaly_count ?? 0) > 0 ? 'text-brand-amber font-medium' : 'text-text-muted'}
        />
      </DataPanel>

      {/* Current Era */}
      <div className="xl:col-span-2 rounded-lg border border-brand-info/30 bg-brand-info/5 p-6 relative overflow-hidden">
        <div className="absolute top-0 right-0 p-4 opacity-10">
          <Globe size={120} />
        </div>
        <div className="relative z-10">
          <div className="flex items-center gap-2 mb-2">
            <span className="flex h-2 w-2 rounded-full bg-brand-info animate-pulse" />
            <span className="text-[10px] font-black uppercase tracking-[0.2em] text-brand-info">Current Historical Era</span>
          </div>
          <h2 className="text-3xl font-black italic text-text-primary mb-3 tracking-tight">
            {String(era.name || 'Chưa xác định')}
          </h2>
          <p className="text-sm text-text-secondary max-w-3xl leading-relaxed">
            {String(era.descriptor || 'Chạy simulation để khởi tạo lịch sử kỷ nguyên.')}
          </p>
          <div className="mt-4 flex gap-3">
            <div className="rounded bg-brand-info/10 px-2 py-1 text-[10px] font-bold text-brand-info border border-brand-info/20 uppercase tracking-wider">
              Stage: {String(era.stage || '—')}
            </div>
            <div className="rounded bg-bg-elevated px-2 py-1 text-[10px] font-bold text-text-disabled border border-border-muted uppercase tracking-wider">
              Focus: {String(era.focus_material || '—')}
            </div>
          </div>
          </div>
        </div>

      <DataPanel title="Simulation Metrics">
        <InfoRow
          label="Current Tick"
          value={String(metrics?.current_tick ?? 0)}
          valueClass="font-mono text-brand-info font-medium"
        />
        <InfoRow
          label="Entropy"
          value={formatMetric(metrics?.entropy, 3)}
          valueClass={
            (metrics?.entropy ?? 0) > 0.7 ? 'text-brand-danger font-medium' :
            (metrics?.entropy ?? 0) > 0.3 ? 'text-brand-amber font-medium' :
            'text-brand-emerald font-medium'
          }
        />
        <InfoRow
          label="Stability"
          value={formatMetric(metrics?.stability, 3)}
        />
        <InfoRow
          label="Religions"
          value={String(metrics?.religion_count ?? 0)}
        />
        <InfoRow
          label="Myths / Scars"
          value={String(metrics?.myth_count ?? 0)}
        />
      </DataPanel>
    </div>
  );
}
