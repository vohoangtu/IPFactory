'use client';

import React, { createContext, useContext, useState, useCallback, useMemo } from 'react';
import { useUniverseOptions } from '@/features/universe/hooks';
import type { UniverseOption } from '@/types/api';

interface UniverseContextValue {
  universes: UniverseOption[];
  selectedUniverseId: number | null;
  activeUniverseId: number | null;
  setSelectedUniverseId: (id: number | null) => void;
  isLoading: boolean;
}

const UniverseContext = createContext<UniverseContextValue | null>(null);

export function UniverseProvider({ children }: { children: React.ReactNode }) {
  const { universes, isLoading } = useUniverseOptions();
  const [selectedUniverseId, setSelectedUniverseId] = useState<number | null>(null);

  const activeUniverseId = useMemo(
    () => selectedUniverseId ?? universes[0]?.id ?? null,
    [selectedUniverseId, universes],
  );

  const handleSetSelected = useCallback((id: number | null) => {
    setSelectedUniverseId(id);
  }, []);

  const value = useMemo<UniverseContextValue>(
    () => ({
      universes,
      selectedUniverseId,
      activeUniverseId,
      setSelectedUniverseId: handleSetSelected,
      isLoading,
    }),
    [universes, selectedUniverseId, activeUniverseId, handleSetSelected, isLoading],
  );

  return <UniverseContext.Provider value={value}>{children}</UniverseContext.Provider>;
}

export function useUniverse(): UniverseContextValue {
  const ctx = useContext(UniverseContext);
  if (!ctx) throw new Error('useUniverse must be used within a UniverseProvider');
  return ctx;
}
