import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

/**
 * Merge Tailwind CSS class names safely.
 * Combines clsx (conditional logic) + tailwind-merge (conflict resolution).
 */
export function cn(...inputs: ClassValue[]): string {
  return twMerge(clsx(inputs));
}

/**
 * Format a number with fixed decimal places, falling back to a default string.
 */
export function formatMetric(value: number | undefined | null, digits = 3): string {
  if (typeof value === 'number' && Number.isFinite(value)) {
    return value.toFixed(digits);
  }
  return '0.' + '0'.repeat(digits);
}

/**
 * Convert snake_case / kebab-case to Title Case sentence.
 */
export function sentenceCase(value: string | undefined | null): string {
  if (!value) return 'Unknown';
  return value
    .replace(/_/g, ' ')
    .replace(/-/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase());
}

/**
 * Guard: cast unknown value to a Record if it is an object (not array/null).
 */
export function getRecord<T = Record<string, unknown>>(
  value: unknown,
): T {
  return value && typeof value === 'object' && !Array.isArray(value)
    ? (value as T)
    : ({} as T);
}

/**
 * Get [key, number] entries from an unknown object value.
 */
export function getEntries(value: unknown): Array<[string, number]> {
  return Object.entries(getRecord(value)).map(([key, count]) => [
    key,
    Number(count ?? 0),
  ]);
}
