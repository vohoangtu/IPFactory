'use client';

import { motion } from 'framer-motion';
import { Shield, AlertTriangle, Zap } from 'lucide-react';

import SectionPanel from '@/components/ui/shared/SectionPanel';

interface Props {
  risk: 'NORMAL' | 'HIGH' | 'CRITICAL' | undefined;
}

const config = {
  NORMAL: {
    bg: 'bg-emerald-500/15 ring-emerald-500/30',
    text: 'text-emerald-300',
    label: 'System Stable',
    Icon: Shield,
    pulse: false,
  },
  HIGH: {
    bg: 'bg-amber-500/15 ring-amber-500/30',
    text: 'text-amber-300',
    label: 'Elevated Risk',
    Icon: AlertTriangle,
    pulse: true,
  },
  CRITICAL: {
    bg: 'bg-rose-500/15 ring-rose-500/30',
    text: 'text-rose-300',
    label: 'Critical Risk',
    Icon: Zap,
    pulse: true,
  },
} as const;

export default function SingularityRisk({ risk }: Props) {
  if (!risk) {
    return (
      <SectionPanel>
        <h3 className="mb-4 text-sm font-black uppercase tracking-[0.2em] text-slate-300">
          Singularity Risk
        </h3>
        <div className="flex h-28 items-center justify-center text-sm text-slate-500">
          Loading...
        </div>
      </SectionPanel>
    );
  }

  const { bg, text, label, Icon, pulse } = config[risk];

  return (
    <SectionPanel>
      <h3 className="mb-4 text-sm font-black uppercase tracking-[0.2em] text-slate-300">
        Singularity Risk
      </h3>

      <motion.div
        initial={{ opacity: 0, scale: 0.85 }}
        animate={{ opacity: 1, scale: 1 }}
        transition={{ type: 'spring', stiffness: 200, damping: 20 }}
        className="flex flex-col items-center justify-center py-4"
      >
        <div
          className={`
            inline-flex items-center gap-3 rounded-2xl px-6 py-4 ring-1 ring-inset
            ${bg}
            ${pulse ? 'animate-pulse' : ''}
          `}
        >
          <Icon size={24} className={text} />
          <span className={`text-lg font-black uppercase tracking-wider ${text}`}>
            {label}
          </span>
        </div>

        <span className="mt-3 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">
          {risk}
        </span>
      </motion.div>
    </SectionPanel>
  );
}
