'use client';
import { useUniverses } from '@/features/universe-workspace';
import { useSimStore } from '@/shared/store/simStore';

export function UniverseSelect() {
  const { data: universes = [] } = useUniverses();
  const selectedId = useSimStore((s) => s.selectedUniverseId);
  const selectUniverse = useSimStore((s) => s.selectUniverse);

  return (
    <select
      aria-label="Chọn Universe"
      className="rounded-lg border border-white/15 bg-black/40 px-2 py-1 text-sm text-gray-200"
      value={selectedId ?? ''}
      onChange={(e) => {
        const value = e.target.value;
        selectUniverse(value === '' ? null : Number(value));
      }}
    >
      <option value="">— Chọn universe —</option>
      {universes.map((u) => (
        <option key={u.id} value={u.id}>
          {u.name}
        </option>
      ))}
    </select>
  );
}
