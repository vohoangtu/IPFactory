'use client';

import { useUniverse } from '@/contexts/UniverseContext';
import {
  useWavefunction,
  useInformationalMass,
  useConsciousness,
  useAscensionFilters,
  useStateDelta,
} from '@/features/wavefunction/hooks';

import WavefunctionGauges from '@/components/dashboard/wavefunction/WavefunctionGauges';
import PageHeader from '@/components/ui/shared/PageHeader';
import EntropyChart from '@/components/dashboard/wavefunction/EntropyChart';
import FieldContributions from '@/components/dashboard/wavefunction/FieldContributions';
import SingularityRisk from '@/components/dashboard/wavefunction/SingularityRisk';
import AutopoiesisStatus from '@/components/dashboard/wavefunction/AutopoiesisStatus';
import AscensionFilters from '@/components/dashboard/wavefunction/AscensionFilters';

/* ── Skeleton placeholder ─────────────────────── */
function Skeleton() {
  return (
    <div className="space-y-6">
      {/* Gauge skeletons */}
      <div className="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
        {Array.from({ length: 4 }).map((_, i) => (
          <div
            key={i}
            className="h-36 animate-pulse rounded-3xl border border-border-subtle bg-bg-base/50"
          />
        ))}
      </div>
      {/* Chart + sidebar skeletons */}
      <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div className="col-span-1 h-[380px] animate-pulse rounded-3xl border border-border-subtle bg-bg-base/50 lg:col-span-2" />
        <div className="space-y-5">
          <div className="h-44 animate-pulse rounded-3xl border border-border-subtle bg-bg-base/50" />
          <div className="h-44 animate-pulse rounded-3xl border border-border-subtle bg-bg-base/50" />
        </div>
      </div>
      {/* Bottom skeletons */}
      <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div className="h-[340px] animate-pulse rounded-3xl border border-border-subtle bg-bg-base/50" />
        <div className="h-[340px] animate-pulse rounded-3xl border border-border-subtle bg-bg-base/50" />
      </div>
    </div>
  );
}

/* ── Page ──────────────────────────────────────── */
export default function WavefunctionPage() {
  const { activeUniverseId } = useUniverse();

  const { wavefunction, isLoading: wfLoading } = useWavefunction(activeUniverseId);
  const { informationalMass, isLoading: massLoading } = useInformationalMass(activeUniverseId);
  useConsciousness(activeUniverseId); // fetched for cache — used by future components
  const { ascensionFilters, isLoading: afLoading } = useAscensionFilters(activeUniverseId);
  const { delta, isLoading: deltaLoading } = useStateDelta(activeUniverseId);

  const isInitialLoad = wfLoading && massLoading && afLoading && deltaLoading;

  return (
    <div className="space-y-8">
      <PageHeader
        title="Wavefunction Observatory"
        subtitle="Quantum state monitoring & ascension diagnostics"
      />

      {isInitialLoad ? (
        <Skeleton />
      ) : (
        <>
          {/* ── Top: Gauges ──────────────────── */}
          <WavefunctionGauges
            wavefunction={wavefunction}
            informationalMass={informationalMass}
          />

          {/* ── Middle: Chart + Risk + Auto ──── */}
          <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <div className="lg:col-span-2">
              <EntropyChart wavefunction={wavefunction} delta={delta} />
            </div>
            <div className="space-y-5">
              <SingularityRisk risk={informationalMass?.singularity_risk} />
              <AutopoiesisStatus autopoiesis={wavefunction?.autopoiesis} />
            </div>
          </div>

          {/* ── Bottom: Field + Ascension ────── */}
          <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <FieldContributions informationalMass={informationalMass} />
            <AscensionFilters data={ascensionFilters} />
          </div>
        </>
      )}
    </div>
  );
}
