'use client';

import React from 'react';

interface RuntimeCardProps {
  title: string;
  description: string;
  icon: React.ReactNode;
  children: React.ReactNode;
}

export default function RuntimeCard({ title, description, icon, children }: RuntimeCardProps) {
  return (
    <div className="rounded-lg border border-border-subtle bg-bg-surface p-6">
      <div className="mb-6 flex items-center gap-3">
        <div className="rounded-lg bg-brand-info/10 p-3 text-brand-info">{icon}</div>
        <div>
          <h2 className="text-lg font-black text-text-primary">{title}</h2>
          <p className="text-xs text-text-muted">{description}</p>
        </div>
      </div>
      {children}
    </div>
  );
}
