export const routes = {
  login: () => '/login',
  multiverse: () => '/multiverse',
  live: (id: number) => `/u/${id}/live`,
  replay: (id: number, tick?: number) => tick == null ? `/u/${id}/replay` : `/u/${id}/replay?tick=${tick}`,
  actor: (id: number, actorId: number) => `/u/${id}/actor/${actorId}`,
} as const;
