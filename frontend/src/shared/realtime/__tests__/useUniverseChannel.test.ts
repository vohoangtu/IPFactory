import { describe, it, expect, beforeEach, vi } from 'vitest';
import { renderHook } from '@testing-library/react';
import { makeFakeCentrifuge } from '@/test/fakeCentrifuge';
import { useSimStore } from '@/shared/store/simStore';

const fake = makeFakeCentrifuge();
vi.mock('@/shared/lib/centrifugo', () => ({ getCentrifuge: () => fake.centrifuge }));

import { useUniverseChannel } from '../useUniverseChannel';

describe('useUniverseChannel', () => {
  beforeEach(() => { vi.clearAllMocks(); useSimStore.getState().reset(); });

  it('subscribes to universes:{id} and pushes ticks into the store', () => {
    renderHook(() => useUniverseChannel(2));
    expect(fake.centrifuge.newSubscription).toHaveBeenCalledWith('universes:2');
    fake.emit({ tick: 12, metrics: { stability: 0.4, entropy: 0.6, era: 2 }, event: { tick: 12, type: 'x', summary: 'X' } });
    const s = useSimStore.getState();
    expect(s.live.tick).toBe(12);
    expect(s.live.events[0].summary).toBe('X');
  });

  it('does nothing when id is null', () => {
    renderHook(() => useUniverseChannel(null));
    expect(fake.centrifuge.newSubscription).not.toHaveBeenCalled();
  });
});
