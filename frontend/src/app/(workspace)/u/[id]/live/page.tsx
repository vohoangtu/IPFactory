'use client';
import { useEffect } from 'react';
import { useParams } from 'next/navigation';
import { WorkspaceLayout } from '@/features/universe-workspace';
import { useSimStore } from '@/shared/store/simStore';
import { useUniverseChannel } from '@/shared/realtime/useUniverseChannel';
import { Panel } from '@/shared/ui/Panel';

export default function LivePage() {
  const params = useParams<{ id: string }>();
  const id = Number(params.id);
  const selectUniverse = useSimStore((s) => s.selectUniverse);
  const setMode = useSimStore((s) => s.setMode);
  useEffect(() => { selectUniverse(id); setMode('live'); }, [id, selectUniverse, setMode]);
  useUniverseChannel(id);
  return (
    <WorkspaceLayout>
      <Panel title="Live Monitor">
        <p className="text-sm text-gray-500">Panels sẽ được xây ở P1 (metrics live, event/narrative stream, zones/actors).</p>
      </Panel>
    </WorkspaceLayout>
  );
}
