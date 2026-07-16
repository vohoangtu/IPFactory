import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { ChronicleEntry } from '../components/ChronicleEntry';
import type { FeedItem } from '@/shared/realtime/envelope';

const base = (over: Partial<FeedItem>): FeedItem => ({
  id: 'x', kind: 'event', type: 'anomaly.detected', tick: 42, universe_id: 5,
  severity: 'critical', occurred_at: '2026-07-15T00:00:00+00:00', payload: {}, ...over,
});

describe('ChronicleEntry', () => {
  it('render anomaly với title + tick', () => {
    render(<ChronicleEntry item={base({ payload: { title: 'Entropy spike', description: 'x' } })} />);
    expect(screen.getByText('Entropy spike')).toBeTruthy();
    expect(screen.getByText('T42')).toBeTruthy();
  });

  it('render epoch shift với tên 2 epoch', () => {
    render(<ChronicleEntry item={base({
      type: 'epoch.transitioned', severity: 'notable',
      payload: { old_epoch: { id: 1, name: 'Bronze' }, new_epoch: { id: 2, name: 'Iron' } },
    })} />);
    expect(screen.getByText(/Bronze/)).toBeTruthy();
    expect(screen.getByText(/Iron/)).toBeTruthy();
  });

  it('render chronicle prose từ payload.content', () => {
    render(<ChronicleEntry item={base({
      kind: 'chronicle', type: 'chronicle',
      payload: { chronicle_id: 9, content: 'Sử thi về đế chế sụp đổ', importance: 0.8, has_animation: false },
    })} />);
    expect(screen.getByText('Sử thi về đế chế sụp đổ')).toBeTruthy();
  });

  it('type lạ không vỡ — hiện type', () => {
    render(<ChronicleEntry item={base({ type: 'unknown.thing', severity: 'info' })} />);
    expect(screen.getByText(/unknown\.thing/)).toBeTruthy();
  });

  it('chronicle entry có link mở cinema theo payload.chronicle_id', () => {
    render(<ChronicleEntry item={base({
      type: 'chronicle', kind: 'chronicle',
      payload: { chronicle_id: 42, content: 'Sử thi', has_animation: true },
    })} />);
    const link = screen.getByRole('link', { name: /Xem cinema/i });
    expect(link.getAttribute('href')).toBe('/chronicle/42');
  });

  it('chronicle entry không có chronicle_id → không render link', () => {
    render(<ChronicleEntry item={base({
      type: 'chronicle', kind: 'chronicle',
      payload: { content: 'Sử thi' },
    })} />);
    expect(screen.queryByRole('link')).toBeNull();
  });
});
