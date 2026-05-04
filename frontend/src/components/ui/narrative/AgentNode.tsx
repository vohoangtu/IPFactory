import React from 'react';
import { motion } from 'framer-motion';

type AgentStatus = 'idle' | 'running' | 'completed' | 'error';

interface AgentNodeProps {
  id: string;
  provider: string;
  model: string;
  role: string;
  tier?: string;
  status?: AgentStatus;
  durationMs?: number;
}

const statusConfig = {
  idle: {
    bg: 'bg-black/40',
    border: 'border-white/10',
    glow: '',
    indicator: 'bg-gray-500',
    pulse: false,
  },
  running: {
    bg: 'bg-violet-900/40',
    border: 'border-violet-500/50',
    glow: 'shadow-[0_0_20px_rgba(139,92,246,0.3)]',
    indicator: 'bg-violet-500',
    pulse: true,
  },
  completed: {
    bg: 'bg-emerald-900/20',
    border: 'border-emerald-500/50',
    glow: 'shadow-[0_0_15px_rgba(52,211,153,0.2)]',
    indicator: 'bg-emerald-400',
    pulse: false,
  },
  error: {
    bg: 'bg-rose-900/30',
    border: 'border-rose-500/50',
    glow: 'shadow-[0_0_15px_rgba(244,63,94,0.2)]',
    indicator: 'bg-rose-500',
    pulse: false,
  },
};

export default function AgentNode({
  id,
  provider,
  model,
  role,
  tier = 'mini',
  status = 'idle',
  durationMs,
}: AgentNodeProps) {
  const config = statusConfig[status];
  const displayId = id.replace(/_/g, ' ');

  return (
    <motion.div
      initial={{ opacity: 0, scale: 0.9 }}
      animate={{ opacity: 1, scale: 1 }}
      className={`relative p-4 rounded-xl border backdrop-blur-md transition-all duration-500 ${config.bg} ${config.border} ${config.glow}`}
    >
      {/* Status indicator */}
      <span className="absolute -top-1 -right-1 flex h-3 w-3">
        {config.pulse && (
          <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-violet-400 opacity-75"></span>
        )}
        <span className={`relative inline-flex rounded-full h-3 w-3 ${config.indicator}`}></span>
      </span>

      {/* Tier badge */}
      <span
        className={`absolute top-2 right-8 text-[9px] font-bold uppercase px-1.5 py-0.5 rounded ${
          tier === 'pro' ? 'bg-amber-500/20 text-amber-400' : 'bg-blue-500/20 text-blue-400'
        }`}
      >
        {tier}
      </span>

      <div className="flex justify-between items-start mb-2">
        <h3 className="text-sm font-bold text-white uppercase tracking-wider truncate pr-16">
          {displayId}
        </h3>
      </div>

      <div className="flex items-center gap-2 mb-2">
        <span
          className={`text-[10px] font-mono px-2 py-0.5 rounded-sm ${
            status === 'running'
              ? 'bg-violet-500/20 text-violet-300'
              : 'bg-gray-800 text-gray-400'
          }`}
        >
          {provider}
        </span>
        {durationMs !== undefined && status === 'completed' && (
          <span className="text-[9px] text-emerald-400">{durationMs}ms</span>
        )}
      </div>

      <p className="text-xs text-gray-400 mb-3 h-6 line-clamp-2">{role}</p>

      <div className="flex items-center gap-2 text-[10px] uppercase font-bold tracking-widest text-gray-500 border-t border-white/5 pt-2">
        <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M13 10V3L4 14h7v7l9-11h-7z"
          />
        </svg>
        <span className="truncate">{model}</span>
      </div>
    </motion.div>
  );
}
