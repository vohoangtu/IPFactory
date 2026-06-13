# Review chi tiết WorldOS V6

**Ngày:** 2026-06-13
**Người review:** Claude Code (Opus 4.8 · 1M)
**Commit:** `ba9881b` (HEAD)
**Phạm vi:** Toàn bộ 5 service (backend PHP, engine Rust, frontend Next.js, narrative-loom, sim/social-engine).
**Phương pháp:**
1. 5 agent review song song (kiến trúc backend, bảo mật backend, engine Rust, frontend, Python) — mỗi finding kèm `file:line`.
2. **Tự xác minh** các claim nghiêm trọng/bất ngờ (loại bỏ false-positive).
3. **Chạy test thật** trong container throwaway dựng từ image `working-backend:latest` — điều review `2026-06-05` *không* làm được (khi đó chưa có Docker).

> **Khác biệt lớn nhất so với review 2026-06-05:** lần này test **chạy thật** (Laravel 13.13.0, PHP 8.4.21, PHPUnit 12.5.23) và phát hiện thêm **nhóm lỗ hổng bảo mật P0** mà review tĩnh bỏ sót.

---

## Đánh giá tổng quan

WorldOS V6 là hệ thống **thật, có chiều sâu** (165 unit test xanh, engine Rust & 2 service Python sạch, logic mô phỏng phong phú) — **nhưng CHƯA an toàn để production**, do **lỗ hổng auth nghiêm trọng**, kiến trúc backend đang xói mòn, và bộ test bị rỗng ruột tới 35%.

**Trạng thái:** *"Chạy được, nhiều phần chất lượng cao, nhưng có lỗ hổng bảo mật chặn-production + nợ kiến trúc/test cần xử lý trước khi mở rộng."*

| Service | Quy mô | Verdict | Điểm nóng nhất |
|---|---|---|---|
| Backend (PHP/Laravel 13) | 1.140 file, 111K LOC | 🔴 **Chặn production** | Auth leak API key, coupling vòng tròn |
| Engine (Rust) | 52 file, 8.5K LOC | 🟠 Gần sẵn sàng | 1 panic OOB trên gRPC path |
| Frontend (Next.js 16) | 192 file, 19.7K LOC | 🟢 Tốt (vài P1) | null-deref audio, test mỏng |
| narrative-loom (Py) | 64 file | 🟠 Tốt, 1 P0 async | block event loop khi gọi pool key |
| sim/social-engine (Py) | 46 file | 🟢 Sạch | thiếu upper-bound input |

**Tiến độ từ review 2026-06-05:** Chỉ khuyến nghị #1 (commit Wave 1 — `ff696dd`) đã làm. **7/8 khuyến nghị còn lại chưa xử lý** (strict_types, script rời, architecture test, test trống, stub, god files, đồng bộ doc).

---

## 🔴 P0 — Chặn production (xử lý trước tiên)

### P0-1 · Lộ API key LLM đã giải mã qua endpoint KHÔNG auth ✅ *(đã tự xác minh)*
- `app/Modules/Intelligence/routes/api.php:29-30` — `GET`/`POST /api/ai-settings/loom-key` nằm trong group `Route::middleware('api')` **công khai** (dòng 25), khác hẳn group `auth:sanctum` ở dòng 19/44.
- `AiSettingsController.php:252` trả thẳng `'api_key' => $runtime['api_key']` — chuỗi giải mã từ `Crypt::decryptString()` (`AiProviderRouter.php:200`).
- **Khai thác:** `curl http://host/api/ai-settings/loom-key` → trả về key OpenAI/OpenRouter/Anthropic còn sống. Đảo `provider`/`tier` để gom nhiều key → chiếm tài khoản + cháy hoá đơn LLM.

### P0-2 · Proxy `/loom/{path}` không auth (passthrough toàn bộ tới service nội bộ)
- `app/Modules/Narrative/routes/api.php:27-28` — `Route::match(['get','post','put','delete'],'/loom/{path}')->where('path','.*')` chỉ có `middleware('api')`, **không auth**.
- `LoomProxyController.php:24-72` forward method + body + header bất kỳ tới NarrativeLoom (vốn gọi LLM/DALL·E tốn phí). Caller ẩn danh điều khiển mọi endpoint nội bộ + traversal `../` trong path.

