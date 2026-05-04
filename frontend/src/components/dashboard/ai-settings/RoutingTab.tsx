'use client';

import DataPanel from '@/components/ui/shared/DataPanel';
import ModelSelect from './ModelSelect';
import { NARRATIVE_PIPELINE_NODES } from '@/features/narrative-runtime/types';
import type { AgentConfig } from './types';

interface RoutingTabProps {
  agentConfigs: AgentConfig[];
  update: (agentId: string, patch: Partial<AgentConfig>) => void;
  providers?: Record<string, { status?: string; key_present?: boolean }>;
}

const engineNodes = NARRATIVE_PIPELINE_NODES.filter((n) => n.phase === 'engine');
const agentNodes = NARRATIVE_PIPELINE_NODES.filter((n) => n.phase === 'agent');

export default function RoutingTab({ agentConfigs, update, providers }: RoutingTabProps) {
  return (
    <div className="space-y-6">
      {providers && (
        <DataPanel title="Provider Status">
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
            {Object.entries(providers).map(([name, info]) => (
              <div
                key={name}
                className="flex items-center justify-between rounded border border-border-subtle bg-bg-base px-3 py-2"
              >
                <span className="text-xs font-medium text-text-secondary capitalize">{name}</span>
                <span
                  className={`h-2 w-2 rounded-full ${
                    info.status === 'ok' || info.key_present ? 'bg-brand-emerald' : 'bg-brand-danger'
                  }`}
                />
              </div>
            ))}
          </div>
        </DataPanel>
      )}

      <DataPanel
        title="Phase 1 — Engines"
        action={<span className="text-xs text-text-muted">Data analysis nodes</span>}
      >
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-border-subtle text-left">
              <th className="pb-2 text-xs font-medium text-text-disabled">Agent</th>
              <th className="pb-2 text-xs font-medium text-text-disabled">Role</th>
              <th className="pb-2 w-56 text-xs font-medium text-text-disabled">Model</th>
            </tr>
          </thead>
          <tbody>
            {engineNodes.map((node) => {
              const cfg = agentConfigs.find((c) => c.agentId === node.id)!;
              return (
                <tr key={node.id} className="border-b border-border-subtle/60">
                  <td className="py-2 pr-4">
                    <div className="flex items-center gap-2">
                      <span
                        className="h-2 w-2 flex-shrink-0 rounded-full"
                        style={{ backgroundColor: node.accent }}
                      />
                      <span className="text-xs font-medium text-text-primary">{node.label}</span>
                    </div>
                  </td>
                  <td className="py-2 pr-4 text-xs text-text-muted">{node.role}</td>
                  <td className="py-2">
                    <ModelSelect value={cfg.model} onChange={(v) => update(node.id, { model: v })} />
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </DataPanel>

      <DataPanel
        title="Phase 2 — Agents"
        action={<span className="text-xs text-text-muted">Creative generation nodes</span>}
      >
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-border-subtle text-left">
              <th className="pb-2 text-xs font-medium text-text-disabled">Agent</th>
              <th className="pb-2 text-xs font-medium text-text-disabled">Role</th>
              <th className="pb-2 w-56 text-xs font-medium text-text-disabled">Model</th>
            </tr>
          </thead>
          <tbody>
            {agentNodes.map((node) => {
              const cfg = agentConfigs.find((c) => c.agentId === node.id)!;
              return (
                <tr key={node.id} className="border-b border-border-subtle/60">
                  <td className="py-2 pr-4">
                    <div className="flex items-center gap-2">
                      <span
                        className="h-2 w-2 flex-shrink-0 rounded-full"
                        style={{ backgroundColor: node.accent }}
                      />
                      <span className="text-xs font-medium text-text-primary">{node.label}</span>
                    </div>
                  </td>
                  <td className="py-2 pr-4 text-xs text-text-muted">{node.role}</td>
                  <td className="py-2">
                    <ModelSelect value={cfg.model} onChange={(v) => update(node.id, { model: v })} />
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </DataPanel>
    </div>
  );
}
