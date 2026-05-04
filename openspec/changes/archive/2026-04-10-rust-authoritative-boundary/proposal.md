## Why

Mỗi tick simulation hiện tại chạy Rust gRPC **hai lần** (zone physics + actor SoA) rồi **43 PHP engines** chạy lại trên cùng state — nhiều engine tính trùng những gì Rust đã tính (entropy, climate, market, war). Ngoài ra một số engine được gọi cả qua `RuleStage` lẫn `PhaseRegistry`, tạo ra double-execution. Config `rust_authoritative` (default `false`) chỉ gate 3/9 PostSnapshotHandlers, không ảnh hưởng 43 PhaseRegistry engines. Kết quả: **PHP ghi đè Rust output**, tính toán thừa ~30%, và state inconsistency tiềm ẩn.

## What Changes

- **BREAKING**: Set `rust_authoritative` default thành `true` — Rust output sẽ là nguồn sự thật cho zone physics, entropy, actor traits, economy fields.
- Phân loại chính thức 43 PHP engines thành 4 nhóm: SUPPLEMENT (giữ), OVERLAP (disable), STUB (remove khỏi registry), BRIDGE (đánh dấu).
- Thêm `rust_authoritative` check vào PhaseRegistry — skip OVERLAP engines khi Rust authoritative.
- Remove ~3 stub engines (FinanceEngine, DiplomacyEngine, ProductionChainEngine) khỏi PhaseRegistry (sẽ implement trong change riêng).
- Xóa double-execution: engines đã chạy qua `RuleStage` sẽ không chạy lại qua `PhaseRegistry`.
- Mở rộng `rust_authoritative` gate cho tất cả PostSnapshotHandlers (không chỉ 3).

## Capabilities

### New Capabilities
- `engine-authority-model`: Formal classification of PHP engines (SUPPLEMENT/OVERLAP/STUB/BRIDGE) with runtime gating based on `rust_authoritative` config.

### Modified Capabilities
- `unified-kernel`: PhaseRegistry gains authority-aware execution — skips OVERLAP engines when Rust is authoritative. Double-execution between RuleStage and PhaseRegistry eliminated.

## Impact

- **Config**: `worldos_simulation.php` — `rust_authoritative` default changes `false` → `true`.
- **PhaseRegistry**: New filtering logic based on engine authority classification.
- **KernelServiceProvider**: Engine registrations updated — stubs removed, overlaps tagged.
- **PostSnapshotHandlers**: All 9 handlers gated by `rust_authoritative`.
- **RuleStage**: Engines already called here will be deregistered from PhaseRegistry.
- **State consistency**: PHP engines will no longer overwrite Rust-computed fields.
