'use client';
import { useState, useCallback } from 'react';
import { apiClient, TOKEN_KEY } from '@/shared/lib/apiClient';

export function useAuth() {
  const [token, setToken] = useState<string | null>(
    () => (typeof window !== 'undefined' ? localStorage.getItem(TOKEN_KEY) : null),
  );

  const login = useCallback(async (email: string, password: string) => {
    const res = await apiClient.post('/auth/login', { email, password });
    const t = (res.data as { access_token: string }).access_token;
    localStorage.setItem(TOKEN_KEY, t);
    setToken(t);
  }, []);

  const logout = useCallback(() => { localStorage.removeItem(TOKEN_KEY); setToken(null); }, []);

  return { token, isAuthenticated: !!token, login, logout };
}
