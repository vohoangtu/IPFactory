'use client';
import { IntelligenceOps } from '@/features/intelligence';

export default function OpsIntelligencePage() {
  return (
    <div className="mx-auto flex max-w-7xl flex-col gap-4">
      <h1 className="font-mono text-xs uppercase tracking-[0.3em] text-[var(--color-text-muted)]">Giám sát AI</h1>
      <IntelligenceOps />
    </div>
  );
}
