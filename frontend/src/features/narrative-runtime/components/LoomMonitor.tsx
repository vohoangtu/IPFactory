"use client";

import { useEffect, useMemo } from "react";
import { ReactFlow, Background, Controls, useNodesState, Node } from "@xyflow/react";
import "@xyflow/react/dist/style.css";
import { useNarrativeRuntime } from "../useNarrativeRuntime";
import { usePipelineManifest } from "../hooks";

const NODE_COLORS: Record<string, string> = {
  idle: "#334155",
  running: "#0ea5e9",
  completed: "#10b981",
  error: "#f43f5e",
};

const DEFAULT_NODES: Node[] = [
  { id: "Event_Normalizer", position: { x: 0, y: 0 }, data: { label: "Event Normalizer" } },
  { id: "Universe_Bridge", position: { x: 0, y: 60 }, data: { label: "Universe Bridge" } },
  { id: "Entropy_Engine", position: { x: -120, y: 120 }, data: { label: "Entropy Engine" } },
  { id: "Style_Analyzer", position: { x: 120, y: 120 }, data: { label: "Style Analyzer" } },
  { id: "Attractor_Engine", position: { x: 0, y: 180 }, data: { label: "Attractor Engine" } },
  { id: "Dramatic_Arc", position: { x: 0, y: 240 }, data: { label: "Dramatic Arc" } },
  { id: "Phase_Engine", position: { x: 0, y: 300 }, data: { label: "Phase Engine" } },
  { id: "Singularity_Engine", position: { x: 0, y: 360 }, data: { label: "Singularity Engine" } },
  { id: "Chief_Editor", position: { x: 0, y: 420 }, data: { label: "Chief Editor" } },
  { id: "The_Historian", position: { x: -120, y: 480 }, data: { label: "The Historian" } },
  { id: "The_Mythologist", position: { x: 120, y: 480 }, data: { label: "The Mythologist" } },
  { id: "The_Psychologist", position: { x: 0, y: 540 }, data: { label: "The Psychologist" } },
  { id: "The_Director", position: { x: 0, y: 600 }, data: { label: "The Director" } },
  { id: "The_Wordsmith", position: { x: 0, y: 660 }, data: { label: "The Wordsmith" } },
  { id: "The_Critic", position: { x: 0, y: 720 }, data: { label: "The Critic" } },
  { id: "VFX_Director", position: { x: 0, y: 780 }, data: { label: "VFX Director" } },
  { id: "The_Archivist", position: { x: 0, y: 840 }, data: { label: "The Archivist" } },
  { id: "News_Anchor", position: { x: 0, y: 900 }, data: { label: "News Anchor" } },
];

export function LoomMonitor({ runtime }: { runtime: ReturnType<typeof useNarrativeRuntime> }) {
  const { pipelineNodes, progress, currentAgent } = runtime;
  const { nodes: manifestNodes } = usePipelineManifest();

  const baseNodes = useMemo(
    () =>
      manifestNodes.length > 0
        ? manifestNodes.map((n, i) => ({
            id: n.id,
            position: { x: 0, y: i * 60 },
            data: { label: n.label },
          }))
        : DEFAULT_NODES,
    [manifestNodes],
  );

  const [nodes, setNodes] = useNodesState(baseNodes);

  useEffect(() => {
    setNodes((nds) =>
      nds.map((node) => {
        const state = pipelineNodes[node.id];
        const status = state?.status ?? "idle";
        return {
          ...node,
          style: {
            background: NODE_COLORS[status] ?? NODE_COLORS.idle,
            color: "#fff",
            border: status === "running" ? "2px solid #fff" : "1px solid #475569",
            width: 180,
          },
        };
      })
    );
  }, [pipelineNodes, setNodes]);

  return (
    <div className="h-[calc(100vh-120px)] w-full rounded-xl border border-border-subtle bg-bg-base">
      <div className="flex items-center gap-4 px-4 py-2 border-b border-border-subtle">
        <h2 className="text-sm font-semibold text-text-primary">Loom Pipeline Monitor</h2>
        <span className="text-xs text-text-disabled">{progress.completed}/{progress.total} nodes</span>
        {currentAgent && <span className="text-xs text-brand-info">Running: {currentAgent}</span>}
      </div>
      <ReactFlow nodes={nodes} fitView className="bg-transparent">
        <Background gap={16} size={1} color="#334155" />
        <Controls />
      </ReactFlow>
    </div>
  );
}
