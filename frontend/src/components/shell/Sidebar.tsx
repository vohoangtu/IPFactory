'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import {
  LayoutDashboard,
  Zap,
  Map,
  Users,
  Play,
  Radio,
  Globe,
  Shield,
  ChevronDown,
  Cpu,
  Sparkles,
  Trophy,
  Clock,
} from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';
import { useUniverse } from '@/contexts/UniverseContext';
import { cn } from '@/lib/utils';

// ── Types ────────────────────────────────────────────────────────────────

interface NavItem {
  icon: React.ComponentType<{ size?: number; className?: string }>;
  label: string;
  href: string;
}

interface NavSection {
  label: string;
  items: NavItem[];
}

// ── Data ─────────────────────────────────────────────────────────────────

const NAV_SECTIONS: NavSection[] = [
  {
    label: 'Overview',
    items: [
      { icon: LayoutDashboard, label: 'Dossier Console', href: '/dashboard' },
      { icon: Globe, label: 'Multiverse Map', href: '/dashboard/multiverse' },
    ],
  },
  {
    label: 'Progression',
    items: [
      { icon: Clock, label: 'World Timeline', href: '/dashboard/timeline' },
      { icon: Trophy, label: 'Achievements', href: '/dashboard/achievements' },
    ],
  },
  {
    label: 'Simulation',
    items: [
      { icon: Play, label: 'Control Panel', href: '/dashboard/simulation' },
      { icon: Users, label: 'Actor Registry', href: '/dashboard/actors' },
      { icon: Map, label: 'Causal Map', href: '/dashboard/causal-map' },
    ],
  },
  {
    label: 'Monitoring',
    items: [
      { icon: Sparkles, label: 'Loom Workshop', href: '/dashboard/loom-workshop' },
      { icon: Radio, label: 'Loom Monitor', href: '/dashboard/loom-monitor' },
      { icon: Radio, label: 'Wavefunction', href: '/dashboard/wavefunction' },
      { icon: Zap, label: 'Narrative Monitor', href: '/dashboard/intelligence/monitor' },
    ],
  },
  {
    label: 'System',
    items: [
      { icon: Shield, label: 'System', href: '/dashboard/system' },
      { icon: Cpu, label: 'AI Runtime', href: '/dashboard/ai-runtime' },
    ],
  },
];

// ── Sub-components ────────────────────────────────────────────────────────

function NavItem({ icon: Icon, label, href, active }: NavItem & { active: boolean }) {
  return (
    <Link href={href}>
      <motion.div
        whileHover={{ x: 4 }}
        whileTap={{ scale: 0.97 }}
        className={cn(
          'group flex cursor-pointer items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-150',
          active
            ? 'bg-brand-primary/10 text-brand-primary ring-1 ring-inset ring-brand-primary/20'
            : 'text-text-muted hover:bg-bg-elevated/50 hover:text-text-primary',
        )}
      >
        <Icon
          size={16}
          className={cn(
            'shrink-0 transition-colors',
            active ? 'text-brand-primary' : 'group-hover:text-text-secondary',
          )}
        />
        <span className="truncate">{label}</span>
        {active && (
          <motion.div
            layoutId="nav-active-dot"
            className="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-brand-primary shadow-glow-cyan"
          />
        )}
      </motion.div>
    </Link>
  );
}

function SectionLabel({ children }: { children: React.ReactNode }) {
  return (
    <p className="mb-1.5 mt-6 px-3.5 text-[9px] font-black uppercase tracking-[0.3em] text-slate-600 first:mt-2">
      {children}
    </p>
  );
}

// ── Universe selector ─────────────────────────────────────────────────────

