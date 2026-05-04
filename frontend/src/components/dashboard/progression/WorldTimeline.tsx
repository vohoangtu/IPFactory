"use client";

import { useState } from "react";
import { useProgression } from "@/hooks/useProgression";
import { useUniverse } from "@/contexts/UniverseContext";
import { TimelineNode } from "./TimelineNode";
import FilterToolbar from "@/components/ui/shared/FilterToolbar";
import PageHeader from "@/components/ui/shared/PageHeader";

const TYPE_FILTERS = [
  { key: "all", label: "All" },
  { key: "epoch", label: "Epochs" },
  { key: "discovery", label: "Discoveries" },
  { key: "scar", label: "Scars" },
  { key: "celebrity", label: "Celebrities" },
];

export function WorldTimeline() {
  const { activeUniverseId } = useUniverse();
  const { data, isLoading } = useProgression(activeUniverseId);
  const [filter, setFilter] = useState("all");

  const events =
    filter === "all"
      ? data?.timeline ?? []
      : data?.timeline.filter((e) => e.type === filter) ?? [];

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
        title="World Timeline"
        action={
          <FilterToolbar
          searchValue=""
          onSearchChange={() => {}}
          filters={[{
            label: "Filter",
            value: filter,
            options: TYPE_FILTERS.map(f => ({ label: f.label, value: f.key })),
            onChange: setFilter,
          }]}
        />
        }
      />

      <div className="relative pl-8 border-l border-border-subtle space-y-8">
        {events.map((event, idx) => (
          <TimelineNode key={idx} event={event} />
        ))}
        {events.length === 0 && (
          <p className="text-sm text-text-disabled">No events recorded yet.</p>
        )}
      </div>
    </div>
  );
}
