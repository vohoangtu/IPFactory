import Link from 'next/link';
import { routes } from '@/shared/config/routes';

const ENTRIES = [
  { href: routes.multiverse(), title: 'Đài quan sát', desc: 'Chòm sao đa vũ trụ, Living Chronicle, các lens quan sát nền văn minh.' },
  { href: routes.opsSimulation(), title: 'Vận hành', desc: 'Điều khiển mô phỏng, Narrative Loom, AI runtime, cấu hình hệ thống.' },
];

export default function HomePage() {
  return (
    <main className="flex min-h-screen flex-col items-center justify-center gap-8 bg-[var(--color-bg-base)] p-8 text-[var(--color-text-primary)]">
      <div className="text-center">
        <h1 className="text-glow-cyan font-mono text-2xl uppercase tracking-[0.4em]">WorldOS</h1>
        <p className="mt-2 text-sm text-[var(--color-text-muted)]">Đài quan sát vũ trụ sống</p>
      </div>
      <div className="grid w-full max-w-2xl grid-cols-1 gap-4 sm:grid-cols-2">
        {ENTRIES.map((e) => (
          <Link
            key={e.href}
            href={e.href}
            className="glass rounded-2xl border border-[var(--border-subtle)] p-6 transition duration-200 ease-out hover:-translate-y-0.5 hover:border-[var(--color-primary)] hover:shadow-[var(--shadow-glow-cyan)]"
          >
            <h2 className="font-medium">{e.title}</h2>
            <p className="mt-1 text-sm text-[var(--color-text-muted)]">{e.desc}</p>
          </Link>
        ))}
      </div>
    </main>
  );
}
