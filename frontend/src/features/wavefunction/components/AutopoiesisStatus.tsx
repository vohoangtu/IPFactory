'use client';

import BadgeLabel from '@/components/ui/shared/BadgeLabel';
import SectionPanel from '@/components/ui/shared/SectionPanel';
import type { WavefunctionData } from '@/types/api';

interface Props {
  autopoiesis: WavefunctionData['autopoiesis'] | undefined;
}

export default function AutopoiesisStatus({ autopoiesis }: Props) {
  if (!autopoiesis) {
    return (
      <SectionPanel>
        <h3 className="mb-4 text-sm font-black uppercase tracking-[0.2em] text-slate-300">
          Autopoiesis
        </h3>
        <div className="flex h-20 items-center justify-center text-sm text-slate-500">
          Loading...
        </div>
      </SectionPanel>
    );
  }

  const mutationVector = autopoiesis.last_mutation_vector;

  return (
    <SectionPanel>
      <h3 className="mb-4 text-sm font-black uppercase tracking-[0.2em] text-slate-300">
        Autopoiesis
      </h3>

      <div className="space-y-3">
        {/* Enabled / Disabled badge */}
        <div className="flex items-center justify-between">
          <span className="text-xs text-slate-400">Status</span>
          <BadgeLabel variant={autopoiesis.enabled ? 'emerald' : 'rose'}>
            {autopoiesis.enabled ? 'Enabled' : 'Disabled'}
          </BadgeLabel>
        </div>

        {/* Entropy threshold */}
        <div className="flex items-center justify-between">
          <span className="text-xs text-slate-400">Entropy Threshold</span>
          <span className="font-mono text-sm text-slate-200">
            {autopoiesis.entropy_threshold.toFixed(3)}
          </span>
        </div>

        {/* Mutation history size */}
        <div className="flex items-center justify-between">
          <span className="text-xs text-slate-400">Mutation History</span>
          <span className="font-mono text-sm text-slate-200">
            {autopoiesis.mutation_history_size}
          </span>
        </div>

        {/* Last mutation vector */}
        {mutationVector && Object.keys(mutationVector).length > 0 && (
          <div className="mt-2 rounded-xl border border-slate-800 bg-slate-900/50 p-3">
            <span className="mb-2 block text-[10px] font-bold uppercase tracking-[0.15em] text-slate-500">
              Last Mutation Vector
            </span>
            <div className="space-y-1">
              {Object.entries(mutationVector).map(([key, value]) => (
                <div key={key} className="flex items-center justify-between text-xs">
                  <span className="text-slate-400">{key}</span>
                  <span className="font-mono text-cyan-300">{value.toFixed(4)}</span>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </SectionPanel>
  );
}
