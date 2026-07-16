'use client';
import { useMemo } from 'react';
import { useInfiniteQuery } from '@tanstack/react-query';
import { qk } from '@/shared/config/queryKeys';
import { useFeedStore } from '@/shared/store/feedStore';
import type { FeedItem } from '@/shared/realtime/envelope';
import { fetchFeed, type FeedPage } from '../api/feed';
import { mergeFeed } from '../lib/mergeFeed';

export interface ChronicleFeed {
  items: FeedItem[];
  fetchOlder: () => void;
  hasOlder: boolean;
  isLoadingOlder: boolean;
  isError: boolean;
  refetchLatest: () => void;
}

export function useChronicleFeed(universeId: number | null): ChronicleFeed {
  const liveItems = useFeedStore((s) => s.items);

  const query = useInfiniteQuery({
    queryKey: universeId != null ? qk.feed(universeId) : ['universes', 'none', 'feed'],
    enabled: universeId != null,
    queryFn: ({ pageParam }) =>
      fetchFeed(universeId as number, pageParam != null ? { before_tick: pageParam } : {}),
    initialPageParam: undefined as number | undefined,
    getNextPageParam: (last: FeedPage) => last.meta.next_before_tick ?? undefined,
  });

  const items = useMemo(
    () => mergeFeed(
      liveItems.filter((i) => i.universe_id === universeId),
      query.data?.pages ?? [],
    ),
    [liveItems, query.data, universeId],
  );

  return {
    items,
    fetchOlder: () => { void query.fetchNextPage(); },
    hasOlder: query.hasNextPage ?? false,
    isLoadingOlder: query.isFetchingNextPage,
    isError: query.isError,
    refetchLatest: () => { void query.refetch(); },
  };
}
