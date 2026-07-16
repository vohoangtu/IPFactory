'use client';
import { type ReactNode, useState } from 'react';
import { QueryClientProvider } from '@tanstack/react-query';
import { makeQueryClient } from '@/shared/lib/queryClient';
import { AuthGate } from '@/features/auth';

export default function WorkspaceRootLayout({ children }: { children: ReactNode }) {
  const [qc] = useState(makeQueryClient);
  return (
    <QueryClientProvider client={qc}>
      <AuthGate>{children}</AuthGate>
    </QueryClientProvider>
  );
}
