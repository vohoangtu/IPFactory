# Thiết kế: Quy hoạch lại (rewrite) Frontend WorldOS V6

**Ngày:** 2026-06-13
**Trạng thái:** Đã duyệt design (chờ user review spec → writing-plans)
**Tác giả:** Claude Code (Opus 4.8) + dev (vohoangtu)

---

## 1. Bối cảnh & động lực

Frontend hiện tại (Next.js 16, ~192 file, build OK, nợ kỹ thuật thấp, 76 test xanh) **về kỹ thuật vẫn khỏe**, nhưng:

- **Pain gốc:** không thể *theo dõi rõ ràng sự vận chuyển (tiến triển) của Universe/World*. Khi mô phỏng chạy, người dùng không có một nơi mạch lạc để quan sát universe tiến hóa theo thời gian.
- **Nguyên nhân IA:** dashboard là ~17 **tab rời rạc** (simulation, multiverse, timeline, causal-map, actors, wavefunction…) — không có luồng quan sát thống nhất.
- **Nguyên nhân code:** tổ chức **lai** — logic rải ở `features/` + `components/dashboard/` + `hooks/` + `contexts/`, state phân tán (Context + React Query), khó biết "thứ gì ở đâu".

> Lưu ý: gốc rễ việc universe "đứng yên" trong demo là **bug backend** (EngineResult/WorldRulesetRuntime sai namespace) đã được sửa riêng (commit `b850bb9`). Spec này giải quyết tầng **frontend** để trải nghiệm quan sát xứng tầm.

### Quyết định đã chốt (qua brainstorming)
| Hạng mục | Quyết định |
|---|---|
| Phạm vi | **Toàn diện** — UX/IA **và** kiến trúc code |
| Hướng | **Rewrite từ đầu** (greenfield), nhưng **thực thi bằng strangler** (dựng song song, giữ app cũ chạy, migrate dần) |
| Động lực | Nền code sạch + IA hợp nhất từ con số 0 + hiện đại hóa tech |
| Tech | **Giữ stack lõi, hiện đại hóa bên trong**: Next.js 16 + React 19 + TS strict + Tailwind 4 + R3F + ReactFlow + Recharts + Centrifugo + React Query; **THÊM Zustand**; design system Tailwind tự xây (**không** UI kit, theo CLAUDE.md); feature-first; state theo URL |
| Backend | **Không đổi** — frontend mới tiêu thụ đúng REST API + Centrifugo hiện có |

### Mục tiêu
- Một **luồng quan sát thống nhất** thay cho tab rời: theo dõi universe tiến hóa realtime, tua lại lịch sử, khám phá đa vũ trụ — tất cả neo vào **một context chung**.
- Kiến trúc **feature-first** nhất quán, ranh giới enforce được, dễ test/mở rộng.
- Tầng state rõ ràng (server vs live vs UI).

### Non-goals (YAGNI)
- Không đổi framework (vẫn Next.js) — giữ SSR + chuẩn dự án.
- Không thêm UI kit (Tailwind tự xây, theo CLAUDE.md).
- Không đổi backend/API.
- Không làm E2E ngay (để phase sau).
- Không big-bang cutover.

---

## 2. Information Architecture — "Universe Workspace"

Bỏ danh sách tab phẳng. Thay bằng workspace xoay quanh **một thực thể đang chọn (Universe)** + **context bar cố định** + **3 mode phối hợp** + **drill-down deep-linkable**.

```
┌───────────────────────────────────────────────────────────────────────┐
│ [Universe ▾ Demo World]  Era 3 · Tick 1240 · ●LIVE   [⏸][▶+1][⚡pulse] │ ← Context Bar (luôn hiện)
├────────┬──────────────────────────────────────────────────────────────┤
│ LIVE ◀ │ ┌─ metrics (live) ──┐ ┌─ event / narrative stream (cuộn) ───┐ │
│ REPLAY │ │ stability ▁▂▃▅▇   │ │ t1240 ⚡ Crisis ở Zone 0            │ │
│ MULTI  │ │ entropy   ▇▅▃▂▁   │ │ t1238 📜 "Dân cư Zone 0 làm chủ…"  │ │
│        │ └───────────────────┘ └─────────────────────────────────────┘ │
│        │ ┌─ zones ─┐ ┌─ actors ─┐ ┌─ multiverse mini ─┐ ┌─ 3D field ─┐ │
└────────┴──────────────────────────────────────────────────────────────┘
```

**Context Bar (cố định):** universe đang chọn (tên, era, current_tick, status pill active/paused/halted), chỉ báo **LIVE** (Centrifugo connected + đang tick), điều khiển (advance/pause/pulse), universe switcher.

