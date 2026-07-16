'use client';
import { useMemo } from 'react';
import { useParams } from 'next/navigation';
import { WorkspaceLayout, useObservedUniverse } from '@/features/universe-workspace';
import { CausalityLens } from '@/features/causal-map';

export default function CausalityLensPage() {
  const params = useParams<{ id: string }>();
  const universeId = useMemo(() => {
    const n = Number(params?.id);
    return Number.isFinite(n) ? n : null;
  }, [params?.id]);
  useObservedUniverse(universeId);

  return (
    <WorkspaceLayout universeId={universeId ?? undefined}>
      {universeId != null && <CausalityLens universeId={universeId} />}
    </WorkspaceLayout>
  );
}
