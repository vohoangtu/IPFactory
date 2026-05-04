'use client';

import { useState } from 'react';
import { Plus } from 'lucide-react';

import { useUniverse } from '@/contexts/UniverseContext';
import EmptyState from '@/components/ui/shared/EmptyState';
import PageHeader from '@/components/ui/shared/PageHeader';
import Button from '@/components/ui/shared/Button';
import TickAdvancePanel from '@/components/dashboard/simulation/TickAdvancePanel';
import UniverseStatusPanel from '@/components/dashboard/simulation/UniverseStatusPanel';
import SnapshotPanel from '@/components/dashboard/simulation/SnapshotPanel';
import ForkPanel from '@/components/dashboard/simulation/ForkPanel';
import CreateUniverseForm from '@/components/dashboard/simulation/CreateUniverseForm';

export default function SimulationPage() {
  const { activeUniverseId, isLoading } = useUniverse();
  const [showCreateModal, setShowCreateModal] = useState(false);

  return (
    <div className="space-y-6">
      <PageHeader
        title="Simulation Control Panel"
        subtitle="Manage tick advancement, snapshots, and universe branching."
        action={
          <Button variant="primary" size="sm" onClick={() => setShowCreateModal(true)}>
            <Plus size={15} />
            New Universe
          </Button>
        }
      />

      {/* Content */}
      {isLoading ? (
        <div className="flex items-center justify-center py-24 text-text-disabled">
          <div className="h-8 w-8 animate-spin rounded-full border-2 border-brand-info/20 border-t-brand-info" />
        </div>
      ) : !activeUniverseId ? (
        <EmptyState
          title="No universe selected"
          message="Create a universe or select one from the dashboard to begin."
        />
      ) : (
        <>
          {/* 2-Column Grid */}
          <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {/* Left Column */}
            <div className="space-y-6">
              <TickAdvancePanel />
              <UniverseStatusPanel />
            </div>

            {/* Right Column */}
            <div>
              <SnapshotPanel />
            </div>
          </div>

          {/* Full-Width Bottom */}
          <ForkPanel />
        </>
      )}

      {/* Create Universe Modal */}
      <CreateUniverseForm
        open={showCreateModal}
        onClose={() => setShowCreateModal(false)}
      />
    </div>
  );
}
