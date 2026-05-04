"use client";

import { useState } from "react";
import { useAchievements } from "@/hooks/useAchievements";
import { useUniverse } from "@/contexts/UniverseContext";
import { AchievementCard } from "./AchievementCard";
import FilterToolbar from "@/components/ui/shared/FilterToolbar";
import ProgressBar from "@/components/ui/shared/ProgressBar";
import PageHeader from "@/components/ui/shared/PageHeader";

const CATEGORY_FILTERS = [
  { key: "all", label: "All" },
  { key: "discovery", label: "Discovery" },
  { key: "survival", label: "Survival" },
  { key: "diplomacy", label: "Diplomacy" },
  { key: "myth", label: "Myth" },
  { key: "epoch", label: "Epoch" },
  { key: "celebrity", label: "Celebrity" },
];

export function AchievementGrid() {
  const { activeUniverseId } = useUniverse();
  const { data, isLoading } = useAchievements(activeUniverseId);
  const [filter, setFilter] = useState("all");

  const achievements =
    filter === "all"
      ? data?.achievements ?? []
      : data?.achievements.filter((a) => a.category === filter) ?? [];

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-brand-info" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Achievements"
        subtitle={`${data?.stats.claimed ?? 0} / ${data?.stats.total ?? 0} claimed`}
        action={
          <FilterToolbar
          searchValue=""
          onSearchChange={() => {}}
          filters={[{
            label: "Category",
            value: filter,
            options: CATEGORY_FILTERS.map(f => ({ label: f.label, value: f.key })),
            onChange: setFilter,
          }]}
        />
        }
      />

      <ProgressBar value={data?.stats.claimed ?? 0} max={Math.max(data?.stats.total ?? 1, 1)} size="sm" />

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {achievements.map((a) => (
          <AchievementCard key={a.id} achievement={a} />
        ))}
      </div>

      {achievements.length === 0 && (
        <p className="text-sm text-text-disabled text-center py-12">
          No achievements in this category yet.
        </p>
      )}
    </div>
  );
}
