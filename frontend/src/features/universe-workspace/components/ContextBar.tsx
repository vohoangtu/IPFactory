'use client';
import { useRouter } from 'next/navigation';
import { useSimStore } from '@/shared/store/simStore';
import { useUniverses } from '../hooks/useUniverses';
import { routes } from '@/shared/config/routes';
import { Pill } from '@/shared/ui/Pill';

export function ContextBar() {
  const router = useRouter();
  const { data: universes = [] } = useUniverses();
  const selectedId = useSimStore((s) => s.selectedUniverseId);
  const tick = useSimStore((s) => s.live.tick);
  const status = useSimStore((s) => s.live.status);
  const connection = useSimStore((s) => s.connection);
  const selected = universes.find((u) => u.id === selectedId);

  return (
    <header className="flex items-center gap-4 border-b border-white/10 bg-black/40 px-4 py-2">
      <select
        className="rounded-lg border border-white/15 bg-black/40 px-2 py-1 text-sm text-gray-200"
        value={selectedId ?? ''}
        onChange={(e) => { const id = Number(e.target.value); router.push(routes.live(id)); }}
      >
        <option value="" disabled>Chọn Universe…</option>
        {universes.map((u) => <option key={u.id} value={u.id}>{u.name}</option>)}
      </select>
      {selected && (
        <span className="text-sm text-gray-300">
          {selected.name} · Era {selected.era} · Tick {tick || selected.current_tick}
        </span>
      )}
      <span className="ml-auto flex items-center gap-2">
        <Pill tone={status === 'paused' ? 'paused' : status === 'halted' ? 'halted' : 'active'}>{status ?? selected?.status ?? '—'}</Pill>
        <Pill tone={connection === 'connected' ? 'active' : 'neutral'}>{connection === 'connected' ? '● LIVE' : connection}</Pill>
      </span>
    </header>
  );
}
