import { vi } from 'vitest';

type Handler = (ctx: { data: unknown }) => void;

export function makeFakeCentrifugeMulti() {
  const handlers = new Map<string, { publication: Handler[]; subscribed: (() => void)[] }>();
  const subs = new Map<string, unknown>();

  const centrifuge = {
    connect: vi.fn(),
    disconnect: vi.fn(),
    newSubscription: vi.fn((channel: string) => {
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
      subs.set(channel, sub);
      return sub;
    }),
  };

  return {
    centrifuge,
    emit: (channel: string, data: unknown) =>
      handlers.get(channel)?.publication.forEach((cb) => cb({ data })),
    resubscribe: (channel: string) => handlers.get(channel)?.subscribed.forEach((cb) => cb()),
    subscribedChannels: () => [...handlers.keys()],
  };
}
