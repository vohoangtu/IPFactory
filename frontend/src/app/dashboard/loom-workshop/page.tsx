'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { FileText, PlayCircle, Settings2, Wand2 } from 'lucide-react';
import { useUniverse } from '@/contexts/UniverseContext';
import { useNarrativeRuntime } from '@/features/narrative-runtime/useNarrativeRuntime';
import PageHeader from '@/components/ui/shared/PageHeader';
import TabBar from '@/components/ui/shared/TabBar';
import RunTab from '@/components/dashboard/loom/RunTab';
import ReviewTab from '@/components/dashboard/loom/ReviewTab';
import ActorIntentTab from './sections/ActorIntentTab';
import ScribeTab from './sections/ScribeTab';
import AssetForgeTab from './sections/AssetForgeTab';
import SystemTab from './sections/SystemTab';

type WorkshopTab = 'run' | 'review' | 'utilities' | 'system';
type UtilityTab = 'actor' | 'scribe' | 'forge';

export default function LoomWorkshopPage() {
  const { activeUniverseId } = useUniverse();
  const [activeTab, setActiveTab] = useState<WorkshopTab>('run');
  const [activeUtilityTab, setActiveUtilityTab] = useState<UtilityTab>('actor');
  const runtime = useNarrativeRuntime(activeUniverseId);

  const mainTabs: Array<{ id: WorkshopTab; label: string; icon: React.ReactNode }> = [
    { id: 'run', label: 'Run', icon: <PlayCircle size={14} /> },
    { id: 'review', label: 'Review', icon: <FileText size={14} /> },
    { id: 'utilities', label: 'Utilities', icon: <Wand2 size={14} /> },
    { id: 'system', label: 'System', icon: <Settings2 size={14} /> },
  ];

  const utilityTabs: Array<{ id: UtilityTab; label: string }> = [
    { id: 'actor', label: 'Actor Intent' },
    { id: 'scribe', label: 'History Scribe' },
    { id: 'forge', label: 'Asset Forge' },
  ];

  return (
    <div className="space-y-5">
      <PageHeader
        title="Loom Workshop"
        subtitle="Submit runs, monitor pipeline progress, and review narrative outputs."
        action={
          <Link
            href="/dashboard/config/ai-settings"
            className="flex items-center gap-1.5 rounded border border-border-muted bg-bg-elevated px-3 py-2 text-xs text-text-secondary transition hover:bg-bg-base"
          >
            <Settings2 size={13} />
            AI Configuration
          </Link>
        }
      />

      <TabBar tabs={mainTabs} activeTab={activeTab} onChange={(id) => setActiveTab(id as WorkshopTab)} />

      {activeTab === 'run' && (
        <RunTab runtime={runtime} activeUniverseId={activeUniverseId != null ? String(activeUniverseId) : null} />
      )}

      {activeTab === 'review' && <ReviewTab runtime={runtime} />}

      {activeTab === 'system' && <SystemTab />}

      {activeTab === 'utilities' && (
        <div className="space-y-4">
          <div className="flex gap-1 rounded-lg border border-border-subtle bg-bg-surface p-1">
            {utilityTabs.map((tab) => (
              <button
                key={tab.id}
                onClick={() => setActiveUtilityTab(tab.id)}
                className={`rounded px-4 py-2 text-xs font-medium transition ${
                  activeUtilityTab === tab.id
                    ? 'bg-bg-elevated text-text-primary'
                    : 'text-text-disabled hover:text-text-secondary'
                }`}
              >
                {tab.label}
              </button>
            ))}
          </div>
          {activeUtilityTab === 'actor' && <ActorIntentTab />}
          {activeUtilityTab === 'scribe' && <ScribeTab />}
          {activeUtilityTab === 'forge' && <AssetForgeTab />}
        </div>
      )}
    </div>
  );
}
