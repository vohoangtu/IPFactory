'use client';

import { useState, useMemo } from 'react';
import { ChevronUp, ChevronDown, Search, Users } from 'lucide-react';
import type { ActorSummary } from '@/types/api';
import EmptyState from '@/components/ui/shared/EmptyState';

interface ActorGridProps {
  actors: ActorSummary[];
  onSelectActor: (id: number) => void;
}

type SortKey = 'name' | 'influence' | 'archetype' | 'alignment' | 'life_stage';
type SortDir = 'asc' | 'desc';

const ALIGNMENT_COLOR: Record<string, string> = {
  good: 'text-emerald-400',
  neutral: 'text-slate-400',
  evil: 'text-rose-400',
  chaotic: 'text-amber-400',
  lawful: 'text-cyan-400',
};

function SortIcon({ col, sortKey, sortDir }: { col: SortKey; sortKey: SortKey; sortDir: SortDir }) {
  if (col !== sortKey) return <span className="ml-1 text-slate-700">↕</span>;
  return sortDir === 'asc'
    ? <ChevronUp size={12} className="ml-1 inline text-blue-400" />
    : <ChevronDown size={12} className="ml-1 inline text-blue-400" />;
}

export default function ActorGrid({ actors, onSelectActor }: ActorGridProps) {
  const [search, setSearch] = useState('');
  const [archetypeFilter, setArchetypeFilter] = useState('');
  const [alignmentFilter, setAlignmentFilter] = useState('');
  const [aliveFilter, setAliveFilter] = useState('');
  const [sortKey, setSortKey] = useState<SortKey>('influence');
  const [sortDir, setSortDir] = useState<SortDir>('desc');

  const archetypeOptions = useMemo(
    () => [...new Set(actors.map((a) => a.archetype))].sort(),
    [actors],
  );
  const alignmentOptions = useMemo(
    () => [...new Set(actors.map((a) => a.alignment))].sort(),
    [actors],
  );

  const filtered = useMemo(() => {
    let list = actors;
    if (search) {
      const q = search.toLowerCase();
      list = list.filter((a) => a.name.toLowerCase().includes(q));
    }
    if (archetypeFilter) list = list.filter((a) => a.archetype === archetypeFilter);
    if (alignmentFilter) list = list.filter((a) => a.alignment === alignmentFilter);
    if (aliveFilter === 'alive') list = list.filter((a) => a.is_alive);
    if (aliveFilter === 'dead') list = list.filter((a) => !a.is_alive);

    return [...list].sort((a, b) => {
      let va: string | number = a[sortKey] ?? '';
      let vb: string | number = b[sortKey] ?? '';
      if (typeof va === 'number' && typeof vb === 'number') {
        return sortDir === 'asc' ? va - vb : vb - va;
      }
      va = String(va).toLowerCase();
      vb = String(vb).toLowerCase();
      return sortDir === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va);
    });
  }, [actors, search, archetypeFilter, alignmentFilter, aliveFilter, sortKey, sortDir]);

  const handleSort = (col: SortKey) => {
    if (sortKey === col) {
      setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'));
    } else {
      setSortKey(col);
      setSortDir('desc');
    }
  };

  const aliveCount = actors.filter((a) => a.is_alive).length;
  const deadCount = actors.length - aliveCount;

  return (
    <div className="space-y-4">
      {/* Toolbar */}
      <div className="flex flex-wrap items-center gap-3">
        <div className="relative flex-1 min-w-[200px]">
          <Search size={13} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500" />
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search by name…"
            className="w-full rounded border border-slate-700 bg-slate-800 pl-8 pr-3 py-2 text-sm text-white placeholder-slate-600 focus:border-blue-500 focus:outline-none"
          />
        </div>

        <select
          value={archetypeFilter}
          onChange={(e) => setArchetypeFilter(e.target.value)}
          className="rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-300 focus:border-blue-500 focus:outline-none"
        >
          <option value="">All archetypes</option>
          {archetypeOptions.map((v) => <option key={v} value={v}>{v}</option>)}
        </select>

        <select
          value={alignmentFilter}
          onChange={(e) => setAlignmentFilter(e.target.value)}
          className="rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-300 focus:border-blue-500 focus:outline-none"
        >
          <option value="">All alignments</option>
          {alignmentOptions.map((v) => <option key={v} value={v}>{v}</option>)}
        </select>

        <select
          value={aliveFilter}
          onChange={(e) => setAliveFilter(e.target.value)}
          className="rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-300 focus:border-blue-500 focus:outline-none"
        >
          <option value="">All status</option>
          <option value="alive">Alive ({aliveCount})</option>
          <option value="dead">Dead ({deadCount})</option>
        </select>

        <span className="ml-auto text-xs text-slate-500">
          {filtered.length} of {actors.length} actors
        </span>
      </div>

      {/* Table */}
      {filtered.length === 0 ? (
        <EmptyState icon={Users} title="No actors found" message="Try adjusting your filters." />
      ) : (
        <div className="rounded-lg border border-slate-800 bg-slate-900 overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-slate-800 text-left">
                  {([
                    { key: 'name' as SortKey, label: 'Name' },
                    { key: 'archetype' as SortKey, label: 'Archetype' },
                    { key: 'alignment' as SortKey, label: 'Alignment' },
                    { key: 'life_stage' as SortKey, label: 'Life Stage' },
                    { key: 'influence' as SortKey, label: 'Influence' },
                  ]).map((col) => (
                    <th
                      key={col.key}
                      onClick={() => handleSort(col.key)}
                      className="cursor-pointer select-none px-4 py-3 text-xs font-medium text-slate-500 hover:text-slate-300"
                    >
                      {col.label}
                      <SortIcon col={col.key} sortKey={sortKey} sortDir={sortDir} />
                    </th>
                  ))}
                  <th className="px-4 py-3 text-xs font-medium text-slate-500 text-right">Status</th>
                </tr>
              </thead>
              <tbody>
                {filtered.map((actor, i) => {
                  const alignClass = ALIGNMENT_COLOR[actor.alignment?.toLowerCase()] ?? 'text-slate-400';
                  return (
                    <tr
                      key={actor.id}
                      onClick={() => onSelectActor(actor.id)}
                      className={`cursor-pointer border-b border-slate-800/60 transition hover:bg-slate-800/40 ${
                        i % 2 === 0 ? '' : 'bg-slate-800/10'
                      }`}
                    >
                      <td className="px-4 py-3">
                        <span className="font-medium text-white">{actor.name}</span>
                        <span className="ml-2 text-[10px] text-slate-600">#{actor.id}</span>
                      </td>
                      <td className="px-4 py-3">
                        <span className="rounded bg-violet-500/10 px-1.5 py-0.5 text-[11px] font-medium text-violet-300">
                          {actor.archetype}
                        </span>
                      </td>
                      <td className={`px-4 py-3 text-xs font-semibold uppercase ${alignClass}`}>
                        {actor.alignment}
                      </td>
                      <td className="px-4 py-3 text-xs text-slate-400">{actor.life_stage}</td>
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-2">
                          <div className="h-1.5 w-20 overflow-hidden rounded-full bg-slate-800">
                            <div
                              className="h-full rounded-full bg-blue-500"
                              style={{ width: `${Math.min(actor.influence, 100)}%` }}
                            />
                          </div>
                          <span className="tabular-nums text-xs text-slate-400">
                            {actor.influence?.toFixed(1)}%
                          </span>
                        </div>
                      </td>
                      <td className="px-4 py-3 text-right">
                        {actor.is_alive ? (
                          <span className="inline-flex items-center gap-1 rounded bg-green-500/10 px-1.5 py-0.5 text-[10px] font-semibold text-green-400">
                            <span className="h-1.5 w-1.5 rounded-full bg-green-400" />
                            Alive
                          </span>
                        ) : (
                          <span className="inline-flex items-center gap-1 rounded bg-slate-800 px-1.5 py-0.5 text-[10px] font-semibold text-slate-500">
                            Dead {actor.death_tick != null ? `· T${actor.death_tick}` : ''}
                          </span>
                        )}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
}
