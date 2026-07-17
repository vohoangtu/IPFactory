import { describe, expect, it } from 'vitest';
import { cn, formatMetric, sentenceCase, getRecord, getEntries } from '../utils';

describe('shared utils', () => {
  it('cn merge conflict tailwind', () => {
    expect(cn('p-2', 'p-4')).toBe('p-4');
    expect(cn('a', false && 'b')).toBe('a');
  });
  it('formatMetric fallback + fixed digits', () => {
    expect(formatMetric(0.12345)).toBe('0.123');
    expect(formatMetric(null)).toBe('0.000');
    expect(formatMetric(2, 1)).toBe('2.0');
  });
  it('sentenceCase snake/kebab', () => {
    expect(sentenceCase('great_filter')).toBe('Great Filter');
    expect(sentenceCase(null)).toBe('Unknown');
  });
  it('getRecord/getEntries guard', () => {
    expect(getRecord(null)).toEqual({});
    expect(getEntries({ a: 2, b: '3' })).toEqual([['a', 2], ['b', 3]]);
  });
});
