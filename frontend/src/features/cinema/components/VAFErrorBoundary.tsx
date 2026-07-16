'use client';

import { Component, type ReactNode } from 'react';

interface Props {
    children: ReactNode;
    onExit?: () => void;
}

interface State {
    hasError: boolean;
    error: Error | null;
}

export default class VAFErrorBoundary extends Component<Props, State> {
    constructor(props: Props) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error: Error): State {
        return { hasError: true, error };
    }

    componentDidCatch(error: Error, info: React.ErrorInfo) {
        console.error('[VAF] Render error:', error, info);
    }

    render() {
        if (this.state.hasError) {
            return (
                <div className="w-screen h-screen bg-black flex items-center justify-center">
                    <div className="text-center max-w-md p-8">
                        <h2 className="text-white text-xl font-bold mb-4">
                            Animation Error
                        </h2>
                        <p className="text-slate-400 mb-6 text-sm">
                            {this.state.error?.message ||
                                'An unexpected error occurred while playing the animation.'}
                        </p>
                        <div className="flex gap-3 justify-center">
                            <button
                                onClick={() =>
                                    this.setState({
                                        hasError: false,
                                        error: null,
                                    })
                                }
                                className="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg transition-colors text-sm"
                            >
                                Try Again
                            </button>
                            <button
                                onClick={() => this.props.onExit?.()}
                                className="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white rounded-lg transition-colors text-sm"
                            >
                                Go Back
                            </button>
                        </div>
                    </div>
                </div>
            );
        }
        return this.props.children;
    }
}
