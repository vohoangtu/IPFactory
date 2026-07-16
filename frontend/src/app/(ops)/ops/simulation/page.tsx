'use client';
import { UniverseSelect } from '@/features/ops-shell';
import { SimulationOps } from '@/features/simulation';
import { useSimStore } from '@/shared/store/simStore';

export default function OpsSimulationPage() {
  const universeId = useSimStore((s) => s.selectedUniverseId);
  return (
    <div className="mx-auto flex max-w-5xl flex-col gap-4">
      <div className="flex items-center justify-between gap-4">
        <h1 className="font-mono text-xs uppercase tracking-[0.3em] text-[var(--color-text-muted)]">Điều khiển mô phỏng</h1>
        <UniverseSelect />
      </div>
      <SimulationOps universeId={universeId} />
    </div>
  );
}
