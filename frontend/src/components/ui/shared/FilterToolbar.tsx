'use client';

import { Search } from 'lucide-react';

export interface FilterOption {
  label: string;
  value: string;
}

interface FilterToolbarProps {
  searchValue: string;
  onSearchChange: (value: string) => void;
  searchPlaceholder?: string;
  filters?: {
    label: string;
    value: string;
    options: FilterOption[];
    onChange: (value: string) => void;
  }[];
}

export default function FilterToolbar({
  searchValue,
  onSearchChange,
  searchPlaceholder = 'Search...',
  filters = [],
}: FilterToolbarProps) {
  return (
    <div className="flex flex-wrap items-center gap-3 mb-6">
      <div className="relative flex-1 min-w-[200px]">
        <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-text-disabled" />
        <input
          type="text"
          value={searchValue}
          onChange={(e) => onSearchChange(e.target.value)}
          placeholder={searchPlaceholder}
          className="w-full rounded-xl border border-border-subtle bg-bg-base/60 pl-9 pr-4 py-2.5 text-sm text-text-secondary placeholder:text-text-disabled outline-none transition focus:border-brand-primary/40"
        />
      </div>
      {filters.map((filter) => (
        <select
          key={filter.label}
          value={filter.value}
          onChange={(e) => filter.onChange(e.target.value)}
          className="rounded-xl border border-border-subtle bg-bg-base/60 px-3 py-2.5 text-sm text-text-secondary outline-none transition focus:border-brand-primary/40"
        >
          <option value="">{filter.label}</option>
          {filter.options.map((opt) => (
            <option key={opt.value} value={opt.value}>
              {opt.label}
            </option>
          ))}
        </select>
      ))}
    </div>
  );
}
