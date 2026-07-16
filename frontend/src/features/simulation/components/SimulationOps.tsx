'use client';

import { useUniverses } from '@/features/universe-workspace';
import TickAdvancePanel from './TickAdvancePanel';
import UniverseStatusPanel from './UniverseStatusPanel';
import SnapshotPanel from './SnapshotPanel';
import ForkPanel from './ForkPanel';
import CreateUniverseForm from './CreateUniverseForm';

export function SimulationOps({ universeId }: { universeId: number | null }) {
  const { data: universes = [] } = useUniverses();
  const universe = universes.find((u) => u.id === universeId);

  if (universeId == null) {
    return (
      <div className="flex flex-col gap-6">
        <p className="rounded-xl border border-dashed border-[var(--border-subtle)] p-6 text-sm text-[var(--color-text-muted)]">
          Chọn một universe để điều khiển — hoặc tạo mới bên dưới.
        </p>
        <CreateUniverseForm />
      </div>
    );
  }
  return (
    <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
      <UniverseStatusPanel universeId={universeId} universe={universe} />
      <TickAdvancePanel universeId={universeId} universe={universe} />
      <SnapshotPanel universeId={universeId} />
      <ForkPanel universeId={universeId} />
      <div className="lg:col-span-2"><CreateUniverseForm /></div>
    </div>
  );
}
