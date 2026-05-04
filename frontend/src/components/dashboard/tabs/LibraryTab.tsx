'use client';

import { useState } from 'react';
import { motion } from 'framer-motion';
import { BookOpen, Flame, Gem, Library, Loader2 } from 'lucide-react';

import { useUniverse } from '@/contexts/UniverseContext';
import { useChronicles, useMythScars, useArtifacts } from '@/hooks/useChronicles';
import FilterToolbar from '@/components/ui/shared/FilterToolbar';
import EmptyState from '@/components/ui/shared/EmptyState';
import ChronicleList from '@/components/dashboard/tabs/library/ChronicleList';
import MythScarList from '@/components/dashboard/tabs/library/MythScarList';
import ArtifactGrid from '@/components/dashboard/tabs/library/ArtifactGrid';

type SubTab = 'chronicles' | 'myth-scars' | 'artifacts';

const subTabs: { key: SubTab; label: string; icon: React.ComponentType<{ size?: number; className?: string }> }[] = [
    { key: 'chronicles', label: 'Chronicles', icon: BookOpen },
    { key: 'myth-scars', label: 'Myth Scars', icon: Flame },
    { key: 'artifacts', label: 'Artifacts', icon: Gem },
];

export default function LibraryTab() {
  const { activeUniverseId: universeId } = useUniverse();
    const [activeSubTab, setActiveSubTab] = useState<SubTab>('chronicles');
    const [searchTerm, setSearchTerm] = useState('');

    const {
        chronicles,
        isLoading: chroniclesLoading,
    } = useChronicles(universeId);

    const {
        mythScars,
        isLoading: mythScarsLoading,
    } = useMythScars(universeId);

    const {
        artifacts,
        isLoading: artifactsLoading,
    } = useArtifacts(universeId);

    if (!universeId) {
        return (
            <EmptyState
                icon={Library}
                title="No Universe Selected"
                message="Select a universe from the sidebar to explore its library."
            />
        );
    }

    const isLoading =
        (activeSubTab === 'chronicles' && chroniclesLoading) ||
        (activeSubTab === 'myth-scars' && mythScarsLoading) ||
        (activeSubTab === 'artifacts' && artifactsLoading);

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex items-center gap-3 mb-2">
                <Library size={24} className="text-cyan-400" />
                <h2 className="text-2xl font-black text-white tracking-tight">
                    Library of Alexandria
                </h2>
            </div>

            {/* Sub-tab navigation */}
            <div className="flex items-center gap-1 rounded-2xl border border-slate-800 bg-slate-950/40 p-1.5 w-fit">
                {subTabs.map((tab) => {
                    const Icon = tab.icon;
                    const isActive = activeSubTab === tab.key;

                    return (
                        <button
                            key={tab.key}
                            onClick={() => {
                                setActiveSubTab(tab.key);
                                setSearchTerm('');
                            }}
                            className={`relative flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 ${
                                isActive
                                    ? 'text-cyan-400'
                                    : 'text-slate-500 hover:text-slate-300'
                            }`}
                        >
                            {isActive && (
                                <motion.div
                                    layoutId="library-subtab"
                                    className="absolute inset-0 rounded-xl bg-cyan-500/10 border border-cyan-500/20"
                                    transition={{ type: 'spring', stiffness: 400, damping: 30 }}
                                />
                            )}
                            <span className="relative flex items-center gap-2">
                                <Icon size={16} />
                                {tab.label}
                            </span>
                        </button>
                    );
                })}
            </div>

            {/* Search toolbar */}
            <FilterToolbar
                searchValue={searchTerm}
                onSearchChange={setSearchTerm}
                searchPlaceholder={
                    activeSubTab === 'chronicles'
                        ? 'Search chronicles by title or summary...'
                        : activeSubTab === 'myth-scars'
                          ? 'Search myth scars by title, name, or description...'
                          : 'Search artifacts by name or description...'
                }
            />

            {/* Content */}
            {isLoading ? (
                <div className="flex items-center justify-center py-20">
                    <Loader2 size={32} className="animate-spin text-cyan-500/60" />
                </div>
            ) : (
                <motion.div
                    key={activeSubTab}
                    initial={{ opacity: 0, y: 12 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3 }}
                >
                    {activeSubTab === 'chronicles' && (
                        <ChronicleList
                            chronicles={chronicles}
                            searchTerm={searchTerm}
                        />
                    )}
                    {activeSubTab === 'myth-scars' && (
                        <MythScarList
                            mythScars={mythScars}
                            searchTerm={searchTerm}
                        />
                    )}
                    {activeSubTab === 'artifacts' && (
                        <ArtifactGrid
                            artifacts={artifacts}
                            searchTerm={searchTerm}
                        />
                    )}
                </motion.div>
            )}
        </div>
    );
}
