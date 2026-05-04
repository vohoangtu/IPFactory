'use client';

import { Menu, X, LogOut } from 'lucide-react';
import Image from 'next/image';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';

interface AppHeaderProps {
  sidebarOpen: boolean;
  onToggleSidebar: () => void;
}

export default function AppHeader({ sidebarOpen, onToggleSidebar }: AppHeaderProps) {
  const { user, logout } = useAuth();
  const router = useRouter();

  const handleLogout = async () => {
    await logout();
    router.replace('/login');
  };

  return (
    <header
      id="app-header"
      className="sticky top-0 z-[var(--z-header)] flex items-center justify-between border-b border-[var(--border-subtle)] bg-[var(--bg-base)]/80 px-6 py-4 backdrop-blur-xl"
    >
      {/* Sidebar toggle */}
      <button
        id="sidebar-toggle-btn"
        onClick={onToggleSidebar}
        aria-label={sidebarOpen ? 'Close sidebar' : 'Open sidebar'}
        className="flex h-9 w-9 items-center justify-center rounded-xl border border-border-subtle text-text-muted transition hover:bg-bg-elevated/50 hover:text-text-primary"
      >
        {sidebarOpen ? <X size={17} /> : <Menu size={17} />}
      </button>

      {/* Right side: user info + logout */}
      <div className="flex items-center gap-3">
        <div className="text-right">
          <p className="text-sm font-semibold text-text-secondary">{user?.name ?? 'Operator'}</p>
          <p className="font-mono text-[9px] uppercase tracking-widest text-cyan-400">
            {user?.email ?? 'Root Access'}
          </p>
        </div>
        <div className="h-9 w-9 overflow-hidden rounded-full border border-border-muted bg-bg-elevated">
          <Image
            src={`https://api.dicebear.com/7.x/bottts/svg?seed=${user?.email ?? 'WorldOS'}`}
            alt="User avatar"
            width={36}
            height={36}
            unoptimized
          />
        </div>
        <button
          onClick={handleLogout}
          title="Logout"
          className="flex h-9 w-9 items-center justify-center rounded-xl border border-border-subtle text-text-disabled transition hover:bg-rose-900/30 hover:text-rose-400 hover:border-rose-500/30"
        >
          <LogOut size={15} />
        </button>
      </div>
    </header>
  );
}
