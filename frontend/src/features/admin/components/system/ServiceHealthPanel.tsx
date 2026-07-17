'use client';

import React from 'react';
import { RefreshCcw, Server } from 'lucide-react';
import type { ServiceStatusResponse } from '../../types';

interface ServiceHealthPanelProps {
  serviceStatus?: ServiceStatusResponse | null;
  isLoading: boolean;
}

export default function ServiceHealthPanel({ serviceStatus, isLoading }: ServiceHealthPanelProps) {
  return (
    <div className="rounded-lg border border-border-subtle/50 bg-bg-surface p-6">
      <div className="mb-5 flex items-center gap-3">
        <div className="rounded-md bg-brand-info/10 p-3 text-brand-info">
          <Server size={18} />
        </div>
        <div>
          <h2 className="text-lg font-black text-text-primary">Service Health</h2>
          <p className="text-xs text-text-muted">
            Live status from <code>/api/worldos/service-status</code>
          </p>
        </div>
      </div>

      {isLoading || !serviceStatus ? (
        <div className="flex items-center justify-center py-8">
          <RefreshCcw size={18} className="animate-spin text-text-disabled" />
        </div>
      ) : (
        <div className="space-y-3">
          {Object.entries(serviceStatus.services).map(([name, service]) => (
            <div
              key={name}
              className="rounded-md border border-border-subtle bg-bg-base/50 p-4"
            >
              <div className="flex items-center justify-between gap-3">
                <div>
                  <p className="text-sm font-black text-text-primary">{name}</p>
                  <p className="text-[11px] text-text-muted">
                    {service.latency_ms ? `${service.latency_ms} ms` : 'No latency sample'}
                  </p>
                </div>
                <span
                  className={`rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-[0.2em] ${
                    service.status === 'ok'
                      ? 'bg-brand-emerald/10 text-emerald-300'
                      : 'bg-brand-amber/10 text-brand-amber'
                  }`}
                >
                  {service.status}
                </span>
              </div>
              {service.error ? (
                <p className="mt-3 text-xs leading-relaxed text-brand-danger">
                  {service.error}
                </p>
              ) : null}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
