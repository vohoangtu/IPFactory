'use client';

import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { ChevronDown, Clock, Film } from 'lucide-react';
import Link from 'next/link';

import type { Chronicle } from '@/shared/types/api';
import BadgeLabel from '@/components/ui/shared/BadgeLabel';
import EmptyState from '@/components/ui/shared/EmptyState';

interface ChronicleListProps {
    chronicles: Chronicle[];
    searchTerm: string;
}

const typeBadgeVariant: Record<string, 'cyan' | 'emerald' | 'rose' | 'amber' | 'violet' | 'indigo' | 'slate'> = {
    narrative: 'cyan',
    event: 'amber',
    conflict: 'rose',
    discovery: 'emerald',
    political: 'violet',
    cultural: 'indigo',
};

function importanceDotColor(importance: number): string {
    if (importance >= 8) return 'bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.6)]';
    if (importance >= 5) return 'bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.5)]';
    if (importance >= 3) return 'bg-cyan-500 shadow-[0_0_8px_rgba(6,182,212,0.5)]';
    return 'bg-slate-500';
}

export default function ChronicleList({ chronicles, searchTerm }: ChronicleListProps) {
    const [expandedId, setExpandedId] = useState<number | null>(null);

    const filtered = chronicles.filter((c) => {
        if (!searchTerm) return true;
        const term = searchTerm.toLowerCase();
        return (
            c.title.toLowerCase().includes(term) ||
            c.summary.toLowerCase().includes(term)
        );
    });

    if (filtered.length === 0) {
        return (
            <EmptyState
                title="No chronicles found"
                message={
                    searchTerm
                        ? 'No chronicles match your search. Try a different keyword.'
                        : 'No chronicles have been recorded in this universe yet.'
                }
            />
        );
    }

    return (
        <div className="space-y-3">
            {filtered.map((chronicle, index) => {
                const isExpanded = expandedId === chronicle.id;
                const variant = typeBadgeVariant[chronicle.type.toLowerCase()] ?? 'slate';

                return (
                    <motion.div
                        key={chronicle.id}
                        initial={{ opacity: 0, y: 18 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: index * 0.04, duration: 0.35 }}
                        className="rounded-3xl border border-slate-800 bg-slate-950/40 overflow-hidden"
                    >
                        <button
                            onClick={() => setExpandedId(isExpanded ? null : chronicle.id)}
                            className="w-full text-left p-6 flex items-start gap-4 group transition hover:bg-white/[0.02]"
                        >
                            <div
                                className={`mt-2 h-2.5 w-2.5 rounded-full flex-shrink-0 ${importanceDotColor(chronicle.importance)}`}
                                title={`Importance: ${chronicle.importance}`}
                            />

                            <div className="flex-1 min-w-0">
                                <div className="flex items-center gap-3 mb-2 flex-wrap">
                                    <h4 className="text-base font-black text-white tracking-tight">
                                        {chronicle.title}
                                    </h4>
                                    <BadgeLabel variant={variant}>
                                        {chronicle.type}
                                    </BadgeLabel>
                                </div>

                                <div className="flex items-center gap-2 text-[11px] text-slate-500 mb-2">
                                    <Clock size={12} />
                                    <span className="font-mono">
                                        Tick {chronicle.from_tick} &mdash; {chronicle.to_tick}
                                    </span>
                                </div>

                                <p className="text-sm text-slate-400 leading-relaxed line-clamp-2">
                                    {chronicle.summary}
                                </p>
                            </div>

                            <div className="flex items-center gap-1 flex-shrink-0">
                                {chronicle.has_animation && (
                                    <Link
                                        href={`/narrative-cinema/${chronicle.id}`}
                                        onClick={(e) => e.stopPropagation()}
                                        className="mt-1 p-1.5 rounded-lg text-cyan-500/60 transition hover:bg-cyan-500/10 hover:text-cyan-400"
                                        title="Watch cinematic"
                                    >
                                        <Film size={15} />
                                    </Link>
                                )}
                                <ChevronDown
                                    size={16}
                                    className={`mt-1 text-slate-600 transition-transform duration-300 ${
                                        isExpanded ? 'rotate-180' : ''
                                    }`}
                                />
                            </div>
                        </button>

                        <AnimatePresence initial={false}>
                            {isExpanded && (
                                <motion.div
                                    initial={{ height: 0, opacity: 0 }}
                                    animate={{ height: 'auto', opacity: 1 }}
                                    exit={{ height: 0, opacity: 0 }}
                                    transition={{ duration: 0.3, ease: 'easeInOut' }}
                                    className="overflow-hidden"
                                >
                                    <div className="px-6 pb-6 pt-0 border-t border-slate-800/50">
                                        <div className="mt-4 p-5 rounded-xl bg-slate-950/60 border border-slate-800/50">
                                            <div className="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-3">
                                                Full Chronicle
                                            </div>
                                            <p className="text-sm text-slate-300 leading-relaxed whitespace-pre-wrap">
                                                {chronicle.content}
                                            </p>
                                        </div>
                                    </div>
                                </motion.div>
                            )}
                        </AnimatePresence>
                    </motion.div>
                );
            })}
        </div>
    );
}
