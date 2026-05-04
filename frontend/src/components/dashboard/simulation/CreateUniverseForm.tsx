'use client';

import { useState } from 'react';
import { Sparkles, RefreshCcw } from 'lucide-react';

import ModalShell from '@/components/ui/shared/ModalShell';
import { useCreateUniverse } from '@/features/simulation/hooks';

const GENRE_OPTIONS = [
  { value: 'fantasy', label: 'Fantasy' },
  { value: 'sci-fi', label: 'Sci-Fi' },
  { value: 'historical', label: 'Historical' },
  { value: 'cyberpunk', label: 'Cyberpunk' },
  { value: 'mythology', label: 'Mythology' },
];

interface CreateUniverseFormProps {
  open: boolean;
  onClose: () => void;
}

export default function CreateUniverseForm({
  open,
  onClose,
}: CreateUniverseFormProps) {
  const createMutation = useCreateUniverse();

  const [name, setName] = useState('');
  const [genre, setGenre] = useState('fantasy');

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!name.trim()) return;

    createMutation.mutate(
      { name: name.trim(), base_genre: genre },
      {
        onSuccess: () => {
          setName('');
          setGenre('fantasy');
          onClose();
        },
      },
    );
  };

  return (
    <ModalShell
      open={open}
      onClose={onClose}
      title="Create New Universe"
      maxWidth="max-w-lg"
    >
      <form onSubmit={handleSubmit} className="space-y-5">
        {/* Name */}
        <div>
          <label className="mb-1.5 block text-xs font-semibold text-slate-400">
            Universe Name <span className="text-rose-400">*</span>
          </label>
          <input
            type="text"
            value={name}
            onChange={(e) => setName(e.target.value)}
            placeholder="Enter a name for your universe..."
            required
            className="w-full rounded-xl border border-slate-800 bg-slate-900/60 px-4 py-3 text-sm text-white placeholder-slate-600 outline-none transition-colors focus:border-cyan-500/40 focus:ring-1 focus:ring-cyan-500/20"
          />
        </div>

        {/* Genre */}
        <div>
          <label className="mb-1.5 block text-xs font-semibold text-slate-400">
            Base Genre
          </label>
          <select
            value={genre}
            onChange={(e) => setGenre(e.target.value)}
            className="w-full appearance-none rounded-xl border border-slate-800 bg-slate-900/60 px-4 py-3 text-sm text-white outline-none transition-colors focus:border-cyan-500/40 focus:ring-1 focus:ring-cyan-500/20"
          >
            {GENRE_OPTIONS.map((opt) => (
              <option key={opt.value} value={opt.value}>
                {opt.label}
              </option>
            ))}
          </select>
        </div>

        {/* Submit */}
        <button
          type="submit"
          disabled={createMutation.isPending || !name.trim()}
          className="flex w-full items-center justify-center gap-2 rounded-xl border border-cyan-500/20 bg-cyan-500/10 px-4 py-3 text-sm font-bold text-cyan-200 transition-all hover:bg-cyan-500/20 disabled:cursor-not-allowed disabled:opacity-40"
        >
          {createMutation.isPending ? (
            <>
              <RefreshCcw size={16} className="animate-spin" />
              Creating...
            </>
          ) : (
            <>
              <Sparkles size={16} />
              Create Universe
            </>
          )}
        </button>
      </form>
    </ModalShell>
  );
}
