'use client';

import { useState } from 'react';
import { useNarrativeRuntime } from '../useNarrativeRuntime';
import RunTab from './RunTab';
import ReviewTab from './ReviewTab';
import { LoomMonitor } from './LoomMonitor';
import ActorIntentTab from './sections/ActorIntentTab';
import ScribeTab from './sections/ScribeTab';
import AssetForgeTab from './sections/AssetForgeTab';
import SystemTab from './sections/SystemTab';

type LoomTabId = 'run' | 'review' | 'monitor' | 'actor' | 'scribe' | 'forge' | 'system';

const TABS: Array<{ id: LoomTabId; label: string }> = [
  { id: 'run', label: 'Run' },
  { id: 'review', label: 'Review' },
  { id: 'monitor', label: 'Monitor' },
  { id: 'actor', label: 'Actor Intent' },
  { id: 'scribe', label: 'Scribe' },
  { id: 'forge', label: 'Asset Forge' },
  { id: 'system', label: 'System' },
];

interface LoomOpsProps {
  universeId: number | null;
}

export function LoomOps({ universeId }: LoomOpsProps) {
  const [activeTab, setActiveTab] = useState<LoomTabId>('run');
  const runtime = useNarrativeRuntime(universeId);

  if (universeId == null) {
    return (
      <p className="rounded-xl border border-dashed border-border-subtle bg-bg-surface/40 p-6 text-sm text-text-disabled">
        Chọn một universe để dệt biên niên sử.
      </p>
    );
  }

  return (
    <div className="space-y-5">
      <div role="tablist" aria-label="Loom Workshop" className="flex flex-wrap gap-1 rounded-lg border border-border-subtle bg-bg-surface p-1">
        {TABS.map((tab) => (
          <button
            key={tab.id}
            type="button"
            role="tab"
            aria-selected={activeTab === tab.id}
            onClick={() => setActiveTab(tab.id)}
            className={`rounded px-4 py-2 text-xs font-medium transition ${
              activeTab === tab.id
                ? 'bg-bg-elevated text-text-primary'
                : 'text-text-disabled hover:text-text-secondary'
            }`}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {activeTab === 'run' && (
        <RunTab runtime={runtime} activeUniverseId={String(universeId)} />
      )}
      {activeTab === 'review' && <ReviewTab runtime={runtime} />}
      {activeTab === 'monitor' && <LoomMonitor universeId={universeId} />}
      {activeTab === 'actor' && <ActorIntentTab universeId={universeId} />}
      {activeTab === 'scribe' && <ScribeTab universeId={universeId} />}
      {activeTab === 'forge' && <AssetForgeTab universeId={universeId} />}
      {activeTab === 'system' && <SystemTab />}
    </div>
  );
}
