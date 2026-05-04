'use client';

export type NarrativeNodeStatus = 'idle' | 'running' | 'completed' | 'error';
export type NarrativePhase = 'engine' | 'agent';
export type NarrativeDeskSize = 'standard' | 'feature' | 'boss' | 'compact';

export interface NarrativeNodeDefinition {
  id: string;
  label: string;
  shortLabel: string;
  role: string;
  description: string;
  phase: NarrativePhase;
  accent: string;
  deskSize: NarrativeDeskSize;
  officeX: number;
  officeY: number;
}

export interface NarrativeRuntimeNodeState {
  status: NarrativeNodeStatus;
  startedAt?: number;
  completedAt?: number;
  durationMs?: number;
  error?: string;
}

export interface PipelineProgress {
  completed: number;
  total: number;
  pct: number;
}

export interface NarrativeResult {
  headline?: string;
  prose?: string;
  newsSlogan?: string;
}

export interface IntermediateOutputs {
  historical_outline?: unknown;
  storyboard?: unknown;
  final_prose?: string;
  vfx_config?: unknown;
}

export interface AgentDetails {
  input?: unknown;
  output?: unknown;
  stage?: string;
  error?: string;
  revisions?: number;
}

export interface PipelineManifestNode {
  id: string;
  label: string;
  short_label: string;
  phase: NarrativePhase;
  role: string;
  description: string;
}

export interface PipelineManifestEdge {
  from: string;
  to: string;
  type: 'sequential' | 'parallel' | 'fan_in' | 'conditional';
  condition?: string;
}

export interface PipelineManifest {
  version: string;
  total_nodes: number;
  nodes: PipelineManifestNode[];
  edges: PipelineManifestEdge[];
}

export interface LoomStatus {
  status: 'online' | 'offline' | 'degraded' | 'error';
  agents: Record<
    string,
    {
      provider?: string;
      model?: string;
      role?: string;
      tier?: string;
    }
  >;
  providers: Record<
    string,
    {
      status?: string;
      key_present?: boolean;
      url?: string;
    }
  >;
  version?: string;
  message?: string;
}

export interface NarrativeEvent {
  type?: string;
  agent?: string;
  duration_ms?: number;
  progress?: PipelineProgress;
  error?: string;
  final_prose?: string;
  news_headline?: string;
  news_slogan?: string;
  historical_outline?: unknown;
  storyboard?: unknown;
  vfx_config?: unknown;
  ts?: number;
  input?: unknown;
  output?: unknown;
  stage?: string;
  total_agents?: number;
  chronicle_id?: string;
}

export interface LoomTaskStatusPayload {
  task_id?: string;
  status?: string;
  result?: {
    historical_outline?: unknown;
    storyboard?: unknown;
    final_prose?: string;
    news_headline?: string;
    news_slogan?: string;
    vfx_config?: unknown;
  };
  error?: string;
  message?: string;
}

