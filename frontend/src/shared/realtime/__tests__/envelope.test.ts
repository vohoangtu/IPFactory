import { describe, expect, it } from 'vitest';
import { parseEnvelope, envelopeToFeedItem } from '../envelope';

const valid = {
  id: 'uuid-1', type: 'epoch.transitioned', tick: 120, universe_id: 5,
  world_id: 3, severity: 'notable', occurred_at: '2026-07-15T00:00:00+00:00',
  payload: { old_epoch: { name: 'Bronze' } },
};

describe('parseEnvelope', () => {
  it('chấp nhận envelope hợp lệ và giữ nguyên field', () => {
    const env = parseEnvelope(valid);
    expect(env).not.toBeNull();
    expect(env!.type).toBe('epoch.transitioned');
    expect(env!.tick).toBe(120);
    expect(env!.world_id).toBe(3);
    expect(env!.severity).toBe('notable');
    expect(env!.payload).toEqual({ old_epoch: { name: 'Bronze' } });
  });

  it('trả null khi thiếu field bắt buộc hoặc sai kiểu', () => {
    expect(parseEnvelope(null)).toBeNull();
    expect(parseEnvelope('x')).toBeNull();
    expect(parseEnvelope({ ...valid, id: 7 })).toBeNull();
    expect(parseEnvelope({ ...valid, tick: 'abc' })).toBeNull();
    expect(parseEnvelope({ ...valid, universe_id: undefined })).toBeNull();
  });

  it('chuẩn hóa field lỏng: severity lạ → info, world_id thiếu → null, payload thiếu → {}', () => {
    const env = parseEnvelope({ ...valid, severity: 'WEIRD', world_id: undefined, payload: undefined });
    expect(env!.severity).toBe('info');
    expect(env!.world_id).toBeNull();
    expect(env!.payload).toEqual({});
  });
});

describe('envelopeToFeedItem', () => {
  it('chuyển envelope thành FeedItem kind=event', () => {
    const item = envelopeToFeedItem(parseEnvelope(valid)!);
    expect(item).toEqual({
      id: 'uuid-1', kind: 'event', type: 'epoch.transitioned', tick: 120,
      universe_id: 5, severity: 'notable',
      occurred_at: '2026-07-15T00:00:00+00:00', payload: { old_epoch: { name: 'Bronze' } },
    });
  });
});
