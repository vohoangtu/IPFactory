'use client';
import { useEffect } from 'react';
import type { PublicationContext } from 'centrifuge';
import { getCentrifuge } from '@/shared/lib/centrifugo';
import { useSimStore } from '@/shared/store/simStore';
import type { LiveMetrics, SimEvent } from '@/shared/types/domain';

interface TickPayload { tick: number; metrics?: LiveMetrics; event?: SimEvent; status?: string; }

/** Subscribe the selected universe's Centrifugo channel and stream ticks into the sim-store. */
export function useUniverseChannel(universeId: number | null): void {
  const setConnection = useSimStore((s) => s.setConnection);
  const applyTick = useSimStore((s) => s.applyTick);

  useEffect(() => {
    if (universeId == null) return;
    const centrifuge = getCentrifuge();
    setConnection('connecting');
    centrifuge.connect();
    const sub = centrifuge.newSubscription(`universes:${universeId}`);
    sub.on('publication', (ctx: PublicationContext) => {
      const p = ctx.data as TickPayload;
      if (typeof p?.tick === 'number') applyTick(p);
    });
    sub.on('subscribed', () => setConnection('connected'));
    sub.subscribe();
    return () => { sub.removeAllListeners(); sub.unsubscribe(); setConnection('disconnected'); };
  }, [universeId, applyTick, setConnection]);
}
