'use client';

import { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { narrativeQueries } from '../api/queries';
import { NARRATIVE_PIPELINE_NODES } from '../types';
import type { PipelineManifestNode } from '../types';

export function usePipelineManifest() {
  const { data, isLoading, error } = useQuery(narrativeQueries.pipelineManifest());

  const nodes: PipelineManifestNode[] = useMemo(
    () => data?.nodes ?? NARRATIVE_PIPELINE_NODES.map((n) => ({
      id: n.id,
      label: n.label,
      short_label: n.shortLabel,
      phase: n.phase,
      role: n.role,
      description: n.description,
    })),
    [data],
  );

  const nodeMap = useMemo(
    () => Object.fromEntries(nodes.map((n) => [n.id, n])),
    [nodes],
  );

  const edges = useMemo(() => data?.edges ?? [], [data]);

  const totalNodes = useMemo(() => data?.total_nodes ?? nodes.length, [data, nodes]);

  return {
    manifest: data,
    nodes,
    nodeMap,
    edges,
    totalNodes,
    isLoading,
    isError: !!error,
  };
}
