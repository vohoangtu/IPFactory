'use client';

import React, { useState } from 'react';
import { motion } from 'framer-motion';
import {
  Crown, Gem, ImageIcon, Music, Loader2, Sparkles,
  Palette,
} from 'lucide-react';
import { toast } from 'sonner';
import { apiClient } from '@/shared/lib/apiClient';
import { useActors } from '@/features/actors';
import { AssetForgeForm } from '../asset-forge/AssetForgeForm';
import { AssetForgeResult } from '../asset-forge/AssetForgeResult';
import type { AssetTabId, AssetForgeResult as AssetForgeResultType } from '../asset-forge/types';

const tabs: Array<{ id: AssetTabId; label: string; icon: typeof Crown }> = [
  { id: 'celebrity', label: 'Celebrity', icon: Crown },
  { id: 'artifact', label: 'Artifact', icon: Gem },
  { id: 'visual', label: 'Visual Asset', icon: ImageIcon },
  { id: 'audio', label: 'Soundtrack', icon: Music },
];

interface AssetForgeTabProps {
  universeId: number | null;
}

export default function AssetForgeTab({ universeId }: AssetForgeTabProps) {
  const { actors } = useActors(universeId);
  const [activeTab, setActiveTab] = useState<AssetTabId>('celebrity');
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<AssetForgeResultType | null>(null);

  const [celebrityData, setCelebrityData] = useState({
    agent_id: '', zone_id: '', fame: 50, vocation: 'warrior', world_era: 'genesis',
  });
  const [artifactData, setArtifactData] = useState({
    artifact_id: '', zone_id: '', mass: 10, knowledge: '', world_era: 'genesis',
  });
  const [visualData, setVisualData] = useState({ prompt: '', is_portrait: true });
  const [audioData, setAudioData] = useState({ epoch_name: '', core_theme: '' });

  const vocations = ['warrior', 'diplomat', 'scholar', 'merchant', 'mystic', 'ruler', 'explorer'];

  const generateAsset = async () => {
    setLoading(true);
    setResult(null);

    try {
      let endpoint = '';
      let payload = {};

      switch (activeTab) {
        case 'celebrity':
          endpoint = '/loom/weave-celebrity';
          payload = celebrityData;
          break;
        case 'artifact':
          endpoint = '/loom/forge-artifact';
          payload = artifactData;
          break;
        case 'visual':
          endpoint = '/loom/paint-asset';
          payload = visualData;
          break;
        case 'audio':
          endpoint = '/loom/compose-track';
          payload = audioData;
          break;
      }

      const response = await apiClient.post(endpoint, payload);
      setResult(response.data);
      toast.success(`${activeTab} generated successfully`);
    } catch (error) {
      console.error(`${activeTab} generation failed:`, error);
      toast.error(`Failed to generate ${activeTab}`);
    } finally {
      setLoading(false);
    }
  };

  const activeLabel = tabs.find((t) => t.id === activeTab)?.label ?? '';
  const ActiveIcon = tabs.find((t) => t.id === activeTab)?.icon ?? Sparkles;

  return (
    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
      {/* Left: Form */}
      <div className="space-y-4">
        <div className="flex flex-wrap gap-2 p-1 bg-slate-800 border border-slate-700 rounded-xl">
          {tabs.map((tab) => {
            const Icon = tab.icon;
            return (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium transition-all ${
                  activeTab === tab.id
                    ? 'bg-slate-700 text-white'
                    : 'text-slate-500 hover:text-slate-300'
                }`}
              >
                <Icon className="w-3 h-3" />
                {tab.label}
              </button>
            );
          })}
        </div>

        <div className="p-4 bg-slate-900 border border-slate-800 rounded-xl">
          <h3 className="text-xs uppercase tracking-widest text-slate-500 mb-4">
            {activeLabel} Configuration
          </h3>
          <AssetForgeForm
            activeTab={activeTab}
            actors={actors}
            vocations={vocations}
            celebrityData={celebrityData}
            setCelebrityData={setCelebrityData}
            artifactData={artifactData}
            setArtifactData={setArtifactData}
            visualData={visualData}
            setVisualData={setVisualData}
            audioData={audioData}
            setAudioData={setAudioData}
          />
        </div>

        <button
          onClick={generateAsset}
          disabled={loading}
          className="w-full flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-violet-500/20 to-fuchsia-500/20 text-white border border-violet-500/30 rounded-xl font-medium hover:from-violet-500/30 hover:to-fuchsia-500/30 transition-all disabled:opacity-50"
        >
          {loading ? (
            <>
              <Loader2 className="w-4 h-4 animate-spin" />
              Forging...
            </>
          ) : (
            <>
              <Sparkles className="w-4 h-4" />
              Generate {activeLabel}
            </>
          )}
        </button>
      </div>

      {/* Right: Result */}
      <div>
        {result ? (
          <motion.div
            initial={{ opacity: 0, scale: 0.95 }}
            animate={{ opacity: 1, scale: 1 }}
            className="p-6 bg-slate-900 border border-slate-800 rounded-xl"
          >
            <div className="flex items-center gap-3 mb-4">
              <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-fuchsia-500 flex items-center justify-center">
                <ActiveIcon className="w-5 h-5 text-white" />
              </div>
              <div>
                <h3 className="font-semibold text-white">{activeLabel} Generated</h3>
                <p className="text-xs text-slate-500">Ready to use</p>
              </div>
            </div>
            <AssetForgeResult activeTab={activeTab} result={result} />
          </motion.div>
        ) : (
          <div className="h-full min-h-[400px] flex items-center justify-center border border-dashed border-slate-800 rounded-xl">
            <div className="text-center">
              <Palette className="w-12 h-12 text-slate-700 mx-auto mb-3" />
              <p className="text-slate-500 text-sm">No assets generated yet</p>
              <p className="text-slate-600 text-xs mt-1">Configure and click Generate</p>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
