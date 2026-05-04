import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import api from "@/lib/api";

export interface Achievement {
  id: number;
  code: string;
  name: string;
  description: string | null;
  category: string;
  icon: string | null;
  rarity: string;
  unlocked: boolean;
  claimed: boolean;
}

export interface AchievementsResponse {
  achievements: Achievement[];
  stats: {
    total: number;
    unlocked: number;
    claimed: number;
  };
}

export function useAchievements(universeId: number | null) {
  return useQuery<AchievementsResponse>({
    queryKey: ["achievements", universeId],
    queryFn: async () => {
      const res = await api.get(`/worldos/universes/${universeId}/achievements`);
      return res.data;
    },
    enabled: !!universeId,
  });
}

export function useClaimAchievement(universeId: number | null) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (achievementId: number) => {
      const res = await api.post(
        `/worldos/universes/${universeId}/achievements/${achievementId}/claim`
      );
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["achievements", universeId] });
    },
  });
}
