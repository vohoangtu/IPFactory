'use client';

import { useEffect, useRef, useState } from 'react';
import type { Centrifuge, PublicationContext, Subscription } from 'centrifuge';
import { getCentrifuge } from '@/lib/centrifugo';

export type ConnectionState = 'disconnected' | 'connecting' | 'connected';

/**
 * Manages the shared Centrifuge client lifecycle and exposes connection state
 * so screens can decide whether to fall back to polling.
 */
export function useCentrifugoConnection(): {
  state: ConnectionState;
  client: Centrifuge | null;
} {
  const [state, setState] = useState<ConnectionState>('disconnected');
  const client = typeof window === 'undefined' ? null : getCentrifuge();

  useEffect(() => {
    if (typeof window === 'undefined') return;

    const centrifuge = getCentrifuge();
    const handleConnected = () => setState('connected');
    const handleConnecting = () => setState('connecting');
    const handleDisconnected = () => setState('disconnected');

    centrifuge.on('connected', handleConnected);
    centrifuge.on('connecting', handleConnecting);
    centrifuge.on('disconnected', handleDisconnected);
    centrifuge.connect();

    return () => {
      centrifuge.off('connected', handleConnected);
      centrifuge.off('connecting', handleConnecting);
      centrifuge.off('disconnected', handleDisconnected);
    };
  }, []);

  return { state, client };
}

/**
 * Subscribes to a Centrifugo channel and forwards each publication payload to
 * the latest callback instance.
 */
export function useCentrifugoSubscription(
  channel: string | null,
  onMessage: (data: Record<string, unknown>) => void,
): void {
  const callbackRef = useRef(onMessage);

  useEffect(() => {
    callbackRef.current = onMessage;
  }, [onMessage]);

  useEffect(() => {
    if (!channel || typeof window === 'undefined') return;

    const client = getCentrifuge();
    const subscription: Subscription = client.getSubscription(channel) ?? client.newSubscription(channel);

    if (!subscription.listeners('publication').length) {
      subscription.on('publication', (ctx: PublicationContext) => {
        callbackRef.current(ctx.data as Record<string, unknown>);
      });
    }

    subscription.subscribe();

    return () => {
      subscription.unsubscribe();
      subscription.removeAllListeners();
    };
  }, [channel]);
}

/**
 * Returns an adaptive polling interval: disable polling when WebSocket is
 * healthy, otherwise fall back to a fixed interval.
 */
export function useAdaptiveRefetchInterval(
  state: ConnectionState,
  fallbackMs = 60_000,
): number | false {
  return state === 'connected' ? false : fallbackMs;
}
