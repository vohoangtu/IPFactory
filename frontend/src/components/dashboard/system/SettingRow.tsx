'use client';

import React from 'react';

interface SettingRowProps {
  label: string;
  detail?: string;
  children: React.ReactNode;
}

export default function SettingRow({ label, detail, children }: SettingRowProps) {
  return (
    <div className="space-y-2">
      <div className="flex items-center justify-between">
        <span className="text-[10px] font-black uppercase tracking-[0.2em] text-text-muted">{label}</span>
        {detail ? <span className="text-[10px] font-bold text-text-disabled">{detail}</span> : null}
      </div>
      {children}
    </div>
  );
}
