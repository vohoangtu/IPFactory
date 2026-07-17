'use client';
import { UniverseSelect } from '@/features/ops-shell';
import { LoomOps } from '@/features/narrative-runtime';
import { useSimStore } from '@/shared/store/simStore';

export default function OpsLoomPage() {
  const universeId = useSimStore((s) => s.selectedUniverseId);
  return (
    <div className="mx-auto flex max-w-5xl flex-col gap-4">
      <div className="flex items-center justify-between gap-4">
        <h1 className="font-mono text-xs uppercase tracking-[0.3em] text-[var(--color-text-muted)]">Narrative Loom</h1>
        <UniverseSelect />
      </div>
      <LoomOps universeId={universeId} />
    </div>
  );
}
