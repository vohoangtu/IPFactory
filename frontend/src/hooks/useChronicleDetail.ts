'use client';

import { useQuery } from '@tanstack/react-query';
import api from '@/lib/api';
import type { Chronicle } from '@/types/api';

export function useChronicleDetail(chronicleId: number | null) {
    const { data, error, isLoading } = useQuery<Chronicle>({
        queryKey: ['chronicles', chronicleId],
        queryFn: () =>
            api
                .get(`/worldos/chronicles/${chronicleId}`)
                .then((res) => res.data),
        enabled: !!chronicleId,
    });

    return {
        chronicle: data ?? null,
        isLoading,
        isError: !!error,
    };
}
