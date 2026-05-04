import { QueryClient } from '@tanstack/react-query';

/**
 * Singleton QueryClient factory.
 *
 * Default options:
 *  - staleTime 0  (always re-fetch on mount, controlled per query)
 *  - gcTime 5min  (keep unused cache for 5 minutes)
 *  - retry 1      (one auto-retry on network failure)
 *  - refetchOnWindowFocus true (keep data live when tab regains focus)
 */
export function createQueryClient(): QueryClient {
  return new QueryClient({
    defaultOptions: {
      queries: {
        staleTime: 5_000,
        gcTime: 5 * 60 * 1000,
        retry: 1,
        refetchOnWindowFocus: true,
      },
      mutations: {
        retry: 0,
      },
    },
  });
}
