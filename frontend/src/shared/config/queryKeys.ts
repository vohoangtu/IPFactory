export const qk = {
  universes: () => ['universes'] as const,
  universe: (id: number) => ['universes', id] as const,
  metrics: (id: number) => ['universes', id, 'metrics'] as const,
  snapshot: (id: number, tick: number) => ['universes', id, 'snapshot', tick] as const,
  chronicles: (id: number) => ['universes', id, 'chronicles'] as const,
  feed: (id: number) => ['universes', id, 'feed'] as const,
  forkTree: () => ['multiverse', 'fork-tree'] as const,
} as const;
