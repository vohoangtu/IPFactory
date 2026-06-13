import type { ReactNode } from 'react';
import { ContextBar } from './ContextBar';
import { ModeSwitcher } from './ModeSwitcher';

export function WorkspaceLayout({ children }: { children: ReactNode }) {
  return (
    <div className="flex h-screen flex-col bg-[#0a0a0f] text-gray-200">
      <ContextBar />
      <div className="flex flex-1 overflow-hidden">
        <ModeSwitcher />
        <main className="flex-1 overflow-auto p-4">{children}</main>
      </div>
    </div>
  );
}
