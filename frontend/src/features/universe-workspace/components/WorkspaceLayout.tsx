import Link from 'next/link';
import type { ReactNode } from 'react';
import { routes } from '@/shared/config/routes';
import { ContextBar } from './ContextBar';
import { LensNav } from './LensNav';

/** Shell của Observatory: thanh bối cảnh + (khi có universe) tab-nav lens + nội dung. */
export function WorkspaceLayout({ children, universeId }: { children: ReactNode; universeId?: number | null }) {
  return (
    <div className="flex min-h-screen flex-col bg-[var(--color-bg-base)] text-[var(--color-text-primary)]">
      <header className="border-b border-[var(--border-subtle)]">
        <div className="flex items-center justify-between gap-4 px-4 py-2">
          <ContextBar />
          <Link
            href={routes.multiverse()}
            className="shrink-0 text-sm text-[var(--color-text-secondary)] hover:text-[var(--color-primary)]"
          >
            Đa vũ trụ
          </Link>
        </div>
        {universeId != null && <LensNav universeId={universeId} />}
      </header>
      <main className="min-h-0 flex-1 p-4">{children}</main>
    </div>
  );
}