function UniverseSelector() {
  const { universes, activeUniverseId, setSelectedUniverseId } = useUniverse();
  const [isOpen, setIsOpen] = useState(false);
  const active = universes.find((u) => u.id === activeUniverseId);
  const label = active?.name ?? `Universe ${active?.id ?? '—'}`;
  const tick = active?.current_tick ?? 0;

  return (
    <div className="relative px-3">
      <button
        id="universe-selector-btn"
        onClick={() => setIsOpen((v) => !v)}
        className={cn(
          'flex w-full items-center justify-between gap-2 rounded-xl border px-3 py-2.5 text-left transition-all duration-200',
          isOpen
            ? 'border-cyan-500/30 bg-cyan-500/5'
            : 'border-[var(--border-subtle)] bg-[var(--bg-elevated)] hover:border-cyan-500/20',
        )}
      >
        <div className="min-w-0">
          <p className="truncate text-xs font-semibold text-text-secondary">{label}</p>
          <p className="font-mono text-[10px] text-brand-primary">Tick {tick.toLocaleString()}</p>
        </div>
        <ChevronDown
          size={13}
          className={cn('shrink-0 text-text-disabled transition-transform', isOpen && 'rotate-180')}
        />
      </button>

      <AnimatePresence>
        {isOpen && (
          <motion.div
            initial={{ opacity: 0, y: -4, scaleY: 0.95 }}
            animate={{ opacity: 1, y: 0, scaleY: 1 }}
            exit={{ opacity: 0, y: -4, scaleY: 0.95 }}
            transition={{ duration: 0.15 }}
            className="absolute left-3 right-3 top-full z-50 mt-1.5 max-h-48 overflow-y-auto rounded-xl border border-[var(--border-muted)] bg-[var(--bg-base)] shadow-2xl custom-scrollbar"
          >
            {universes.map((u) => (
              <button
                key={u.id}
                onClick={() => {
                  setSelectedUniverseId(u.id);
                  setIsOpen(false);
                }}
                className={cn(
                  'flex w-full items-center justify-between px-3.5 py-2.5 text-sm transition hover:bg-slate-800/50',
                  u.id === activeUniverseId ? 'text-brand-primary' : 'text-text-muted',
                )}
              >
                <span className="font-medium">{u.name ?? `Universe ${u.id}`}</span>
                <span className="font-mono text-xs text-slate-600">
                  T{u.current_tick ?? 0}
                </span>
              </button>
            ))}
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}

// ── Main Sidebar export ───────────────────────────────────────────────────

interface SidebarProps {
  isOpen: boolean;
}

export default function Sidebar({ isOpen }: SidebarProps) {
  const pathname = usePathname();

  return (
    <motion.aside
      initial={false}
      animate={{ width: isOpen ? 256 : 0, opacity: isOpen ? 1 : 0 }}
      transition={{ duration: 0.25, ease: [0.16, 1, 0.3, 1] }}
      className="relative z-[var(--z-sidebar)] flex h-screen shrink-0 flex-col overflow-hidden border-r border-[var(--border-subtle)] bg-[var(--bg-surface)]"
    >
      {/* Logo */}
      <div className="flex items-center gap-3 border-b border-[var(--border-subtle)] px-5 py-5">
        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-cyan-600 to-violet-600 shadow-lg shadow-cyan-900/30">
          <Zap size={18} className="fill-white text-white" />
        </div>
        <div>
          <p className="text-sm font-bold tracking-tight text-text-primary">WorldOS</p>
          <p className="font-mono text-[9px] uppercase tracking-widest text-cyan-500">
            V6 Simulation
          </p>
        </div>
      </div>

      {/* Universe picker */}
      <div className="border-b border-[var(--border-subtle)] py-3">
        <UniverseSelector />
      </div>

      {/* Nav */}
      <nav className="flex-1 overflow-y-auto px-2 py-2 custom-scrollbar">
        {NAV_SECTIONS.map((section) => (
          <React.Fragment key={section.label}>
            <SectionLabel>{section.label}</SectionLabel>
            {section.items.map((item) => (
              <NavItem
                key={item.href}
                {...item}
                active={
                  item.href === '/dashboard'
                    ? pathname === '/dashboard'
                    : pathname.startsWith(item.href)
                }
              />
            ))}
          </React.Fragment>
        ))}
      </nav>

      {/* Status footer */}
      <div className="border-t border-[var(--border-subtle)] p-3">
        <div className="flex items-center gap-2.5 rounded-xl bg-[var(--bg-elevated)] px-3.5 py-3">
          <span className="relative flex h-2 w-2 shrink-0">
            <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60" />
            <span className="relative inline-flex h-2 w-2 rounded-full bg-emerald-500" />
          </span>
          <div>
            <p className="text-xs font-semibold text-text-secondary">Intelligence Active</p>
            <p className="text-[9px] text-text-disabled">All systems nominal</p>
          </div>
        </div>
      </div>
    </motion.aside>
  );
}
