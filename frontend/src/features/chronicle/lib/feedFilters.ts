export const FEED_FILTERS = [
  { key: 'narrative', label: 'Tường thuật', types: ['chronicle', 'artifact.discovered', 'celebrity.emerged', 'history.shifted'] },
  { key: 'epoch', label: 'Kỷ nguyên', types: ['epoch.transitioned'] },
  { key: 'anomaly', label: 'Dị thường', types: ['anomaly.detected'] },
  { key: 'autopoiesis', label: 'Tự biến đổi', types: ['autopoiesis.mutation'] },
] as const;

export type FeedFilterKey = (typeof FEED_FILTERS)[number]['key'];

/** Hợp các nhóm active thành danh sách type gửi BE; rỗng = không lọc (tất cả). */
export function typesForFilters(activeKeys: string[]): string[] {
  return FEED_FILTERS.filter((f) => activeKeys.includes(f.key)).flatMap((f) => [...f.types]);
}
