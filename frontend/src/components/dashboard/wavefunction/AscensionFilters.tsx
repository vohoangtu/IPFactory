'use client';

import { motion } from 'framer-motion';

import BadgeLabel from '@/components/ui/shared/BadgeLabel';
import ProgressBar from '@/components/ui/shared/ProgressBar';
import SectionPanel from '@/components/ui/shared/SectionPanel';
import type { AscensionFilterData } from '@/types/api';

interface Props {
  data: AscensionFilterData | undefined;
}

type FilterStatus = AscensionFilterData['filters'][number]['status'];

const statusVariant: Record<FilterStatus, 'emerald' | 'cyan' | 'rose' | 'amber' | 'slate' | 'red'> = {
  PASSED:  'emerald',
  ACTIVE:  'cyan',
  DANGER:  'rose',
  WARNING: 'amber',
  LOCKED:  'slate',
  OPEN:    'slate',
  FAILED:  'red',
};

const statusColor: Record<FilterStatus, 'emerald' | 'cyan' | 'rose' | 'amber' | 'slate' | 'violet'> = {
  PASSED:  'emerald',
  ACTIVE:  'cyan',
  DANGER:  'rose',
  WARNING: 'amber',
  LOCKED:  'slate',
  OPEN:    'slate',
  FAILED:  'rose',
};

export default function AscensionFilters({ data }: Props) {
  if (!data) {
    return (
      <SectionPanel>
        <h3 className="mb-4 text-sm font-black uppercase tracking-[0.2em] text-slate-300">
          Ascension Filters
        </h3>
        <div className="flex h-[280px] items-center justify-center text-sm text-slate-500">
          Loading...
        </div>
      </SectionPanel>
    );
  }

  return (
    <SectionPanel>
      <h3 className="mb-6 text-sm font-black uppercase tracking-[0.2em] text-slate-300">
        Ascension Filters
      </h3>

      {/* Overall singularity probability */}
      <div className="mb-6">
        <div className="mb-2 flex items-end justify-between">
          <span className="text-xs text-slate-400">Singularity Probability</span>
          <span className="text-3xl font-black tabular-nums text-white">
            {(data.singularity_probability * 100).toFixed(1)}%
          </span>
        </div>
        <ProgressBar
          value={data.singularity_probability}
          color={data.singularity_probability > 0.7 ? 'rose' : data.singularity_probability > 0.4 ? 'amber' : 'cyan'}
          showPercent={false}
        />
      </div>

      {/* Individual filters */}
      <div className="space-y-4">
        {data.filters.map((filter, idx) => (
          <motion.div
            key={filter.id}
            initial={{ opacity: 0, x: -12 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ delay: idx * 0.07 }}
            className="space-y-2"
          >
            <div className="flex items-center justify-between">
              <span className="text-sm font-bold text-slate-200">{filter.name}</span>
              <BadgeLabel variant={statusVariant[filter.status]}>
                {filter.status}
              </BadgeLabel>
            </div>
            <ProgressBar
              value={filter.progress}
              color={statusColor[filter.status]}
              size="sm"
              showPercent
            />
          </motion.div>
        ))}
      </div>
    </SectionPanel>
  );
}
