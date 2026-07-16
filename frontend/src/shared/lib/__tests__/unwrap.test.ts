import { describe, expect, it } from 'vitest';
import { takeData } from '../unwrap';

describe('takeData', () => {
  it('bóc {data} khi chỉ có data + meta/links', () => {
    expect(takeData<{ x: number }[]>({ data: [{ x: 1 }], meta: { total: 1 } })).toEqual([{ x: 1 }]);
    expect(takeData<number[]>({ data: [1, 2] })).toEqual([1, 2]);
  });
  it('giữ nguyên body không có wrapper', () => {
    expect(takeData<number[]>([1, 2])).toEqual([1, 2]);
    expect(takeData<{ a: 1; b: 2 }>({ a: 1, b: 2 })).toEqual({ a: 1, b: 2 });
  });
  it('KHÔNG bóc khi có key khác ngoài data/meta/links', () => {
    expect(takeData<object>({ data: [1], extra: true })).toEqual({ data: [1], extra: true });
  });
});
