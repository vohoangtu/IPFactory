'use client';
import { useRouter } from 'next/navigation';
import { useSimStore, type SimMode } from '@/shared/store/simStore';
import { routes } from '@/shared/config/routes';

const MODES: { key: SimMode; label: string }[] = [
  { key: 'live', label: 'LIVE' },
  { key: 'replay', label: 'REPLAY' },
  { key: 'multiverse', label: 'MULTIVERSE' },
];

export function ModeSwitcher() {
  const router = useRouter();
  const mode = useSimStore((s) => s.view.mode);
  const id = useSimStore((s) => s.selectedUniverseId);
  return (
    <nav className="flex flex-col gap-1 border-r border-white/10 p-2">
      {MODES.map((m) => (
        <button
          key={m.key}
          aria-current={mode === m.key ? 'page' : undefined}
          onClick={() => {
            if (m.key === 'multiverse') return router.push(routes.multiverse());
            if (id != null) router.push(m.key === 'live' ? routes.live(id) : routes.replay(id));
          }}
          className={`rounded-lg px-3 py-2 text-left text-xs font-bold tracking-wider ${mode === m.key ? 'bg-white/15 text-white' : 'text-gray-400 hover:bg-white/5'}`}
        >
          {m.label}
        </button>
      ))}
    </nav>
  );
}
