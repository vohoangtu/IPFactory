'use client';

import { Activity, Shield, AlertTriangle, Database } from 'lucide-react';

import GaugeCard from '@/components/ui/shared/GaugeCard';
import type { WavefunctionData, InformationalMass } from '@/types/api';

interface Props {
  wavefunction: WavefunctionData | undefined;
  informationalMass: InformationalMass | undefined;
}

type GaugeTone = 'cyan' | 'amber' | 'danger' | 'emerald' | 'violet';

function entropyTone(v: number): GaugeTone {
  if (v > 0.7) return 'danger';
  if (v < 0.3) return 'cyan';
  return 'amber';
}

function stabilityTone(v: number): GaugeTone {
  if (v > 0.5) return 'emerald';
  if (v < 0.3) return 'danger';
  return 'amber';
}

function collapseTone(v: number): GaugeTone {
  if (v > 0.5) return 'danger';
  if (v < 0.2) return 'emerald';
  return 'amber';
}

export default function WavefunctionGauges({ wavefunction, informationalMass }: Props) {
  const wf = wavefunction?.wavefunction;

  const entropy  = wf?.entropy ?? 0;
  const stability = wf?.stability_index ?? 0;
  const collapse  = wf?.collapse_probability ?? 0;
  const mass      = informationalMass?.informational_mass ?? 0;

  return (
    <div className="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
      <GaugeCard
        label="Entropy"
        value={entropy.toFixed(3)}
        meta={`Attractor: ${wf?.active_attractor ?? '—'}`}
        icon={Activity}
        tone={entropyTone(entropy)}
        progress={entropy}
        index={0}
      />
      <GaugeCard
        label="Stability"
        value={stability.toFixed(3)}
        meta={`Density: ${(wf?.information_density ?? 0).toFixed(2)}`}
        icon={Shield}
        tone={stabilityTone(stability)}
        progress={stability}
        index={1}
      />
      <GaugeCard
        label="Collapse Probability"
        value={(collapse * 100).toFixed(1) + '%'}
        meta={collapse > 0.5 ? 'High risk' : 'Stable'}
        icon={AlertTriangle}
        tone={collapseTone(collapse)}
        progress={collapse}
        index={2}
      />
      <GaugeCard
        label="Informational Mass"
        value={mass.toFixed(2)}
        meta={`Risk: ${informationalMass?.singularity_risk ?? '—'}`}
        icon={Database}
        tone="violet"
        index={3}
      />
    </div>
  );
}
