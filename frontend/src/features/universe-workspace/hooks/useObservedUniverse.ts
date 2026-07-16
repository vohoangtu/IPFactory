'use client';
import { useEffect } from 'react';
import { useSimStore } from '@/shared/store/simStore';
import { useFeedStore } from '@/shared/store/feedStore';
import { useUniverseChannels } from '@/shared/realtime/useUniverseChannels';

/** Chọn universe vào simStore (clear feed khi đổi) + subscribe cụm kênh realtime. Dùng ở hero + mọi lens. */
export function useObservedUniverse(universeId: number | null, opts: { onLiveGap?: () => void } = {}): void {
  const selectUniverse = useSimStore((s) => s.selectUniverse);
  const selectedUniverseId = useSimStore((s) => s.selectedUniverseId);
  const clearFeed = useFeedStore((s) => s.clear);

  useEffect(() => {
    if (universeId != null && selectedUniverseId !== universeId) {
      clearFeed();
      selectUniverse(universeId);
    }
  }, [universeId, selectedUniverseId, selectUniverse, clearFeed]);

  useUniverseChannels(universeId, opts);
}
