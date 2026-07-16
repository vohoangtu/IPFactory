export const routes = {
  login: () => '/login',
  multiverse: () => '/multiverse',
  universe: (id: number) => `/u/${id}`,
} as const;
