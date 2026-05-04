'use client';

import { useEffect, useCallback } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { X } from 'lucide-react';
import { cn } from '@/lib/utils';

interface ModalShellProps {
  open: boolean;
  onClose: () => void;
  title?: string;
  subtitle?: string;
  children: React.ReactNode;
  maxWidth?: string;
  className?: string;
}

export default function ModalShell({
  open,
  onClose,
  title,
  subtitle,
  children,
  maxWidth = 'max-w-4xl',
  className,
}: ModalShellProps) {
  const handleEsc = useCallback(
    (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose();
    },
    [onClose],
  );

  useEffect(() => {
    if (open) {
      document.addEventListener('keydown', handleEsc);
      document.body.style.overflow = 'hidden';
    }
    return () => {
      document.removeEventListener('keydown', handleEsc);
      document.body.style.overflow = '';
    };
  }, [open, handleEsc]);

  return (
    <AnimatePresence>
      {open && (
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.2 }}
          className="fixed inset-0 z-[var(--z-modal)] flex items-center justify-center p-4"
          role="dialog"
          aria-modal="true"
          aria-label={title ?? 'Modal'}
        >
          {/* Backdrop */}
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="absolute inset-0 bg-black/75 backdrop-blur-md"
            onClick={onClose}
          />

          {/* Panel */}
          <motion.div
            initial={{ opacity: 0, scale: 0.96, y: 24 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.96, y: 24 }}
            transition={{ duration: 0.22, ease: [0.16, 1, 0.3, 1] }}
            className={cn(
              'relative w-full max-h-[88vh] overflow-y-auto',
              'rounded-2xl border border-border-subtle glass p-8 shadow-2xl',
              'custom-scrollbar',
              maxWidth,
              className,
            )}
          >
            {/* Close button */}
            <button
              onClick={onClose}
              id="modal-close-btn"
              aria-label="Close modal"
              className="absolute right-5 top-5 z-10 flex h-8 w-8 items-center justify-center rounded-xl border border-border-muted bg-bg-elevated/60 text-text-muted transition hover:bg-bg-overlay hover:text-text-primary"
            >
              <X size={15} />
            </button>

            {/* Header */}
            {(title || subtitle) && (
              <div className="mb-6 pr-12">
                {title && (
                  <h2 className="text-xl font-bold tracking-tight text-text-primary">{title}</h2>
                )}
                {subtitle && (
                  <p className="mt-1 text-sm text-text-muted">{subtitle}</p>
                )}
              </div>
            )}

            {children}
          </motion.div>
        </motion.div>
      )}
    </AnimatePresence>
  );
}
