'use client';
import { useEffect, useRef } from 'react';
import type { PublicationContext } from 'centrifuge';
import { getCentrifuge } from '@/shared/lib/centrifugo';
import { useSimStore } from '@/shared/store/simStore';
import { useFeedStore } from '@/shared/store/feedStore';
import { envelopeToFeedItem, parseEnvelope } from './envelope';

const LENS_SUFFIXES = ['', ':narrative', ':anomaly', ':autopoiesis'] as const;

interface Options { onLiveGap?: () => void }

/**
 * Subscribe cụm kênh Observatory của một universe (hợp đồng P1).
 * universe.pulsed → simStore; mọi sự kiện tường thuật khác → feedStore.
 * onLiveGap được gọi khi kênh gốc re-subscribe sau khi mất kết nối (caller nên refetch feed).
 */
export function useUniverseChannels(universeId: number | null, opts: Options = {}): void {
  const setConnection = useSimStore((s) => s.setConnection);
  const applyPulse = useSimStore((s) => s.applyPulse);
  const pushLive = useFeedStore((s) => s.pushLive);
  const onLiveGapRef = useRef(opts.onLiveGap);
  useEffect(() => {
    onLiveGapRef.current = opts.onLiveGap;
  });

  useEffect(() => {
    if (universeId == null) return;
    const centrifuge = getCentrifuge();
    setConnection('connecting');
    centrifuge.connect();

    const onPublication = (ctx: PublicationContext) => {
      const env = parseEnvelope(ctx.data);
      if (!env || env.universe_id !== universeId) return;
      if (env.type === 'universe.pulsed') applyPulse(env);
      else pushLive(envelopeToFeedItem(env));
    };

    let firstSubscribe = true;
    const subs = LENS_SUFFIXES.map((suffix) => {
      const sub = centrifuge.newSubscription(`universes:${universeId}${suffix}`);
      sub.on('publication', onPublication);
      if (suffix === '') {
        sub.on('subscribed', () => {
          setConnection('connected');
          if (!firstSubscribe) onLiveGapRef.current?.();
          firstSubscribe = false;
        });
      }
      sub.subscribe();
      return sub;
    });

    return () => {
      subs.forEach((sub) => { sub.removeAllListeners(); sub.unsubscribe(); });
      setConnection('disconnected');
    };
  }, [universeId, applyPulse, pushLive, setConnection]);
}