export const NARRATIVE_PIPELINE_NODES: NarrativeNodeDefinition[] = [
  {
    id: 'Event_Normalizer',
    label: 'Event Normalizer',
    shortLabel: 'Normalizer',
    role: 'Normalize raw chronicles',
    description: 'Validates, deduplicates, and prepares event input for the rest of the pipeline.',
    phase: 'engine',
    accent: '#e8c97a',
    deskSize: 'standard',
    officeX: 7,
    officeY: 14,
  },
  {
    id: 'Universe_Bridge',
    label: 'Universe Bridge',
    shortLabel: 'Bridge',
    role: 'Inject world context',
    description: 'Enriches the pipeline with world, era, and relationship context from WorldOS.',
    phase: 'engine',
    accent: '#7c6af5',
    deskSize: 'standard',
    officeX: 30,
    officeY: 14,
  },
  {
    id: 'Entropy_Engine',
    label: 'Entropy Engine',
    shortLabel: 'Entropy',
    role: 'Measure chaos',
    description: 'Scores world volatility and feeds tone pressure into downstream narrative decisions.',
    phase: 'engine',
    accent: '#e84a5f',
    deskSize: 'standard',
    officeX: 7,
    officeY: 39,
  },
  {
    id: 'Attractor_Engine',
    label: 'Attractor Engine',
    shortLabel: 'Attractor',
    role: 'Detect clusters',
    description: 'Finds gravity wells across actors, places, and events.',
    phase: 'engine',
    accent: '#4caf8c',
    deskSize: 'standard',
    officeX: 30,
    officeY: 39,
  },
  {
    id: 'Style_Analyzer',
    label: 'Style Analyzer',
    shortLabel: 'Style',
    role: 'Pick narrative tone',
    description: 'Chooses stylistic guidance based on era, genre, and world context.',
    phase: 'engine',
    accent: '#00acc1',
    deskSize: 'standard',
    officeX: 7,
    officeY: 64,
  },
  {
    id: 'Dramatic_Arc',
    label: 'Dramatic Arc',
    shortLabel: 'Arc',
    role: 'Find dramatic structure',
    description: 'Builds the exposition-to-resolution arc from chronological event patterns.',
    phase: 'engine',
    accent: '#e8a030',
    deskSize: 'standard',
    officeX: 30,
    officeY: 64,
  },
  {
    id: 'Phase_Engine',
    label: 'Phase Engine',
    shortLabel: 'Phase',
    role: 'Classify lifecycle stage',
    description: 'Classifies the narrative state into early, mid, late, or endgame context.',
    phase: 'engine',
    accent: '#f59e0b',
    deskSize: 'standard',
    officeX: 7,
    officeY: 82,
  },
  {
    id: 'Singularity_Engine',
    label: 'Singularity Engine',
    shortLabel: 'Singularity',
    role: 'Flag irreversible events',
    description: 'Identifies pivotal moments that should dominate the final chronicle.',
    phase: 'engine',
    accent: '#9c27b0',
    deskSize: 'standard',
    officeX: 30,
    officeY: 82,
  },
  {
    id: 'Chief_Editor',
    label: 'Chief Editor',
    shortLabel: 'Editor',
    role: 'Set editorial angle',
    description: 'Defines the central voice, theme, and narrative mandate for the creative phase.',
    phase: 'agent',
    accent: '#7c6af5',
    deskSize: 'boss',
    officeX: 59,
    officeY: 12,
  },
  {
    id: 'The_Historian',
    label: 'Historian',
    shortLabel: 'Historian',
    role: 'Write historical outline',
    description: 'Turns normalized events into a structured backbone with causal ordering.',
    phase: 'agent',
    accent: '#795548',
    deskSize: 'standard',
    officeX: 55,
    officeY: 37,
  },
  {
    id: 'The_Mythologist',
    label: 'Mythologist',
    shortLabel: 'Mythologist',
    role: 'Overlay mythic meaning',
    description: 'Adds archetypes, symbols, and legendary resonance to the outline.',
    phase: 'agent',
    accent: '#9c27b0',
    deskSize: 'standard',
    officeX: 72,
    officeY: 37,
  },
  {
    id: 'The_Psychologist',
    label: 'Psychologist',
    shortLabel: 'Psychologist',
    role: 'Profile motivations',
    description: 'Builds psychological context for key actors and pressures on the narrative.',
    phase: 'agent',
    accent: '#00acc1',
    deskSize: 'standard',
    officeX: 55,
    officeY: 61,
  },
  {
    id: 'The_Director',
    label: 'Director',
    shortLabel: 'Director',
    role: 'Storyboard scenes',
    description: 'Translates the outline into scenes, pacing, and visual narrative beats.',
    phase: 'agent',
    accent: '#f44336',
    deskSize: 'standard',
    officeX: 72,
    officeY: 61,
  },
  {
    id: 'The_Wordsmith',
    label: 'Wordsmith',
    shortLabel: 'Wordsmith',
    role: 'Write final prose',
    description: 'Produces the actual chronicle prose from the storyboard and editorial directives.',
    phase: 'agent',
    accent: '#4caf8c',
    deskSize: 'feature',
    officeX: 63,
    officeY: 82,
  },
  {
    id: 'The_Critic',
    label: 'Critic',
    shortLabel: 'Critic',
    role: 'Evaluate and revise',
    description: 'Runs quality control and may trigger revision loops back into Wordsmith.',
    phase: 'agent',
    accent: '#e84a5f',
    deskSize: 'standard',
    officeX: 82,
    officeY: 82,
  },
  {
    id: 'VFX_Director',
    label: 'VFX Director',
    shortLabel: 'VFX',
    role: 'Generate visual mood cues',
    description: 'Prepares visual effects hints and atmosphere metadata for presentation layers.',
    phase: 'agent',
    accent: '#8b5cf6',
    deskSize: 'compact',
    officeX: 55,
    officeY: 95,
  },
  {
    id: 'The_Archivist',
    label: 'Archivist',
    shortLabel: 'Archivist',
    role: 'Persist approved output',
    description: 'Archives the approved chronicle and prepares metadata for downstream consumers.',
    phase: 'agent',
    accent: '#607d8b',
    deskSize: 'standard',
    officeX: 72,
    officeY: 95,
  },
  {
    id: 'News_Anchor',
    label: 'News Anchor',
    shortLabel: 'Anchor',
    role: 'Generate headlines',
    description: 'Produces concise in-world headlines and summary language for notifications.',
    phase: 'agent',
    accent: '#ff6f00',
    deskSize: 'compact',
    officeX: 86,
    officeY: 95,
  },
];

export const NARRATIVE_NODE_MAP = Object.fromEntries(
  NARRATIVE_PIPELINE_NODES.map((node) => [node.id, node]),
) as Record<string, NarrativeNodeDefinition>;
