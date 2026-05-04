"use client";

import { Achievement } from "@/hooks/useAchievements";
import { useClaimAchievement } from "@/hooks/useAchievements";
import { useUniverse } from "@/contexts/UniverseContext";


const RARITY_STYLES: Record<string, string> = {
  common: "border-white/10 bg-white/5",
  uncommon: "border-brand-emerald/30 bg-brand-emerald/5",
  rare: "border-brand-info/30 bg-brand-info/5",
  epic: "border-brand-accent/30 bg-brand-accent/5",
  legendary: "border-brand-amber/30 bg-brand-amber/5",
};

interface Props {
  achievement: Achievement;
}

export function AchievementCard({ achievement }: Props) {
  const { activeUniverseId } = useUniverse();
  const claim = useClaimAchievement(activeUniverseId);

  const rarityClass =
    RARITY_STYLES[achievement.rarity] ?? RARITY_STYLES.common;

  return (
    <div
      className={`relative rounded-xl border p-4 transition-opacity ${rarityClass} ${
        !achievement.unlocked ? "opacity-40" : "opacity-100"
      }`}
    >
      <div className="flex items-start justify-between">
        <div className="space-y-1">
          <div className="flex items-center gap-2">
            <span className="text-lg">{achievement.icon ?? "🏆"}</span>
            <h3 className="text-sm font-semibold text-text-primary">
              {achievement.name}
            </h3>
          </div>
          <p className="text-xs text-text-muted">{achievement.description}</p>
          <p className="text-[10px] uppercase tracking-wider text-text-disabled">
            {achievement.category} • {achievement.rarity}
          </p>
        </div>

        {achievement.unlocked && !achievement.claimed && (
          <button
            className="text-xs px-2 py-1 rounded-md border bg-transparent transition-colors border-brand-amber/50 text-brand-amber hover:bg-brand-amber/10 disabled:opacity-50 disabled:cursor-not-allowed"
            onClick={() => claim.mutate(achievement.id)}
            disabled={claim.isPending}
          >
            {claim.isPending ? "..." : "Claim"}
          </button>
        )}

        {achievement.claimed && (
          <span className="text-[10px] px-2 py-1 rounded-full bg-brand-emerald/20 text-brand-emerald border border-brand-emerald/30">
            Claimed
          </span>
        )}
      </div>
    </div>
  );
}
