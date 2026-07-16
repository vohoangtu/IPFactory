export type UniverseStatus = 'active' | 'paused' | 'halted';

export interface Universe {
  id: number;
  world_id: number;
  name: string;
  status: UniverseStatus;
  current_tick: number;
  era: number;
}

export interface LiveMetrics {
  entropy?: number;
  stability?: number;
  [key: string]: number | undefined;
}

export interface MetricPoint {
  tick: number;
  entropy: number | null;
  stability: number | null;
}

export interface SimEvent {
  tick: number;
  type: string;
  summary: string;
}

export interface Snapshot {
  tick: number;
  metrics: LiveMetrics;
  events: SimEvent[];
}
