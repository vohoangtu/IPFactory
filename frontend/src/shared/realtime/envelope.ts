export type EnvelopeSeverity = 'info' | 'notable' | 'critical';

/** Hợp đồng broadcast P1 — mọi payload Centrifugo đều là envelope này. */
export interface WorldEventEnvelope {
  id: string;
  type: string;
  tick: number;
  universe_id: number;
  world_id: number | null;
  severity: EnvelopeSeverity;
  occurred_at: string;
  payload: Record<string, unknown>;
}

/** Một mục trong dòng biên niên sử — trùng shape item của observatory/feed. */
export interface FeedItem {
  id: string;
  kind: 'event' | 'chronicle';
  type: string;
  tick: number;
  universe_id: number;
  severity: EnvelopeSeverity;
  occurred_at: string;
  payload: Record<string, unknown>;
}

export function parseEnvelope(data: unknown): WorldEventEnvelope | null {
  if (!data || typeof data !== 'object' || Array.isArray(data)) return null;
  const d = data as Record<string, unknown>;
  if (typeof d.id !== 'string' || typeof d.type !== 'string') return null;
  if (typeof d.tick !== 'number' || typeof d.universe_id !== 'number') return null;
  const severity: EnvelopeSeverity =
    d.severity === 'notable' || d.severity === 'critical' ? d.severity : 'info';
  return {
    id: d.id,
    type: d.type,
    tick: d.tick,
    universe_id: d.universe_id,
    world_id: typeof d.world_id === 'number' ? d.world_id : null,
    severity,
    occurred_at: typeof d.occurred_at === 'string' ? d.occurred_at : '',
    payload:
      d.payload && typeof d.payload === 'object' && !Array.isArray(d.payload)
        ? (d.payload as Record<string, unknown>)
        : {},
  };
}

export function envelopeToFeedItem(env: WorldEventEnvelope): FeedItem {
  return {
    id: env.id,
    kind: 'event',
    type: env.type,
    tick: env.tick,
    universe_id: env.universe_id,
    severity: env.severity,
    occurred_at: env.occurred_at,
    payload: env.payload,
  };
}
