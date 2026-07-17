// ──────────────────────────────────────────────
// Shared API response types for WorldOS V6 Dashboard
// ──────────────────────────────────────────────

// ── Actors ───────────────────────────────────

export interface ActorSummary {
  id: number;
  universe_id: number;
  name: string;
  role: string;
  archetype: string;
  influence: number;
  alignment: string;
  last_decision: string;
  is_alive: boolean;
  life_stage: string;
  birth_tick: number;
  death_tick: number | null;
}

export interface ActorDetail extends ActorSummary {
  biography: string | null;
  traits: Record<string, number>;
  metrics: Record<string, number>;
  stats: Record<string, number>;
  capabilities: string[];
  vitality: Record<string, unknown>;
  supreme_entity: SupremeEntity | null;
  recent_events: ActorEvent[];
}

export interface ActorEvent {
  id: number;
  tick: number;
  type: string;
  summary: string;
  context: Record<string, unknown>;
}

export interface ActorDecision {
  id: number;
  actor_id: number;
  universe_id: number;
  tick: number;
  action_type: string;
  summary: string;
  utility_score: number;
  confidence: number;
  impact: Record<string, unknown>;
  context_snapshot: Record<string, unknown>;
}

export interface SupremeEntity {
  id: number;
  name: string;
  entity_type: string;
  domain: string;
  power_level: number;
  alignment: Record<string, unknown>;
  status: string;
  actor_id: number | null;
}

// ── Snapshots & Branches ─────────────────────

export interface Snapshot {
  id: number;
  universe_id: number;
  tick: number;
  label: string;
  created_at: string | null;
  summary: string;
  note: string;
  entropy: number;
  stability_index: number;
  metrics: Record<string, unknown>;
}

export interface SnapshotDetail extends Snapshot {
  state_vector: Record<string, unknown>;
}

export interface BranchSummary {
  id: number;
  universe_id: number;
  name: string;
  label: string;
  status: string;
  divergence_tick: number;
  forked_at_tick: number;
  current_tick: number;
  created_at: string | null;
}

export interface BranchComparison {
  universe_id: number;
  branch_id: number;
  source: BranchComparisonSide;
  branch: BranchComparisonSide;
  tick_span: number;
  deltas: Record<string, number>;
  metric_deltas: Record<string, number>;
}

interface BranchComparisonSide {
  id: number;
  name: string;
  status: string;
  forked_at_tick: number;
  current_tick: number;
  snapshot_id: number;
  tick: number;
  entropy: number;
  stability_index: number;
  metrics: Record<string, unknown>;
}

// ── Chronicles & Narrative ───────────────────

export interface Chronicle {
  id: number;
  universe_id: number;
  tick: number;
  from_tick: number;
  to_tick: number;
  title: string;
  summary: string;
  content: string;
  type: string;
  importance: number;
  actor_id: number | null;
  world_event_id: number | null;
  has_animation: boolean;
  /** VAF script thô — features/cinema parse bằng parseAnimationScript(raw: unknown) */
  animation_script: unknown;
}

export type ChronicleDetail = Chronicle;

export interface MythScar {
  id: number;
  title: string;
  name: string;
  severity: string;
  severity_score: number;
  origin_tick: number;
  created_at_tick: number;
  consequence: string;
  description: string;
  zone_id: number | null;
  resolved_at_tick: number | null;
}

export interface Artifact {
  id: number;
  name: string;
  type: string;
  description: string;
  origin_tick: number;
  [key: string]: unknown;
}

export interface TimelineEvent {
  id: string;
  tick: number;
  year: number | null;
  category: string;
  zone: string;
  summary: string;
  actors: unknown[];
  institutions: unknown[];
  facts: unknown[];
}

// ── Wavefunction & APEX ──────────────────────

export interface WavefunctionData {
  universe_id: number;
  tick: number;
  wavefunction: {
    entropy: number;
    stability_index: number;
    information_density: number;
    active_attractor: string;
    collapse_probability: number;
    fields: Record<string, number>;
    pressures: Record<string, number>;
  };
  causal_topology: {
    ancestor_ids: number[];
    residual_seeds: unknown[];
    inherited_attractor: string;
  };
  autopoiesis: {
    enabled: boolean;
    entropy_threshold: number;
    mutation_history_size: number;
    last_mutation_vector: Record<string, number> | null;
  };
}

