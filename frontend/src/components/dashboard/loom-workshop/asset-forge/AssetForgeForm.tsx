'use client';

import type {
  AssetTabId,
  ActorOption,
  CelebrityFormData,
  ArtifactFormData,
  VisualFormData,
  AudioFormData,
} from './types';

interface AssetForgeFormProps {
  activeTab: AssetTabId;
  actors: ActorOption[];
  vocations: string[];
  celebrityData: CelebrityFormData;
  setCelebrityData: (data: CelebrityFormData) => void;
  artifactData: ArtifactFormData;
  setArtifactData: (data: ArtifactFormData) => void;
  visualData: VisualFormData;
  setVisualData: (data: VisualFormData) => void;
  audioData: AudioFormData;
  setAudioData: (data: AudioFormData) => void;
}

export function AssetForgeForm({
  activeTab,
  actors,
  vocations,
  celebrityData,
  setCelebrityData,
  artifactData,
  setArtifactData,
  visualData,
  setVisualData,
  audioData,
  setAudioData,
}: AssetForgeFormProps) {
  switch (activeTab) {
    case 'celebrity':
      return (
        <div className="space-y-3">
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="text-[10px] uppercase text-slate-500 mb-1 block">Actor</label>
              <select
                value={celebrityData.agent_id}
                onChange={(e) => setCelebrityData({ ...celebrityData, agent_id: e.target.value })}
                className="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm"
              >
                <option value="">Select actor...</option>
                {actors.map((a) => (
                  <option key={a.id} value={String(a.id)}>{a.name ?? `Actor ${a.id}`}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="text-[10px] uppercase text-slate-500 mb-1 block">Zone ID</label>
              <input
                type="text"
                value={celebrityData.zone_id}
                onChange={(e) => setCelebrityData({ ...celebrityData, zone_id: e.target.value })}
                className="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm"
                placeholder="e.g. zone_alpha"
              />
            </div>
          </div>
          <div>
            <label className="text-[10px] uppercase text-slate-500 mb-1 block">Vocation</label>
            <select
              value={celebrityData.vocation}
              onChange={(e) => setCelebrityData({ ...celebrityData, vocation: e.target.value })}
              className="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm"
            >
              {vocations.map((v) => (
                <option key={v} value={v}>{v.charAt(0).toUpperCase() + v.slice(1)}</option>
              ))}
            </select>
          </div>
          <div>
            <label className="text-[10px] uppercase text-slate-500 mb-1 block">Fame Level (0-100)</label>
            <input
              type="range"
              min="0"
              max="100"
              value={celebrityData.fame}
              onChange={(e) => setCelebrityData({ ...celebrityData, fame: parseInt(e.target.value) })}
              className="w-full h-1 bg-slate-800 rounded-full appearance-none cursor-pointer accent-violet-500"
            />
            <p className="text-xs text-slate-500 mt-1">{celebrityData.fame} / 100</p>
          </div>
        </div>
      );

    case 'artifact':
      return (
        <div className="space-y-3">
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="text-[10px] uppercase text-slate-500 mb-1 block">Actor Owner</label>
              <select
                value={artifactData.artifact_id}
                onChange={(e) => setArtifactData({ ...artifactData, artifact_id: e.target.value })}
                className="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm"
              >
                <option value="">Select actor...</option>
                {actors.map((a) => (
                  <option key={a.id} value={String(a.id)}>{a.name ?? `Actor ${a.id}`}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="text-[10px] uppercase text-slate-500 mb-1 block">Zone ID</label>
              <input
                type="text"
                value={artifactData.zone_id}
                onChange={(e) => setArtifactData({ ...artifactData, zone_id: e.target.value })}
                className="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm"
                placeholder="e.g. ancient_ruins"
              />
            </div>
          </div>
          <div>
            <label className="text-[10px] uppercase text-slate-500 mb-1 block">Mass</label>
            <input
              type="number"
              value={artifactData.mass}
              onChange={(e) => setArtifactData({ ...artifactData, mass: parseInt(e.target.value) })}
              className="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm"
            />
          </div>
          <div>
            <label className="text-[10px] uppercase text-slate-500 mb-1 block">Knowledge / Lore Context</label>
            <textarea
              value={artifactData.knowledge}
              onChange={(e) => setArtifactData({ ...artifactData, knowledge: e.target.value })}
              className="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm h-20 resize-none"
              placeholder="What is known about this artifact..."
            />
          </div>
        </div>
      );

    case 'visual':
      return (
        <div className="space-y-3">
          <div>
            <label className="text-[10px] uppercase text-slate-500 mb-1 block">Image Prompt</label>
            <textarea
              value={visualData.prompt}
              onChange={(e) => setVisualData({ ...visualData, prompt: e.target.value })}
              className="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm h-32 resize-none"
              placeholder="Describe the image you want to generate..."
            />
          </div>
          <div className="flex items-center gap-3">
            <input
              type="checkbox"
              id="is_portrait"
              checked={visualData.is_portrait}
              onChange={(e) => setVisualData({ ...visualData, is_portrait: e.target.checked })}
              className="w-4 h-4 rounded border-slate-700 bg-slate-800 accent-violet-500"
            />
            <label htmlFor="is_portrait" className="text-sm text-slate-300">Portrait mode (character)</label>
          </div>
        </div>
      );

    case 'audio':
      return (
        <div className="space-y-3">
          <div>
            <label className="text-[10px] uppercase text-slate-500 mb-1 block">Epoch Name</label>
            <input
              type="text"
              value={audioData.epoch_name}
              onChange={(e) => setAudioData({ ...audioData, epoch_name: e.target.value })}
              className="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm"
              placeholder="e.g. The Golden Age"
            />
          </div>
          <div>
            <label className="text-[10px] uppercase text-slate-500 mb-1 block">Core Theme</label>
            <input
              type="text"
              value={audioData.core_theme}
              onChange={(e) => setAudioData({ ...audioData, core_theme: e.target.value })}
              className="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm"
              placeholder="e.g. war, peace, mystery, discovery"
            />
          </div>
        </div>
      );

    default:
      return null;
  }
}
