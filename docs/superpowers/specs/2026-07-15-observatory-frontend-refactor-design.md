# WorldOS Observatory — Tái thiết toàn bộ Frontend (Big-bang)

**Ngày:** 2026-07-15
**Trạng thái:** Đã duyệt thiết kế, chờ implementation plan
**Phạm vi:** Toàn bộ frontend + lớp hỗ trợ backend (realtime + read-API). Không đụng logic simulation.

## 1. Bối cảnh & vấn đề

Frontend hiện tại không khớp với nền tảng hạ tầng bên dưới:

1. **Hạ tầng lớn hơn giao diện.** Ba module backend (Psychology, Institutions, World) không có HTTP route nào — engine tính toán phong phú nhưng frontend không nhìn thấy. Nhiều endpoint đã tồn tại nhưng không có consumer: `state-at/{tick}`, `history-timeline`, `mutation-chronicle`, `meaning-seeds`, `omen-context`, `analytics/ticks`.
2. **Real-time đứt gãy.** Backend phát trên ~8 kênh Centrifugo nhưng frontend chỉ nghe 3–4. Lệch quy ước tên kênh: Event class khai báo `universes.{id}` (dấu chấm) trong khi broadcaster chỉ auth `universes:{id}` (hai chấm) và frontend subscribe dạng hai chấm — một số event có thể chưa bao giờ tới UI.
3. **Hai kiến trúc song song.** UI cũ (`components/dashboard`, `contexts/`, `hooks/`, `lib/`) gánh phần lớn màn hình; kiến trúc mới (`features/` + `shared/` + `(workspace)`) mới chỉ là khung với các trang placeholder.
4. **Lệch định vị công cụ.** React Three Fiber có trong dependencies nhưng hạ tầng không phát dữ liệu không gian (GeographyEngine không có API) — frontend chọn công cụ theo tham vọng 3D trong khi hạ tầng là cỗ máy sự kiện + tường thuật.

## 2. Quyết định gốc (đã chốt với người dùng)

| Quyết định | Lựa chọn |
|---|---|
| Mục đích frontend | **Đài quan sát vũ trụ sống** — xem là chính: thế giới tiến hóa real-time, biến cố, narrative, epoch shifts |
| Hero screen | **Living Chronicle** — dòng sự kiện + narrative tự cuộn theo tick |
| Chiến lược | **Big-bang** — đập đi xây lại toàn bộ frontend một lần, không duy trì hai kiến trúc song song, xóa code cũ |
| Phạm vi backend | **FE + BE thoải mái** — backend được refactor theo nhu cầu giao diện (chuẩn hóa kênh, envelope, thêm route đọc) |
| Kiến trúc | **Event-first Observatory + rewind nhẹ** — dòng sự kiện là xương sống; cuộn ngược lịch sử bằng endpoint sẵn có, không làm full time-machine |

3D/không gian **không thuộc phạm vi** lần này — để dành khi geography có API.

## 3. Backend — lớp hỗ trợ quan sát

### 3.1 Chuẩn hóa Centrifugo

- Một quy ước duy nhất, dùng hai chấm (khớp broadcaster hiện tại):
  - `universes:{id}` — tick pulse, metrics, sự kiện simulation chung
  - `universes:{id}:narrative` — ArtifactDiscovered, CelebrityEmerged, HistoricalEpochShifted
  - `universes:{id}:anomaly` — AnomalyDetected
  - `universes:{id}:autopoiesis` — AutopoiesisMutationApplied
  - `universes:{id}:power` — PowerSystemTransitionTriggered (gộp từ `worlds.{id}`)
  - Kênh hệ thống giữ nguyên: `public:universes`, `loom:system:status`, `narrative:{worldId}:{taskId}`, `global_universe`
- Sửa `broadcastOn()` của **tất cả** Event class về quy ước trên; cập nhật regex auth trong `CentrifugoBroadcaster` cho phủ các hậu tố lens.
- **Phong bì sự kiện thống nhất** — mọi payload broadcast bọc trong `WorldEventEnvelope`:
  ```json
  {"type": "...", "tick": 0, "universe_id": 0, "world_id": null,
   "severity": "info|notable|critical", "occurred_at": "ISO8601", "payload": {}}
  ```
  Hiện thực bằng một abstract class/trait cho các Event broadcast; frontend chỉ cần một parser.

### 3.2 Observatory API (read-only, module WorldOS)

| Endpoint | Nội dung |
|---|---|
| `GET /api/worldos/observatory/universes/{id}/feed?after_tick=&before_tick=&types=&limit=` | Dòng sự kiện + chronicle hợp nhất, sắp theo tick. Phục vụ hero (tải trang đầu, backfill sau reconnect) và rewind (cuộn ngược). Tái dùng bảng events/chronicles sẵn có — khảo sát schema hiện trạng ở bước plan; nếu event chưa được persist đủ thì thêm bảng `world_events` ghi từ chính các Domain Event. |
| `GET /api/worldos/observatory/actors/{actorId}/psyche` | Read-model Psychology: drives, emotions, goals, quyết định gần nhất (từ DecisionEngine/GoalGenerator/MeaningEngine). |
| `GET /api/worldos/observatory/universes/{id}/civilization` | Read-model Institutions: entropy, stability, ascension, great-filter, omega-point, complexity. |
| `GET /api/worldos/observatory/universes/{id}/world` | Read-model World: epoch hiện tại, religions, technologies, treaties. |