export interface InformationalMass {
  universe_id: number;
  tick: number;
  informational_mass: number;
  information_density: number;
  field_contributions: { field: string; mass: number }[];
  singularity_risk: 'NORMAL' | 'HIGH' | 'CRITICAL';
}

export interface ConsciousnessField {
  universe_id: number;
  global_resonance: number;
  primary_dimension: string;
  heatmap: {
    zone_id: number;
    x: number;
    y: number;
    intensity: number;
    phase: 'DORMANT' | 'AWAKENING' | 'APOTHEOSIS';
  }[];
}

export interface AscensionFilterData {
  universe_id: number;
  singularity_probability: number;
  filters: {
    id: string;
    name: string;
    status: 'PASSED' | 'ACTIVE' | 'DANGER' | 'WARNING' | 'OPEN' | 'LOCKED' | 'FAILED';
    progress: number;
  }[];
}

export interface StateDelta {
  universe_id: number;
  from_tick: number;
  to_tick: number;
  entropy_delta: number;
  stability_delta: number;
  tick_span: number;
  metric_deltas: Record<string, number>;
}

// ── Topology & Causal Map ────────────────────

export interface TopologyData {
  universe_id: number;
  tick: number;
  topology: {
    nodes: TopologyNode[];
    edges: TopologyEdge[];
  };
}

export interface TopologyNode {
  id: string;
  type: string;
  label: string;
  metrics: Record<string, number>;
}

export interface TopologyEdge {
  id: string;
  source: string;
  target: string;
  type: string;
  label: string;
  intensity: number;
}

export interface CausalLinkData {
  universe_id: number;
  links: CausalLink[];
}

export interface CausalLink {
  id: string;
  source: string;
  target: string;
  type: string;
  tick: number;
  [key: string]: unknown;
}

export interface RealityState {
  universe_id: number;
  tick: number;
  era: string;
  pulse: {
    entropy: number;
    stability_index: number;
    entropy_threshold: number;
    collapse_probability: number;
  };
  layers: {
    physical: Record<string, unknown>;
    life: Record<string, unknown>;
    social: Record<string, unknown>;
    narrative: Record<string, unknown>;
  };
  materials: unknown[];
  civilization: Record<string, unknown>;
  vfx_config: Record<string, unknown>;
}

// ── Multiverse ───────────────────────────────

export interface MultiverseBloom {
  id: string;
  label: string;
  sub: string;
  worlds: MultiverseWorld[];
}

export interface MultiverseWorld {
  id: string;
  label: string;
  genre: string;
  sci: number;
  status: string;
  universes: MultiverseUniverse[];
}

export interface MultiverseUniverse {
  id: string;
  label: string;
  sub: string;
  status: string;
  sci: number;
  parentUniverseId: string | null;
  saliency: number;
}

export interface ResonancePollen {
  id: number;
  universe_id: number;
  headline: string;
  slogan: string;
  story_snippet: string;
  intensity: number;
  distortion: number;
  vfx: Record<string, unknown>;
  tags: string[];
  effects: unknown[];
  origin_tick: number;
}

export interface MultiverseResonance {
  resonance_pollen: ResonancePollen[];
  global_narrative_entropy: number;
}

// ── Universe Dossier ─────────────────────────

export interface UniverseMetrics {
  universe_id: number;
  status: string;
  current_tick: number;
  stability: number;
  entropy: number;
  snapshot_count: number;
  branch_count: number;
  actor_count: number;
  chronicle_count: number;
  anomaly_count: number;
  myth_count: number;
  religion_count: number;
  material_identity: Record<string, unknown>;
  culture_identity: Record<string, unknown>;
}

// ── AI Logs ──────────────────────────────────

export type JsonValue =
  | string
  | number
  | boolean
  | null
  | JsonValue[]
  | { [key: string]: JsonValue };

export interface AiLog {
  id: number;
  feature: string;
  driver: string;
  model: string | null;
  input: JsonValue;
  output: JsonValue;
  latency_ms: number;
  status: 'success' | 'error';
  error_message?: string;
  created_at: string;
}

export interface PaginatedAiLogs {
  data: AiLog[];
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}

export interface AiStats {
  total_requests: number;
  success_rate: number;
  avg_latency: number;
  providers: { name: string; count: number }[];
  models: { name: string; count: number }[];
}
