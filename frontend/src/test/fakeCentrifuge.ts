import { vi } from 'vitest';

export function makeFakeCentrifuge() {
  const handlers: Record<string, (ctx: { data: unknown }) => void> = {};
  const sub = {
    on: vi.fn((event: string, cb: (ctx: { data: unknown }) => void) => { handlers[event] = cb; return sub; }),
    subscribe: vi.fn(),
    unsubscribe: vi.fn(),
    removeAllListeners: vi.fn(),
  };
  const centrifuge = {
    connect: vi.fn(),
    disconnect: vi.fn(),
    newSubscription: vi.fn(() => sub),
    getSubscription: vi.fn(() => null),
  };
  return { centrifuge, sub, emit: (data: unknown) => handlers['publication']?.({ data }) };
}
