export const routes = {
  login: () => '/login',
  multiverse: () => '/multiverse',
  universe: (id: number) => `/u/${id}`,
  universeActors: (id: number) => `/u/${id}/actors`,
  universeCivilization: (id: number) => `/u/${id}/civilization`,
  universeCausality: (id: number) => `/u/${id}/causality`,
  universeWavefunction: (id: number) => `/u/${id}/wavefunction`,
  chronicle: (chronicleId: number) => `/chronicle/${chronicleId}`,
} as const;