### P0-3 · Webhook không auth → tiêm nội dung, broadcast cho toàn bộ client
- `app/Modules/Narrative/routes/api.php:24` — `POST /api/narrative-loom/webhook` công khai, **không HMAC/chữ ký** (xác nhận: không có `hash_hmac`/`X-Signature` trong `LoomWebhookController`).
- Ghi `final_prose`/`news_headline` do attacker kiểm soát vào `Chronicle`/`Narrative` của bất kỳ `world_id`, rồi đẩy qua WebSocket cho mọi subscriber → data-integrity + stored-content-injection (XSS ở tầng render). Comment "internal network only" **không được enforce**.

### P0-4 · Không có rate limiting ở bất kỳ đâu
- `throttle` middleware xuất hiện trên **0 route**. `POST /api/auth/login` (`AuthController.php:37`) và `register` không giới hạn → credential stuffing / tạo account vô hạn.
- Endpoint đắt tiền không throttle: `simulation/advance`, `*/pulse`, `*/generate-chronicle`, `historian/generate`, `/loom/{path}` → DoS chi phí (mỗi request kích hoạt LLM).

### P0-5 · Rust: panic out-of-bounds trên gRPC path `run_process_fields_v7` ✅ *(đã tự xác minh)*
- `engine/worldos-grpc/src/engine.rs:738-740` — `neighbors[offset + j]` **không** kiểm tra bound trước khi truy cập (guard `neighbor_idx >= count` nằm *sau*). `fields[neighbor_idx*8+f]` cũng không kiểm `fields.len()`.
- **Tác động:** Laravel gửi CSR data sai (offset vượt `neighbors.len()`, hoặc `fields` ngắn hơn `count*8`) → **panic, sập toàn bộ tiến trình gRPC server**. Đây là rủi ro crash runtime cao nhất của engine.

### P0-6 · Kiến trúc: mạng phụ thuộc vòng tròn 11 cặp module + 146 import concrete chéo module nguy hiểm
- 11 cặp cycle (vd: `Simulation ⇄ Intelligence`, `Simulation ⇄ Narrative`, `Narrative ⇄ Intelligence`...). Vi phạm rule DDD "module chỉ giao tiếp qua `app/Contracts/`".
- Trong ~950 import chéo module: **705 là Model-sharing (chấp nhận tạm)**, nhưng **146 là import concrete Service/Action/Repository (nguy hiểm)** — tập trung ở `Simulation → Narrative` (46). File tệ nhất: `Simulation/Core/Runtime/Stages/MetaCosmicStage.php` (9), `Simulation/Listeners/SynchronizeNarrativeHistory.php` (9), `Simulation/Services/Evaluation/NarrativeChronicleService.php` (8).
- *Đính chính review cũ:* các interface trong `app/Contracts/` **CÓ được dùng** (ActionInterface 82 file, SimulationEngineClientInterface 20...). Vấn đề là import concrete *song song tồn tại*, không phải "interface bị bỏ không".

---

## 🟠 P1 — Quan trọng

### Backend
- **Full-table `::all()` mỗi tick (hiệu năng):** `Simulation/Core/Runtime/Kernel/AgentBatchProcessor.php:37-43` load `FactionRelation::all()`, `Belief::all()`, `Technology::all()` **mỗi tick** + bypass repository (`Universe::find()` dòng 34). O(table) DB load mỗi vòng lặp.
- **35% unit test bị SKIP — test rot (xem mục Test bên dưới).** 3 unit fail thật: `MeaningEngineTest` (2), `IntelligenceExplosionTest` (1) → nghi regression logic.
- **31 Action/Service ghi ≥3 entity KHÔNG có `DB::transaction`** (chỉ 13 file dùng transaction). Vd: `Narrative/Services/SerialStoryService.php` (8 write), `Simulation/Actions/MergeUniverseAction.php` (7), `Institutions/Services/GreatFilterEngine.php` (7) → rủi ro hỏng dữ liệu khi ghi dở.
- **God object `WorldState.php` (458 dòng, 76 method public)** — `Simulation/Core/Runtime/State/WorldState.php`, import chéo module 129×. SRP vỡ, không đóng gói.
- **Centrifugo token cấp cho user ẩn danh** (`WorldOS/routes/api.php:60` công khai) + authz channel yếu (`CentrifugoBroadcaster.php:41-55` cho phép mọi `universes:<id>` đang active) → ẩn danh subscribe stream mọi universe.

