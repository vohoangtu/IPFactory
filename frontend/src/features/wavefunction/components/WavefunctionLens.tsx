'use client';

import {
  useWavefunction,
  useInformationalMass,
  useConsciousness,
  useAscensionFilters,
  useStateDelta,
} from '../hooks';
import WavefunctionGauges from './WavefunctionGauges';
import EntropyChart from './EntropyChart';
import FieldContributions from './FieldContributions';
import SingularityRisk from './SingularityRisk';
import AutopoiesisStatus from './AutopoiesisStatus';
import AscensionFilters from './AscensionFilters';

function LensSkeleton() {
  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
        <div className="h-36 animate-pulse rounded-3xl border border-[var(--border-subtle)] bg-[var(--color-bg-base)]/50" />
        <div className="h-36 animate-pulse rounded-3xl border border-[var(--border-subtle)] bg-[var(--color-bg-base)]/50" />
      </div>
      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        {Array.from({ length: 4 }).map((_, i) => (
          <div
            key={i}
            className="h-40 animate-pulse rounded-3xl border border-[var(--border-subtle)] bg-[var(--color-bg-base)]/50"
          />
        ))}
      </div>
    </div>
  );
}

/** Lens Wavefunction — gauges + entropy chart hàng đầu; singularity/autopoiesis/ascension/field hàng sau. */
export function WavefunctionLens({ universeId }: { universeId: number }) {
  const { wavefunction, isLoading: wfLoading, isError: wfError } = useWavefunction(universeId);
  const { informationalMass, isLoading: massLoading, isError: massError } = useInformationalMass(universeId);
  useConsciousness(universeId); // warm cache — chưa có panel tiêu thụ trực tiếp trong lens này
  const { ascensionFilters, isLoading: afLoading, isError: afError } = useAscensionFilters(universeId);
  const { delta, isLoading: deltaLoading, isError: deltaError } = useStateDelta(universeId);

  const isInitialLoad = wfLoading && massLoading && afLoading && deltaLoading;
  const isError = wfError || massError || afError || deltaError;

  return (
    <div className="space-y-6">
      {isError && (
        <p className="text-sm text-[var(--color-danger)]" role="alert">
          Không tải được đầy đủ dữ liệu wavefunction — một số panel có thể trống.
        </p>
      )}

      {isInitialLoad ? (
        <LensSkeleton />
      ) : (
        <>
          {/* Hàng đầu: gauges + entropy chart */}
          <div className="grid grid-cols-1 gap-5 xl:grid-cols-2">
            <WavefunctionGauges wavefunction={wavefunction} informationalMass={informationalMass} />
            <EntropyChart wavefunction={wavefunction} delta={delta} />
          </div>

          {/* Hàng sau: singularity / autopoiesis / ascension / field */}
          <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <SingularityRisk risk={informationalMass?.singularity_risk} />
            <AutopoiesisStatus autopoiesis={wavefunction?.autopoiesis} />
            <AscensionFilters data={ascensionFilters} />
            <FieldContributions informationalMass={informationalMass} />
          </div>
        </>
      )}
    </div>
  );
}
