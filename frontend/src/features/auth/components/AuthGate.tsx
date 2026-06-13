'use client';
import { type ReactNode, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '../hooks/useAuth';
import { routes } from '@/shared/config/routes';

export function AuthGate({ children }: { children: ReactNode }) {
  const { isAuthenticated } = useAuth();
  const router = useRouter();
  useEffect(() => { if (!isAuthenticated) router.replace(routes.login()); }, [isAuthenticated, router]);
  if (!isAuthenticated) return null;
  return <>{children}</>;
}