### Engine (Rust)
- **Hardcode `17` thay vì `TRAIT_COUNT`** ở 3 file: `engine.rs:277,378,530`, `technology.rs:30-31`, `universe_social.rs:51` — vi phạm rule "never hardcode trait indices". Nếu `TRAIT_COUNT` đổi → vector trait sai âm thầm. (`belief.rs` & `behavior_graph.rs` thì **đúng chuẩn**.)
- `belief.rs:41` dùng `debug_assert_eq!` — **bị loại bỏ ở release build** → input sai sinh kết quả rác im lặng thay vì báo lỗi.

### Frontend
- **P0/P1 null-deref:** `components/ui/audio/AtmospherePlayer.tsx` — `crossfade()` async dùng `audioRef.current!` sau mỗi `await` mà không re-check `mountedRef` → crash nếu unmount giữa chừng.
- **CDN ngoài hardcode:** `components/ui/map/WeatherEngine.tsx:53` dùng `url('https://grainy-gradients.vercel.app/noise.svg')` (preview deploy, có thể biến mất).
- **Rò email user ra ngoài:** `components/shell/AppHeader.tsx:47` gửi `user.email` thẳng tới `api.dicebear.com` mỗi lần render.
- **Non-null assertion trên `.find()`:** `ai-settings/RoutingTab.tsx:54,91`, `ParamsTab.tsx:25` — crash nếu config lệch.
- **0 component/page test** (chỉ 6 file test lib/hooks).

### Python
- **[narrative-loom] P0 async:** `utils/llm_factory.py:156,99` gọi `httpx.post/get` **đồng bộ** trong async agent node → block event loop Celery khi pool backend chậm/down (timeout 10s × tối đa 3 fallback).
- **[narrative-loom] Thiếu `timeout` cho `ChatAnthropic`** (`llm_factory.py:264-268`) — mọi provider khác đều có; Anthropic có thể hang vô hạn.
- **[sim] `agents_count` không có upper-bound** (`app/api/swarm_routes.py:24`, `WorldContext`) — nên `Field(ge=1, le=50)`. Hiện cap cứng ở factory che chắn, nhưng là DoS surface.

---

## 🟡 P2 — Vệ sinh & nợ kỹ thuật

- **`declare(strict_types=1)` thiếu ở 1.066/1.140 file (93%)** — y nguyên review cũ. Fix bằng Pint (1 lệnh).
- **11 script PHP rời** ở `backend/` (`check_scars.php`, `debug_*.php`, `fix_namespaces.php`, `refactor_services.php`, `restore_ai.php`, `temp_seed_loom_agents.php`, `update_tickrates.php`...) + `verify_autonaut.php` ở **repo root** (rủi ro lộ khi deploy). Nên chuyển thành Artisan Command hoặc xóa.
- **`console.log` còn sót** `components/ui/audio/AtmospherePlayer.tsx:81` — đã báo từ 2026-06-05, **8 ngày chưa xóa**.
- **Doc drift:** CLAUDE.md ghi 9 module, thực tế **10** (thiếu `Achievement`). Module `Achievement` còn skeletal (không có Models/Actions/Contracts/Repositories; controller dùng raw `DB::table` + logic inline — `AchievementController.php:27,74-90`).
- **Comment lỗi thời gây hiểu nhầm:** `KernelServiceProvider.php:28,~127` ghi *"STUB engines removed entirely — FinanceEngine, DiplomacyEngine, ProductionChainEngine"*, **nhưng thực tế 3 engine này đã implement đầy đủ (85-143 dòng) VÀ vẫn được đăng ký sống** qua `registerSystem()` ở dòng 417/422/432 (có cả Feature test). → Không phải dead code; chỉ là comment sai. Cần sửa comment.
- **`NarrativeStrategyRegistry`** được nạp 6 strategy ở `AppServiceProvider.php:67-74` nhưng `all()` **không bao giờ được gọi**; 5 strategy (`Anomaly/Death/Rebirth/Paradox/Legacy`) là stub 11 dòng trả `[]` → wiring chết.
- **Trùng tên class:** `WorldState` ×2 (`Core/State/` 14 dòng vs `Core/Runtime/State/` 458 dòng), `DecisionEngine` ×3 (Psychology/Intelligence/Simulation) → rủi ro import nhầm.
- **[sim] deps không pin** (`requirements.txt`: `fastapi>=`, `openai>=`, `pydantic>=`); **narrative-loom pin đầy đủ** (tốt hơn).
- **[narrative-loom] Hardcoded key trong `test_multi_ai.py:7`** (`sk-7c88...`) — file bị `pytest.skip` nên không chạy, nhưng key vẫn trong git history. Nên xóa.
- **[Python] Không có auth** trên endpoint nào của cả 2 service (nội bộ, nhưng thiếu defense-in-depth).

