import { beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import type { ReactNode } from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

const mockGet = vi.hoisted(() => vi.fn());
const mockPost = vi.hoisted(() => vi.fn());

vi.mock('@/shared/lib/apiClient', () => ({
  TOKEN_KEY: 'worldos_token',
  apiClient: { get: mockGet, post: mockPost },
}));

import { useLoomStatus } from '../hooks';

const wrapper = ({ children }: { children: ReactNode }) => {
  const qc = new QueryClient({ defaultOptions: { queries: { retry: false, gcTime: 0 } } });
  return <QueryClientProvider client={qc}>{children}</QueryClientProvider>;
};

describe('useLoomStatus', () => {
  beforeEach(() => {
    mockGet.mockReset();
    mockPost.mockReset();
  });

  it('gọi GET /loom-status và trả về data', async () => {
    mockGet.mockResolvedValueOnce({
      data: {
        status: 'online',
        agents: { Chief_Editor: { provider: 'openai', model: 'gpt-4o' } },
        providers: { openai: { status: 'ok', key_present: true } },
        version: '1.0.0',
      },
    });

    const { result } = renderHook(() => useLoomStatus(), { wrapper });

    await waitFor(() => expect(result.current.loomStatus?.status).toBe('online'));
    expect(mockGet).toHaveBeenCalledWith('/loom-status');
    expect(result.current.loomStatus?.agents.Chief_Editor?.model).toBe('gpt-4o');
    expect(result.current.isLoading).toBe(false);
    expect(result.current.isError).toBe(false);
  });
});
