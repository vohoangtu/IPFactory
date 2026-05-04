'use client';

import { Sparkles, ScrollText } from 'lucide-react';
import { useUniverseDossier } from '@/features/universe/hooks';
import { useUniverse } from '@/contexts/UniverseContext';
import { formatMetric, sentenceCase, getRecord, getEntries } from '@/lib/utils';
import DataPanel from '@/components/ui/shared/DataPanel';

export default function LoreTab() {
  const { activeUniverseId } = useUniverse();
  const { dossier } = useUniverseDossier(activeUniverseId);

  const myths = getRecord(dossier?.myths);
  const religions = getRecord(dossier?.religions);
  const dominantReligion = getRecord(religions.dominant);
  return (
    <div className="grid gap-4 md:grid-cols-2">
      <DataPanel title="Mythogenetic Tree" action={<Sparkles size={14} className="text-brand-accent" />}>
        <div className="space-y-3">
          {getEntries(myths.top_types).map(([label, count]) => (
            <div key={label} className="rounded-lg border border-border-subtle bg-bg-base p-3">
              <div className="flex justify-between items-center mb-1">
                <span className="font-bold text-text-primary text-sm">{sentenceCase(label)}</span>
                <span className="text-brand-accent font-black">{String(count)}</span>
              </div>
              <div className="text-xs text-text-muted italic">Genesis from chronicle patterns</div>
            </div>
          ))}
        </div>
      </DataPanel>

      <DataPanel title="Active Religions" action={<ScrollText size={14} className="text-brand-emerald" />}>
        <div className="space-y-4">
          <div className="rounded-lg bg-brand-emerald/5 border border-brand-emerald/20 p-4">
            <div className="text-[10px] font-black text-brand-emerald uppercase tracking-widest mb-2">Dominant Faith</div>
            <h4 className="text-xl font-black text-text-primary">{sentenceCase(String(dominantReligion.name || 'none'))}</h4>

            <div className="mt-3 rounded-md bg-bg-base border border-brand-emerald/10 p-3">
              <div className="text-[9px] font-black text-text-disabled uppercase tracking-widest mb-2">Sacred Doctrine</div>
              <p className="text-sm text-brand-emerald/80 leading-relaxed italic">
                &quot;{String(dominantReligion.doctrine || 'No records in the Great Library.')}&quot;
              </p>
            </div>

            <div className="mt-3 grid grid-cols-2 gap-3">
              <div className="bg-bg-base p-3 rounded-md border border-border-subtle">
                <div className="text-[9px] text-text-disabled uppercase font-black tracking-widest mb-1">Followers</div>
                <div className="text-lg font-bold text-text-primary">{String(dominantReligion.followers || 0)}</div>
              </div>
              <div className="bg-bg-base p-3 rounded-md border border-border-subtle">
                <div className="text-[9px] text-text-disabled uppercase font-black tracking-widest mb-1">Spread Rate</div>
                <div className="text-lg font-bold text-text-primary">{formatMetric(Number(dominantReligion.spread_rate || 0), 2)}</div>
              </div>
            </div>

            {Array.isArray(dominantReligion.holy_sites) && dominantReligion.holy_sites.length > 0 && (
              <div className="mt-3">
                <div className="text-[9px] text-text-disabled uppercase font-black tracking-widest mb-2">Holy Sites</div>
                <div className="flex flex-wrap gap-2">
                  {dominantReligion.holy_sites.map((site: unknown, i: number) => (
                    <span key={i} className="px-3 py-1 rounded-full bg-brand-emerald/10 text-[10px] font-bold text-brand-emerald border border-brand-emerald/20">
                      {String(site)}
                    </span>
                  ))}
                </div>
              </div>
            )}
          </div>
        </div>
      </DataPanel>
    </div>
  );
}
