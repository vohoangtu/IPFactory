export interface AgentConfig {
  agentId: string;
  model: string;
  temperature: number;
  maxTokens: number;
  retryAttempts: number;
}

export interface EpistemicConfig {
  noiseLevel: number;
  tier: 'oracle' | 'historian' | 'myth';
  strictMode: boolean;
}
