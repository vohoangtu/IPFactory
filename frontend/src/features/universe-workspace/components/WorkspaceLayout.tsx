import type { ReactNode } from 'react';
import { ContextBar } from './ContextBar';

export function WorkspaceLayout({ children }: { children: ReactNode }) {
  return (
    <div className="flex h-screen flex-col bg-[#0a0a0f] text-gray-200">
      <ContextBar />
      <div className="flex flex-1 overflow-hidden">
        <main className="flex-1 overflow-auto p-4">{children}</main>
      </div>
    </div>
  );
}
