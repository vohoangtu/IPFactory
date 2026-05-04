import { Centrifuge } from 'centrifuge';
import api from '@/lib/api';

const CENTRIFUGO_URL =
  process.env.NEXT_PUBLIC_CENTRIFUGO_URL || 'ws://localhost/connection/websocket';

let _instance: Centrifuge | null = null;

/**
 * Get or create a singleton Centrifuge client.
 *
 * The client fetches a JWT token from the backend on connection.
 * Token is refreshed automatically when it expires.
 */
export function getCentrifuge(): Centrifuge {
  if (_instance) return _instance;

  _instance = new Centrifuge(CENTRIFUGO_URL, {
    getToken: async () => {
      try {
        const res = await api.post('/worldos/centrifugo/token');
        return res.data.token;
      } catch {
        return '';
      }
    },
  });

  return _instance;
}

/** For testing or cleanup */
export function resetCentrifuge(): void {
  if (_instance) {
    _instance.disconnect();
    _instance = null;
  }
}
