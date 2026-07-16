'use client';
import { useMemo } from 'react';
import { useParams } from 'next/navigation';
import { WorkspaceLayout, useObservedUniverse } from '@/features/universe-workspace';
import { CivilizationLens, useCivilization, useWorldState } from '@/features/civilization';

export default function CivilizationLensPage() {
  const params = useParams<{ id: string }>();
  const universeId = useMemo(() => {
    const n = Number(params?.id);
    return Number.isFinite(n) ? n : null;
  }, [params?.id]);
  useObservedUniverse(universeId);

  const civ = useCivilization(universeId);
  const world = useWorldState(universeId);

  return (
    <WorkspaceLayout universeId={universeId ?? undefined}>
      {(civ.isError || world.isError) && (
        <p className="mb-3 text-sm text-[var(--color-amber)]" role="alert">Một phần dữ liệu văn minh không tải được.</p>
      )}
      <CivilizationLens civilization={civ.civilization} world={world.world} />
    </WorkspaceLayout>
  );
}
