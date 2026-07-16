'use client';
import { useEffect, useMemo } from 'react';
import { useParams } from 'next/navigation';
import { AlertTriangle } from 'lucide-react';
import { WorkspaceLayout } from '@/features/universe-workspace';
import { ChronicleStream, MetricsSparkline, useChronicleFeed } from '@/features/chronicle';
import { useUniverseChannels } from '@/shared/realtime/useUniverseChannels';
import { useSimStore, type SimStore } from '@/shared/store/simStore';
import { useFeedStore } from '@/shared/store/feedStore';
import { Panel } from '@/shared/ui/Panel';

const CONNECTION_META: Record<SimStore['connection'], { label: string; color: string; pulse: boolean }> = {
  connecting: { label: 'Đang kết nối…', color: 'var(--color-amber)', pulse: true },
  connected: { label: 'Trực tuyến', color: 'var(--color-emerald)', pulse: true },
  disconnected: { label: 'Mất kết nối', color: 'var(--color-danger)', pulse: false },
};

function formatMetric(value: number | null | undefined): string {
  return value == null ? '—' : value.toFixed(2);
}

export default function UniverseHeroPage() {
  const params = useParams<{ id: string }>();
  const universeId = useMemo(() => {
    const n = Number(params?.id);
    return Number.isFinite(n) ? n : null;
  }, [params?.id]);

  const selectUniverse = useSimStore((s) => s.selectUniverse);
  const selectedUniverseId = useSimStore((s) => s.selectedUniverseId);
  const connection = useSimStore((s) => s.connection);
  const history = useSimStore((s) => s.live.history);
  const clearFeed = useFeedStore((s) => s.clear);

  useEffect(() => {
    if (universeId != null && selectedUniverseId !== universeId) {
      clearFeed();
      selectUniverse(universeId);
    }
  }, [universeId, selectedUniverseId, selectUniverse, clearFeed]);

  const feed = useChronicleFeed(universeId);
  useUniverseChannels(universeId, { onLiveGap: feed.refetchLatest });

  const latest = history.length > 0 ? history[history.length - 1] : null;
  const connMeta = CONNECTION_META[connection];

  return (
    <WorkspaceLayout>
      {feed.isError && (
        <div
          className="mb-3 flex items-center gap-2 rounded-lg border border-[var(--color-amber)] bg-[var(--bg-elevated)] px-3 py-2 text-sm text-[var(--color-amber)]"
          role="alert"
        >
          <AlertTriangle size={15} strokeWidth={1.75} className="shrink-0" />
          Chế độ suy giảm: không tải được lịch sử — chỉ hiển thị sự kiện realtime.
        </div>
      )}
      <div className="grid h-full min-h-0 grid-cols-1 gap-4 lg:grid-cols-3">
        <section className="min-h-0 lg:col-span-2" aria-label="Dòng biên niên sử">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="font-mono text-xs uppercase tracking-[0.3em] text-[var(--color-text-muted)]">
              Biên niên sử
            </h2>
            {feed.items.length > 0 && (
              <span className="font-mono text-[11px] tabular-nums text-[var(--color-text-disabled)]">
                {feed.items.length} mục
              </span>
            )}
          </div>
          <ChronicleStream
            items={feed.items}
            hasOlder={feed.hasOlder}
            isLoadingOlder={feed.isLoadingOlder}
            onLoadOlder={feed.fetchOlder}
          />
        </section>
        <aside className="flex flex-col gap-4">
          <Panel title="Nhịp đập vũ trụ">
            <MetricsSparkline history={history} />
            {latest && (
              <div className="mt-3 flex items-center gap-4 font-mono text-[11px] text-[var(--color-text-muted)]">
                <span className="flex items-center gap-1.5">
                  <span className="h-1.5 w-1.5 rounded-full" style={{ background: 'var(--color-primary)' }} />
                  Stability {formatMetric(latest.stability)}
                </span>
                <span className="flex items-center gap-1.5">
                  <span className="h-1.5 w-1.5 rounded-full" style={{ background: 'var(--color-danger)' }} />
                  Entropy {formatMetric(latest.entropy)}
                </span>
              </div>
            )}
          </Panel>
          <Panel title="Kết nối">
            <div className="flex items-center gap-2">
              <span
                className={`h-2 w-2 shrink-0 rounded-full ${connMeta.pulse ? 'animate-pulse' : ''}`}
                style={{ background: connMeta.color }}
                aria-hidden="true"
              />
              <p className="font-mono text-sm text-[var(--color-text-secondary)]">{connMeta.label}</p>
            </div>
          </Panel>
        </aside>
      </div>
    </WorkspaceLayout>
  );
}
