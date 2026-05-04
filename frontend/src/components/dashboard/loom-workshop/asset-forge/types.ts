export type AssetTabId = 'celebrity' | 'artifact' | 'visual' | 'audio';

export interface CelebrityAssetResult {
  name?: string;
  biography?: string;
}

export interface ArtifactAssetResult {
  name?: string;
  lore?: string;
}

export interface VisualAssetResult {
  image_url?: string;
}

export interface AudioAssetResult {
  epoch_name?: string;
  style?: string;
  stream_url?: string;
}

export type AssetForgeResult =
  | CelebrityAssetResult
  | ArtifactAssetResult
  | VisualAssetResult
  | AudioAssetResult;

export interface CelebrityFormData {
  agent_id: string;
  zone_id: string;
  fame: number;
  vocation: string;
  world_era: string;
}

export interface ArtifactFormData {
  artifact_id: string;
  zone_id: string;
  mass: number;
  knowledge: string;
  world_era: string;
}

export interface VisualFormData {
  prompt: string;
  is_portrait: boolean;
}

export interface AudioFormData {
  epoch_name: string;
  core_theme: string;
}

export interface ActorOption {
  id: string | number;
  name?: string;
}
