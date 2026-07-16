export interface ActorPsyche {
  actor: { id: number; universe_id: number; name: string; archetype: string | null; is_alive: boolean; life_stage: string | null };
  emotions: Record<string, number>;
  needs: Record<string, number>;
  goals: { type: string; priority: number }[];
  trait_vector: number[];
  recent_decisions: {
    id: number; tick: number; action_type: string | null; reasoning: string | null;
    utility_score: number | null; confidence: number | null; impact: Record<string, unknown> | null;
  }[];
}
