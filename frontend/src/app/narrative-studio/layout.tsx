import React from 'react';
import DashboardShell from '@/components/dashboard/DashboardShell';

export const dynamic = 'force-dynamic';

export default function NarrativeStudioLayout({ children }: { children: React.ReactNode }) {
    return <DashboardShell>{children}</DashboardShell>;
}
