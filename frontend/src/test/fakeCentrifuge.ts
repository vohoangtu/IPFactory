import { vi } from 'vitest';

type Handler = (ctx: { data: unknown }) => void;

export function makeFakeCentrifugeMulti() {
  const handlers = new Map<string, { publication: Handler[]; subscribed: (() => void)[] }>();
  const registry = new Map<string, unknown>();
  const clientHandlers = new Map<string, (() => void)[]>();

  const centrifuge = {
    connect: vi.fn(),
    disconnect: vi.fn(),
    on: vi.fn((event: string, cb: () => void) => {
      const list = clientHandlers.get(event) ?? [];
      list.push(cb);
      clientHandlers.set(event, list);
      return centrifuge;
    }),
    removeListener: vi.fn((event: string, cb: () => void) => {
      const list = clientHandlers.get(event);
      if (!list) return centrifuge;
      const idx = list.indexOf(cb);
      if (idx !== -1) list.splice(idx, 1);
      return centrifuge;
    }),
    newSubscription: vi.fn((channel: string) => {
      if (registry.has(channel)) {
        throw new Error(`Subscription to the channel ${channel} already exists`);
      }
      const entry = { publication: [] as Handler[], subscribed: [] as (() => void)[] };
      handlers.set(channel, entry);
      const sub = {
        on: vi.fn((event: string, cb: Handler | (() => void)) => {
          if (event === 'publication') entry.publication.push(cb as Handler);
          if (event === 'subscribed') entry.subscribed.push(cb as () => void);
          return sub;
        }),
        subscribe: vi.fn(() => entry.subscribed.forEach((cb) => cb())),
        unsubscribe: vi.fn(),
        removeAllListeners: vi.fn(),
      };
      registry.set(channel, sub);
      return sub;
    }),
    removeSubscription: vi.fn((sub: unknown) => {
      for (const [channel, registered] of registry) {
        if (registered === sub) {
          registry.delete(channel);
          break;
        }
      }
    }),
  };

  return {
    centrifuge,
    emit: (channel: string, data: unknown) =>
      handlers.get(channel)?.publication.forEach((cb) => cb({ data })),
    resubscribe: (channel: string) => handlers.get(channel)?.subscribed.forEach((cb) => cb()),
    subscribedChannels: () => [...handlers.keys()],
    emitClient: (event: string) => clientHandlers.get(event)?.forEach((cb) => cb()),
  };
}
