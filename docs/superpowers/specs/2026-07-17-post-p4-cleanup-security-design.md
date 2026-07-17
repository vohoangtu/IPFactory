# Post-P4 Cleanup + Security — Design

**Ngày:** 2026-07-17 · **Trạng thái:** Approved (user chốt trong session brainstorm)
**Tiền đề:** Observatory hoàn tất 4/4 plan. Branch `feature/observatory-p4` đã push, chờ PR merge vào `main`.

## Mục tiêu

Một phase nhỏ (~1 branch, ~9 task) trả các món nợ hậu-P4 đã ghi trong `.dev_status.md` Session 18: vá bảo mật route, xóa dead code hai phía, wire save thật cho `/ops/settings`, và một batch test/polish nhỏ. KHÔNG mở tính năng mới.

## Quyết định phạm vi (user chốt 2026-07-17)

1. **Build verify (CI/Docker): BỎ QUA đợt này** — merge P4 và phase này không có build gate; rủi ro chấp nhận tới khi deploy. (Đề xuất dựng GitHub Actions bị hoãn — không nằm trong phase này.)
2. **4 route nghi vấn** (`history-timeline`, `analytics/ticks`, `worlds/pulse`, `test-weave`): **XÓA route 0-consumer** theo tiền lệ `chronicles/raw` P4 — grep gate từng route lúc thực thi; route nào lộ ra caller thật thì GIỮ + khóa `auth:sanctum`+throttle, ghi report.
3. **`/ops/settings`: wire save THẬT** — đổi hành vi có chủ đích từ fake-save (toast + setTimeout, kế thừa trang cũ) sang persist qua `useUpdateAiSetting`.
4. Tổ chức: **một branch `chore/post-p4-cleanup` tách từ `main` SAU khi PR P4 merge**; thực thi subagent-driven như P4.

## Phạm vi

### BE — bảo mật
- **S1. `POST /worldos/universes/{id}/generate-chronicle` → `auth:sanctum`** (`backend/app/Modules/WorldOS/routes/api.php:95-96`, hiện chỉ `middleware('api')` — ghi chú "test route bỏ qua auth"). Caller duy nhất: FE `features/narrative-runtime/api/queries.ts:47` qua `apiClient` (đã gửi Bearer token) — đã khảo sát, không có caller Python. Test: 401 khi thiếu token, 200 khi có.
- **S2. Xóa 4 route 0-consumer** theo quyết định 2. Khảo sát sơ bộ (2026-07-17): 0 hit trong `frontend/src` + `narrative-loom`. Lúc thực thi grep lại cả `backend/` (caller nội bộ, test, command) trước khi xóa; method controller chỉ xóa khi 0 caller còn lại.
- **S3. Fix route shadowing `ai-provider-models`** (`backend/app/Modules/Intelligence/routes/api.php:34` vs `:53`): `GET ai-provider-models/{id}` public đăng ký trước, nuốt `GET ai-provider-models/export` protected → Export ở `/ops/ai-runtime` 404. Fix: `->whereNumber('id')` cho route `{id}` (giữ nguyên thứ tự), test lock cả hai hành vi.

### BE — dead code
- **S4. Xóa cây `Services/Transition/`** (~6 file: `TransitionProcessor` + 4 transformer + 2 guard) + block DI `EngineServiceProvider.php:222-243`. Bối cảnh: P4 Task 15 xóa listener `HandlePowerSystemTransition` (injection site duy nhất) nhưng giữ cây theo gate grep của plan; final review xác nhận giờ là registered-but-never-injected. Grep gate lại lúc thực thi; `ModuleBoundaryTest` ratchet chỉ được giảm.

### FE — hành vi
- **S5. Wire save thật `/ops/settings`**: `handleSave` trong `app/(ops)/ops/settings/page.tsx` nối vào `useUpdateAiSetting` (`@/features/admin`, endpoint `POST /ai-settings/update` có sẵn). Giữ shape state/tab hiện có; toast theo kết quả thật (success/error); test mock mutation assert gọi đúng key/payload. Lưu ý map đúng cấu trúc state trang (routing/params/epistemic per-agent) sang payload `key/value` mà endpoint nhận — đọc `AiSettingValueHandler` BE để khớp hợp đồng.

### FE — dọn
- **S6. Xóa dead exports**: `simulationQueries.config()` (`features/simulation/api/queries.ts:44-50`, 0 consumer, key trùng `/apex/settings`); `useCompareBranch`/`useBranchComparison` (`features/simulation`, 0 consumer); `useUniverseOptions`/`useUniverseDossier` (`features/universe`, dead không qua index.ts); gỡ `usePipelineManifest` khỏi public `features/narrative-runtime/index.ts` (giữ hook nội bộ — LoomMonitor dùng).
- **S7. Chuẩn hóa query key + unwrap**: thêm `qk.aiDrivers()` (thay literal `['admin','ai-drivers']`); rà key `/apex/settings` về một mối (`qk.simulationSettings()`); `useAiPool` (`features/intelligence/hooks`) đổi đọc trực tiếp `response.data` → `takeData`.

### Test/polish nhỏ
- **S8.** Test reject `global_universe:x` (CentrifugoChannelAuthTest — code `===` đã đúng, khóa bằng test); regression test `clearLogs` dual-invalidation (`features/intelligence`); disable nút Save/Reset khi `isLoading` ở `/ops/system` page.

### Gate cuối
- **S9.** Full FE test + check, BE Unit + filter liên quan; cập nhật `.dev_status.md`.

## Ngoài phạm vi

CI/GitHub Actions & build verify; realtime stack (Centrifugo/subscribe proxy); nợ test BE (92 skip / 91 feature fail); dựng lại Achievements/Timeline; mọi redesign UI.

## Ràng buộc kỹ thuật (kế thừa P4)

- Môi trường: Incus `worldos-dev`; FE `npm test -- --pool=threads` (BẮT BUỘC), `npm run check`; BE `php artisan test`; **pint CHỈ trên file đã sửa**.
- Baseline: FE 40 file / 150 test, check 0 err / 0 warn; BE Unit 170-171 pass / 92 skip (flake `IntelligenceExplosionTest` được phép).
- Layering FE giữ nguyên (app → features → shared, public API qua index.ts); BE `ModuleBoundaryTest` ratchet chỉ giảm; TDD từng task; không sửa migration.

## Kiểm chứng thành công

- `generate-chronicle` trả 401 không token; nút Run `/ops/loom` vẫn hoạt động khi đăng nhập.
- 4 route nghi vấn biến mất khỏi `route:list` (hoặc được khóa nếu lộ caller — ghi report).
- Nút Export `/ops/ai-runtime` tải file thật (hết shadowing).
- Save ở `/ops/settings` persist qua reload (đọc lại từ `/ai-settings`).
- Grep `Services/Transition` = 0 hit; baseline test không tụt ngoài chủ đích.
