'use client';

import { useCallback, useState } from 'react';
import { ReactFlowProvider } from '@xyflow/react';
import { Loader2 } from 'lucide-react';
import TopologyGraph from './TopologyGraph';
import CausalLinkPanel from './CausalLinkPanel';
import { useTopology } from '../hooks';

/** Lens Nhân quả — bọc ReactFlowProvider quanh topology graph + panel causal-links. */
export function CausalityLens({ universeId }: { universeId: number }) {
  const { topology, isLoading, isError } = useTopology(universeId);
  const [highlightedNodes, setHighlightedNodes] = useState<string[]>([]);
  const [isPanelOpen, setIsPanelOpen] = useState(true);

  const handleHighlight = useCallback((nodeIds: string[]) => {
    setHighlightedNodes(nodeIds);
  }, []);

  const handleTogglePanel = useCallback(() => {
    setIsPanelOpen((prev) => !prev);
  }, []);

  if (isError) {
    return (
      <p className="text-sm text-[var(--color-danger)]" role="alert">
        Không tải được topology.
      </p>
    );
  }

  return (
    <div className="flex h-[calc(100vh-10rem)] flex-col gap-2">
      {isLoading && (
        <div className="flex items-center gap-2">
          <Loader2 size={14} className="animate-spin text-[var(--color-text-muted)]" />
          <span className="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--color-text-muted)]">
            Đang tải topology...
          </span>
        </div>
      )}

      <div className="flex flex-1 min-h-0 overflow-hidden rounded-2xl border border-[var(--border-subtle)] bg-[var(--color-bg-base)]">
        {!topology ? (
          <div className="flex flex-1 items-center justify-center">
            <p className="text-sm text-[var(--color-text-muted)]">Chưa có topology — hãy chạy tick.</p>
          </div>
        ) : (
          <>
            <ReactFlowProvider>
              <TopologyGraph
                topology={topology}
                highlightedNodes={highlightedNodes}
                isPanelOpen={isPanelOpen}
                onTogglePanel={handleTogglePanel}
              />
            </ReactFlowProvider>

            {isPanelOpen && (
              <CausalLinkPanel universeId={universeId} onHighlight={handleHighlight} />
            )}
          </>
        )}
      </div>
    </div>
  );
}
