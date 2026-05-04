'use client';

import { useEffect } from 'react';

export default function GlobalError({
    error,
    reset,
}: {
    error: Error & { digest?: string };
    reset: () => void;
}) {
    useEffect(() => {
        console.error('Unhandled error:', error);
    }, [error]);

    return (
        <div className="flex min-h-screen items-center justify-center bg-bg-base text-text-primary">
            <div className="text-center space-y-4">
                <h2 className="text-2xl font-bold text-brand-danger">Something went wrong</h2>
                <p className="text-text-muted max-w-md">{error.message || 'An unexpected error occurred.'}</p>
                <button
                    onClick={reset}
                    className="px-4 py-2 bg-bg-elevated hover:bg-bg-overlay rounded-lg transition-colors"
                >
                    Try again
                </button>
            </div>
        </div>
    );
}
