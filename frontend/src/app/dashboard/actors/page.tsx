'use client';

import { useState } from 'react';
import { Users, Loader2 } from 'lucide-react';
import { useUniverse } from '@/contexts/UniverseContext';
import { useActors } from '@/features/actors/hooks';
import ActorGrid from '@/components/dashboard/actors/ActorGrid';
import ActorDetailModal from '@/components/dashboard/actors/ActorDetailModal';
import EmptyState from '@/components/ui/shared/EmptyState';
import PageHeader from '@/components/ui/shared/PageHeader';

export default function ActorsPage() {
  const { activeUniverseId, universes } = useUniverse();
  const { actors, isLoading } = useActors(activeUniverseId);
  const [selectedActorId, setSelectedActorId] = useState<number | null>(null);

  const activeName = universes.find((u) => u.id === activeUniverseId)?.name ?? 'Unknown';
  const aliveCount = actors.filter((a) => a.is_alive).length;

  return (
    <div className="space-y-6">
      <PageHeader
        title="Actor Registry"
        subtitle={
          activeUniverseId
            ? `${activeName} · ${actors.length} actors · ${aliveCount} alive`
            : 'Select a universe to view actors.'
        }
      />

      {/* Content */}
      {!activeUniverseId ? (
        <EmptyState icon={Users} title="No universe selected" message="Select a universe from the sidebar." />
      ) : isLoading ? (
        <div className="flex items-center justify-center py-24 text-text-disabled">
          <Loader2 size={24} className="animate-spin mr-3" />
          <span className="text-sm">Loading actors…</span>
        </div>
      ) : (
        <ActorGrid actors={actors} onSelectActor={setSelectedActorId} />
      )}

      <ActorDetailModal
        actorId={selectedActorId}
        open={selectedActorId !== null}
        onClose={() => setSelectedActorId(null)}
      />
    </div>
  );
}
