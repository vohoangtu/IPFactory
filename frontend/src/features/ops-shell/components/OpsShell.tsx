'use client';
import Link from 'next/link';
import type { ReactNode } from 'react';
import { useRouter } from 'next/navigation';
import { routes } from '@/shared/config/routes';
import { useAuth } from '@/features/auth';
import { OpsNav } from './OpsNav';

export function OpsShell({ children }: { children: ReactNode }) {
  const router = useRouter();
  const { logout } = useAuth();

  return (
    <div className="flex min-h-screen flex-col bg-[var(--color-bg-base)] text-[var(--color-text-primary)]">
      <header className="border-b border-[var(--border-subtle)]">
        <div className="flex items-center justify-between gap-4 px-4 py-2">
          <h1 className="shrink-0 text-sm font-semibold uppercase tracking-wider">Vận hành</h1>
          <Link
            href={routes.multiverse()}
            className="mr-auto text-sm text-[var(--color-text-secondary)] hover:text-[var(--color-primary)]"
          >
            ← Đài quan sát
          </Link>
          <button
            type="button"
            onClick={() => {
              logout();
              router.push(routes.login());
            }}
            className="shrink-0 text-sm text-[var(--color-text-secondary)] hover:text-[var(--color-primary)]"
          >
            Đăng xuất
          </button>
        </div>
        <OpsNav />
      </header>
      <main className="min-h-0 flex-1 p-4">{children}</main>
    </div>
  );
}
