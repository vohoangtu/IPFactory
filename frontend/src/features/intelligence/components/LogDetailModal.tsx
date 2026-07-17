'use client';

import React from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { X, Command, Code2, AlertTriangle, Clock, Zap } from 'lucide-react';

import type { AiLog, JsonValue } from '@/shared/types/api';
import { resolveLogModel } from '../lib/log-utils';

interface LogDetailModalProps {
    log: AiLog | null;
    open: boolean;
    onClose: () => void;
}

function formatJson(data: JsonValue): string {
    try {
        return JSON.stringify(data, null, 2);
    } catch {
        return String(data);
    }
}

export default function LogDetailModal({ log, open, onClose }: LogDetailModalProps) {
    if (!log) return null;

    return (
        <AnimatePresence>
            {open && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        onClick={onClose}
                        className="absolute inset-0 bg-black/80 backdrop-blur-sm"
                    />

                    <motion.div
                        initial={{ opacity: 0, scale: 0.95, y: 20 }}
                        animate={{ opacity: 1, scale: 1, y: 0 }}
                        exit={{ opacity: 0, scale: 0.95, y: 20 }}
                        className="relative w-full max-w-4xl max-h-[85vh] bg-[#0d0d11] border border-slate-800 rounded-3xl overflow-hidden shadow-[0_30px_60px_-12px_rgba(0,0,0,0.5)]"
                    >
                        <div className="flex items-center justify-between p-6 border-b border-slate-800/50 bg-slate-900/20">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-xl bg-cyan-500/10 flex items-center justify-center text-cyan-400">
                                    <Command size={20} />
                                </div>
                                <div>
                                    <h3 className="text-lg font-black text-white leading-none">Diagnostic Report</h3>
                                    <p className="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-1.5">
                                        Transaction ID: {log.id} • {log.feature}
                                    </p>
                                </div>
                            </div>
                            <button
                                onClick={onClose}
                                className="p-2.5 rounded-xl bg-slate-800/50 text-slate-400 hover:text-white hover:bg-slate-800 transition-all"
                            >
                                <X size={20} />
                            </button>
                        </div>

                        <div className="p-0 overflow-y-auto max-h-[calc(85vh-88px)] custom-scrollbar">
                            <div className="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-800/50">
                                <div className="p-6 space-y-6 bg-slate-900/10">
                                    <section>
                                        <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block mb-3">Service</label>
                                        <div className="flex items-center gap-2 text-white font-bold bg-white/5 p-3 rounded-2xl border border-white/5">
                                            <Command size={16} className="text-cyan-400" />
                                            {log.feature.toUpperCase()}
                                        </div>
                                    </section>

                                    <section>
                                        <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block mb-3">Model Driver</label>
                                        <div className="flex items-center gap-2 text-white font-bold bg-white/5 p-3 rounded-2xl border border-white/5">
                                            <Zap size={16} className="text-amber-400" />
                                            {log.driver.toUpperCase()}
                                        </div>
                                    </section>

                                    <section>
                                        <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block mb-3">Resolved Model</label>
                                        <div className="flex items-center gap-2 text-white font-mono bg-white/5 p-3 rounded-2xl border border-white/5 break-all">
                                            {resolveLogModel(log)}
                                        </div>
                                    </section>

                                    <section>
                                        <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest block mb-3">Latency</label>
                                        <div className="flex items-center gap-2 text-white font-bold bg-white/5 p-3 rounded-2xl border border-white/5">
                                            <Clock size={16} className="text-cyan-400" />
                                            {log.latency_ms}ms
                                        </div>
                                    </section>

                                    {log.status === 'error' && (
                                        <section className="p-4 rounded-2xl bg-rose-500/5 border border-rose-500/20 space-y-2">
                                            <div className="flex items-center gap-2 text-rose-400 font-bold text-xs">
                                                <AlertTriangle size={14} />
                                                EXCEPTION DETECTED
                                            </div>
                                            <p className="text-xs text-rose-300 leading-relaxed font-mono break-words">
                                                {log.error_message}
                                            </p>
                                        </section>
                                    )}
                                </div>

                                <div className="md:col-span-2 p-6 space-y-8">
                                    <section>
                                        <div className="flex items-center gap-2 mb-4">
                                            <Code2 size={16} className="text-slate-400" />
                                            <h4 className="text-[10px] font-black text-slate-400 uppercase tracking-widest">Input Payload (Prompt)</h4>
                                        </div>
                                        <div className="bg-black/40 rounded-2xl p-6 font-mono text-[13px] leading-relaxed text-slate-300 border border-slate-800/50 overflow-x-auto">
                                            <pre>{formatJson(log.input)}</pre>
                                        </div>
                                    </section>

                                    <section>
                                        <div className="flex items-center gap-2 mb-4">
                                            <Code2 size={16} className="text-slate-400" />
                                            <h4 className="text-[10px] font-black text-slate-400 uppercase tracking-widest">Output Content (Response)</h4>
                                        </div>
                                        <div className="bg-cyan-500/5 rounded-2xl p-6 font-mono text-[13px] leading-relaxed text-cyan-50 border border-cyan-500/10 overflow-x-auto">
                                            <pre className="whitespace-pre-wrap">{formatJson(log.output)}</pre>
                                        </div>
                                    </section>
                                </div>
                            </div>
                        </div>
                    </motion.div>
                </div>
            )}
        </AnimatePresence>
    );
}
