"use client";

import { useState } from "react";
import { TimelineEvent } from "@/hooks/useProgression";
import BadgeLabel from "@/components/ui/shared/BadgeLabel";

const TYPE_STYLES: Record<string, { icon: string; color: string }> = {
  epoch: { icon: "◆", color: "text-brand-amber" },
  discovery: { icon: "★", color: "text-brand-emerald" },
  scar: { icon: "🔥", color: "text-brand-danger" },
  celebrity: { icon: "♔", color: "text-brand-accent" },
};

interface Props {
  event: TimelineEvent;
}

export function TimelineNode({ event }: Props) {
  const [expanded, setExpanded] = useState(false);
  const style = TYPE_STYLES[event.type] ?? { icon: "●", color: "text-text-primary" };

  return (
    <div className="relative">
      <div
        className="absolute -left-[33px] top-0 flex items-center justify-center w-5 h-5 rounded-full bg-bg-base border border-border-muted"
        style={{ color: style.color }}
      >
        <span className="text-xs">{style.icon}</span>
      </div>

      <button
        onClick={() => setExpanded(!expanded)}
        className="text-left w-full group"
      >
        <div className="flex items-center gap-3">
          <span className="text-xs font-mono text-text-disabled">
            Tick {event.tick}
          </span>
          <span className="text-sm font-medium text-text-primary group-hover:text-text-primary/80">
            {event.label}
          </span>
          <BadgeLabel
            variant={
              event.type === "scar"
                ? "danger"
                : event.type === "discovery"
                ? "success"
                : "default"
            }
          >
            {event.type}
          </BadgeLabel>
          {event.claimable && (
            <span className="text-[10px] px-1.5 py-0.5 rounded bg-brand-amber/20 text-brand-amber border border-brand-amber/30">
              Claimable
            </span>
          )}
        </div>
      </button>

      {expanded && (
        <div className="mt-2 p-3 rounded-lg bg-bg-surface/50 border border-border-subtle">
          {event.description && (
            <p className="text-sm text-text-primary/70">{event.description}</p>
          )}
          {event.actor_id && (
            <p className="text-xs text-text-primary/40 mt-1">
              Actor ID: {event.actor_id}
            </p>
          )}
          {event.severity && (
            <p className="text-xs text-brand-danger mt-1">
              Severity: {event.severity}
            </p>
          )}
        </div>
      )}
    </div>
  );
}
