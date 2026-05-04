'use client';

import React, { useState } from 'react';
import { motion } from 'framer-motion';
import { Activity, RefreshCcw, Trash2 } from 'lucide-react';
import { toast } from 'sonner';
import { useAiLogs, useAiStats, useAiPool } from '@/features/intelligence/hooks';
import type { AiLog } from '@/types/api';
import LogDetailModal from '@/components/ui/intelligence/LogDetailModal';
import StatsBento from '@/components/dashboard/intelligence/StatsBento';
import LogFilters from '@/components/dashboard/intelligence/LogFilters';
import LogTable from '@/components/dashboard/intelligence/LogTable';
import SynthesisTicker from '@/components/dashboard/intelligence/SynthesisTicker';

export default function NarrativeLoomMonitor() {
  const [page, setPage] = useState(1);
  const [filterStatus, setFilterStatus] = useState<string>('');
  const [filterDriver, setFilterDriver] = useState<string>('');
  const [search, setSearch] = useState('');
  const [selectedLog, setSelectedLog] = useState<AiLog | null>(null);

  const {
    logs,
    pagination,
    isLoading,
    clearLogs,
    mutate: mutateLogs,
  } = useAiLogs({
    page,
    status: filterStatus,
    driver: filterDriver,
    search,
    limit: 15,
  });

  const { stats: aiStats, isLoading: isStatsLoading, mutate: mutateStats } = useAiStats();
  const { usePool } = useAiPool();

  const handleRefresh = () => {
    mutateLogs();
    mutateStats();
  };

  const handleClear = async () => {
    if (confirm('Delete all diagnostic intelligence logs? This action cannot be undone.')) {
      try {
        await clearLogs();
        toast.success('Intelligence logs purged.');
      } catch {}
    }
  };

  return (
    <div className="mx-auto max-w-7xl pb-20">
      <style jsx global>{`
        @keyframes marquee {
          0% {
            transform: translateX(0);
          }
          100% {
            transform: translateX(-50%);
          }
        }
        .animate-marquee {
          animation: marquee 30s linear infinite;
        }
        .animate-marquee:hover {
          animation-play-state: paused;
        }
      `}</style>

      {/* Header Section */}
      <div className="mb-12 flex flex-col justify-between gap-6 md:flex-row md:items-end">
        <div>
          <motion.div
            initial={{ opacity: 0, x: -20 }}
            animate={{ opacity: 1, x: 0 }}
            className="mb-2 flex items-center gap-3 text-cyan-400"
          >
            <Activity size={18} />
            <span className="text-[10px] font-black uppercase tracking-[0.3em]">Surveillance / Intelligence</span>
          </motion.div>
          <h1 className="text-5xl font-black italic tracking-tighter text-white">Narrative Loom Monitor</h1>
          <p className="mt-2 max-w-xl font-medium leading-relaxed text-slate-500">
            Real-time diagnostic stream of all civilizational logic requests processed by the Loom&apos;s
            heterogeneous AI nodes.
          </p>
        </div>

        <div className="flex items-center gap-3">
          <button
            onClick={handleRefresh}
            className="group rounded-2xl border border-slate-700/50 bg-slate-800/50 p-3.5 text-slate-400 transition-all hover:bg-slate-800 hover:text-white"
            title="Force Synchronize"
          >
            <RefreshCcw
              size={20}
              className={
                isLoading || isStatsLoading
                  ? 'animate-spin'
                  : 'transition-transform duration-500 group-active:rotate-180'
              }
            />
          </button>
          <button
            onClick={handleClear}
            className="flex items-center gap-2 rounded-2xl border border-rose-500/20 bg-rose-500/10 px-6 py-3.5 font-bold text-rose-400 transition-all hover:bg-rose-500 hover:text-white"
          >
            <Trash2 size={18} />
            Purge Memory
          </button>
        </div>
      </div>

      {/* Quick Stats Bento */}
      <StatsBento stats={aiStats} usePool={usePool ?? null} />

      {/* Synthesis Discovery Ticker (Multiverse-wide) */}
      <SynthesisTicker />

      {/* Controls & Table */}
      <div className="overflow-hidden rounded-3xl border border-slate-800/50 bg-[#0a0a0c]/80 backdrop-blur-md">
        <LogFilters
          search={search}
          onSearchChange={(v) => {
            setSearch(v);
            setPage(1);
          }}
          driver={filterDriver}
          onDriverChange={(v) => {
            setFilterDriver(v);
            setPage(1);
          }}
          status={filterStatus}
          onStatusChange={(v) => {
            setFilterStatus(v);
            setPage(1);
          }}
        />
        <LogTable
          logs={logs}
          pagination={pagination}
          isLoading={isLoading}
          page={page}
          onPageChange={setPage}
          onInspect={setSelectedLog}
        />
      </div>

      {/* Inspect Modal */}
      <LogDetailModal log={selectedLog} open={!!selectedLog} onClose={() => setSelectedLog(null)} />
    </div>
  );
}
