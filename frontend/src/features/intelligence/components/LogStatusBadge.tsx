import React from 'react';
import { CheckCircle2, XCircle, Clock } from 'lucide-react';

interface LogStatusBadgeProps {
    status: 'success' | 'error' | 'cooldown' | 'active' | string;
    className?: string;
}

export const LogStatusBadge: React.FC<LogStatusBadgeProps> = ({ status, className = "" }) => {
    const isSuccess = status === 'success' || status === 'active';
    const isError = status === 'error' || status === 'inactive';
    const isCooldown = status === 'cooldown';

    if (isSuccess) {
        return (
            <div className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] font-black uppercase tracking-wider shadow-[0_0_15px_rgba(16,185,129,0.1)] ${className}`}>
                <CheckCircle2 size={12} />
                <span>Operational</span>
            </div>
        );
    }

    if (isError) {
        return (
            <div className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-400 text-[10px] font-black uppercase tracking-wider shadow-[0_0_15px_rgba(244,63,94,0.1)] ${className}`}>
                <XCircle size={12} />
                <span>Malfunction</span>
            </div>
        );
    }

    if (isCooldown) {
        return (
            <div className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 text-[10px] font-black uppercase tracking-wider shadow-[0_0_15px_rgba(245,158,11,0.1)] ${className}`}>
                <Clock size={12} />
                <span>Cooldown</span>
            </div>
        );
    }

    return (
        <div className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-500/10 border border-slate-500/20 text-slate-400 text-[10px] font-black uppercase tracking-wider ${className}`}>
            <span>{status.toUpperCase()}</span>
        </div>
    );
};
