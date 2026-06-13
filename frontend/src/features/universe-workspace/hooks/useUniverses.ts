'use client';
import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@/shared/lib/apiClient';
import { qk } from '@/shared/config/queryKeys';
import type { Universe } from '@/shared/types/domain';

export function useUniverses() {
  return useQuery({ queryKey: qk.universes(), queryFn: async () => (await apiClient.get('/worldos/universes')).data as Universe[] });
}
