'use client';

import { Activity, Cpu, Zap } from 'lucide-react';
import type { AiStats } from '@/types/api';

interface StatsBentoProps {
  stats: AiStats | undefined;
  usePool: boolean | null;
}

export default function StatsBento({ stats, usePool }: StatsBentoProps) {
  const avgLatency = stats?.avg_latency ?? 0;
  const successRate = stats?.success_rate ?? 0;
  const totalRequests = stats?.total_requests ?? 0;
  const routedProviders = stats?.providers?.map((p) => p.name.toUpperCase()) ?? [];
  const providersCount = routedProviders.length;
  const primaryModel = stats?.models?.[0]?.name ?? 'idle';

  return (
    <div className="grid grid-cols-1 gap-4 md:grid-cols-4 mb-8">
      <div className="group rounded-3xl border border-slate-800/50 bg-slate-900/20 p-6 transition-all hover:border-cyan-500/30">
        <div className="mb-3 flex items-center gap-4">
          <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-500/10 text-cyan-400 transition-transform group-hover:scale-110">
            <Cpu size={20} />
          </div>
          <span className="text-[10px] font-black uppercase tracking-widest text-slate-500">Routing Mode</span>
        </div>
        <div className="text-3xl font-black tracking-tight text-white">
          {usePool === null ? 'Syncing' : usePool ? 'AI Pool' : 'Direct'}
        </div>
        <p className="mt-3 text-[10px] font-bold uppercase leading-relaxed tracking-widest text-slate-500">
          Providers: {routedProviders.length > 0 ? routedProviders.join(' / ') : 'None'}
          <br />
          Primary model: {primaryModel}
        </p>
      </div>

      <div className="rounded-3xl border border-slate-800/50 bg-slate-900/20 p-6">
        <div className="mb-3 flex items-center gap-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
          <Zap size={18} className="text-amber-400" />
          Avg Latency
        </div>
        <div className="text-4xl font-black tracking-tight text-white">
          {avgLatency}
          <span className="ml-1 text-lg text-slate-500">ms</span>
        </div>
      </div>

      <div className="rounded-3xl border border-slate-800/50 bg-slate-900/20 p-6">
        <div className="mb-3 flex items-center gap-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
          <Activity size={18} className="text-emerald-400" />
          Success Rate
        </div>
        <div className="text-4xl font-black tracking-tight text-white">
          {successRate}
          <span className="ml-1 text-lg text-slate-500">%</span>
        </div>
      </div>

      <div className="flex flex-col justify-between rounded-3xl border border-cyan-500/10 bg-cyan-500/5 p-6">
        <div>
          <p className="mb-2 flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-cyan-400">
            <Activity size={12} /> Volume Metrics
          </p>
          <div className="mt-6 space-y-4">
            <div className="flex items-center justify-between">
              <span className="text-[10px] font-bold uppercase tracking-widest text-slate-500">Total Requests</span>
              <span className="text-xl font-black text-white">{totalRequests.toLocaleString()}</span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-[10px] font-bold uppercase tracking-widest text-slate-500">Total Providers</span>
              <span className="text-xl font-black text-white">{providersCount}</span>
            </div>
          </div>
        </div>
        <div className="mt-4 border-t border-cyan-500/10 pt-4">
          <p className="text-[9px] font-medium italic text-slate-400">Live flow of civilizational decisions</p>
        </div>
      </div>
    </div>
  );
}
