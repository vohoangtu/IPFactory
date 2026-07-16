'use client';
import { useCallback, useMemo } from 'react';
import { useInfiniteQuery } from '@tanstack/react-query';
import { qk } from '@/shared/config/queryKeys';
import { useFeedStore } from '@/shared/store/feedStore';
import type { FeedItem } from '@/shared/realtime/envelope';
import { fetchFeed, FEED_PAGE_LIMIT, type FeedPage } from '../api/feed';
import { mergeFeed } from '../lib/mergeFeed';

export interface ChronicleFeed {
  items: FeedItem[];
  fetchOlder: () => void;
  hasOlder: boolean;
  isLoadingOlder: boolean;
  isError: boolean;
  refetchLatest: () => void;
  backfillLatest: () => void;
}

export function useChronicleFeed(
  universeId: number | null,
  opts: { types?: string[] } = {},
): ChronicleFeed {
  const liveItems = useFeedStore((s) => s.items);
  // eslint-disable-next-line react-hooks/exhaustive-deps -- ổn định reference theo nội dung CSV
  const types = useMemo(() => opts.types ?? [], [opts.types?.join(',')]);

  const query = useInfiniteQuery({
    queryKey: universeId != null ? qk.feed(universeId, types) : ['universes', 'none', 'feed'],
    enabled: universeId != null,
    queryFn: ({ pageParam }) =>
      fetchFeed(universeId as number, {
        ...(pageParam != null ? { before_tick: pageParam } : {}),
        types,
      }),
    initialPageParam: undefined as number | undefined,
    getNextPageParam: (last: FeedPage) => last.meta.next_before_tick ?? undefined,
  });

  const liveFiltered = useMemo(
    () => liveItems.filter(
      (i) => i.universe_id === universeId && (types.length === 0 || types.includes(i.type)),
    ),
    [liveItems, universeId, types],
  );

  const items = useMemo(
    () => mergeFeed(liveFiltered, query.data?.pages ?? []),
    [liveFiltered, query.data],
  );

  const refetch = query.refetch;
  const latestTick = items.length > 0 ? items[0].tick : null;

  const backfillLatest = useCallback(() => {
    if (universeId == null) return;
    if (latestTick == null) { void refetch(); return; }
    void (async () => {
      try {
        const page = await fetchFeed(universeId, { after_tick: Math.max(0, latestTick - 1), types });
        const push = useFeedStore.getState().pushLive;
        page.data.forEach(push);
        if (page.meta.next_before_tick != null || page.meta.count >= FEED_PAGE_LIMIT) void refetch(); // có thể còn sót nhiều hơn 1 trang
      } catch {
        void refetch(); // backfill lỗi → refetch toàn bộ như cũ
      }
    })();
  }, [universeId, latestTick, refetch, types]);

  return {
    items,
    fetchOlder: () => { void query.fetchNextPage(); },
    hasOlder: query.hasNextPage ?? false,
    isLoadingOlder: query.isFetchingNextPage,
    isError: query.isError,
    refetchLatest: () => { void query.refetch(); },
    backfillLatest,
  };
}
