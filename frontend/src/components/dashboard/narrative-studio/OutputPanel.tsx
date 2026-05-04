'use client';

import { useState } from 'react';
import { BookOpen, Copy, ScrollText, Sparkles } from 'lucide-react';
import { toast } from 'sonner';

type OutputTab = 'prose' | 'storyboard' | 'vfx';

interface OutputPanelProps {
  prose?: string | null;
  headline?: string | null;
  storyboard?: unknown;
  vfxConfig?: unknown;
}

const TABS: Array<{ id: OutputTab; label: string; icon: typeof ScrollText }> = [
  { id: 'prose', label: 'Prose', icon: ScrollText },
  { id: 'storyboard', label: 'Storyboard', icon: BookOpen },
  { id: 'vfx', label: 'VFX Config', icon: Sparkles },
];

export default function OutputPanel({ prose, headline, storyboard, vfxConfig }: OutputPanelProps) {
  const [outputTab, setOutputTab] = useState<OutputTab>('prose');

  return (
    <div className="rounded-lg border border-border-subtle bg-bg-surface">
      <div className="border-b border-border-subtle">
        <div className="flex">
          {TABS.map(tab => (
            <button
              key={tab.id}
              onClick={() => setOutputTab(tab.id)}
              className={`flex items-center gap-1.5 border-b-2 px-4 py-3 text-xs font-semibold transition ${
                outputTab === tab.id
                  ? 'border-brand-info text-brand-info'
                  : 'border-transparent text-text-disabled hover:text-text-secondary'
              }`}
            >
              <tab.icon size={13} />
              {tab.label}
            </button>
          ))}
        </div>
      </div>

      <div className="p-4">
        {outputTab === 'prose' && (
          <ProseView prose={prose} headline={headline} />
        )}
        {outputTab === 'storyboard' && (
          <StoryboardView storyboard={storyboard} />
        )}
        {outputTab === 'vfx' && (
          <VfxView vfxConfig={vfxConfig} />
        )}
      </div>
    </div>
  );
}

function ProseView({ prose, headline }: { prose?: string | null; headline?: string | null }) {
  if (!prose) {
    return (
      <div className="flex flex-col items-center justify-center py-16 text-text-disabled">
        <ScrollText size={24} className="mb-2" />
        <p className="text-sm">No prose yet. Start a weave to generate output.</p>
      </div>
    );
  }
  return (
    <div className="space-y-3">
      {headline && <p className="font-semibold text-text-primary">{headline}</p>}
      <div className="relative rounded border border-border-muted bg-bg-elevated">
        <button
          onClick={() => { navigator.clipboard.writeText(prose); toast.success('Copied.'); }}
          className="absolute right-2 top-2 rounded p-1 text-text-disabled hover:text-text-secondary transition"
        >
          <Copy size={13} />
        </button>
        <p className="p-4 pr-10 font-serif text-sm leading-7 text-text-secondary whitespace-pre-wrap">
          {prose}
        </p>
      </div>
    </div>
  );
}

function StoryboardView({ storyboard }: { storyboard?: unknown }) {
  if (!storyboard) {
    return (
      <div className="flex flex-col items-center justify-center py-16 text-text-disabled">
        <BookOpen size={24} className="mb-2" />
        <p className="text-sm">No storyboard yet.</p>
      </div>
    );
  }
  return (
    <pre className="overflow-auto rounded border border-border-muted bg-bg-elevated p-4 text-xs text-text-secondary whitespace-pre-wrap">
      {typeof storyboard === 'string' ? storyboard : JSON.stringify(storyboard, null, 2)}
    </pre>
  );
}

function VfxView({ vfxConfig }: { vfxConfig?: unknown }) {
  if (!vfxConfig) {
    return (
      <div className="flex flex-col items-center justify-center py-16 text-text-disabled">
        <Sparkles size={24} className="mb-2" />
        <p className="text-sm">No VFX config yet.</p>
      </div>
    );
  }
  return (
    <pre className="overflow-auto rounded border border-border-muted bg-bg-elevated p-4 text-xs text-text-secondary whitespace-pre-wrap">
      {JSON.stringify(vfxConfig, null, 2)}
    </pre>
  );
}
