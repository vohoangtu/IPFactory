import Link from 'next/link';

export default function NotFound() {
    return (
        <div className="flex min-h-screen items-center justify-center bg-bg-base text-text-primary">
            <div className="text-center space-y-4">
                <h2 className="text-4xl font-bold text-text-secondary">404</h2>
                <p className="text-text-muted">Page not found</p>
                <Link
                    href="/"
                    className="inline-block px-4 py-2 bg-bg-elevated hover:bg-bg-overlay rounded-lg transition-colors"
                >
                    Return Home
                </Link>
            </div>
        </div>
    );
}
