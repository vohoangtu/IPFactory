'use client';
import { Line, LineChart, ResponsiveContainer, YAxis } from 'recharts';
import type { MetricPoint } from '@/shared/types/domain';

export function MetricsSparkline({ history }: { history: MetricPoint[] }) {
  if (history.length === 0) {
    return <p className="text-sm text-[var(--color-text-muted)]">Chưa có nhịp đập nào.</p>;
  }
  return (
    <div className="h-24 w-full" role="img" aria-label="Diễn biến entropy và stability">
      <ResponsiveContainer width="100%" height="100%">
        <LineChart data={history} margin={{ top: 4, right: 4, bottom: 0, left: 4 }}>
          <YAxis hide domain={[0, 1]} />
          <Line type="monotone" dataKey="stability" stroke="var(--color-primary)" dot={false} strokeWidth={1.5} isAnimationActive={false} />
          <Line type="monotone" dataKey="entropy" stroke="var(--color-danger)" dot={false} strokeWidth={1.5} isAnimationActive={false} />
        </LineChart>
      </ResponsiveContainer>
    </div>
  );
}
