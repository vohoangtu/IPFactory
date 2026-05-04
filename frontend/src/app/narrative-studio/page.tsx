'use client';

import { useUniverse } from '@/contexts/UniverseContext';
import { useNarrativeRuntime } from '@/features/narrative-runtime/useNarrativeRuntime';
import PageHeader from '@/components/ui/shared/PageHeader';
import WeaveParametersPanel from '@/components/dashboard/narrative-studio/WeaveParametersPanel';
import PipelineProgressPanel from '@/components/dashboard/narrative-studio/PipelineProgressPanel';
import OutputPanel from '@/components/dashboard/narrative-studio/OutputPanel';

export default function NarrativeStudioPage() {
  const { activeUniverseId } = useUniverse();
  const runtime = useNarrativeRuntime();

  return (
    <div className="space-y-6">
      <PageHeader
        title="Narrative Studio"
        subtitle="Submit world events to the pipeline and review generated chronicles."
        action={
          <div className="flex items-center gap-2 text-sm text-text-muted">
            Universe: <span className="font-medium text-text-primary">{activeUniverseId ?? '—'}</span>
          </div>
        }
      />

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-[300px_1fr_360px]">
        <WeaveParametersPanel
          activeUniverseId={activeUniverseId}
          isSubmitting={runtime.isSubmitting}
          isWeaving={runtime.isWeaving}
          lastError={runtime.lastError}
          onSubmit={() => void runtime.startWeave()}
        />

        <PipelineProgressPanel
          pipelineNodes={runtime.pipelineNodes}
          currentAgent={runtime.currentAgent}
          progressPct={runtime.progress.pct}
          connectionState={runtime.connectionState}
        />

        <OutputPanel
          prose={runtime.narrativeResult?.prose ?? runtime.intermediateOutputs.final_prose}
          headline={runtime.narrativeResult?.headline}
          storyboard={runtime.intermediateOutputs.storyboard}
          vfxConfig={runtime.intermediateOutputs.vfx_config}
        />
      </div>
    </div>
  );
}
