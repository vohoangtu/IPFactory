'use client';

import { Activity, Clock, ExternalLink } from 'lucide-react';
import { LogStatusBadge } from '@/components/ui/intelligence/LogStatusBadge';
import { resolveLogModel } from '@/lib/log-utils';
import type { AiLog, PaginatedAiLogs } from '@/shared/types/api';

interface LogTableProps {
  logs: AiLog[];
  pagination: Pick<PaginatedAiLogs, 'current_page' | 'last_page' | 'total'> | null;
  isLoading: boolean;
  page: number;
  onPageChange: (p: number) => void;
  onInspect: (log: AiLog) => void;
}

export default function LogTable({ logs, pagination, isLoading, page, onPageChange, onInspect }: LogTableProps) {
  return (
    <>
      <div className="relative overflow-x-auto">
        {isLoading && (
          <div className="absolute inset-0 z-10 flex items-center justify-center bg-black/20 backdrop-blur-[2px]">
            <div className="flex items-center gap-3">
              <div className="h-4 w-4 animate-spin rounded-full border-2 border-cyan-500/20 border-t-cyan-500" />
              <span className="text-[10px] font-black uppercase tracking-widest text-cyan-500">Scanning Grid...</span>
            </div>
          </div>
        )}

        <table className="w-full border-collapse">
          <thead>
            <tr className="border-b border-slate-800/50 bg-white/5">
              <th className="p-6 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Transaction</th>
              <th className="p-6 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Service</th>
              <th className="p-6 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Driver / Model</th>
              <th className="p-6 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Latency</th>
              <th className="p-6 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Status</th>
              <th className="p-6 text-right text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-800/30">
            {logs.map((log) => (
              <tr key={log.id} className="group transition-colors hover:bg-white/[0.02]">
                <td className="p-6">
                  <div className="flex items-center gap-3">
                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-800 text-[10px] font-black text-slate-400 transition-colors group-hover:bg-cyan-500/10 group-hover:text-cyan-400">
                      {log.id}
                    </div>
                    <div className="flex flex-col">
                      <span className="text-xs font-black text-white transition-transform group-hover:translate-x-1">
                        TRANS_{log.id}
                      </span>
                      <div className="mt-1 flex items-center gap-1.5 text-[10px] text-slate-500">
                        <Clock size={10} />
                        {new Date(log.created_at).toLocaleTimeString()}
                      </div>
                    </div>
                  </div>
                </td>
                <td className="p-6">
                  <div className="space-y-2">
                    <span className="inline-flex rounded-lg bg-slate-800/50 px-3 py-1.5 text-[10px] font-bold uppercase tracking-tight text-slate-400">
                      {log.feature}
                    </span>
                    <div className="text-[10px] font-bold uppercase tracking-widest text-slate-600">Service Route</div>
                  </div>
                </td>
                <td className="p-6">
                  <div className="space-y-2">
                    <div className="flex items-center gap-2 text-xs font-bold text-slate-300">
                      <div className={`h-1.5 w-1.5 rounded-full ${log.driver === 'local' ? 'bg-amber-400' : 'bg-cyan-400'}`} />
                      {log.driver.toUpperCase()}
                    </div>
                    <div className="break-all text-[11px] font-mono text-cyan-300">{resolveLogModel(log)}</div>
                  </div>
                </td>
                <td className="p-6">
                  <div className="flex items-center gap-2">
                    <div className="text-xs font-mono font-bold text-slate-300">{log.latency_ms}</div>
                    <div className="h-1 w-12 overflow-hidden rounded-full bg-slate-800">
                      <div
                        className={`h-full rounded-full transition-all duration-1000 ${
                          log.latency_ms > 2000 ? 'bg-rose-500' : log.latency_ms > 1000 ? 'bg-amber-500' : 'bg-emerald-500'
                        }`}
                        style={{ width: `${Math.min(100, (log.latency_ms / 3000) * 100)}%` }}
                      />
                    </div>
                  </div>
                </td>
                <td className="p-6">
                  <LogStatusBadge status={log.status} />
                </td>
                <td className="p-6 text-right">
                  <button
                    onClick={() => onInspect(log)}
                    className="rounded-xl p-2.5 text-slate-500 transition-all hover:bg-cyan-500/10 hover:text-cyan-400"
                    title="Inspect Transaction"
                  >
                    <ExternalLink size={18} />
                  </button>
                </td>
              </tr>
            ))}

            {logs.length === 0 && !isLoading && (
              <tr>
                <td colSpan={6} className="p-20 text-center">
                  <div className="flex flex-col items-center gap-4">
                    <div className="flex h-16 w-16 items-center justify-center rounded-full bg-slate-900 text-slate-700">
                      <Activity size={32} />
                    </div>
                    <div className="text-xs font-bold uppercase tracking-widest text-slate-500">
                      No Signal Detected in Frequency
                    </div>
                  </div>
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>

      {pagination && pagination.last_page > 1 && (
        <div className="flex items-center justify-between border-t border-slate-800/50 bg-slate-900/10 p-6">
          <p className="text-[10px] font-black uppercase tracking-widest text-slate-500">
            Showing Page {pagination.current_page} of {pagination.last_page} • Total Logs: {pagination.total}
          </p>
          <div className="flex items-center gap-2">
            <button
              onClick={() => onPageChange(Math.max(1, page - 1))}
              disabled={page === 1}
              className="rounded-xl bg-slate-800/50 px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-400 shadow-sm transition-all hover:bg-slate-800 hover:text-white disabled:opacity-30"
            >
              Previous Flux
            </button>
            <button
              onClick={() => onPageChange(Math.min(pagination.last_page, page + 1))}
              disabled={page === pagination.last_page}
              className="rounded-xl bg-slate-800/50 px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-400 shadow-sm transition-all hover:bg-slate-800 hover:text-white disabled:opacity-30"
            >
              Next Flux
            </button>
          </div>
        </div>
      )}
    </>
  );
}
