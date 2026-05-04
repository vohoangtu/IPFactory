'use client';

import { ChevronDown } from 'lucide-react';
import { AnimatePresence, motion } from 'framer-motion';
import DataPanel from '@/components/ui/shared/DataPanel';
import { NARRATIVE_PIPELINE_NODES } from '@/features/narrative-runtime/types';
import Slider from './Slider';
import type { AgentConfig } from './types';

interface ParamsTabProps {
  agentConfigs: AgentConfig[];
  update: (agentId: string, patch: Partial<AgentConfig>) => void;
  expandedAgent: string | null;
  setExpandedAgent: (id: string | null) => void;
}

export default function ParamsTab({ agentConfigs, update, expandedAgent, setExpandedAgent }: ParamsTabProps) {
  return (
    <DataPanel
      title="Agent Parameters"
      action={<span className="text-xs text-text-muted">Click a row to expand</span>}
    >
      <div className="divide-y divide-border-subtle">
        {NARRATIVE_PIPELINE_NODES.map((node) => {
          const cfg = agentConfigs.find((c) => c.agentId === node.id)!;
          const isOpen = expandedAgent === node.id;
          return (
            <div key={node.id}>
              <button
                onClick={() => setExpandedAgent(isOpen ? null : node.id)}
                className="flex w-full items-center gap-3 px-1 py-3 text-left transition hover:bg-bg-elevated/40"
              >
                <span
                  className="h-2 w-2 flex-shrink-0 rounded-full"
                  style={{ backgroundColor: node.accent }}
                />
                <span className="flex-1 text-sm font-medium text-text-primary">{node.label}</span>
                <span
                  className={`text-[10px] font-semibold uppercase ${
                    node.phase === 'engine' ? 'text-brand-amber' : 'text-brand-accent'
                  }`}
                >
                  {node.phase}
                </span>
                <span className="text-xs text-text-disabled">
                  T:{cfg.temperature} · {cfg.maxTokens} tok · ×{cfg.retryAttempts}
                </span>
                <ChevronDown
                  size={14}
                  className={`flex-shrink-0 text-text-muted transition-transform ${isOpen ? 'rotate-180' : ''}`}
                />
              </button>
              <AnimatePresence>
                {isOpen && (
                  <motion.div
                    initial={{ height: 0, opacity: 0 }}
                    animate={{ height: 'auto', opacity: 1 }}
                    exit={{ height: 0, opacity: 0 }}
                    transition={{ duration: 0.18 }}
                    className="overflow-hidden"
                  >
                    <div className="grid grid-cols-1 gap-6 bg-bg-elevated/30 px-5 py-4 sm:grid-cols-3">
                      <Slider
                        label="Temperature"
                        value={cfg.temperature}
                        min={0}
                        max={2}
                        step={0.1}
                        onChange={(v) => update(node.id, { temperature: v })}
                      />
                      <Slider
                        label="Max Tokens"
                        value={cfg.maxTokens}
                        min={256}
                        max={8192}
                        step={256}
                        unit=" tok"
                        onChange={(v) => update(node.id, { maxTokens: v })}
                      />
                      <Slider
                        label="Retry Attempts"
                        value={cfg.retryAttempts}
                        min={1}
                        max={5}
                        step={1}
                        onChange={(v) => update(node.id, { retryAttempts: v })}
                      />
                    </div>
                  </motion.div>
                )}
              </AnimatePresence>
            </div>
          );
        })}
      </div>
    </DataPanel>
  );
}
