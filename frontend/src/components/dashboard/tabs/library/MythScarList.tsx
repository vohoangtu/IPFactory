'use client';

import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { ChevronDown, AlertTriangle } from 'lucide-react';

import type { MythScar } from '@/shared/types/api';
import BadgeLabel from '@/components/ui/shared/BadgeLabel';
import EmptyState from '@/components/ui/shared/EmptyState';

interface MythScarListProps {
    mythScars: MythScar[];
    searchTerm: string;
}

const severityBadgeVariant: Record<string, 'slate' | 'amber' | 'rose' | 'red'> = {
    low: 'slate',
    medium: 'amber',
    high: 'rose',
    critical: 'red',
};

const severityBarColor: Record<string, string> = {
    low: 'bg-slate-500',
    medium: 'bg-amber-500',
    high: 'bg-rose-500',
    critical: 'bg-red-500',
};

export default function MythScarList({ mythScars, searchTerm }: MythScarListProps) {
    const [expandedId, setExpandedId] = useState<number | null>(null);

    const filtered = mythScars.filter((ms) => {
        if (!searchTerm) return true;
        const term = searchTerm.toLowerCase();
        return (
            ms.title.toLowerCase().includes(term) ||
            ms.name.toLowerCase().includes(term) ||
            ms.description.toLowerCase().includes(term)
        );
    });

    if (filtered.length === 0) {
        return (
            <EmptyState
                icon={AlertTriangle}
                title="No myth scars found"
                message={
                    searchTerm
                        ? 'No myth scars match your search. Try a different keyword.'
                        : 'No myth scars have manifested in this universe yet.'
                }
            />
        );
    }

    return (
        <div className="space-y-3">
            {filtered.map((scar, index) => {
                const isExpanded = expandedId === scar.id;
                const severity = scar.severity.toLowerCase();
                const badgeVariant = severityBadgeVariant[severity] ?? 'slate';
                const barColor = severityBarColor[severity] ?? 'bg-slate-500';
                const barWidth = Math.min(Math.max(scar.severity_score * 10, 5), 100);

                return (
                    <motion.div
                        key={scar.id}
                        initial={{ opacity: 0, y: 18 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: index * 0.04, duration: 0.35 }}
                        className="rounded-3xl border border-slate-800 bg-slate-950/40 overflow-hidden"
                    >
                        <button
                            onClick={() => setExpandedId(isExpanded ? null : scar.id)}
                            className="w-full text-left p-6 group transition hover:bg-white/[0.02]"
                        >
                            <div className="flex items-start justify-between gap-4">
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-3 mb-2 flex-wrap">
                                        <h4 className="text-base font-black text-white tracking-tight">
                                            {scar.title}
                                        </h4>
                                        <BadgeLabel variant={badgeVariant}>
                                            {scar.severity}
                                        </BadgeLabel>
                                    </div>

                                    <div className="text-[11px] text-slate-500 font-mono mb-3">
                                        Origin Tick {scar.origin_tick}
                                        {scar.resolved_at_tick !== null && (
                                            <span className="ml-3 text-emerald-500">
                                                Resolved at Tick {scar.resolved_at_tick}
                                            </span>
                                        )}
                                    </div>

                                    <p className="text-sm text-slate-400 leading-relaxed line-clamp-2">
                                        {scar.consequence}
                                    </p>
                                </div>

                                <ChevronDown
                                    size={16}
                                    className={`mt-1 text-slate-600 transition-transform duration-300 flex-shrink-0 ${
                                        isExpanded ? 'rotate-180' : ''
                                    }`}
                                />
                            </div>

                            {/* Severity score bar */}
                            <div className="mt-4">
                                <div className="flex items-center justify-between mb-1">
                                    <span className="text-[9px] font-black text-slate-600 uppercase tracking-widest">
                                        Severity Score
                                    </span>
                                    <span className="text-[10px] font-mono text-slate-500">
                                        {(scar.severity_score ?? 0).toFixed(1)}
                                    </span>
                                </div>
                                <div className="h-1 w-full rounded-full bg-slate-800 overflow-hidden">
                                    <div
                                        className={`h-full rounded-full transition-all duration-500 ${barColor}`}
                                        style={{ width: `${barWidth}%` }}
                                    />
                                </div>
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
                                                Full Description
                                            </div>
                                            <p className="text-sm text-slate-300 leading-relaxed whitespace-pre-wrap">
                                                {scar.description}
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
