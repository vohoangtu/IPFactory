'use client';

import { useState, useEffect } from 'react';
import { usePathname, useRouter } from 'next/navigation';
import { motion, AnimatePresence } from 'framer-motion';
import { UniverseProvider } from '@/contexts/UniverseContext';
import { useAuth } from '@/contexts/AuthContext';
import { useRealtimeSync } from '@/hooks/useRealtimeSync';
import Sidebar from '@/components/shell/Sidebar';
import AppHeader from '@/components/shell/AppHeader';

function AuthGuard({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, isLoading } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (!isLoading && !isAuthenticated) {
      router.replace('/login');
    }
  }, [isLoading, isAuthenticated, router]);

  if (isLoading) {
    return (
      <div className="flex h-screen items-center justify-center bg-[var(--bg-base)]">
        <div className="w-6 h-6 rounded-full border-2 border-brand-accent/30 border-t-brand-accent animate-spin" />
      </div>
    );
  }

  if (!isAuthenticated) return null;

  return <>{children}</>;
}

function ShellContent({ children }: { children: React.ReactNode }) {
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const pathname = usePathname();

  // Activate WebSocket → React Query cache invalidation bridge
  useRealtimeSync();

  return (
    <div className="flex h-screen overflow-hidden bg-bg-base text-text-secondary">
      {/* Sidebar */}
      <Sidebar isOpen={sidebarOpen} />

      {/* Main area */}
      <div className="flex flex-1 flex-col overflow-hidden">
        <AppHeader
          sidebarOpen={sidebarOpen}
          onToggleSidebar={() => setSidebarOpen((v) => !v)}
        />

        <main
          id="main-content"
          className="flex-1 overflow-y-auto custom-scrollbar bg-[radial-gradient(ellipse_at_50%_-10%,rgba(30,27,75,0.4),transparent_60%)]"
        >
          <div className="mx-auto max-w-[1600px] p-6 lg:p-8">
            <AnimatePresence mode="wait">
              <motion.div
                key={pathname}
                initial={{ opacity: 0, y: 12 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -8 }}
                transition={{ duration: 0.25, ease: [0.16, 1, 0.3, 1] }}
              >
                {children}
              </motion.div>
            </AnimatePresence>
          </div>
        </main>
      </div>
    </div>
  );
}

/**
 * DashboardShell — root layout wrapper for all dashboard routes.
 * Wraps content with UniverseProvider and renders the Sidebar + AppHeader.
 */
export default function DashboardShell({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <AuthGuard>
      <UniverseProvider>
        <ShellContent>{children}</ShellContent>
      </UniverseProvider>
    </AuthGuard>
  );
}
