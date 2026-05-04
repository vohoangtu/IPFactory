'use client';

import { useState } from 'react';
import { RefreshCcw, BookOpen, Globe, Loader2 } from 'lucide-react';

import { useUniverse } from '@/contexts/UniverseContext';
import { useUniverseDossier, useUniverseMetrics } from '@/features/universe/hooks';
import { formatMetric, getRecord } from '@/lib/utils';

import OverviewTab from '@/components/dashboard/tabs/OverviewTab';
import LibraryTab from '@/components/dashboard/tabs/LibraryTab';
import CivilizationTab from '@/components/dashboard/tabs/CivilizationTab';
import LoreTab from '@/components/dashboard/tabs/LoreTab';
import HistoryTab from '@/components/dashboard/tabs/HistoryTab';
import Button from '@/components/ui/shared/Button';
import PageHeader from '@/components/ui/shared/PageHeader';
import MetricCard from '@/components/ui/shared/MetricCard';
import TabBar from '@/components/ui/shared/TabBar';

// ── Page ──────────────────────────────────────

type TabId = 'status' | 'chronicles' | 'world';

export default function DashboardWorldDossierPage() {
  const { activeUniverseId, universes, isLoading: isUniverseListLoading } = useUniverse();
  const { metrics, isLoading: isMetricsLoading, mutate: refreshMetrics } = useUniverseMetrics(activeUniverseId);
  const { dossier, isLoading: isDossierLoading } = useUniverseDossier(activeUniverseId);

  const [activeTab, setActiveTab] = useState<TabId>('status');

  const activeUniverse = universes.find((u) => u.id === activeUniverseId);

  type Dictionary = Record<string, unknown>;
  const governance = getRecord(dossier?.civilization_profile).governance as Dictionary;
  const economy = getRecord(dossier?.civilization_profile).economy as Dictionary;

  const isLoading = isUniverseListLoading || isMetricsLoading || isDossierLoading;

  // Entropy color: 0-0.3 green, 0.3-0.7 amber, >0.7 red
  const entropy = metrics?.entropy ?? 0;
  const entropyColor = entropy > 0.7 ? 'red' : entropy > 0.3 ? 'amber' : 'green';

  const tabs: Array<{ id: TabId; label: string; icon: React.ReactNode }> = [
    { id: 'status', label: 'Status', icon: <Globe size={14} /> },
    { id: 'chronicles', label: 'Chronicles', icon: <BookOpen size={14} /> },
    { id: 'world', label: 'World Info', icon: <RefreshCcw size={14} /> },
  ];

  return (
    <div className="space-y-6">

      <PageHeader
        title="Universe Overview"
        subtitle={
          activeUniverse
            ? <>
                <span className="font-medium text-text-primary">{activeUniverse.name}</span>
                {' · '}
                <span className={`font-medium ${activeUniverse.status === 'active' ? 'text-green-400' : 'text-text-disabled'}`}>
                  {activeUniverse.status ?? 'unknown'}
                </span>
                {' · Tick '}
                <span className="font-mono text-brand-info">{metrics?.current_tick ?? activeUniverse.current_tick ?? 0}</span>
              </>
            : 'Select a universe to view its status.'
        }
        action={
          <Button
            variant="secondary"
            size="sm"
            onClick={() => { void refreshMetrics(); }}
            disabled={isLoading}
          >
            <RefreshCcw size={13} className={isLoading ? 'animate-spin' : ''} />
            Refresh
          </Button>
        }
      />

      {/* Metric strip */}
      {activeUniverseId && (
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <MetricCard
            label="Entropy"
            value={formatMetric(metrics?.entropy, 3)}
            subLabel="Stability"
            subValue={formatMetric(metrics?.stability, 3)}
            hint="Entropy 0 = stable, 1 = chaotic. Stability = inverse chaos resistance."
            color={entropyColor}
          />
          <MetricCard
            label="Prosperity"
            value={formatMetric(Number(economy?.prosperity_index ?? 0), 2)}
            subLabel="Trend"
            subValue={`${Number(economy?.prosperity_trend ?? 0) >= 0 ? '↑' : '↓'} ${formatMetric(Math.abs(Number(economy?.prosperity_trend ?? 0)), 2)}`}
            hint="Economic prosperity index. Higher = better material conditions."
            color="amber"
          />
          <MetricCard
            label="Chronicles"
            value={String(metrics?.chronicle_count ?? 0)}
            subLabel="Snapshots"
            subValue={String(metrics?.snapshot_count ?? 0)}
            hint="Number of narrative chronicles generated for this universe."
            color="violet"
          />
          <MetricCard
            label="Population"
            value={(Number(governance?.total_population || metrics?.actor_count || 0)).toLocaleString()}
            subLabel="Legitimacy"
            subValue={formatMetric(Number(governance?.legitimacy || 0), 2)}
            hint="Total actor count. Legitimacy = governance acceptance level (0–1)."
            color="green"
          />
        </div>
      )}

      <TabBar tabs={tabs} activeTab={activeTab} onChange={(id) => setActiveTab(id as TabId)} />

      {/* Loading */}
      {isLoading && (
        <div className="flex items-center gap-2 text-sm text-text-disabled">
          <Loader2 size={14} className="animate-spin" />
          Loading universe data…
        </div>
      )}

      {/* Tab content */}
      {activeTab === 'status' && <OverviewTab />}

      {activeTab === 'chronicles' && <LibraryTab />}

      {activeTab === 'world' && (
        <div className="space-y-6">
          <CivilizationTab />
          <LoreTab />
          <HistoryTab />
        </div>
      )}
    </div>
  );
}
