'use client';

import { Search, Filter } from 'lucide-react';

interface LogFiltersProps {
  search: string;
  onSearchChange: (v: string) => void;
  driver: string;
  onDriverChange: (v: string) => void;
  status: string;
  onStatusChange: (v: string) => void;
}

export default function LogFilters({
  search,
  onSearchChange,
  driver,
  onDriverChange,
  status,
  onStatusChange,
}: LogFiltersProps) {
  return (
    <div className="flex flex-wrap items-center gap-4 border-b border-slate-800/50 bg-slate-900/30 p-6">
      <div className="relative min-w-[240px] flex-1">
        <Search size={18} className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500" />
        <input
          type="text"
          placeholder="Scan by request, service, driver, or model..."
          value={search}
          onChange={(e) => onSearchChange(e.target.value)}
          className="w-full rounded-2xl border border-slate-800 bg-slate-950/50 py-3 pl-12 pr-4 text-sm font-medium text-white transition-all placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-cyan-500/20"
        />
      </div>

      <div className="flex items-center gap-2">
        <Filter size={16} className="text-slate-500" />
        <select
          value={driver}
          onChange={(e) => onDriverChange(e.target.value)}
          className="rounded-2xl border border-slate-800 bg-slate-950/50 px-4 py-3 text-xs font-bold uppercase tracking-widest text-slate-400 transition-all focus:outline-none"
        >
          <option value="">ALL DRIVERS</option>
          <option value="openai">OpenAI</option>
          <option value="gemini">Gemini</option>
          <option value="openrouter">OpenRouter</option>
          <option value="zai">ZAI</option>
          <option value="local">Local</option>
        </select>
      </div>

      <select
        value={status}
        onChange={(e) => onStatusChange(e.target.value)}
        className="rounded-2xl border border-slate-800 bg-slate-950/50 px-4 py-3 text-xs font-bold uppercase tracking-widest text-slate-400 transition-all focus:outline-none"
      >
        <option value="">ALL STATUS</option>
        <option value="success">Operational</option>
        <option value="error">Malfunction</option>
      </select>
    </div>
  );
}
