'use client';

import React, { useState, useCallback, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';
import { Loader2 } from 'lucide-react';

export default function LoginPage() {
  const { login, isAuthenticated } = useAuth();
  const router = useRouter();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Already logged in — redirect to dashboard
  useEffect(() => {
    if (isAuthenticated) {
      router.replace('/dashboard');
    }
  }, [isAuthenticated, router]);

  const handleSubmit = useCallback(async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setIsSubmitting(true);
    try {
      await login(email, password);
      router.replace('/dashboard');
    } catch (err: unknown) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message
        ?? 'Đăng nhập thất bại.';
      setError(msg);
    } finally {
      setIsSubmitting(false);
    }
  }, [email, password, login, router]);

  return (
    <div className="min-h-screen bg-bg-base flex items-center justify-center p-6 font-mono">
      {/* Background grid */}
      <div className="absolute inset-0 bg-[url('/grid.svg')] bg-center opacity-20 pointer-events-none" />
      <div className="absolute inset-0 bg-gradient-to-b from-violet-950/20 via-bg-base to-bg-base pointer-events-none" />

      <div className="relative z-10 w-full max-w-md">
        {/* Logo / Title */}
        <div className="text-center mb-10">
          <h1 className="text-4xl font-black italic tracking-tighter text-brand-accent mb-1">
            WorldOS
          </h1>
          <p className="text-xs text-text-disabled uppercase tracking-widest">Civilizational Dynamics Engine v6</p>
        </div>

        {/* Card */}
        <div className="bg-white/[0.03] border border-border-subtle rounded-2xl p-8 shadow-2xl backdrop-blur-sm">
          <h2 className="text-sm font-bold uppercase tracking-widest text-text-secondary mb-6">
            Operator Authentication
          </h2>

          <form onSubmit={handleSubmit} className="flex flex-col gap-5">
            <div className="flex flex-col gap-1.5">
              <label className="text-[11px] uppercase tracking-widest text-text-disabled" htmlFor="email">
                Email
              </label>
              <input
                id="email"
                type="email"
                autoComplete="email"
                required
                value={email}
                onChange={e => setEmail(e.target.value)}
                className="bg-black/40 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-text-primary placeholder-text-disabled focus:outline-none focus:border-brand-accent/60 focus:ring-1 focus:ring-brand-accent/30 transition-all"
                placeholder="operator@worldos.io"
              />
            </div>

            <div className="flex flex-col gap-1.5">
              <label className="text-[11px] uppercase tracking-widest text-text-disabled" htmlFor="password">
                Password
              </label>
              <input
                id="password"
                type="password"
                autoComplete="current-password"
                required
                value={password}
                onChange={e => setPassword(e.target.value)}
                className="bg-black/40 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-text-primary placeholder-text-disabled focus:outline-none focus:border-brand-accent/60 focus:ring-1 focus:ring-brand-accent/30 transition-all"
                placeholder="••••••••"
              />
            </div>

            {error && (
              <p className="text-xs text-brand-danger bg-brand-danger/10 border border-brand-danger/20 rounded-lg px-4 py-2.5">
                {error}
              </p>
            )}

            <button
              type="submit"
              disabled={isSubmitting}
              className="mt-1 w-full py-2.5 rounded-lg text-sm font-bold uppercase tracking-wider transition-all
                bg-violet-600 hover:bg-violet-500 text-white shadow-[0_0_20px_rgba(139,92,246,0.3)]
                disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none"
            >
              {isSubmitting ? (
                <span className="flex items-center justify-center gap-2">
                  <Loader2 size={16} className="animate-spin" />
                  Authenticating...
                </span>
              ) : 'Access System'}
            </button>
          </form>
        </div>

        <p className="text-center text-[10px] text-text-disabled mt-6 uppercase tracking-widest">
          WorldOS Restricted Access — Authorized Personnel Only
        </p>
      </div>
    </div>
  );
}
