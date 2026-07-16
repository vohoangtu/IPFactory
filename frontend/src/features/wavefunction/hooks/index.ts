'use client';

import { useQuery } from '@tanstack/react-query';
import { wavefunctionQueries } from '../api/queries';

export function useWavefunction(universeId: number | null) {
  const { data, error, isLoading } = useQuery({
    ...wavefunctionQueries.snapshot(universeId ?? 0),
    enabled: !!universeId,
  });
  return { wavefunction: data, isLoading, isError: !!error };
}

export function useInformationalMass(universeId: number | null) {
  const { data, error, isLoading } = useQuery({
    ...wavefunctionQueries.informationalMass(universeId ?? 0),
    enabled: !!universeId,
  });
  return { informationalMass: data, isLoading, isError: !!error };
}

export function useConsciousness(universeId: number | null) {
  const { data, error, isLoading } = useQuery({
    ...wavefunctionQueries.consciousness(universeId ?? 0),
    enabled: !!universeId,
  });
  return { consciousness: data, isLoading, isError: !!error };
}

export function useAscensionFilters(universeId: number | null) {
  const { data, error, isLoading } = useQuery({
    ...wavefunctionQueries.ascensionFilters(universeId ?? 0),
    enabled: !!universeId,
  });
  return { ascensionFilters: data, isLoading, isError: !!error };
}

export function useStateDelta(universeId: number | null) {
  const { data, error, isLoading } = useQuery({
    ...wavefunctionQueries.stateDelta(universeId ?? 0),
    enabled: !!universeId,
  });
  return { delta: data, isLoading, isError: !!error };
}
