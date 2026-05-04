'use client';

import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Brain, Zap, ScrollText, Sparkles, Loader2 } from 'lucide-react';

import { useActorDetail, useActorEvents, useActorDecisions, useMindMeld } from '@/features/actors/hooks';
import ModalShell from '@/components/ui/shared/ModalShell';
import BadgeLabel from '@/components/ui/shared/BadgeLabel';
import ProgressBar from '@/components/ui/shared/ProgressBar';
import SectionPanel from '@/components/ui/shared/SectionPanel';

interface ActorDetailModalProps {
  actorId: number | null;
  open: boolean;
  onClose: () => void;
}

type DetailTab = 'events' | 'decisions';

const traitColors: Array<'cyan' | 'emerald' | 'rose' | 'amber' | 'violet' | 'indigo'> = [
  'cyan', 'emerald', 'violet', 'amber', 'rose', 'indigo',
];

// ── Skeleton loaders ──

function SkeletonBlock({ className = '' }: { className?: string }) {
  return (
    <div className={`animate-pulse rounded-xl bg-slate-800/40 ${className}`} />
  );
}

function DetailSkeleton() {
  return (
    <div className="grid gap-6 md:grid-cols-2">
      <div className="space-y-4">
        <SkeletonBlock className="h-24 w-full" />
        <SkeletonBlock className="h-40 w-full" />
        <SkeletonBlock className="h-20 w-full" />
      </div>
      <div className="space-y-4">
        <SkeletonBlock className="h-10 w-48" />
        <SkeletonBlock className="h-64 w-full" />
      </div>
    </div>
  );
}

// ── Main component ──

