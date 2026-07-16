'use client';

import { useMemo } from 'react';
import {
  ResponsiveContainer,
  AreaChart,
  Area,
  XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
} from 'recharts';

import SectionPanel from '@/components/ui/shared/SectionPanel';
import type { WavefunctionData, StateDelta } from '@/shared/types/api';

interface Props {
  wavefunction: WavefunctionData | undefined;
  delta: StateDelta | undefined;
}

export default function EntropyChart({ wavefunction, delta }: Props) {
  const data = useMemo(() => {
    if (!wavefunction) return [];

    const currentEntropy = wavefunction.wavefunction.entropy;
    const currentStability = wavefunction.wavefunction.stability_index;

    if (delta) {
      const prevEntropy = Math.max(0, currentEntropy - delta.entropy_delta);
      const prevStability = Math.max(0, currentStability - delta.stability_delta);

      // Build a small history from the two known points with an interpolated midpoint
      const midEntropy = (prevEntropy + currentEntropy) / 2;
      const midStability = (prevStability + currentStability) / 2;

      return [
        {
          name: `T-${delta.tick_span}`,
          entropy: Number(prevEntropy.toFixed(4)),
          stability: Number(prevStability.toFixed(4)),
        },
        {
          name: `T-${Math.floor(delta.tick_span / 2)}`,
          entropy: Number(midEntropy.toFixed(4)),
          stability: Number(midStability.toFixed(4)),
        },
        {
          name: `T${wavefunction.tick}`,
          entropy: Number(currentEntropy.toFixed(4)),
          stability: Number(currentStability.toFixed(4)),
        },
      ];
    }

    // Fallback — only current snapshot available
    return [
      {
        name: `T${wavefunction.tick}`,
        entropy: Number(currentEntropy.toFixed(4)),
        stability: Number(currentStability.toFixed(4)),
      },
    ];
  }, [wavefunction, delta]);

  const isEmpty = data.length === 0;

  return (
    <SectionPanel>
      <h3 className="mb-6 text-sm font-black uppercase tracking-[0.2em] text-slate-300">
        Entropy &amp; Stability
      </h3>

      {isEmpty ? (
        <div className="flex h-[300px] items-center justify-center text-sm text-slate-500">
          No data available
        </div>
      ) : (
        <ResponsiveContainer width="100%" height={300}>
          <AreaChart data={data}>
            <defs>
              <linearGradient id="cyanGradient" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stopColor="#22d3ee" stopOpacity={0.3} />
                <stop offset="95%" stopColor="#22d3ee" stopOpacity={0} />
              </linearGradient>
              <linearGradient id="roseGradient" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stopColor="#f43f5e" stopOpacity={0.3} />
                <stop offset="95%" stopColor="#f43f5e" stopOpacity={0} />
              </linearGradient>
            </defs>
            <CartesianGrid strokeDasharray="3 3" stroke="#1e293b" />
            <XAxis
              dataKey="name"
              stroke="#475569"
              tick={{ fill: '#64748b', fontSize: 12 }}
            />
            <YAxis
              stroke="#475569"
              tick={{ fill: '#64748b', fontSize: 12 }}
              domain={[0, 1]}
            />
            <Tooltip
              contentStyle={{
                backgroundColor: '#0f0f12',
                border: '1px solid #1e293b',
                borderRadius: 12,
                color: '#e2e8f0',
              }}
            />
            <Area
              type="monotone"
              dataKey="entropy"
              stroke="#f43f5e"
              strokeWidth={2}
              fill="url(#roseGradient)"
              name="Entropy"
            />
            <Area
              type="monotone"
              dataKey="stability"
              stroke="#22d3ee"
              strokeWidth={2}
              fill="url(#cyanGradient)"
              name="Stability"
            />
          </AreaChart>
        </ResponsiveContainer>
      )}
    </SectionPanel>
  );
}
