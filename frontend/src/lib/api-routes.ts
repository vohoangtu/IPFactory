/**
 * Centralized API route constants for WorldOS V6 frontend.
 *
 * All API paths live here to prevent hardcoded strings scattered across
 * the codebase. If a backend route changes, update it in one place.
 */

export const apiRoutes = {
  auth: {
    me: () => '/auth/me',
    login: () => '/auth/login',
    logout: () => '/auth/logout',
  },

  actors: {
    list: (universeId: number) => `/worldos/universes/${universeId}/actors`,
    detail: (actorId: number) => `/worldos/actors/${actorId}`,
    events: (actorId: number) => `/worldos/actors/${actorId}/events`,
    decisions: (actorId: number) => `/worldos/actors/${actorId}/decisions`,
    mindMeld: (actorId: number) => `/worldos/actors/${actorId}/mind-meld`,
    supremeEntities: (universeId: number) => `/worldos/universes/${universeId}/supreme-entities`,
  },

  universes: {
    list: () => '/worldos/universes',
    detail: (id: number) => `/worldos/universes/${id}`,
    create: () => '/worldos/universes',
    delete: (id: number) => `/worldos/universes/${id}`,
    toggleStatus: (id: number) => `/worldos/universes/${id}/toggle-status`,
    metrics: (id: number) => `/worldos/universes/${id}/metrics`,
    dossier: (id: number) => `/worldos/universes/${id}/dossier`,
    snapshots: (id: number) => `/worldos/universes/${id}/snapshots`,
    createSnapshot: (id: number) => `/worldos/universes/${id}/snapshots`,
    forks: (id: number) => `/worldos/universes/${id}/forks`,
    fork: (id: number) => `/worldos/universes/${id}/fork`,
    compareBranch: (id: number) => `/worldos/universes/${id}/forks/compare`,
    realityState: (id: number) => `/worldos/universes/${id}/reality-state`,
    causalLinks: (id: number) => `/worldos/universes/${id}/causal-links`,
  },

  simulation: {
    advance: () => '/worldos/simulation/advance',
  },

  multiverse: {
    bloom: () => '/apex/multiverse/bloom',
    resonance: () => '/apex/multiverse/resonance',
  },

  wavefunction: {
    snapshot: (universeId: number) => `/apex/wavefunction/${universeId}`,
    informationalMass: (universeId: number) => `/apex/informational-mass/${universeId}`,
    consciousness: (universeId: number) => `/apex/v10/universes/${universeId}/consciousness`,
    ascensionFilters: (universeId: number) => `/apex/v10/universes/${universeId}/ascension-filters`,
    delta: (universeId: number) => `/apex/v10/universes/${universeId}/delta`,
    topology: (universeId: number) => `/apex/v10/universes/${universeId}/topology`,
  },

  causalMap: {
    topology: (universeId: number) => `/apex/v10/universes/${universeId}/topology`,
    links: (universeId: number) => `/worldos/universes/${universeId}/causal-links`,
  },

  ai: {
    settings: () => '/ai-settings',
    keyPool: () => '/ai-key-pool',
    providerModels: () => '/ai-provider-models',
    diagnostics: () => '/ai-diagnostics',
    logs: () => '/ai-logs',
    logsClear: () => '/ai-logs/clear',
    logsStats: () => '/ai-logs/stats',
  },

  loom: {
    status: () => '/loom-status',
    tasks: (taskId: string) => `/loom-tasks/${taskId}/status`,
    actorIntent: () => '/loom/actor-intent',
    scribe: () => '/loom/scribe-history',
    health: () => '/loom/health',
    metrics: () => '/loom/metrics',
    config: () => '/loom/config',
  },

  chronicles: {
    list: (universeId: number) => `/worldos/universes/${universeId}/chronicles`,
  },

  wiki: {
    search: () => '/wiki/search',
  },
} as const;
