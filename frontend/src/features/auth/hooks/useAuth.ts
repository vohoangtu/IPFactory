'use client';
import { useState, useCallback, useEffect } from 'react';
import { apiClient, TOKEN_KEY } from '@/shared/lib/apiClient';

export function useAuth() {
  const [token, setToken] = useState<string | null>(null);
  useEffect(() => { setToken(localStorage.getItem(TOKEN_KEY)); }, []);

  const login = useCallback(async (email: string, password: string) => {
    const res = await apiClient.post('/auth/login', { email, password });
    const t = (res.data as { token: string }).token;
    localStorage.setItem(TOKEN_KEY, t);
    setToken(t);
  }, []);

  const logout = useCallback(() => { localStorage.removeItem(TOKEN_KEY); setToken(null); }, []);

  return { token, isAuthenticated: !!token, login, logout };
}
