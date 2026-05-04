'use client';

import { useState, useCallback } from 'react';
import { ReactFlowProvider } from '@xyflow/react';
import { Loader2 } from 'lucide-react';

import { useUniverse } from '@/contexts/UniverseContext';
import { useTopology } from '@/features/causal-map/hooks';
import TopologyGraph from '@/components/dashboard/causal-map/TopologyGraph';
import PageHeader from '@/components/ui/shared/PageHeader';
import CausalLinkPanel from '@/components/dashboard/causal-map/CausalLinkPanel';

export default function CausalMapPage() {
  const { activeUniverseId, isLoading: isUniverseLoading } = useUniverse();
  const { topology, isLoading: isTopologyLoading } = useTopology(activeUniverseId);

  const [highlightedNodes, setHighlightedNodes] = useState<string[]>([]);
  const [isPanelOpen, setIsPanelOpen] = useState(true);

  const handleHighlight = useCallback((nodeIds: string[]) => {
    setHighlightedNodes(nodeIds);
  }, []);

  const handleTogglePanel = useCallback(() => {
    setIsPanelOpen((prev) => !prev);
  }, []);

  const isLoading = isUniverseLoading || isTopologyLoading;

  return (
    <div className="flex flex-col h-[calc(100vh-8rem)]">
      <PageHeader
        title="Spatial Topology & Causal Links"
        subtitle="Explore the spatial structure of your universe and trace causal connections between zones."
        action={
          isLoading && (
            <div className="flex items-center gap-2">
              <Loader2 size={14} className="animate-spin text-brand-info" />
              <span className="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">
                Loading topology...
              </span>
            </div>
          )
        }
      />

      {/* Main content */}
      <div className="flex flex-1 min-h-0 rounded-2xl border border-border-subtle/50 bg-bg-base overflow-hidden">
        <ReactFlowProvider>
          <TopologyGraph
            topology={topology}
            highlightedNodes={highlightedNodes}
            isPanelOpen={isPanelOpen}
            onTogglePanel={handleTogglePanel}
          />
        </ReactFlowProvider>

        {isPanelOpen && (
          <CausalLinkPanel
            universeId={activeUniverseId}
            onHighlight={handleHighlight}
          />
        )}
      </div>
    </div>
  );
}
