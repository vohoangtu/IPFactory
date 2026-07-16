'use client';

import { use, useState } from 'react';
import { useRouter } from 'next/navigation';
import { ArrowLeft, RefreshCw, AlertTriangle } from 'lucide-react';

import { useChronicleDetail, parseAnimationScript, CinematicPlayer, VAFErrorBoundary } from '@/features/cinema';

interface PageProps {
    params: Promise<{ chronicleId: string }>;
}

export default function NarrativeCinemaPage(props: PageProps) {
    const { chronicleId: rawId } = use(props.params);
    const chronicleId = Number(rawId);
    const router = useRouter();

    // retryCount buộc VAFErrorBoundary remount hoàn toàn khi người dùng nhấn "Thử Lại"
    const [retryCount, setRetryCount] = useState(0);

    const { chronicle, isLoading, isError } = useChronicleDetail(
        Number.isFinite(chronicleId) ? chronicleId : null,
    );

    // ── Loading state ───────────────────────────
    if (isLoading) {
        return (
            <div className="fixed inset-0 z-50 flex items-center justify-center bg-black">
                <div className="h-10 w-10 animate-spin rounded-full border-4 border-cyan-500 border-t-transparent" />
            </div>
        );
    }

    // ── Error state (H1 fix) ────────────────────
    // Hiển thị khi API trả lỗi (4xx/5xx) thay vì màn đen im lặng.
    if (isError) {
        return (
            <div className="fixed inset-0 z-50 flex flex-col items-center justify-center gap-6 bg-black text-white">
                <AlertTriangle className="h-10 w-10 text-amber-500" />
                <div className="text-center space-y-2">
                    <h2 className="text-lg font-semibold">Không thể tải chronicle</h2>
                    <p className="text-sm text-slate-400">
                        Yêu cầu thất bại. Kiểm tra kết nối hoặc liên hệ quản trị viên.
                    </p>
                </div>
                <div className="flex gap-3">
                    <button
                        onClick={() => setRetryCount((c) => c + 1)}
                        className="flex items-center gap-2 rounded-xl bg-white/10 px-5 py-2.5 text-sm font-medium transition hover:bg-white/20"
                    >
                        <RefreshCw size={14} />
                        Thử Lại
                    </button>
                    <button
                        onClick={() => router.back()}
                        className="flex items-center gap-2 rounded-xl bg-white/5 px-5 py-2.5 text-sm font-medium text-slate-400 transition hover:bg-white/10 hover:text-white"
                    >
                        <ArrowLeft size={14} />
                        Quay Lại
                    </button>
                </div>
            </div>
        );
    }

    // ── Parse animation script ──────────────────
    const animation = chronicle?.animation_script
        ? parseAnimationScript(chronicle.animation_script)
        : null;

    // ── Fallback: no animation available ────────
    if (!chronicle || !animation) {
        return (
            <div className="fixed inset-0 z-50 flex flex-col bg-black text-white">
                <div className="absolute left-4 top-4 z-50">
                    <button
                        onClick={() => router.back()}
                        className="flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-medium backdrop-blur-md transition hover:bg-white/20"
                    >
                        <ArrowLeft size={16} />
                        Back
                    </button>
                </div>

                <div className="flex flex-1 items-center justify-center p-8">
                    <div className="max-w-2xl space-y-4 text-center">
                        <h1 className="text-2xl font-black tracking-tight">
                            {chronicle?.title ?? 'Chronicle Not Found'}
                        </h1>
                        <p className="text-sm text-slate-400">
                            No cinematic animation is available for this chronicle.
                        </p>
                        {chronicle?.content && (
                            <div className="mt-6 rounded-2xl border border-slate-800 bg-slate-950/60 p-6 text-left">
                                <p className="text-sm leading-relaxed text-slate-300 whitespace-pre-wrap">
                                    {chronicle.content}
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        );
    }

    // ── Cinematic player ────────────────────────
    // key={chronicleId}-${retryCount} buộc VAFErrorBoundary + CinematicPlayer
    // remount hoàn toàn khi retry, tránh infinite render loop (M1 fix).
    return (
        <div className="fixed inset-0 z-50 bg-black">
            <div className="absolute left-4 top-4 z-50">
                <button
                    onClick={() => router.back()}
                    className="flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-medium text-white backdrop-blur-md transition hover:bg-white/20"
                >
                    <ArrowLeft size={16} />
                </button>
            </div>

            <VAFErrorBoundary
                key={`${chronicleId}-${retryCount}`}
                onExit={() => router.back()}
            >
                <CinematicPlayer
                    key={`${chronicleId}-${retryCount}`}
                    animationScript={animation}
                    chronicleTitle={chronicle.title}
                    chronicleContent={chronicle.content}
                    onExit={() => router.back()}
                />
            </VAFErrorBoundary>
        </div>
    );
}
