import type { AiLog } from '@/shared/types/api';

/**
 * Extract a string value from a potentially nested object or return the raw string.
 */
export function extractString(value: unknown): string | null {
    return typeof value === 'string' && value.trim() ? value.trim() : null;
}

/**
 * Resolve the display name for a log model identifier.
 */
export function resolveLogModel(log: AiLog): string {
    const input = log.input && typeof log.input === 'object' && !Array.isArray(log.input)
        ? log.input as Record<string, unknown>
        : null;

    return (
        extractString(log.model) ??
        extractString(input?.model_name) ??
        extractString(input?.model) ??
        'unknown-model'
    );
}
