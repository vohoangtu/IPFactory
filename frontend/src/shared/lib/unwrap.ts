/**
 * Bóc wrapper Laravel {data, meta?, links?} — parity với interceptor của client cũ (@/lib/api).
 * apiClient chỉ tự bóc body 1-key {data}; endpoint list cũ có thể kèm meta/links → dùng helper này.
 */
export function takeData<T>(body: unknown): T {
  if (body && typeof body === 'object' && !Array.isArray(body) && 'data' in body) {
    const rest = Object.keys(body).filter((k) => k !== 'meta' && k !== 'links');
    if (rest.length === 1 && rest[0] === 'data') return (body as { data: T }).data;
  }
  return body as T;
}
