import { apiClient } from '@/shared/lib/apiClient';
import type { FeedItem } from '@/shared/realtime/envelope';

export interface FeedPage {
  data: FeedItem[];
  meta: { count: number; next_before_tick: number | null };
}

export const FEED_PAGE_LIMIT = 50;

/** Body feed có 2 key (data+meta) nên interceptor unwrapEnvelope không bóc — đọc nguyên body. */
export async function fetchFeed(
  universeId: number,
  params: { before_tick?: number; after_tick?: number; limit?: number; types?: string[] } = {},
): Promise<FeedPage> {
  const { types, ...rest } = params;
  const res = await apiClient.get(`/worldos/observatory/universes/${universeId}/feed`, {
    params: {
      limit: FEED_PAGE_LIMIT,
      ...rest,
      ...(types && types.length > 0 ? { types: types.join(',') } : {}),
    },
  });
  return res.data as FeedPage;
}
