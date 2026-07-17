'use client';

import { useEffect, useState } from 'react';
import type { Centrifuge } from 'centrifuge';
import { getCentrifuge } from '@/shared/lib/centrifugo';

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
