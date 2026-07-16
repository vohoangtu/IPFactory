export interface UniverseCivilization {
  universe_id: number;
  status: string;
  current_tick: number;
  epoch: number | null;
  metrics: { entropy: number | null; stability_index: number | null; structural_coherence: number | null; fitness_score: number | null };
  complexity: { actor_count: number; living_actor_count: number; supreme_entity_count: number };
  snapshot: { tick: number; metrics: Record<string, unknown> } | null;
}

export interface UniverseWorldState {
  universe_id: number;
  world_id: number | null;
  epoch: { id: number; name: string; theme: string | null; description: string | null; start_tick: number | null; end_tick: number | null; status: string | null } | null;
  religions: { id: number; name: string; followers: number; spread_rate: number | null; doctrine: unknown }[];
  treaties: { id: number; treaty_type: string; source_civ_id: number; target_civ_id: number; started_at_tick: number; ends_at_tick: number | null }[];
  technologies: { id: number; name: string; code: string; adopters: number; avg_level: number }[];
}
