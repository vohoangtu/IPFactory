'use client';

export type SimulationValue = string | number | boolean | null;

export interface SimulationSetting {
  id?: number;
  key: string;
  value: SimulationValue;
  group: string;
  description?: string | null;
}

export interface GroupedSimulationSettings {
  general: SimulationSetting[];
  physics: SimulationSetting[];
  simulation: SimulationSetting[];
  psychology: SimulationSetting[];
  entropy: SimulationSetting[];
  [group: string]: SimulationSetting[];
}

export interface ServiceCheck {
  status: 'ok' | 'error';
  latency_ms?: number;
  error?: string;
  http_status?: number;
  circuit_breaker?: string;
}

export interface ServiceStatusResponse {
  overall: 'healthy' | 'degraded';
  services: Record<string, ServiceCheck>;
  checked_at: string;
}

export type DriverName =
  | 'pool'
  | 'zai'
  | 'openai'
  | 'openrouter'
  | 'local'
  | 'gemini'
  | 'qwen'
  | string;

export interface AiSettingRecord {
  id: number;
  key: string;
  value: unknown;
  group: string;
  description?: string | null;
  is_secret: boolean;
  created_at: string;
  updated_at: string;
}

export interface LoomAgentRecord extends AiSettingRecord {
  agent_name?: string;
}

export interface AiFeatureProfile {
  driver: DriverName;
  model: string;
  max_tokens: string;
  tier: 'any' | 'free' | 'premium';
  model_group: string;
}

export interface AiDiagnosticsResult {
  status: 'success' | 'error';
  driver: string;
  prompt?: string;
  latency_ms: number;
  response?: string | null;
  error?: string;
  checked_at: string;
}

export interface AiKeyMetadata {
  url?: string;
  model?: string;
  [key: string]: unknown;
}

export interface AiKey {
  id: number;
  provider: string;
  label: string;
  tier: 'free' | 'premium';
  level: number;
  usage_count: number;
  status: 'active' | 'inactive' | 'cooldown';
  last_used_at: string | null;
  cooldown_until: string | null;
  model_group?: string;
  metadata?: AiKeyMetadata;
  key_preview?: string;
}

export interface AiKeyPayload {
  provider: string;
  label: string;
  key?: string;
  tier: 'free' | 'premium';
  status?: 'active' | 'inactive' | 'cooldown';
  level: number;
  model_group?: string;
  metadata?: AiKeyMetadata;
}

export interface AiProviderModelMetadata {
  tier?: string;
  context_length?: number;
  [key: string]: unknown;
}

export interface AiProviderModel {
  id: number;
  provider: string;
  model_name: string;
  display_name: string;
  is_active: boolean;
  metadata?: AiProviderModelMetadata | null;
  created_at?: string;
  updated_at?: string;
}

export interface ProviderModelsExportPayload {
  version: string;
  provider_models: AiProviderModel[];
}
