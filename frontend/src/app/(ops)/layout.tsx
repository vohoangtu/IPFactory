'use client';
import { type ReactNode, useState } from 'react';
import { QueryClientProvider } from '@tanstack/react-query';
import { makeQueryClient } from '@/shared/lib/queryClient';
import { AuthGate } from '@/features/auth';
import { OpsShell } from '@/features/ops-shell';

export default function OpsRootLayout({ children }: { children: ReactNode }) {
  const [qc] = useState(makeQueryClient);
  return (
    <QueryClientProvider client={qc}>
      <AuthGate>
        <OpsShell>{children}</OpsShell>
      </AuthGate>
    </QueryClientProvider>
  );
}
