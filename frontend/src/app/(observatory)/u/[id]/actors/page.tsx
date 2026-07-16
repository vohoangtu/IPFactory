'use client';
import { useMemo, useState } from 'react';
import { useParams } from 'next/navigation';
import { WorkspaceLayout, useObservedUniverse } from '@/features/universe-workspace';
import { ActorGrid, ActorPsychePanel, useActors, useActorPsyche } from '@/features/actors';
import { Panel } from '@/shared/ui/Panel';

export default function ActorsLensPage() {
  const params = useParams<{ id: string }>();
  const universeId = useMemo(() => {
    const n = Number(params?.id);
    return Number.isFinite(n) ? n : null;
  }, [params?.id]);
  useObservedUniverse(universeId);

  const [selectedActorId, setSelectedActorId] = useState<number | null>(null);
  const { actors, isLoading, isError } = useActors(universeId);
  const psyche = useActorPsyche(selectedActorId);

  return (
    <WorkspaceLayout universeId={universeId ?? undefined}>
      <div className="grid h-full min-h-0 grid-cols-1 gap-4 lg:grid-cols-3">
        <section className="custom-scrollbar min-h-0 overflow-y-auto lg:col-span-2" aria-label="Danh sách actor">
          <h2 className="mb-3 font-mono text-xs uppercase tracking-[0.3em] text-[var(--color-text-muted)]">Actors</h2>
          {isLoading && <p className="skeleton h-24 rounded-lg" aria-hidden="true" />}
          {isError && <p className="text-[var(--color-danger)]">Không tải được danh sách actor.</p>}
          {!isLoading && !isError && (
            <ActorGrid actors={actors} selectedId={selectedActorId} onSelect={setSelectedActorId} />
          )}
        </section>
        <aside className="min-h-0">
          <Panel title="Tâm lý (Psyche)">
            <ActorPsychePanel psyche={psyche.psyche} isLoading={psyche.isLoading} />
          </Panel>
        </aside>
      </div>
    </WorkspaceLayout>
  );
}
