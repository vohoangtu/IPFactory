import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen } from '@testing-library/react';
import { ChronicleStream } from '../components/ChronicleStream';
import type { FeedItem } from '@/shared/realtime/envelope';

const item = (id: string, tick: number): FeedItem => ({
  id, kind: 'event', type: 'anomaly.detected', tick, universe_id: 5,
  severity: 'info', occurred_at: '2026-07-15T00:00:00+00:00', payload: { title: `Sự kiện ${id}` },
});

describe('ChronicleStream', () => {
  it('empty state khi không có item', () => {
    render(<ChronicleStream items={[]} hasOlder={false} isLoadingOlder={false} onLoadOlder={() => {}} />);
    expect(screen.getByText(/chưa có biến cố/)).toBeTruthy();
  });

  it('render danh sách + nút tải quá khứ gọi onLoadOlder', () => {
    const onLoadOlder = vi.fn();
    render(<ChronicleStream items={[item('a', 2), item('b', 1)]} hasOlder isLoadingOlder={false} onLoadOlder={onLoadOlder} />);
    expect(screen.getByText('Sự kiện a')).toBeTruthy();
    fireEvent.click(screen.getByRole('button', { name: /Tải thêm quá khứ/ }));
    expect(onLoadOlder).toHaveBeenCalledTimes(1);
  });

  it('ẩn nút khi hết quá khứ', () => {
    render(<ChronicleStream items={[item('a', 2)]} hasOlder={false} isLoadingOlder={false} onLoadOlder={() => {}} />);
    expect(screen.queryByRole('button', { name: /Tải thêm quá khứ/ })).toBeNull();
  });
});
