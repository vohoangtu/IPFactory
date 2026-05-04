'use client';

import { cn } from '@/lib/utils';

export interface TabItem {
  id: string;
  label: string;
  icon?: React.ReactNode;
}

interface TabBarProps {
  tabs: TabItem[];
  activeTab: string;
  onChange: (id: string) => void;
  className?: string;
}

export default function TabBar({ tabs, activeTab, onChange, className }: TabBarProps) {
  return (
    <div className={cn('flex border-b border-border-subtle', className)}>
      {tabs.map((tab) => (
        <button
          key={tab.id}
          onClick={() => onChange(tab.id)}
          className={cn(
            'flex items-center gap-1.5 border-b-2 px-4 py-2.5 text-sm font-medium transition',
            activeTab === tab.id
              ? 'border-brand-info text-brand-info'
              : 'border-transparent text-text-disabled hover:text-text-secondary',
          )}
        >
          {tab.icon}
          {tab.label}
        </button>
      ))}
    </div>
  );
}
