# Review hệ thống WorldOS V6

**Ngày:** 2026-06-05
**Người review:** Claude Code (Opus 4.8)
**Phạm vi:** Phân tích tĩnh toàn bộ 5 service dựa trên code thực tế tại commit `c9e70f9`.
**Phương pháp:** Đọc trực tiếp diff Wave 1 (10 file chưa commit) + 3 agent thăm dò song song (tech debt, độ phủ test, tuân thủ kiến trúc backend) + knowledge graph 18.298 nodes.

> **Lưu ý giới hạn:** Chưa chạy được test/Docker (môi trường không có Docker). Mọi nhận định về test là dựa trên **kiểm kê file tĩnh**, không phải kết quả chạy thực tế.

---

## Đánh giá tổng quan

WorldOS V6 là một hệ thống **tham vọng và có chiều sâu kỹ thuật thật** — không phải vỏ rỗng. Engine Rust và 2 service Python sạch sẽ, production-ready. Tuy nhiên backend PHP (1.140 file) mang **nợ kiến trúc đáng kể** do tăng trưởng nhanh.

**Trạng thái:** *"Chạy được nhưng cần củng cố nền tảng trước khi mở rộng tiếp."*

| Service | Quy mô | Tech debt | Test (tĩnh) | Verdict |
|---|---|---|---|---|
| Backend (PHP/Laravel) | 1.140 file | Trung bình | Vừa (3 module 0 test, 47 test skip) | ⚠️ Cần củng cố |
| Engine (Rust) | 52 file | **Sạch** | Mỏng (56 test rải rác) | ✅ Tốt |
| Frontend (Next.js) | 190 file | Thấp | Mỏng (0 component test) | ⚠️ Test yếu |
| narrative-loom (Py) | 63 file | **Sạch** | Vừa (37 test) | ✅ Tốt |
| sim/social-engine (Py) | 46 file | **Sạch** | Vừa (34 test) | ✅ Tốt |

---

## ✅ Điểm mạnh

### 1. Code Wave 1 (chưa commit) có chất lượng cao thật sự
Đã đọc trực tiếp diff:

- **`GeographyEngine.php`**: thay `mt_srand()` toàn cục bằng `Randomizer(Mt19937)` cô lập — sửa lỗi rò rỉ random giữa các tick chạy song song (Octane/FPM/async). Kỹ thuật tốt.
- **`GenerateVisualAssetJob.php`**: sửa bug thật — pattern `orWhere()->update()` cũ có thể ghi đè 2 row trong 1 UPDATE; nay query-rồi-update từng row, có audit log + bounded blast radius.
- **`SelfImprovingSimulationService.php`**: thêm cổng config auto-deploy **mặc định TẮT** + audit log channel riêng + deployment ID. Tư duy production rõ ràng (không để AI tự deploy rule mà không có người duyệt).
- **`belief.rs` + `agent.rs`**: thay hardcode `17` bằng hằng `TRAIT_COUNT` + `debug_assert` — đúng chính xác quy tắc CLAUDE.md ("never hardcode trait indices").

### 2. Engine Rust & Python services sạch
0 marker `todo!()` / `unimplemented!()` / `NotImplementedError` / placeholder trên cả 3 service.

### 3. Kiến trúc tổng thể rõ ràng
Modular monolith, simulation loop 5 phase, event-driven, knowledge graph 18k nodes — nhất quán với tài liệu.

---

## ⚠️ Vấn đề cần xử lý (xếp theo độ ưu tiên)

### 🔴 P0 — Code Wave 1 chất lượng cao đang chưa được commit
10 file hardening (mô tả trong `.dev_status.md` là "đã hoàn thành") thực tế đang ở working tree, **chưa commit**. Commit `c9e70f9` "Waves 1-5" không chứa chúng.
**Rủi ro:** mất việc nếu reset nhầm; trạng thái git không phản ánh thực tế.
**→ Nên commit ngay.**

Danh sách 10 file:
- `backend/app/Broadcasting/CentrifugoBroadcaster.php`
- `backend/app/Jobs/GenerateVisualAssetJob.php`
- `backend/app/Modules/Intelligence/Listeners/ActorBornEventListener.php`
- `backend/app/Modules/Intelligence/Listeners/ActorDiedEventListener.php`
- `backend/app/Modules/Narrative/Services/HeroImageService.php`
- `backend/app/Modules/Narrative/Services/NarrativeLoomService.php`
- `backend/app/Modules/Simulation/Services/Core/SelfImprovingSimulationService.php`
- `backend/app/Modules/World/Services/GeographyEngine.php`
- `engine/worldos-core/src/agent.rs`
- `engine/worldos-grpc/src/belief.rs`

### 🔴 P0 — Coupling chéo module (vi phạm DDD)
~**947 import concrete class chéo module** (vi phạm rule "chỉ giao tiếp qua Contracts"). Tập trung ở Simulation (517), Intelligence (154), Narrative (132), WorldOS (55), Institutions (53).

