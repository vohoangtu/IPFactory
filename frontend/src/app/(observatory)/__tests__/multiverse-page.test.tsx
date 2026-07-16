import { describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { renderWithClient } from '@/test/render';

vi.mock('@/shared/lib/apiClient', () => ({
  TOKEN_KEY: 'worldos_token',
  apiClient: {
    get: vi.fn().mockResolvedValue({
      data: [
        { id: 1, world_id: 1, name: 'Aurora', status: 'active', current_tick: 120, era: 2 },
        { id: 2, world_id: 1, name: 'Umbra', status: 'paused', current_tick: 40, era: 1 },
      ],
    }),
  },
}));
vi.mock('next/navigation', () => ({ useRouter: () => ({ push: vi.fn(), replace: vi.fn() }) }));

import MultiversePage from '../multiverse/page';

describe('Landing multiverse', () => {
  it('render thẻ universe với link vào hero', async () => {
    renderWithClient(<MultiversePage />);
    // Dùng role 'link' thay vì text thô: ContextBar (trong WorkspaceLayout) cũng liệt kê
    // tên universe trong <select><option>, nên text 'Aurora'/'Umbra' xuất hiện 2 lần trong DOM.
    const link = await screen.findByRole('link', { name: /Aurora/ });
    expect(link).toBeTruthy();
    expect(screen.getByRole('link', { name: /Umbra/ })).toBeTruthy();
    expect(link.getAttribute('href')).toBe('/u/1');
  });
});
