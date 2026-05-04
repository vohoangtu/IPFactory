import { useQuery } from "@tanstack/react-query";
import api from "@/lib/api";

export interface TimelineEvent {
  tick: number;
  type: "epoch" | "discovery" | "scar" | "celebrity" | "milestone";
  label: string;
  description?: string;
  actor_id?: number;
  severity?: string;
  claimable: boolean;
}

export interface ProgressionResponse {
  timeline: TimelineEvent[];
  stats: {
    total_discoveries: number;
    total_scars: number;
    total_celebrities: number;
    current_epoch: string;
  };
}

export function useProgression(universeId: number | null) {
  return useQuery<ProgressionResponse>({
    queryKey: ["progression", universeId],
    queryFn: async () => {
      const res = await api.get(`/worldos/universes/${universeId}/progression`);
      return res.data;
    },
    enabled: !!universeId,
  });
}
