'use client';

import { User, Gem, Copy, Download, Headphones } from 'lucide-react';
import { toast } from 'sonner';
import type {
  AssetTabId,
  AssetForgeResult,
  CelebrityAssetResult,
  ArtifactAssetResult,
  VisualAssetResult,
  AudioAssetResult,
} from './types';

interface AssetForgeResultProps {
  activeTab: AssetTabId;
  result: AssetForgeResult;
}

export function AssetForgeResult({ activeTab, result }: AssetForgeResultProps) {
  switch (activeTab) {
    case 'celebrity': {
      const celebrityResult = result as CelebrityAssetResult;
      return (
        <div className="space-y-4">
          <div className="p-4 bg-violet-500/10 border border-violet-500/20 rounded-lg">
            <p className="text-[10px] uppercase text-violet-400 mb-1">Name</p>
            <p className="text-xl font-bold text-white">{celebrityResult.name}</p>
          </div>
          <div className="p-4 bg-slate-800 rounded-lg">
            <p className="text-[10px] uppercase text-slate-500 mb-2 flex items-center gap-1">
              <User className="w-3 h-3" />
              Biography
            </p>
            <p className="text-sm text-slate-300 leading-relaxed">{celebrityResult.biography}</p>
          </div>
        </div>
      );
    }

    case 'artifact': {
      const artifactResult = result as ArtifactAssetResult;
      return (
        <div className="space-y-4">
          <div className="p-4 bg-amber-500/10 border border-amber-500/20 rounded-lg">
            <p className="text-[10px] uppercase text-amber-400 mb-1">Artifact Name</p>
            <p className="text-xl font-bold text-white">{artifactResult.name}</p>
          </div>
          <div className="p-4 bg-slate-800 rounded-lg">
            <p className="text-[10px] uppercase text-slate-500 mb-2 flex items-center gap-1">
              <Gem className="w-3 h-3" />
              Lore
            </p>
            <p className="text-sm text-slate-300 leading-relaxed">{artifactResult.lore}</p>
          </div>
        </div>
      );
    }

    case 'visual': {
      const visualResult = result as VisualAssetResult;
      return (
        <div className="space-y-4">
          {visualResult.image_url ? (
            <div className="space-y-3">
              <div className="relative aspect-square rounded-xl overflow-hidden border border-white/10">
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img
                  src={visualResult.image_url}
                  alt="Generated"
                  className="w-full h-full object-cover"
                />
              </div>
              <div className="flex gap-2">
                <a
                  href={visualResult.image_url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-lg text-sm text-slate-300 transition-colors"
                >
                  <Download className="w-4 h-4" />
                  View Full Size
                </a>
                <button
                  onClick={() => {
                    navigator.clipboard.writeText(visualResult.image_url || '');
                    toast.success('URL copied');
                  }}
                  className="px-4 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-lg text-slate-300 transition-colors"
                >
                  <Copy className="w-4 h-4" />
                </button>
              </div>
            </div>
          ) : (
            <div className="p-4 bg-rose-500/10 border border-rose-500/20 rounded-lg">
              <p className="text-sm text-rose-400">Image generation failed</p>
            </div>
          )}
        </div>
      );
    }

    case 'audio': {
      const audioResult = result as AudioAssetResult;
      return (
        <div className="space-y-4">
          <div className="p-4 bg-cyan-500/10 border border-cyan-500/20 rounded-lg">
            <p className="text-[10px] uppercase text-cyan-400 mb-1">Track</p>
            <p className="text-lg font-bold text-white">{audioResult.epoch_name}</p>
            <p className="text-sm text-slate-400 mt-1">Style: {audioResult.style}</p>
          </div>
          {audioResult.stream_url && (
            <div className="p-4 bg-slate-800 rounded-lg">
              <p className="text-[10px] uppercase text-slate-500 mb-3">Preview</p>
              <audio controls className="w-full">
                <source src={audioResult.stream_url} type="audio/mpeg" />
              </audio>
              <a
                href={audioResult.stream_url}
                target="_blank"
                rel="noopener noreferrer"
                className="mt-3 flex items-center gap-2 text-sm text-cyan-400 hover:text-cyan-300 transition-colors"
              >
                <Headphones className="w-4 h-4" />
                Open in new tab
              </a>
            </div>
          )}
        </div>
      );
    }

    default:
      return null;
  }
}
