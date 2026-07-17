'use client';

import React from 'react';
import Link from 'next/link';
import { Info, Settings2 } from 'lucide-react';
import { routes } from '@/shared/config/routes';

export default function SystemInfoCard() {
  return (
    <div className="rounded-lg border border-border-subtle/50 bg-bg-surface p-6">
      <div className="mb-4 flex items-center gap-3">
        <div className="rounded-md bg-brand-accent/10 p-3 text-brand-accent">
          <Info size={18} />
        </div>
        <div>
          <h2 className="text-lg font-black text-text-primary">Next Surface</h2>
          <p className="text-xs text-text-muted">
            AI routing, diagnostics, provider models, and key pool live separately.
          </p>
        </div>
      </div>
      <Link
        href={routes.opsAiRuntime()}
        className="inline-flex items-center gap-2 rounded-md border border-brand-info/20 bg-brand-info/10 px-4 py-3 text-sm font-black text-cyan-300 transition hover:bg-cyan-500/20"
      >
        <Settings2 size={16} />
        Open AI Runtime
      </Link>
    </div>
  );
}
