import type { FeedItem } from '@/shared/realtime/envelope';
import type { FeedPage } from '../api/feed';

/** Gộp live (realtime) + các trang lịch sử: dedup theo id, DESC theo tick;
 *  cùng tick thì event trước chronicle, rồi occurred_at mới trước. */
export function mergeFeed(live: FeedItem[], pages: FeedPage[]): FeedItem[] {
  const seen = new Set<string>();
  const all: FeedItem[] = [];
  for (const item of [...live, ...pages.flatMap((p) => p.data)]) {
    if (seen.has(item.id)) continue;
    seen.add(item.id);
    all.push(item);
  }
  return all.sort((a, b) => {
    if (b.tick !== a.tick) return b.tick - a.tick;
    if (a.kind !== b.kind) return a.kind === 'event' ? -1 : 1;
    return b.occurred_at.localeCompare(a.occurred_at);
  });
}
