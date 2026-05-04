'use client';

import { History } from 'lucide-react';
import { useUniverseDossier } from '@/features/universe/hooks';
import { useUniverse } from '@/contexts/UniverseContext';
import { sentenceCase, getRecord } from '@/lib/utils';
import DataPanel from '@/components/ui/shared/DataPanel';

type Dictionary = Record<string, unknown>;

export default function HistoryTab() {
  const { activeUniverseId } = useUniverse();
  const { dossier } = useUniverseDossier(activeUniverseId);

  const history = getRecord(dossier?.history);
  const historySpine = getRecord(history.spine);
  const eraSummaries = Array.isArray(history.eras) ? history.eras : [];

  const historyEvents: Array<{ label: string; event: Dictionary }> = [
    { label: 'Founding', event: getRecord(historySpine.founding_event) },
    { label: 'Golden Age', event: getRecord(historySpine.golden_age_hint) },
    { label: 'Crisis', event: getRecord(historySpine.crisis_hint) },
  ];
  return (
    <div className="grid gap-4 xl:grid-cols-[0.7fr_1.3fr]">
      <div className="space-y-4">
        {historyEvents.map(({ label, event }) => (
          <DataPanel key={label} title={label} action={
            <span className="rounded bg-brand-info/10 px-2 py-1 text-[10px] font-black text-brand-info border border-brand-info/20">
              TICK {String(event.tick ?? '???')}
            </span>
          }>
            <div className="text-base font-black text-text-primary mb-2">{sentenceCase(String(event.type || 'Undefined'))}</div>
            <p className="text-sm text-text-secondary leading-relaxed italic line-clamp-3">
              &quot;{String(event.summary || 'Chưa có bản ghi lịch sử.')}&quot;
            </p>
          </DataPanel>
        ))}
      </div>

      <DataPanel title="Historical Eras / Timeline" action={<History size={14} className="text-brand-danger" />}>
        <div className="relative pl-8 before:absolute before:left-3 before:top-2 before:bottom-0 before:w-px before:bg-gradient-to-b before:from-brand-danger/60 before:to-transparent">
          {eraSummaries.map((era, i) => (
            <div key={i} className="mb-8 relative">
              <div className="absolute -left-[25px] top-1 h-3 w-3 rounded-full bg-brand-danger shadow-[0_0_10px_rgba(244,63,94,0.5)] border-2 border-bg-base" />
              <div className="flex items-center gap-4 mb-2">
                <span className="text-xs font-black text-brand-danger tracking-widest">
                  {String((era as Dictionary).start_tick)} - {String((era as Dictionary).end_tick)}
                </span>
                <div className="h-px flex-1 bg-border-subtle" />
              </div>
              <h4 className="text-lg font-black text-text-primary mb-1 tracking-tight">{String((era as Dictionary).title)}</h4>
              <p className="text-sm text-text-secondary leading-relaxed">{String((era as Dictionary).summary)}</p>
            </div>
          ))}
        </div>
      </DataPanel>
    </div>
  );
}
