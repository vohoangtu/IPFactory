import Link from 'next/link';
import type { ReactNode } from 'react';
import { routes } from '@/shared/config/routes';
import { ContextBar } from './ContextBar';

/** Shell của Observatory: thanh bối cảnh (universe + tick + trạng thái) + nội dung. */
export function WorkspaceLayout({ children }: { children: ReactNode }) {
  return (
    <div className="flex min-h-screen flex-col bg-[var(--color-bg-base)] text-[var(--color-text-primary)]">
      <header className="flex items-center justify-between gap-4 border-b border-[var(--border-subtle)] px-4 py-2">
        <ContextBar />
        <Link
          href={routes.multiverse()}
          className="shrink-0 text-sm text-[var(--color-text-secondary)] hover:text-[var(--color-primary)]"
        >
          Đa vũ trụ
        </Link>
      </header>
      <main className="min-h-0 flex-1 p-4">{children}</main>
    </div>
  );
}
