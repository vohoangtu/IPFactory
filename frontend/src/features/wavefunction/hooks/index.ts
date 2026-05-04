'use client';

import { useQuery } from '@tanstack/react-query';
import { wavefunctionQueries } from '../api/queries';
import { useCentrifugoConnection, useAdaptiveRefetchInterval } from '@/hooks/useCentrifugo';

export function useWavefunction(universeId: number | null) {
  const { state } = useCentrifugoConnection();
  const refetchInterval = useAdaptiveRefetchInterval(state, 5_000);
  const { data, error, isLoading } = useQuery({
    ...wavefunctionQueries.snapshot(universeId ?? 0),
    enabled: !!universeId,
    refetchInterval,
  });
  return { wavefunction: data, isLoading, isError: !!error };
}

export function useInformationalMass(universeId: number | null) {
  const { state } = useCentrifugoConnection();
  const refetchInterval = useAdaptiveRefetchInterval(state, 10_000);
  const { data, error, isLoading } = useQuery({
    ...wavefunctionQueries.informationalMass(universeId ?? 0),
    enabled: !!universeId,
    refetchInterval,
  });
  return { informationalMass: data, isLoading, isError: !!error };
}

export function useConsciousness(universeId: number | null) {
  const { state } = useCentrifugoConnection();
  const refetchInterval = useAdaptiveRefetchInterval(state, 10_000);
  const { data, error, isLoading } = useQuery({
    ...wavefunctionQueries.consciousness(universeId ?? 0),
    enabled: !!universeId,
    refetchInterval,
  });
  return { consciousness: data, isLoading, isError: !!error };
}

export function useAscensionFilters(universeId: number | null) {
  const { state } = useCentrifugoConnection();
  const refetchInterval = useAdaptiveRefetchInterval(state, 10_000);
  const { data, error, isLoading } = useQuery({
    ...wavefunctionQueries.ascensionFilters(universeId ?? 0),
    enabled: !!universeId,
    refetchInterval,
  });
  return { ascensionFilters: data, isLoading, isError: !!error };
}

export function useStateDelta(universeId: number | null) {
  const { state } = useCentrifugoConnection();
  const refetchInterval = useAdaptiveRefetchInterval(state, 15_000);
  const { data, error, isLoading } = useQuery({
    ...wavefunctionQueries.stateDelta(universeId ?? 0),
    enabled: !!universeId,
    refetchInterval,
  });
  return { delta: data, isLoading, isError: !!error };
}