**3 mode dùng chung context:**
1. **LIVE (Mission Control)** — mặc định. Metrics live, event/narrative stream cuộn realtime, mini-panel zones/actors/multiverse/3D — cập nhật theo tick qua Centrifugo.
2. **REPLAY (Time-travel)** — scrubber tick (play/step/so sánh before-after), tái dùng đúng các panel nhưng nguồn từ snapshot lịch sử.
3. **MULTIVERSE (Explorer)** — cây fork/branch (ReactFlow); drill vào nhánh → set context → xem Live/Replay nhánh đó; so sánh dòng thời gian song song.

**Drill-down** (focused-view trong workspace, không phải tab rời): actor detail, chronicle/narrative-cinema, causal-map, wavefunction (R3F).

**URL phản ánh state** (chia sẻ/bookmark):
`/u/:id/live` · `/u/:id/replay?tick=N` · `/multiverse` · `/u/:id/actor/:actorId`

---

## 3. Kiến trúc code & cấu trúc thư mục

Feature-first (vertical slice) + tầng `shared/` + ranh giới enforce được.

```
src/
  app/                        # Next App Router — CHỈ route shell mỏng (compose feature, 0 business logic)
    (workspace)/u/[id]/live/ · replay/ · actor/[actorId]/   ·   multiverse/   ·   login/
  features/                   # mỗi feature = lát dọc TỰ CHỨA
    universe-workspace/        # shell: context bar, mode switcher, layout
    live-monitor/  replay/  multiverse/                       # 3 mode observability
    actors/ narrative/ causal-map/ wavefunction/ ai-runtime/ achievements/ ...
      <feature>/
        components/   hooks/   api.ts   store.ts(optional)   types.ts   index.ts(public API)
  shared/                     # cross-cutting, KHÔNG chứa domain logic
    ui/         # design system primitives (Button/Panel/Pill/StatPill/ChartFrame…) — Tailwind, không UI kit
    lib/        # axios client, centrifugo client, queryClient, utils
    realtime/   # cầu nối Centrifugo → store (channels, subscriptions)
    store/      # GLOBAL sim-state (Zustand): universe đang chọn, tick/metrics live, connection
    types/      # domain types dùng chung (Universe, Actor, Chronicle, snapshot DTOs…)
    config/     # routes, query-keys registry, env
  test/                       # vitest + testing-library setup & helpers
```

**Nguyên tắc ranh giới (enforce):**
1. Hướng phụ thuộc 1 chiều: `app → features → shared`. `shared` không phụ thuộc nội bộ. Feature **không** import nội bộ feature khác — chỉ qua `index.ts`.
2. Route mỏng: `app/*` chỉ compose feature + set context.
3. Một nguồn sự thật cho "universe sống": `shared/store` (Zustand).
4. Một tầng data nhất quán: `shared/lib/api` + `<feature>/api.ts` (query keys + fetchers) qua React Query.
5. **Guardrail** (song song với `ModuleBoundaryTest` backend): ESLint `import/no-restricted-paths` / eslint-plugin-boundaries chặn vi phạm tầng/feature → ngăn "lai" tái diễn.

---

## 4. State & data flow

3 loại state tách bạch:

```
Backend ──REST──▶ React Query ──▶ server cache  (universes, snapshots, chronicles, fork-tree, actors)
   │
   └─Centrifugo push (tick/metrics/event/narrative)─▶ realtime bridge ─▶ Zustand live store ─▶ panels (LIVE)
                                                                              ▲
 REPLAY: scrubber ▶ snapshotQuery(tick=N) ─────────────────────────────────── ┘  (CÙNG panel, đổi nguồn data)
 URL  /u/:id/:mode?tick=N  ⇄  store.view {selectedUniverse, mode, replayTick, selectedActor}
```

| Loại | Công cụ | Giữ gì |
|---|---|---|
| Server state | React Query | universes, snapshot(tick), chronicles, fork-tree, actor — cache + query keys; mutation (advance/pulse/fork) invalidate |
| Live sim-state | Zustand + Centrifugo | `tick`, `metrics` live, `events[]` (ring buffer giới hạn), `connection` — push-based, không refetch |
| UI/view state | Zustand + URL | selectedUniverse, mode, replayTick, selectedActor — phần chia sẻ được đẩy lên URL |

**Mảnh chính:**
- **`shared/store` (Zustand):** `connection`, `selectedUniverseId`, `live{tick, metrics, events, status}`, `view{mode, replayTick, selectedActorId}`; actions: `selectUniverse`, `applyTick(payload)`, `setMode`, `setReplayTick`.
- **`shared/realtime` bridge:** subscribe channel `universes:{id}` của universe đang chọn → map payload → store actions; resubscribe khi đổi universe; xử lý connect/reconnect (gom logic Centrifugo rải rác về 1 chỗ).
- **Panel data-source abstraction:** mỗi panel đọc qua hook đổi-nguồn, vd `useUniverseMetrics(mode)` → LIVE = live store, REPLAY = snapshotQuery(tick). → **một bộ panel phục vụ cả Live lẫn Replay**.