Nguyên tắc: controller mỏng → Action/Service đọc qua contracts; GET public (theo quy ước auth hiện có); không thay đổi vòng lặp simulation.

## 4. Frontend — cấu trúc mới

### 4.1 Routes

```
src/app/
  (observatory)/
    page.tsx                      # Landing: chòm sao multiverse (bloom/resonance), chọn universe
    u/[id]/page.tsx               # HERO: Living Chronicle
    u/[id]/actors/…               # Lens: actors + psyche
    u/[id]/civilization/page.tsx  # Lens: institutions engines
    u/[id]/causality/page.tsx     # Lens: causal map
    u/[id]/wavefunction/page.tsx  # Lens: wavefunction + informational mass
    chronicle/[chronicleId]/page.tsx  # Cinema: VAF player (port từ narrative-cinema)
  (ops)/ops/
    ai-runtime | loom | settings | system | simulation   # Port nghiệp vụ từ dashboard cũ
  login/page.tsx
```

### 4.2 Hero — Living Chronicle (`u/[id]`)

- **Trục giữa:** stream sự kiện + narrative ảo hóa (virtualized), tự cuộn khi live, lọc theo `type`/`severity`. Mỗi entry render theo loại (epoch shift nổi bật, anomaly cảnh báo, chronicle là đoạn tường thuật, artifact/celebrity là thẻ). Bấm chronicle → cinema.
- **Header:** đồng hồ tick + epoch hiện tại + trạng thái live/paused.
- **Panel bên:** metrics sparkline (entropy, stability, prosperity, population) + actor nổi bật (dẫn sang lens actors).
- **Rewind:** cuộn ngược = phân trang `feed?before_tick=`; nút "về hiện tại" nhảy lại live.

### 4.3 Realtime pipeline

`useUniverseChannels(id)` (trong `shared/realtime/`): subscribe cụm kênh của universe → parse `WorldEventEnvelope` (một parser duy nhất, có test) → đẩy vào store zustand (`simStore`) → React Query invalidate **có chọn lọc** theo `type`. Mất kết nối → reconnect + backfill bằng `feed?after_tick={tick cuối đã thấy}`.

### 4.4 Layering (giữ guardrail ESLint hiện có)

- `app/` → route mỏng; `features/{chronicle, actors, civilization, causality, wavefunction, multiverse, narrative-cinema, narrative-runtime, auth, ops-*}` — chỉ lộ qua `index.ts`; `shared/{ui, realtime, store, lib, config, types}`.
- Mở rộng scope ESLint `no-restricted-imports` phủ toàn bộ `src/app/**` (sau khi xóa code cũ không còn ngoại lệ).

### 4.5 Xóa ở bước cuối

`src/components/dashboard/`, `src/contexts/`, `src/hooks/` (cũ), `src/lib/` (cũ — sau khi port `vaf/parser`, `log-utils` vào features/shared tương ứng), `src/app/dashboard/`, `src/app/narrative-studio/` (là công cụ điều khiển weave → port vào `(ops)/ops/loom`), `src/app/narrative-cinema/` (port vào `(observatory)/chronicle/`).

## 5. Thứ tự xây (bên trong big-bang)

1. BE: chuẩn hóa kênh + envelope (kèm test broadcast)
2. BE: Observatory API (feed trước, 3 lens sau)
3. FE: `shared/realtime` — client + envelope parser + `useUniverseChannels`
4. FE: shell `(observatory)` + landing multiverse
5. FE: hero Living Chronicle
6. FE: lenses (actors/psyche → civilization → causality → wavefunction)
7. FE: `(ops)` — port nghiệp vụ quản trị
8. FE: cinema port
9. Xóa toàn bộ code cũ + mở rộng guardrail ESLint
10. Xanh toàn bộ: `npm run check`, Vitest, PHPUnit; cập nhật CLAUDE.md/AGENTS.md phần frontend

## 6. Kiểm thử

- **BE:** Feature test cho từng Observatory endpoint; test khẳng định kênh + envelope của từng Event class (chặn tái lệch quy ước).
- **FE:** Vitest — envelope parser, feed hook (phân trang/backfill), chronicle reducer/store; pattern `__tests__/` cạnh code như hiện tại.

## 7. Xử lý lỗi & trạng thái rỗng

- Reconnect Centrifugo có backoff; backfill qua feed để không mất sự kiện.
- Universe chưa có sự kiện → empty state hướng dẫn chạy tick (link sang ops simulation).
- Feed endpoint lỗi → hero vẫn hiển thị phần realtime đang nghe được, kèm banner degraded.

## 8. Ngoài phạm vi

- 3D/không gian (chờ geography API), full time-machine (mọi view theo tick), thay đổi logic simulation/engine Rust, mobile layout chuyên biệt.
