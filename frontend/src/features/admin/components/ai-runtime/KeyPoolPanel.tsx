'use client';

import React from 'react';
import { Database, Plus } from 'lucide-react';
import RuntimeCard from './RuntimeCard';
import KeyTable from '../key-pool/KeyTable';
import StatsOverview from '../key-pool/StatsOverview';
import type { AiKey } from '../../types';

interface KeyPoolPanelProps {
  keys: AiKey[];
  onAdd: () => void;
  onEdit: (key: AiKey) => void;
  onDelete: (id: number) => void;
}

export default function KeyPoolPanel({ keys, onAdd, onEdit, onDelete }: KeyPoolPanelProps) {
  return (
    <div className="space-y-6">
      <RuntimeCard
        title="Key Pool"
        description="Manage AI key pool entries used by pool-first routing"
        icon={<Database size={18} />}
      >
        <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
          <button
            onClick={onAdd}
            className="inline-flex items-center gap-2 rounded-md bg-white px-4 py-3 text-sm font-black text-black transition hover:bg-brand-info/80"
          >
            <Plus size={16} />
            Add Key
          </button>
        </div>

        <StatsOverview keys={keys} />
        <KeyTable keys={keys} onEdit={onEdit} onDelete={onDelete} />
      </RuntimeCard>

      <div className="rounded-lg border border-emerald-500/10 bg-emerald-500/5 p-6">
        <h3 className="mb-2 text-lg font-black text-text-primary">Pool-first note</h3>
        <p className="text-sm leading-relaxed text-text-secondary">
          The runtime prefers explicit pool behavior. If no suitable key exists, the
          request fails clearly instead of silently falling back to legacy fixed
          credentials.
        </p>
      </div>
    </div>
  );
}
