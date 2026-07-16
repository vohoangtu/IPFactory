'use client';

import { motion } from 'framer-motion';
import { Gem } from 'lucide-react';

import type { Artifact } from '@/shared/types/api';
import BadgeLabel from '@/components/ui/shared/BadgeLabel';
import EmptyState from '@/components/ui/shared/EmptyState';

interface ArtifactGridProps {
    artifacts: Artifact[];
    searchTerm: string;
}

const typeBadgeVariant: Record<string, 'cyan' | 'emerald' | 'rose' | 'amber' | 'violet' | 'indigo' | 'slate'> = {
    weapon: 'rose',
    relic: 'violet',
    tool: 'cyan',
    document: 'amber',
    symbol: 'indigo',
    material: 'emerald',
};

export default function ArtifactGrid({ artifacts, searchTerm }: ArtifactGridProps) {
    const filtered = artifacts.filter((a) => {
        if (!searchTerm) return true;
        const term = searchTerm.toLowerCase();
        return (
            a.name.toLowerCase().includes(term) ||
            a.description.toLowerCase().includes(term)
        );
    });

    if (filtered.length === 0) {
        return (
            <EmptyState
                icon={Gem}
                title="No artifacts found"
                message={
                    searchTerm
                        ? 'No artifacts match your search. Try a different keyword.'
                        : 'No artifacts have been discovered in this universe yet.'
                }
            />
        );
    }

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {filtered.map((artifact, index) => {
                const variant = typeBadgeVariant[artifact.type.toLowerCase()] ?? 'slate';

                return (
                    <motion.div
                        key={artifact.id}
                        initial={{ opacity: 0, y: 18 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: index * 0.04, duration: 0.35 }}
                        className="rounded-3xl border border-slate-800 bg-slate-950/40 p-6 transition hover:bg-white/[0.02] hover:border-slate-700"
                    >
                        <div className="flex items-center gap-3 mb-3 flex-wrap">
                            <h4 className="text-base font-black text-white tracking-tight">
                                {artifact.name}
                            </h4>
                            <BadgeLabel variant={variant}>
                                {artifact.type}
                            </BadgeLabel>
                        </div>

                        <p className="text-sm text-slate-400 leading-relaxed mb-4 line-clamp-3">
                            {artifact.description}
                        </p>

                        <div className="text-[11px] text-slate-500 font-mono">
                            Origin Tick {artifact.origin_tick}
                        </div>
                    </motion.div>
                );
            })}
        </div>
    );
}
