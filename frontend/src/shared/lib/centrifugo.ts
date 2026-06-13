import { Centrifuge } from 'centrifuge';
import { apiClient } from './apiClient';

let instance: Centrifuge | null = null;

/** Singleton Centrifuge client; fetches a connection token from the backend. */
export function getCentrifuge(): Centrifuge {
  if (instance) return instance;
  const wsUrl = (process.env.NEXT_PUBLIC_WS_URL || '/connection/websocket');
  instance = new Centrifuge(wsUrl, {
    getToken: async () => {
      const res = await apiClient.post('/worldos/centrifugo/token');
      return (res.data as { token: string }).token;
    },
  });
  return instance;
}
