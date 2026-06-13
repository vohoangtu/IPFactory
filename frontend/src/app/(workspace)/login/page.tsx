'use client';
import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/features/auth';
import { Button } from '@/shared/ui/Button';

export default function LoginPage() {
  const { login } = useAuth();
  const router = useRouter();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    try { await login(email, password); router.replace('/multiverse'); }
    catch { setError('Đăng nhập thất bại'); }
  };
  return (
    <main className="flex h-screen items-center justify-center bg-[#0a0a0f] text-gray-200">
      <form onSubmit={submit} className="flex w-80 flex-col gap-3 rounded-xl border border-white/10 bg-black/40 p-6">
        <h1 className="text-lg font-bold">WorldOS</h1>
        <input className="rounded border border-white/15 bg-black/40 px-3 py-2 text-sm" placeholder="Email" value={email} onChange={(e) => setEmail(e.target.value)} />
        <input type="password" className="rounded border border-white/15 bg-black/40 px-3 py-2 text-sm" placeholder="Mật khẩu" value={password} onChange={(e) => setPassword(e.target.value)} />
        {error && <p className="text-xs text-rose-400">{error}</p>}
        <Button type="submit">Đăng nhập</Button>
      </form>
    </main>
  );
}