---

## ✅ Đã xác minh là SẠCH (không phải giả định)

- **Backend:** SQL injection (91 raw-SQL site đều dùng binding/literal — 0 chỗ lấy từ request), hardcoded secrets (config dùng `env()`, không có `.env` trong git), mass assignment (76 model đều `$fillable`, 0 `$guarded=[]`), command injection, deserialization, host-SSRF, mass-data-exposure (key được mask). DLQ của Centrifugo có `Cache::lock` + cap 100 — **không** race/unbounded như nghi ngại ban đầu.
- **Engine Rust:** 0 `todo!()`/`panic!()`/`unsafe`; concurrency sạch (stateless per-request, không lock chia sẻ, không deadlock); gRPC error → `tonic::Status` đúng.
- **Python:** 0 placeholder thật; LLM fallback chain 4 cấp (pool→DB→file→local) + tenacity retry; Pydantic validation đầy đủ ở sim; Celery config production-grade (`acks_late`, `soft_time_limit`).
- **AI auto-deploy gate** (`SelfImprovingSimulationService.php:206-213`): mặc định TẮT, có audit log, candidate từ config (không phải raw AI). *Latent risk:* `deployRule()` đang `public` — nên để `private`/luôn gate để tránh caller tương lai bypass.

---

## ❌ Đính chính false-positive của agent (đã kiểm chứng)

> *Minh bạch về độ tin cậy: 2 claim của agent đã được kiểm tra và LOẠI BỎ.*

1. **"27 `onSuccess` bị bỏ qua trong TanStack Query v5" → SAI.** Các callback này nằm trên `useMutation` (vd `features/simulation/hooks/index.ts:107`), mà v5 **vẫn hỗ trợ** `onSuccess`/`onError` cho `useMutation` — chỉ gỡ khỏi `useQuery`. Cache invalidation hoạt động bình thường. *Không phải bug.*
2. **"3 engine là dead code chưa đăng ký" → SAI.** Như mục P2 ở trên: chúng được đăng ký sống qua `registerSystem()`. Chỉ comment là lỗi thời.

---

## 🧪 Kết quả test (CHẠY THẬT — điểm mới so với review tĩnh)

Container throwaway từ `working-backend:latest`, SQLite `:memory:`, code = HEAD.

**Unit suite (sạch, đã loại file rác tồn dư trong image):**
```
Tests: 3 failed, 92 skipped, 165 passed (478 assertions)  ·  Duration 1.28s
```
- ✅ **165 pass / 168 chạy = 98,2%** — phần code *được test* rất ổn.
- 🔴 **92/260 = 35% test bị SKIP** (review tĩnh chỉ đếm 47 `markTestSkipped` ở mức *câu lệnh*; runtime cho thấy **92 method** bị tắt). Phân rã:
  - **65×** lý do copy-paste *"Test fails due to pre-existing refactoring changes (interface/DB fixtures)"* → cả lớp test bị vô hiệu hóa thay vì sửa.
  - Còn lại: service đã xóa (`BarterMarketResolver`, `HarvestingService`, `CraftingService`, `EnvironmentTickService`, `Weather`/`Tile` ValueObject) + interface đổi *"Test needs full rewrite"* (`ForkUniverseAction`, `ApplyMythScarAction`) + `SQLite FK constraint issue`.
- 🔴 3 fail thật: `Psychology/MeaningEngineTest` ("more negative for neurotic actor", "different actors → different meanings" — sinh 0.0/giống nhau), `Services/IntelligenceExplosionTest` ("meta learning" → mảng rỗng). **Nghi regression logic, cần điều tra.**