**Quy tắc:** RQ = sự thật từ server; Zustand = luồng push live + UI state tạm. Không nhân đôi data server vào Zustand (trừ delta stream).

**Phụ thuộc backend:** cần backend broadcast tick/metrics/event qua channel `universes:{id}` ở phase 5 (Broadcast). Hạ tầng `CentrifugoBroadcaster` đã có — cần đảm bảo payload được phát (kiểm tra khi làm P1).

---

## 5. Chiến lược migration (strangler)

**Cùng tồn tại trong 1 app Next:** code mới (`shared/` + `features/` + route group `(workspace)`) dựng song song; route cũ (`app/dashboard/*`, `components/dashboard/*`, `contexts/`) **vẫn chạy** tới khi từng phần port xong rồi xóa. 1 build/deploy, rollback dễ.

| Phase | Nội dung | Giá trị |
|---|---|---|
| **P0 Foundations** | `shared/` (ui, api, queryClient, centrifugo bridge, store, config) + workspace shell + auth + route group | Khung + login; `/dashboard` cũ nguyên |
| **P1 LIVE** | live-monitor: metrics live, event/narrative stream, mini-panel zones/actors — wired Centrifugo→store | **Giải pain #1: theo dõi realtime** |
| **P2 REPLAY** | scrubber + snapshot queries, tái dùng panel P1 | "Phát phim" tiến hóa |
| **P3 MULTIVERSE** | fork-tree (ReactFlow) + drill-down → set context | Toàn cảnh đa vũ trụ |
| **P4 Drill-downs** | actor detail, narrative-cinema, causal-map, wavefunction (R3F) | Chi tiết sâu |
| **P5 Admin + cutover** | ai-settings, loom-workshop/monitor, achievements, system → flip root + **XÓA code cũ** | Hoàn tất |

**Quản trị rủi ro:** app cũ chạy đủ suốt; cutover chỉ ở P5 (feature-flag/route-default); mỗi phase độc lập ship + test; backend không đổi; guardrail boundaries áp từ P0. Cuối P5: `src/` còn `app/`(mỏng) + `features/` + `shared/` + `test/`.

---

## 6. Testing & chất lượng

Tận dụng hạ tầng vitest + testing-library + jsdom đã sửa (`npm run check` xanh). Test là first-class (vá lỗ "0 component test").

| Tầng | Công cụ | Test gì |
|---|---|---|
| Unit | vitest | Zustand actions (`applyTick`, `selectUniverse`, ring-buffer cap), transforms, query-key builders, data-source hooks, utils |
| Component | vitest + testing-library + jsdom | Component chính mỗi feature: render loading/error/data + tương tác + cập nhật từ store; mock api + wrapper QueryClient+store |
| Realtime | vitest + Centrifugo fake | publication giả → bridge → store → panel react (đường observability — quan trọng nhất) |
| E2E (tùy chọn, sau) | Playwright trên stack thật | login → chọn universe → tick live → replay |

**Quality gates (CI):**
1. `npm run check` (tsc --noEmit + eslint **+ rule boundaries**) xanh.
2. `npm test` xanh; coverage floor: store + realtime + component chính.
3. Definition of Done mỗi phase: lát dọc + test + check xanh mới merge.

**Infra:** `src/test/` render helper (QueryClient + store provider), Centrifugo fake, api mock; co-locate test trong feature slice.

**Triết lý coverage:** ưu tiên store/realtime + state component chính + chuyển nguồn live↔replay; bỏ qua phần trình bày tầm thường.

---

## 7. Success criteria

- Một workspace thống nhất: chọn 1 universe → quan sát **LIVE** (tick/metrics/event cập nhật realtime), **REPLAY** (tua lịch sử), **MULTIVERSE** (cây fork) — không còn tab rời.
- `src/` cuối cùng: `app/`(mỏng) + `features/` (đồng dạng) + `shared/` + `test/`; guardrail boundaries xanh.
- State 3 tầng rõ ràng; live update push-based qua Centrifugo (không refetch để theo dõi tick).
- Mỗi feature có component test; `npm run check` + `npm test` xanh; app cũ không gãy trong suốt migration.

## 8. Rủi ro & câu hỏi mở

- **Payload realtime:** cần xác nhận `CentrifugoBroadcaster` phát đủ tick/metrics/event theo channel `universes:{id}` (kiểm tra ở P1; có thể cần chỉnh nhẹ backend — nằm ngoài phạm vi spec frontend này nhưng cần phối hợp).
- **R3F/ReactFlow:** port (không viết lại) các visualization nặng; cân nhắc lazy-load để giữ bundle gọn.
- **Khối lượng:** rewrite toàn diện là lớn — strangler + chia phase giữ rủi ro thấp, nhưng tổng effort đáng kể (nhiều phase).
- **Auth:** giữ luồng sanctum token hiện có (bearer trong localStorage) — chuyển vào `shared/`.
