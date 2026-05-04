'use client';

import Link from 'next/link';
import { Copy, Download, Film } from 'lucide-react';
import { toast } from 'sonner';
import { useNarrativeRuntime } from '@/features/narrative-runtime/useNarrativeRuntime';
import IntermediateOutputsPanel from '@/components/ui/narrative/IntermediateOutputsPanel';

interface ReviewTabProps {
  runtime: ReturnType<typeof useNarrativeRuntime>;
}

export default function ReviewTab({ runtime }: ReviewTabProps) {
  const hasOutput = runtime.narrativeResult?.headline || runtime.narrativeResult?.prose;
  const hasIntermediate =
    runtime.intermediateOutputs.historical_outline ||
    runtime.intermediateOutputs.storyboard ||
    runtime.intermediateOutputs.final_prose;

  return (
    <div className="space-y-5">
      {hasOutput ? (
        <div className="rounded-lg border border-border-subtle bg-bg-surface">
          <div className="flex items-center justify-between border-b border-border-subtle px-5 py-4">
            <div>
              <h2 className="text-sm font-semibold text-text-primary">
                {runtime.narrativeResult?.headline ?? 'Final Output'}
              </h2>
              {runtime.narrativeResult?.newsSlogan && (
                <p className="mt-0.5 text-xs text-brand-emerald">{runtime.narrativeResult.newsSlogan}</p>
              )}
            </div>
            <div className="flex items-center gap-2">
              <button
                onClick={() => {
                  navigator.clipboard.writeText(runtime.narrativeResult?.prose ?? '');
                  toast.success('Copied.');
                }}
                className="flex items-center gap-1.5 rounded border border-border-muted bg-bg-elevated px-3 py-1.5 text-xs text-text-secondary hover:bg-slate-700"
              >
                <Copy size={13} />
                Copy
              </button>
              <button
                onClick={() => {
                  const blob = new Blob([runtime.narrativeResult?.prose ?? ''], { type: 'text/plain' });
                  const url = URL.createObjectURL(blob);
                  const a = document.createElement('a');
                  a.href = url;
                  a.download = `chronicle-${new Date().toISOString().slice(0, 10)}.txt`;
                  a.click();
                  URL.revokeObjectURL(url);
                  toast.success('Exported.');
                }}
                className="flex items-center gap-1.5 rounded border border-border-muted bg-bg-elevated px-3 py-1.5 text-xs text-text-secondary hover:bg-slate-700"
              >
                <Download size={13} />
                Export
              </button>
              {runtime.chronicleId && (
                <Link
                  href={`/narrative-cinema/${runtime.chronicleId}`}
                  className="flex items-center gap-1.5 rounded border border-amber-700/50 bg-amber-900/20 px-3 py-1.5 text-xs text-amber-300 hover:bg-amber-900/30"
                >
                  <Film size={13} />
                  Watch Cinematic
                </Link>
              )}
            </div>
          </div>
          {runtime.narrativeResult?.prose && (
            <div className="p-5">
              <p className="whitespace-pre-wrap font-serif text-sm leading-8 text-text-secondary">
                {runtime.narrativeResult.prose}
              </p>
            </div>
          )}
        </div>
      ) : (
        <div className="flex items-center justify-center rounded-lg border border-dashed border-border-subtle bg-bg-surface py-16 text-sm text-text-disabled">
          No output yet. Start a weave from the Run tab.
        </div>
      )}

      {hasIntermediate && (
        <div className="rounded-lg border border-border-subtle bg-bg-surface">
          <div className="border-b border-border-subtle px-5 py-3">
            <h3 className="text-sm font-semibold text-text-primary">Intermediate Outputs</h3>
          </div>
          <div className="p-5">
            <IntermediateOutputsPanel
              historicalOutline={runtime.intermediateOutputs.historical_outline}
              storyboard={runtime.intermediateOutputs.storyboard}
              finalProse={runtime.intermediateOutputs.final_prose}
            />
          </div>
        </div>
      )}
    </div>
  );
}
