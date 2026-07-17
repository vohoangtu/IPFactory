'use client';

import React, { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { X, Save, AlertCircle } from 'lucide-react';
import type { AiKey, AiKeyPayload } from '../../types';

interface KeyFormProps {
    isOpen: boolean;
    onClose: () => void;
    onSubmit: (data: AiKeyPayload) => Promise<void>;
    initialData?: AiKey | null;
}

type KeyFormState = {
    provider: string;
    label: string;
    key: string;
    tier: 'free' | 'premium';
    status: 'active' | 'inactive' | 'cooldown';
    level: number;
    model_group: string;
    base_url: string;
    model: string;
};

function buildInitialState(initialData?: AiKey | null): KeyFormState {
    if (!initialData) {
        return {
            provider: 'openai',
            label: '',
            key: '',
            tier: 'free',
            status: 'active',
            level: 1,
            model_group: '',
            base_url: '',
            model: '',
        };
    }

    return {
        provider: initialData.provider,
        label: initialData.label,
        key: '',
        tier: initialData.tier,
        status: initialData.status,
        level: initialData.level,
        model_group: initialData.model_group || '',
        base_url: initialData.metadata?.url ? String(initialData.metadata.url) : '',
        model: initialData.metadata?.model ? String(initialData.metadata.model) : '',
    };
}

export default function KeyForm({ isOpen, onClose, onSubmit, initialData }: KeyFormProps) {
    const [formData, setFormData] = useState<KeyFormState>(() => buildInitialState(initialData));

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setFormData((prev) => ({
            ...prev,
            [name]: name === 'level' ? Number(value) : value,
        }));
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        const payload: AiKeyPayload = {
            provider: formData.provider,
            label: formData.label,
            ...(formData.key ? { key: formData.key } : {}),
            tier: formData.tier,
            status: formData.status,
            level: Number(formData.level),
            ...(formData.model_group ? { model_group: formData.model_group } : {}),
            metadata: {
                ...(formData.base_url ? { url: formData.base_url } : {}),
                ...(formData.model ? { model: formData.model } : {}),
            },
        };

        await onSubmit(payload);
        onClose();
    };

    return (
        <AnimatePresence>
            {isOpen && (
                <>
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        onClick={onClose}
                        className="fixed inset-0 bg-black/80 backdrop-blur-sm z-[100]"
                    />
                    <motion.div
                        initial={{ opacity: 0, scale: 0.95, y: 20 }}
                        animate={{ opacity: 1, scale: 1, y: 0 }}
                        exit={{ opacity: 0, scale: 0.95, y: 20 }}
                        className="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-lg bg-[#0f0f12] border border-slate-800 rounded-3xl p-8 shadow-2xl z-[101]"
                    >
                        <div className="flex justify-between items-center mb-8">
                            <div>
                                <h2 className="text-2xl font-black text-white">
                                    {initialData ? 'Edit Intelligent Key' : 'Register New Key'}
                                </h2>
                                <p className="text-sm text-slate-500 font-medium">Configure your AI asset parameters</p>
                            </div>
                            <button onClick={onClose} className="p-2 hover:bg-slate-800 rounded-xl transition-colors">
                                <X size={20} className="text-slate-400" />
                            </button>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div className="space-y-2">
                                    <label className="text-[10px] font-bold uppercase tracking-widest text-slate-500 ml-1">Provider</label>
                                    <select
                                        name="provider"
                                        value={formData.provider}
                                        onChange={handleChange}
                                        className="w-full bg-slate-900/50 border border-slate-800 rounded-xl px-4 py-3 text-sm focus:border-cyan-500 outline-none transition-all"
                                    >
                                        <option value="zai">Z.AI</option>
                                        <option value="openai">OpenAI</option>
                                        <option value="gemini">Gemini</option>
                                        <option value="openrouter">OpenRouter</option>
                                        <option value="local">Local</option>
                                    </select>
                                </div>
                                <div className="space-y-2">
                                    <label className="text-[10px] font-bold uppercase tracking-widest text-slate-500 ml-1">Tier</label>
                                    <select
                                        name="tier"
                                        value={formData.tier}
                                        onChange={handleChange}
                                        className={`w-full bg-slate-900/50 border border-slate-800 rounded-xl px-4 py-3 text-sm focus:border-cyan-500 outline-none transition-all font-bold ${formData.tier === 'premium' ? 'text-cyan-400' : 'text-slate-100'}`}
                                    >
                                        <option value="free">Free Access</option>
                                        <option value="premium">Premium Access</option>
                                    </select>
                                </div>
                                <div className="space-y-2">
                                    <label className="text-[10px] font-bold uppercase tracking-widest text-slate-500 ml-1">Status</label>
                                    <select
                                        name="status"
                                        value={formData.status}
                                        onChange={handleChange}
                                        className={`w-full bg-slate-900/50 border border-slate-800 rounded-xl px-4 py-3 text-sm focus:border-cyan-500 outline-none transition-all font-bold ${
                                            formData.status === 'active'
                                                ? 'text-emerald-400'
                                                : formData.status === 'cooldown'
                                                    ? 'text-amber-400'
                                                    : 'text-slate-300'
                                        }`}
                                    >
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="cooldown">Cooldown</option>
                                    </select>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <label className="text-[10px] font-bold uppercase tracking-widest text-slate-500 ml-1">Label</label>
                                <input
                                    name="label"
                                    placeholder="e.g. Primary OpenAI v4"
                                    value={formData.label}
                                    onChange={handleChange}
                                    className="w-full bg-slate-900/50 border border-slate-800 rounded-xl px-4 py-3 text-sm focus:border-cyan-500 outline-none transition-all"
                                    required
                                />
                            </div>

                            <div className="space-y-2">
                                <label className="text-[10px] font-bold uppercase tracking-widest text-slate-500 ml-1">API Key</label>
                                <input
                                    name="key"
                                    type="password"
                                    placeholder={initialData ? 'Leave empty to keep current' : 'sk-xxxxxxxxxxxxxxxxxxxx'}
                                    value={formData.key}
                                    onChange={handleChange}
                                    className="w-full bg-slate-900/50 border border-slate-800 rounded-xl px-4 py-3 text-sm focus:border-cyan-500 outline-none transition-all font-mono"
                                    required={!initialData}
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-[10px] font-bold uppercase tracking-widest text-slate-500 ml-1">Level (1-5)</label>
                                    <input
                                        name="level"
                                        type="number"
                                        min="1"
                                        max="5"
                                        value={formData.level}
                                        onChange={handleChange}
                                        className="w-full bg-slate-900/50 border border-slate-800 rounded-xl px-4 py-3 text-sm focus:border-cyan-500 outline-none transition-all"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <label className="text-[10px] font-bold uppercase tracking-widest text-slate-500 ml-1">Model Group</label>
                                    <input
                                        name="model_group"
                                        placeholder="gpt-4o"
                                        value={formData.model_group}
                                        onChange={handleChange}
                                        className="w-full bg-slate-900/50 border border-slate-800 rounded-xl px-4 py-3 text-sm focus:border-cyan-500 outline-none transition-all"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-[10px] font-bold uppercase tracking-widest text-slate-500 ml-1">Base URL</label>
                                    <input
                                        name="base_url"
                                        placeholder="https://api.openai.com/v1/chat/completions"
                                        value={formData.base_url}
                                        onChange={handleChange}
                                        className="w-full bg-slate-900/50 border border-slate-800 rounded-xl px-4 py-3 text-sm focus:border-cyan-500 outline-none transition-all"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <label className="text-[10px] font-bold uppercase tracking-widest text-slate-500 ml-1">Model Override</label>
                                    <input
                                        name="model"
                                        placeholder="gpt-4o-mini"
                                        value={formData.model}
                                        onChange={handleChange}
                                        className="w-full bg-slate-900/50 border border-slate-800 rounded-xl px-4 py-3 text-sm focus:border-cyan-500 outline-none transition-all"
                                    />
                                </div>
                            </div>

                            <div className="p-4 rounded-2xl bg-amber-500/5 border border-amber-500/10 flex gap-3 text-amber-500/80">
                                <AlertCircle size={18} className="shrink-0 mt-0.5" />
                                <p className="text-[10px] font-medium leading-relaxed">
                                    SECURITY: API keys are encrypted immediately upon storage. WorldOS does not transmit plain-text keys after registration.
                                </p>
                            </div>

                            <button
                                type="submit"
                                className="w-full h-14 bg-white text-black font-black rounded-2xl hover:bg-cyan-400 transition-all shadow-[0_0_20px_rgba(255,255,255,0.1)] flex items-center justify-center gap-2 group"
                            >
                                <Save size={20} className="group-hover:scale-110 transition-transform" />
                                {initialData ? 'Save Changes' : 'Initialize Asset'}
                            </button>
                        </form>
                    </motion.div>
                </>
            )}
        </AnimatePresence>
    );
}
