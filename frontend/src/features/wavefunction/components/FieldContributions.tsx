'use client';

import { useMemo } from 'react';
import {
  ResponsiveContainer,
  BarChart,
  Bar,
  XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
  Cell,
} from 'recharts';

import SectionPanel from '@/shared/ui/SectionPanel';
import type { InformationalMass } from '@/shared/types/api';

interface Props {
  informationalMass: InformationalMass | undefined;
}

const BAR_COLORS = ['#22d3ee', '#a78bfa', '#fbbf24', '#34d399', '#f472b6', '#60a5fa'];

export default function FieldContributions({ informationalMass }: Props) {
  const data = useMemo(() => {
    if (!informationalMass?.field_contributions) return [];
    return informationalMass.field_contributions.map((fc) => ({
      field: fc.field,
      mass: Number(fc.mass.toFixed(3)),
    }));
  }, [informationalMass]);

  const isEmpty = data.length === 0;

  return (
    <SectionPanel>
      <h3 className="mb-6 text-sm font-black uppercase tracking-[0.2em] text-slate-300">
        Field Contributions
      </h3>

      {isEmpty ? (
        <div className="flex h-[280px] items-center justify-center text-sm text-slate-500">
          No field data
        </div>
      ) : (
        <ResponsiveContainer width="100%" height={280}>
          <BarChart data={data} layout="vertical" margin={{ left: 20 }}>
            <CartesianGrid strokeDasharray="3 3" stroke="#1e293b" horizontal={false} />
            <XAxis
              type="number"
              stroke="#475569"
              tick={{ fill: '#64748b', fontSize: 12 }}
            />
            <YAxis
              dataKey="field"
              type="category"
              stroke="#475569"
              tick={{ fill: '#94a3b8', fontSize: 12 }}
              width={100}
            />
            <Tooltip
              contentStyle={{
                backgroundColor: '#0f0f12',
                border: '1px solid #1e293b',
                borderRadius: 12,
                color: '#e2e8f0',
              }}
            />
            <Bar dataKey="mass" radius={[0, 6, 6, 0]} name="Mass">
              {data.map((_, idx) => (
                <Cell key={idx} fill={BAR_COLORS[idx % BAR_COLORS.length]} fillOpacity={0.8} />
              ))}
            </Bar>
          </BarChart>
        </ResponsiveContainer>
      )}
    </SectionPanel>
  );
}