export default function ActorDetailModal({ actorId, open, onClose }: ActorDetailModalProps) {
  const { actor, isLoading: loadingActor } = useActorDetail(open ? actorId : null);
  const { events, isLoading: loadingEvents } = useActorEvents(open ? actorId : null);
  const { decisions, isLoading: loadingDecisions } = useActorDecisions(open ? actorId : null);
  const mindMeld = useMindMeld();
  const [activeTab, setActiveTab] = useState<DetailTab>('events');
  const [mindMeldResult, setMindMeldResult] = useState<{ action: string; confidence: number } | null>(null);

  const isLoading = loadingActor || loadingEvents || loadingDecisions;

  const handleMindMeld = () => {
    if (!actorId) return;
    setMindMeldResult(null);
    mindMeld.mutate(actorId, {
      onSuccess: (res) => {
        setMindMeldResult(res);
      },
    });
  };

  const handleClose = () => {
    setMindMeldResult(null);
    setActiveTab('events');
    onClose();
  };

  const modalTitle = actor
    ? `${actor.name}`
    : 'Actor Details';

  return (
    <ModalShell open={open} onClose={handleClose} title={modalTitle} maxWidth="max-w-5xl">
      {isLoading || !actor ? (
        <DetailSkeleton />
      ) : (
        <div className="grid gap-6 md:grid-cols-2">
          {/* ── Left column ── */}
          <div className="space-y-5">
            {/* Header badges */}
            <div className="flex flex-wrap items-center gap-2">
              <BadgeLabel variant="cyan">{actor.role}</BadgeLabel>
              <BadgeLabel variant="violet">{actor.archetype}</BadgeLabel>
              <BadgeLabel variant={actor.is_alive ? 'emerald' : 'red'}>
                {actor.is_alive ? 'Alive' : `Dead (tick #${actor.death_tick})`}
              </BadgeLabel>
              <BadgeLabel variant="amber">{actor.life_stage}</BadgeLabel>
            </div>

            {/* Biography */}
            {actor.biography && (
              <SectionPanel>
                <h4 className="mb-2 flex items-center gap-2 text-sm font-bold text-slate-300">
                  <ScrollText size={14} className="text-cyan-400" />
                  Biography
                </h4>
                <p className="text-sm leading-relaxed text-slate-400">
                  {actor.biography}
                </p>
              </SectionPanel>
            )}

            {/* Traits */}
            {actor.traits && Object.keys(actor.traits).length > 0 && (
              <SectionPanel>
                <h4 className="mb-3 flex items-center gap-2 text-sm font-bold text-slate-300">
                  <Brain size={14} className="text-violet-400" />
                  Traits
                </h4>
                <div className="space-y-2.5">
                  {Object.entries(actor.traits).map(([name, value], idx) => (
                    <ProgressBar
                      key={name}
                      label={name}
                      value={value}
                      max={1}
                      color={traitColors[idx % traitColors.length]}
                      size="sm"
                    />
                  ))}
                </div>
              </SectionPanel>
            )}

            {/* Capabilities */}
            {actor.capabilities && actor.capabilities.length > 0 && (
              <SectionPanel>
                <h4 className="mb-3 flex items-center gap-2 text-sm font-bold text-slate-300">
                  <Sparkles size={14} className="text-amber-400" />
                  Capabilities
                </h4>
                <div className="flex flex-wrap gap-1.5">
                  {actor.capabilities.map((cap) => (
                    <BadgeLabel key={cap} variant="indigo">{cap}</BadgeLabel>
                  ))}
                </div>
              </SectionPanel>
            )}

            {/* Vitality */}
            {actor.vitality && Object.keys(actor.vitality).length > 0 && (
              <SectionPanel>
                <h4 className="mb-3 flex items-center gap-2 text-sm font-bold text-slate-300">
                  <Zap size={14} className="text-emerald-400" />
                  Vitality
                </h4>
                <div className="grid grid-cols-2 gap-3">
                  {Object.entries(actor.vitality).map(([key, val]) => (
                    <div key={key} className="rounded-xl border border-slate-800 bg-slate-900/40 px-3 py-2">
                      <span className="block text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        {key}
                      </span>
                      <span className="text-sm font-mono text-slate-200">
                        {typeof val === 'number' ? val.toFixed(2) : String(val)}
                      </span>
                    </div>
                  ))}
                </div>
              </SectionPanel>
            )}
          </div>

          {/* ── Right column ── */}
          <div className="space-y-4">
            {/* Sub-tabs */}
            <div className="flex gap-1 rounded-xl border border-slate-800 bg-slate-950/60 p-1">
              {(['events', 'decisions'] as const).map((tab) => (
                <button
                  key={tab}
                  onClick={() => setActiveTab(tab)}
                  className={`flex-1 rounded-lg px-4 py-2 text-xs font-bold uppercase tracking-wider transition-colors ${
                    activeTab === tab
                      ? 'bg-cyan-500/10 text-cyan-300'
                      : 'text-slate-500 hover:text-slate-300'
                  }`}
                >
                  {tab}
                </button>
              ))}
            </div>

            {/* Tab content */}
            <div className="max-h-[50vh] space-y-3 overflow-y-auto pr-1 custom-scrollbar">
              <AnimatePresence mode="wait">
                {activeTab === 'events' && (
                  <motion.div
                    key="events"
                    initial={{ opacity: 0, x: -10 }}
                    animate={{ opacity: 1, x: 0 }}
                    exit={{ opacity: 0, x: 10 }}
                    transition={{ duration: 0.2 }}
                    className="space-y-2.5"
                  >
                    {events.length === 0 ? (
                      <p className="py-8 text-center text-sm text-slate-600">No events recorded.</p>
                    ) : (
                      events.map((evt) => (
                        <div
                          key={evt.id}
                          className="rounded-xl border border-slate-800 bg-slate-950/40 p-4"
                        >
                          <div className="mb-1.5 flex items-center gap-2">
                            <span className="font-mono text-[10px] text-slate-500">
                              Tick #{evt.tick}
                            </span>
                            <BadgeLabel variant="amber">{evt.type}</BadgeLabel>
                          </div>
                          <p className="text-sm leading-relaxed text-slate-300">
                            {evt.summary}
                          </p>
                        </div>
                      ))
                    )}
                  </motion.div>
                )}

                {activeTab === 'decisions' && (
                  <motion.div
                    key="decisions"
                    initial={{ opacity: 0, x: -10 }}
                    animate={{ opacity: 1, x: 0 }}
                    exit={{ opacity: 0, x: 10 }}
                    transition={{ duration: 0.2 }}
                    className="space-y-2.5"
                  >
                    {decisions.length === 0 ? (
                      <p className="py-8 text-center text-sm text-slate-600">No decisions recorded.</p>
                    ) : (
                      decisions.map((dec) => (
                        <div
                          key={dec.id}
                          className="rounded-xl border border-slate-800 bg-slate-950/40 p-4"
                        >
                          <div className="mb-1.5 flex items-center gap-2">
                            <span className="font-mono text-[10px] text-slate-500">
                              Tick #{dec.tick}
                            </span>
                            <BadgeLabel variant="cyan">{dec.action_type}</BadgeLabel>
                          </div>
                          <p className="mb-2 text-sm leading-relaxed text-slate-300">
                            {dec.summary}
                          </p>
                          <div className="grid grid-cols-2 gap-3">
                            <ProgressBar
                              label="Utility"
                              value={dec.utility_score}
                              max={1}
                              color="emerald"
                              size="sm"
                            />
                            <div>
                              <span className="block text-xs text-slate-400">Confidence</span>
                              <span className="text-sm font-mono text-slate-200">
                                {(dec.confidence * 100).toFixed(1)}%
                              </span>
                            </div>
                          </div>
                        </div>
                      ))
                    )}
                  </motion.div>
                )}
              </AnimatePresence>
            </div>

            {/* Mind Meld */}
            <div className="mt-4 border-t border-slate-800 pt-4">
              <button
                onClick={handleMindMeld}
                disabled={mindMeld.isPending}
                className="flex w-full items-center justify-center gap-2 rounded-2xl border border-violet-500/30 bg-violet-500/10 px-6 py-3 text-sm font-bold text-violet-300 transition-all hover:bg-violet-500/20 hover:border-violet-500/50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {mindMeld.isPending ? (
                  <Loader2 size={16} className="animate-spin" />
                ) : (
                  <Brain size={16} />
                )}
                Mind Meld
              </button>

              {/* Mind Meld result */}
              <AnimatePresence>
                {mindMeldResult && (
                  <motion.div
                    initial={{ opacity: 0, y: 8 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: 8 }}
                    className="mt-3 rounded-xl border border-violet-500/20 bg-violet-500/5 p-4"
                  >
                    <p className="mb-2 text-sm text-slate-300">
                      <span className="font-bold text-violet-300">Predicted action: </span>
                      {mindMeldResult.action}
                    </p>
                    <div className="flex items-center gap-2">
                      <BadgeLabel variant={mindMeldResult.confidence >= 0.7 ? 'emerald' : mindMeldResult.confidence >= 0.4 ? 'amber' : 'rose'}>
                        {(mindMeldResult.confidence * 100).toFixed(1)}% confidence
                      </BadgeLabel>
                    </div>
                  </motion.div>
                )}
              </AnimatePresence>

              {/* Mind Meld error */}
              {mindMeld.isError && (
                <p className="mt-2 text-xs text-red-400">
                  Failed to perform mind meld. Please try again.
                </p>
              )}
            </div>
          </div>
        </div>
      )}
    </ModalShell>
  );
}
