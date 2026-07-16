import { create } from 'zustand';
import type { FeedItem } from '@/shared/realtime/envelope';

const MAX_ITEMS = 300;

export interface FeedStore {
  items: FeedItem[];
  pushLive: (item: FeedItem) => void;
  clear: () => void;
}

/** Sự kiện tường thuật đến qua realtime, mới nhất trước; nguồn "live" của Living Chronicle. */
export const useFeedStore = create<FeedStore>((set) => ({
  items: [],
  pushLive: (item) => set((s) => {
    if (s.items.some((i) => i.id === item.id)) return s;
    return { items: [item, ...s.items].slice(0, MAX_ITEMS) };
  }),
  clear: () => set({ items: [] }),
}));
