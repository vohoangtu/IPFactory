'use client';

import React from 'react';
import { motion } from 'framer-motion';
import {
    Trash2,
    Edit3,
    AlertCircle,
    CheckCircle2,
    Timer
} from 'lucide-react';
import type { AiKey } from '@/features/admin/types';
import { formatDistanceToNow } from 'date-fns';

interface KeyTableProps {
    keys: AiKey[];
    onEdit: (key: AiKey) => void;
    onDelete: (id: number) => void;
}

const StatusBadge = ({ status, cooldownUntil }: { status: AiKey['status']; cooldownUntil: AiKey['cooldown_until'] }) => {
    if (status === 'cooldown') {
        const time = cooldownUntil ? formatDistanceToNow(new Date(cooldownUntil)) : 'Unknown';
        return (
            <div className="flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20 text-[10px] font-bold uppercase tracking-tighter">
                <Timer size={12} className="animate-spin-slow" />
                Cooldown ({time})
            </div>
        );
    }

    if (status === 'active') {
        return (
            <div className="flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-bold uppercase tracking-tighter shadow-[0_0_10px_rgba(16,185,129,0.1)]">
                <CheckCircle2 size={12} />
                Active
            </div>
        );
    }

    return (
        <div className="flex items-center gap-2 px-3 py-1 rounded-full bg-slate-500/10 text-slate-400 border border-slate-500/20 text-[10px] font-bold uppercase tracking-tighter">
            <AlertCircle size={12} />
            Inactive
        </div>
    );
};

export default function KeyTable({ keys, onEdit, onDelete }: KeyTableProps) {
    return (
        <div className="w-full bg-[#111116] rounded-3xl border border-slate-800/50 overflow-hidden">
            <table className="w-full text-left border-collapse">
                <thead>
                    <tr className="border-b border-slate-800/50 bg-slate-900/20">
                        <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-500">Provider & Label</th>
                        <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-500">Tier / Level</th>
                        <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-500">Model Routing</th>
                        <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-500">Usage</th>
                        <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-500">Status</th>
                        <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-500 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-800/50">
                    {keys.length === 0 ? (
                        <tr>
                            <td colSpan={6} className="px-6 py-20 text-center text-slate-500 italic font-medium">
                                No keys found in the intelligence pool. Start by adding one.
                            </td>
                        </tr>
                    ) : (
                        keys.map((key) => (
                            <motion.tr
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                key={key.id}
                                className="group hover:bg-slate-800/20 transition-colors"
                            >
                                <td className="px-6 py-4">
                                    <div className="flex items-center gap-4">
                                        <div className={`w-10 h-10 rounded-xl flex items-center justify-center text-xs font-black uppercase text-white shadow-lg ${
                                            key.provider === 'zai' ? 'bg-fuchsia-600' :
                                            key.provider === 'openai' ? 'bg-emerald-600' :
                                            key.provider === 'gemini' ? 'bg-blue-600' :
                                            key.provider === 'openrouter' ? 'bg-purple-600' :
                                            'bg-slate-600'
                                        }`}>
                                            {key.provider.substring(0, 2)}
                                        </div>
                                        <div>
                                            <p className="text-sm font-bold text-white group-hover:text-cyan-400 transition-colors">{key.label}</p>
                                            <p className="text-[10px] font-semibold text-slate-500 tracking-wider uppercase">{key.provider}</p>
                                            <p className="text-[10px] text-slate-500 font-mono mt-1">{key.key_preview || '********'}</p>
                                        </div>
                                    </div>
                                </td>
                                <td className="px-6 py-4">
                                    <div className="flex flex-col gap-1">
                                        <span className={`text-[10px] font-black uppercase tracking-widest ${key.tier === 'premium' ? 'text-cyan-400' : 'text-slate-400'}`}>
                                            {key.tier}
                                        </span>
                                        <div className="flex gap-1">
                                            {[...Array(5)].map((_, i) => (
                                                <div key={i} className={`w-3 h-1 rounded-full ${i < key.level ? (key.tier === 'premium' ? 'bg-cyan-500' : 'bg-slate-500') : 'bg-slate-800'}`} />
                                            ))}
                                        </div>
                                    </div>
                                </td>
                                <td className="px-6 py-4">
                                    <div className="flex flex-col gap-1">
                                        <p className="text-sm font-semibold text-slate-200">
                                            {key.metadata?.model || key.model_group || 'default'}
                                        </p>
                                        <p className="text-[10px] text-slate-500 truncate max-w-[220px]">
                                            {key.metadata?.url || 'provider default endpoint'}
                                        </p>
                                    </div>
                                </td>
                                <td className="px-6 py-4">
                                    <p className="text-sm font-mono font-bold text-slate-300">{(key.usage_count ?? 0).toLocaleString()}</p>
                                    <p className="text-[10px] text-slate-500">total requests</p>
                                </td>
                                <td className="px-6 py-4">
                                    <StatusBadge status={key.status} cooldownUntil={key.cooldown_until} />
                                </td>
                                <td className="px-6 py-4 text-right">
                                    <div className="flex items-center justify-end gap-2">
                                        <button
                                            onClick={() => onEdit(key)}
                                            className="p-2 rounded-lg hover:bg-slate-700/50 text-slate-400 hover:text-white transition-all"
                                        >
                                            <Edit3 size={16} />
                                        </button>
                                        <button
                                            onClick={() => onDelete(key.id)}
                                            className="p-2 rounded-lg hover:bg-rose-500/10 text-slate-400 hover:text-rose-400 transition-all"
                                        >
                                            <Trash2 size={16} />
                                        </button>
                                    </div>
                                </td>
                            </motion.tr>
                        ))
                    )}
                </tbody>
            </table>
        </div>
    );
}
