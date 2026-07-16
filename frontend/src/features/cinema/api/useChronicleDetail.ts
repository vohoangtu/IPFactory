'use client';
import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@/shared/lib/apiClient';
import { qk } from '@/shared/config/queryKeys';
import { takeData } from '@/shared/lib/unwrap';
import type { Chronicle } from '@/shared/types/api';

export function useChronicleDetail(chronicleId: number | null) {
  const { data, error, isLoading } = useQuery({
    queryKey: qk.chronicle(chronicleId ?? 0),
    queryFn: async (): Promise<Chronicle> =>
      takeData<Chronicle>((await apiClient.get(`/worldos/chronicles/${chronicleId}`)).data),
    enabled: !!chronicleId,
  });
  return { chronicle: data ?? null, isLoading, isError: !!error };
}
