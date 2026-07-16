'use client';
import { ScrollText } from 'lucide-react';
import type { FeedItem } from '@/shared/realtime/envelope';
import { ChronicleEntry } from './ChronicleEntry';

interface Props {
  items: FeedItem[];
  hasOlder: boolean;
  isLoadingOlder: boolean;
  onLoadOlder: () => void;
}

export function ChronicleStream({ items, hasOlder, isLoadingOlder, onLoadOlder }: Props) {
  if (items.length === 0) {
    return (
      <div className="flex h-full flex-col items-center justify-center gap-3 p-8 text-center">
        <span
          className="flex h-11 w-11 items-center justify-center rounded-full"
          style={{ background: 'rgba(255, 255, 255, 0.04)', border: '1px solid var(--border-muted)' }}
        >
          <ScrollText size={18} strokeWidth={1.5} className="text-[var(--color-text-disabled)]" />
        </span>
        <p className="max-w-xs text-sm text-[var(--color-text-muted)]">
          Vũ trụ chưa có biến cố nào — hãy chạy tick để lịch sử bắt đầu.
        </p>
      </div>
    );
  }

  return (
    <div className="custom-scrollbar flex h-full flex-col gap-2 overflow-y-auto pr-1">
      {items.map((item) => (
        <ChronicleEntry key={item.id} item={item} />
      ))}
      {hasOlder && (
        <button
          type="button"
          onClick={onLoadOlder}
          disabled={isLoadingOlder}
          className="mx-auto my-3 rounded-full border border-[var(--border-subtle)] px-4 py-1.5 text-sm text-[var(--color-text-secondary)] transition-colors duration-200 hover:border-[var(--color-primary)] hover:text-[var(--color-text-primary)] disabled:cursor-not-allowed disabled:opacity-50"
        >
          {isLoadingOlder ? 'Đang lật trang sử…' : 'Tải thêm quá khứ'}
        </button>
      )}
    </div>
  );
}
