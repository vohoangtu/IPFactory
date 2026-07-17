'use client';

import React from 'react';

interface ConfigCardProps {
  title: string;
  description: string;
  icon: React.ReactNode;
  children: React.ReactNode;
}

export default function ConfigCard({ title, description, icon, children }: ConfigCardProps) {
  return (
    <div className="rounded-lg border border-border-subtle bg-bg-surface p-6">
      <div className="mb-5 flex items-center gap-4">
        <div className="rounded-lg bg-brand-info/10 p-3 text-brand-info">{icon}</div>
        <div>
          <h2 className="text-lg font-black text-text-primary">{title}</h2>
          <p className="text-xs text-text-muted">{description}</p>
        </div>
      </div>
      <div className="space-y-4">{children}</div>
    </div>
  );
}
