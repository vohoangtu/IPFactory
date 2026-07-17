'use client';

import React from 'react';
import { TestTube2 } from 'lucide-react';
import RuntimeCard from './RuntimeCard';
import type { AiDiagnosticsResult, DriverName } from '../../types';

interface DiagnosticsPanelProps {
  diagnosticsDriver: DriverName;
  driverOptions: DriverName[];
  diagnosticsPrompt: string;
  diagnostics: AiDiagnosticsResult | null;
  isPending: boolean;
  onDriverChange: (driver: DriverName) => void;
  onPromptChange: (prompt: string) => void;
  onRun: () => void;
}

export default function DiagnosticsPanel({
  diagnosticsDriver,
  driverOptions,
  diagnosticsPrompt,
  diagnostics,
  isPending,
  onDriverChange,
  onPromptChange,
  onRun,
}: DiagnosticsPanelProps) {
  return (
    <RuntimeCard
      title="Runtime Diagnostics"
      description="Probe the current routing path via AiGateway"
      icon={<TestTube2 size={18} />}
    >
      <div className="space-y-4">
        <label className="space-y-2 block">
          <span className="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">
            Provider Filter
          </span>
          <select
            value={diagnosticsDriver}
            onChange={(event) => onDriverChange(event.target.value)}
            className="w-full rounded-md border border-border-subtle bg-bg-base/60 px-4 py-3 text-sm text-text-primary outline-none"
          >
            {driverOptions.map((driver) => (
              <option key={driver} value={driver}>
                {driver.toUpperCase()}
              </option>
            ))}
          </select>
        </label>

        <label className="space-y-2 block">
          <span className="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">
            Prompt
          </span>
          <input
            value={diagnosticsPrompt}
            onChange={(event) => onPromptChange(event.target.value)}
            className="w-full rounded-md border border-border-subtle bg-bg-base/60 px-4 py-3 text-sm text-text-primary outline-none"
          />
        </label>

        <button
          onClick={onRun}
          disabled={isPending}
          className="flex w-full items-center justify-center gap-2 rounded-md border border-brand-emerald/20 bg-brand-emerald/10 px-4 py-3 text-sm font-black text-brand-emerald transition hover:bg-brand-emerald/20 disabled:opacity-50"
        >
          <TestTube2 size={16} />
          {isPending
            ? `Testing ${diagnosticsDriver.toUpperCase()}...`
            : 'Run Probe'}
        </button>

        <div className="min-h-[180px] rounded-md border border-border-subtle bg-bg-base/60 p-4">
          {diagnostics ? (
            <div className="space-y-3">
              <div className="flex items-center justify-between text-xs uppercase tracking-widest">
                <span
                  className={
                    diagnostics.status === 'success'
                      ? 'font-black text-brand-emerald'
                      : 'font-black text-brand-danger'
                  }
                >
                  {diagnostics.status}
                </span>
                <span className="text-text-muted">
                  {diagnostics.latency_ms} ms
                </span>
              </div>
              <div className="text-sm font-semibold text-text-secondary">
                {diagnostics.driver.toUpperCase()}
              </div>
              <div className="whitespace-pre-wrap text-sm leading-relaxed text-text-secondary">
                {diagnostics.response || diagnostics.error || 'No output returned.'}
              </div>
            </div>
          ) : (
            <div className="text-sm leading-relaxed text-text-muted">
              Run a probe to verify the current AI runtime path from Laravel through
              AiGateway.
            </div>
          )}
        </div>
      </div>
    </RuntimeCard>
  );
}
