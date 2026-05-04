'use client';

import { useEffect } from 'react';

export default function DashboardError({
    error,
    reset,
}: {
    error: Error & { digest?: string };
    reset: () => void;
}) {
    useEffect(() => {
        console.error('Dashboard error:', error);
    }, [error]);

    return (
        <div className="flex items-center justify-center min-h-[60vh] text-zinc-100">
            <div className="text-center space-y-4">
                <h2 className="text-xl font-bold text-red-400">Dashboard Error</h2>
                <p className="text-zinc-400 max-w-md">{error.message || 'Failed to load dashboard.'}</p>
                <button
                    onClick={reset}
                    className="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 rounded-lg transition-colors"
                >
                    Retry
                </button>
            </div>
        </div>
    );
}