**Feature suite (39 file):** hầu hết là **integration test cần stack sống** (engine gRPC/HTTP, Kafka, Redis) — `HttpSimulationEngineClientTest`, `EngineDriverTest`, `KafkaEventStreamTest`, `GrpcTimeoutHandlingTest`, `AdvanceSimulationEndToEndTest`... → **không chạy được trong container cô lập**, sức khỏe chưa kiểm chứng (cần `DC up` full stack mới đánh giá được).

> ⚠️ **Lưu ý:** image `working-backend:latest` (build 2026-06-04) còn chứa **test domain ERP/du lịch cũ** (`FinanceLedgerTest`, `HrmPayrollTest`, `BookingDraftTest`, `OperationResourcesTest`...) — **không tồn tại trong HEAD**. Image dường như build từ một boilerplate trước khi dọn. Lần chạy đầu bị nhiễm các test này; đã loại bỏ và chạy lại sạch. *Khuyến nghị: rebuild image từ HEAD để loại tồn dư.*

---

## Khuyến nghị hành động (theo ưu tiên)

**Ngay lập tức (bảo mật, chặn production):**
1. **P0-1:** Đưa `ai-settings/loom-key` vào `auth:sanctum`, **ngừng trả `api_key` cleartext** trong mọi HTTP response (proxy LLM phía server, frontend không bao giờ nhận key).
2. **P0-3 + Centrifugo:** Ký HMAC (shared-secret) + IP allowlist cho webhook; siết `CentrifugoBroadcaster::auth()` yêu cầu user đã xác thực theo từng channel universe.
3. **P0-2:** `/loom/{path}` vào `auth:sanctum`, giới hạn verb + allowlist path.
4. **P0-4:** Thêm `throttle:` global + siết riêng `auth/login|register` và các endpoint LLM/simulation.
5. **P0-5 (Rust):** Thêm bound-check `offset + neighbor_counts[i] <= neighbors.len()` và `fields.len() == count*8` trong `run_process_fields_v7` trước vòng lặp.

**Tuần này (kiến trúc & độ tin cậy):**
6. Viết **architecture test (PHPUnit)** chặn import concrete Service/Action chéo module — ngăn coupling tái diễn (thay vì sửa tay 146 chỗ). Bắt đầu cắt 4 file tệ nhất ở P0-6.
7. Sửa **per-tick `::all()`** trong `AgentBatchProcessor` → cache/repository.
8. Bọc **`DB::transaction`** cho 31 Action/Service đa-write (ưu tiên `MergeUniverseAction`, `TransitionEpochAction`, `GreatFilterEngine`).
9. **[narrative-loom]** Bọc `_get_pool_key()` bằng `asyncio.to_thread()` hoặc `httpx.AsyncClient`; thêm `timeout` cho `ChatAnthropic`.

**Dọn nợ (rẻ, lợi lớn):**
10. Điều tra & sửa 3 unit test fail (`MeaningEngine`, `IntelligenceExplosion`); **lập kế hoạch hồi sinh 92 test bị skip** (đặc biệt 65 test "refactoring changes") — đây là rủi ro lớn nhất về độ tin cậy.
11. `pint` thêm `strict_types` toàn backend (1 lệnh, ~1.066 file).
12. Xóa 11 script rời (đặc biệt `verify_autonaut.php` ở root) + `console.log` + comment "STUB removed" sai + key hardcode trong `test_multi_ai.py`.
13. Đồng bộ doc: CLAUDE.md 9→10 module (+`Achievement`); thay `17` bằng `TRAIT_COUNT` ở 3 file Rust; rebuild image từ HEAD.

---

## Phụ lục: số liệu

- **Test (chạy thật):** Unit 165 pass / 3 fail / 92 skip (35% skip). Feature 39 file (cần full stack, chưa chạy).
- **strict_types:** 74/1.140 có (93% thiếu). **markTestSkipped (tĩnh):** 47 câu lệnh → **92 method skip** (runtime).
- **Cross-module import:** ~950 tổng (705 Model + 146 Service/Action/Repo nguy hiểm). **Circular deps:** 11 cặp.
- **God files (>400 dòng):** `KernelServiceProvider` 567, `AgentBatchProcessor` 557, `NarrativeLoomService` 466, `WorldState` 458 (y nguyên).
- **Môi trường xác nhận chạy được:** Laravel 13.13.0, PHP 8.4.21, PHPUnit 12.5.23.
