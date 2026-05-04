'use client';

import { useRouter } from 'next/navigation';
import { useMultiverseBloom, useMultiverseResonance } from '@/features/multiverse/hooks';
import PageHeader from '@/components/ui/shared/PageHeader';
import { useUniverse } from '@/contexts/UniverseContext';

import MultiverseTree from '@/components/dashboard/multiverse/MultiverseTree';
import ResonanceFeed from '@/components/dashboard/multiverse/ResonanceFeed';
import NarrativeEntropyGauge from '@/components/dashboard/multiverse/NarrativeEntropyGauge';

// ── Loading skeleton ────────────────────────────

function LoadingSkeleton() {
  return (
    <div className="animate-pulse space-y-6">
      {/* Entropy gauge skeleton */}
      <div className="h-20 rounded-3xl bg-bg-base/40" />
      {/* Main content skeleton */}
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div className="lg:col-span-2 space-y-4">
          <div className="h-48 rounded-3xl bg-bg-base/40" />
          <div className="h-48 rounded-3xl bg-bg-base/40" />
        </div>
        <div className="space-y-4">
          <div className="h-36 rounded-3xl bg-bg-base/40" />
          <div className="h-36 rounded-3xl bg-bg-base/40" />
          <div className="h-36 rounded-3xl bg-bg-base/40" />
        </div>
      </div>
    </div>
  );
}

// ── Page Component ──────────────────────────────

export default function MultiversePage() {
  const router = useRouter();
  const { setSelectedUniverseId } = useUniverse();
  const { bloom, isLoading: bloomLoading } = useMultiverseBloom();
  const { resonance, isLoading: resonanceLoading } = useMultiverseResonance();

  const isLoading = bloomLoading && resonanceLoading;

  // When a universe is selected from the tree, set it as active and navigate back
  const handleSelectUniverse = (universeId: string) => {
    const numericId = Number(universeId);
    if (!Number.isNaN(numericId)) {
      setSelectedUniverseId(numericId);
    }
    router.push('/dashboard');
  };

  return (
    <div className="space-y-6">
      <PageHeader
        title="Multiverse Observatory"
        subtitle="Explore worlds, universes, and narrative resonance across the multiverse"
      />

      {isLoading ? (
        <LoadingSkeleton />
      ) : (
        <>
          {/* Top: Narrative Entropy Gauge — full width compact */}
          <NarrativeEntropyGauge entropy={resonance?.global_narrative_entropy} />

          {/* Main: 2-column layout */}
          <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {/* Left: Multiverse Tree (2/3) */}
            <div className="lg:col-span-2">
              <MultiverseTree bloom={bloom} onSelectUniverse={handleSelectUniverse} />
            </div>

            {/* Right: Resonance Feed (1/3) */}
            <div className="lg:col-span-1">
              <ResonanceFeed resonance={resonance} />
            </div>
          </div>
        </>
      )}
    </div>
  );
}
