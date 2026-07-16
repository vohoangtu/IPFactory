'use client';

import { useMemo, useCallback } from 'react';
import {
  ReactFlow,
  Background,
  Controls,
  MiniMap,
  useReactFlow,
  type Node,
  type Edge,
  type NodeTypes,
} from '@xyflow/react';
import '@xyflow/react/dist/style.css';
import Dagre from 'dagre';

import type { TopologyData } from '@/shared/types/api';
import ZoneNode from './ZoneNode';
import MapControls from './MapControls';

// ── Node Types ──────────────────────────────────

const nodeTypes: NodeTypes = {
  zone: ZoneNode,
};

// ── Edge color by type ──────────────────────────

const edgeColorMap: Record<string, string> = {
  quantum_trade: '#06b6d4', // cyan-500
  social: '#f59e0b',        // amber-500
  ecological: '#10b981',    // emerald-500
  narrative: '#8b5cf6',     // violet-500
  economic: '#f43f5e',      // rose-500
  political: '#6366f1',     // indigo-500
};

const DEFAULT_EDGE_COLOR = '#475569'; // slate-600

// ── Dagre Layout ────────────────────────────────

const NODE_WIDTH = 200;
const NODE_HEIGHT = 80;

function getLayoutedElements(nodes: Node[], edges: Edge[]) {
  const g = new Dagre.graphlib.Graph().setDefaultEdgeLabel(() => ({}));
  g.setGraph({ rankdir: 'TB', nodesep: 80, ranksep: 120 });

  nodes.forEach((node) => g.setNode(node.id, { width: NODE_WIDTH, height: NODE_HEIGHT }));
  edges.forEach((edge) => g.setEdge(edge.source, edge.target));

  Dagre.layout(g);

  return {
    nodes: nodes.map((node) => {
      const pos = g.node(node.id);
      return {
        ...node,
        position: {
          x: (pos?.x ?? 0) - NODE_WIDTH / 2,
          y: (pos?.y ?? 0) - NODE_HEIGHT / 2,
        },
      };
    }),
    edges,
  };
}

// ── Props ───────────────────────────────────────

interface TopologyGraphProps {
  topology: TopologyData | undefined;
  highlightedNodes?: string[];
  isPanelOpen: boolean;
  onTogglePanel: () => void;
}

export default function TopologyGraph({
  topology,
  highlightedNodes = [],
  isPanelOpen,
  onTogglePanel,
}: TopologyGraphProps) {
  const { fitView } = useReactFlow();

  const highlightSet = useMemo(() => new Set(highlightedNodes), [highlightedNodes]);

  // Convert topology data to ReactFlow nodes + edges
  const { nodes, edges } = useMemo(() => {
    if (!topology?.topology?.nodes?.length) {
      return { nodes: [], edges: [] };
    }

    const rawNodes: Node[] = topology.topology.nodes.map((n) => ({
      id: n.id,
      type: 'zone',
      position: { x: 0, y: 0 }, // will be set by dagre
      data: {
        label: n.label,
        type: n.type,
        metrics: n.metrics ?? {},
        highlighted: highlightSet.has(n.id),
      },
    }));

    const rawEdges: Edge[] = topology.topology.edges.map((e) => {
      const color = edgeColorMap[e.type] ?? DEFAULT_EDGE_COLOR;
      const strokeWidth = Math.max(1, Math.min(6, Math.round(e.intensity ?? 1)));

      return {
        id: e.id,
        source: e.source,
        target: e.target,
        animated: true,
        label: e.label || undefined,
        style: {
          stroke: color,
          strokeWidth,
        },
        labelStyle: {
          fill: '#94a3b8',
          fontSize: 10,
          fontWeight: 600,
        },
        labelBgStyle: {
          fill: '#0f172a',
          fillOpacity: 0.8,
        },
        labelBgPadding: [6, 4] as [number, number],
        labelBgBorderRadius: 4,
      };
    });

    return getLayoutedElements(rawNodes, rawEdges);
  }, [topology, highlightSet]);

  const handleFitView = useCallback(() => {
    fitView({ padding: 0.2, duration: 400 });
  }, [fitView]);

  return (
    <div className="relative flex-1 h-full">
      <ReactFlow
        nodes={nodes}
        edges={edges}
        nodeTypes={nodeTypes}
        fitView
        minZoom={0.3}
        maxZoom={2}
        proOptions={{ hideAttribution: true }}
        className="!bg-[#0a0a0c]"
      >
        <Background color="#1e293b" gap={20} size={1} />
        <MiniMap
          nodeColor={() => '#1e293b'}
          maskColor="rgba(0, 0, 0, 0.7)"
          style={{
            backgroundColor: '#0f172a',
            border: '1px solid rgba(51, 65, 85, 0.5)',
            borderRadius: 12,
          }}
        />
        <Controls
          showInteractive={false}
          style={{
            borderRadius: 12,
            overflow: 'hidden',
            border: '1px solid rgba(51, 65, 85, 0.5)',
          }}
        />
      </ReactFlow>

      <MapControls
        onFitView={handleFitView}
        onTogglePanel={onTogglePanel}
        isPanelOpen={isPanelOpen}
      />

      {/* Empty state overlay */}
      {nodes.length === 0 && (
        <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
          <div className="text-center">
            <div className="w-16 h-16 mx-auto mb-4 rounded-2xl bg-slate-800/50 border border-slate-700/50 flex items-center justify-center">
              <svg
                width="28"
                height="28"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.5"
                strokeLinecap="round"
                strokeLinejoin="round"
                className="text-slate-600"
              >
                <circle cx="12" cy="12" r="10" />
                <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" />
                <path d="M2 12h20" />
              </svg>
            </div>
            <p className="text-sm font-bold text-slate-500">No topology data</p>
            <p className="text-xs text-slate-600 mt-1">
              Waiting for universe topology to load...
            </p>
          </div>
        </div>
      )}
    </div>
  );
}
