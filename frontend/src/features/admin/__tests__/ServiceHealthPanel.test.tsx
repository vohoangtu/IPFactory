import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import ServiceHealthPanel from '../components/system/ServiceHealthPanel';
import type { ServiceStatusResponse } from '../types';

const fixture: ServiceStatusResponse = {
  overall: 'degraded',
  checked_at: '2026-07-17T00:00:00Z',
  services: {
    database: { status: 'ok', latency_ms: 12 },
    engine: { status: 'error', error: 'connection refused' },
  },
};

describe('ServiceHealthPanel', () => {
  it('hiển thị tên dịch vụ và trạng thái tương ứng (healthy + down)', () => {
    render(<ServiceHealthPanel serviceStatus={fixture} isLoading={false} />);

    expect(screen.getByText('database')).toBeDefined();
    expect(screen.getByText('ok')).toBeDefined();
    expect(screen.getByText('engine')).toBeDefined();
    expect(screen.getByText('error')).toBeDefined();
    expect(screen.getByText('connection refused')).toBeDefined();
  });

  it('hiển thị spinner khi đang loading', () => {
    render(<ServiceHealthPanel serviceStatus={null} isLoading={true} />);

    expect(screen.queryByText('database')).toBeNull();
  });
});