> **Sắc thái quan trọng:** phần lớn là import **Model** chéo module (Eloquent) — codebase đang pragmatic chia sẻ Model. Đây vẫn là nợ kiến trúc thật, nhưng "947" gồm cả Model-sharing chứ không phải 947 chỗ gọi Service sai. Cần phân biệt khi xử lý: ưu tiên cắt phụ thuộc **Service/Action** concrete chéo module trước (nguy hiểm hơn), Model-sharing có thể chấp nhận tạm.

Ví dụ điển hình:
- `WorldOS/Services/UniverseMetricsService.php` — import 8 Model concrete + Service từ nhiều module.
- `Institutions/Services/GreatFilterEngine.php` — import từ World, Intelligence, Narrative, Simulation (9 import trực tiếp).
- `Simulation/Services/Society/ActorCognitiveService.php` — 30+ import chéo module.

### 🟠 P1 — `declare(strict_types=1)` thiếu ở 93% file backend (1.066/1.140)
Vi phạm trực tiếp CLAUDE.md ("PHP: PSR-12, strict typing"). Fix tự động bằng Pint. Việc rẻ, lợi lớn.

### 🟠 P1 — God files (>400 dòng)
| File | Dòng |
|---|---|
| `Simulation/Providers/KernelServiceProvider.php` | 567 |
| `Simulation/Core/Runtime/Kernel/AgentBatchProcessor.php` | 557 |
| `Narrative/Services/NarrativeLoomService.php` | 466 |
| `Simulation/Core/Runtime/State/WorldState.php` | 458 |

Nên tách theo single-responsibility.

### 🟠 P1 — Lỗ hổng test
- **Backend:** 3 module **0 test** (SocialGraph, Institutions, Achievement) + **47 `markTestSkipped`** (nợ từ refactor: removed services như BarterMarketResolver, HarvestingService...).
- **Frontend:** **0 component/page test** — toàn bộ UI chưa kiểm thử (chỉ test lib/hooks).
- **Engine:** culture_engine, vocation system, gRPC transport, Kafka chưa có test.

### 🟡 P2 — Vệ sinh repo & dead code
- **11 script PHP rời rạc** ở root backend & repo (`check_scars.php`, `debug_*.php`, `fix_namespaces.php`, `refactor_services.php`, `temp_seed_loom_agents.php`, `update_tickrates.php`, `verify_autonaut.php`). Nên chuyển thành Artisan Command hoặc xóa.
  - `verify_autonaut.php` ở **repo root** có rủi ro lộ nếu deploy.
  - `temp_seed_loom_agents.php` + `fix_namespaces.php` gợi ý refactor dở dang.
- **3 engine bị gỡ thành stub rỗng** (DiplomacyEngine, FinanceEngine, ProductionChainEngine — `KernelServiceProvider.php:127-129`) + **5 narrative strategy rỗng** (AnomalyNarrativeStrategy, DeathNarrativeStrategy, RebirthNarrativeStrategy, ParadoxNarrativeStrategy, LegacyNarrativeStrategy) — cần quyết định: implement hay xóa dead code.
- **Module `Achievement` không có trong tài liệu** (CLAUDE.md ghi 9 module, thực tế có 10).
- Module SocialGraph, Psychology, Knowledge, Achievement thiếu nhiều thư mục chuẩn (Actions/, Contracts/, Events/, Jobs/...).
- 1 `console.log` còn sót: `frontend/src/components/ui/audio/AtmospherePlayer.tsx:81`.

---

## Khuyến nghị hành động (thứ tự ưu tiên)

1. **Commit ngay** 10 file Wave 1 (chất lượng tốt, đang treo).
2. Chạy `pint` thêm `strict_types` toàn backend (1 lệnh, fix 1.066 file).
3. Dọn 11 script rời → Artisan Command; xóa `verify_autonaut.php` ở root.
4. Viết **architecture test** (PHPUnit) chặn import Service concrete chéo module — ngăn coupling tái diễn, thay vì sửa thủ công 947 chỗ.
5. Bổ sung test cho 3 module trống + component test frontend.
6. Quyết định số phận 3 stub engine + 5 strategy rỗng (implement/xóa).
7. Tách 4 god files lớn nhất.
8. Đồng bộ tài liệu: cập nhật CLAUDE.md (9 → 10 module, bổ sung Achievement).

---

## Phụ lục: số liệu thô

- **Tech debt markers:** Backend 22 (medium), Frontend 12 (low, chủ yếu test cast), Engine 0, narrative-loom 0, sim 0.
- **Test (tĩnh):** Backend ~359 method / 112 file (47 skip), Engine 56 test, Frontend ~96 method / 6 file (0 component), narrative-loom 37, sim 34.
- **Knowledge graph:** 18.298 nodes, 15.785 edges, 92 layers, 69 tour steps.
