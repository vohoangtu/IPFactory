# Review Kiến Trúc Actor - IPFactory

## 📋 Tổng Quan
Hệ thống Actor trong IPFactory được thiết kế theo mô hình lai (Hybrid Architecture) giữa **Backend (Laravel)** và **Simulation Engine (Rust)**. Kiến trúc này tách biệt rõ ràng giữa việc quản lý dữ liệu hướng Narrative (Kể chuyện) và mô phỏng logic quy mô lớn (High-performance Simulation).

## 🏗️ Phân Tích Cấu Trúc

### 1. Backend Layer (Laravel 12)
Nằm tại [app/Models/Actor.php](file:///c:/projects/IPFactory/backend/app/Models/Actor.php) và các module liên quan.

*   **Actor Model**: Đóng vai trò là "Single Source of Truth" cho dữ liệu bền vững. Lưu trữ đầy đủ thông tin về vòng đời (`is_alive`, `birth_tick`, `death_tick`), đặc điểm (`traits`), chỉ số ([metrics](file:///c:/projects/IPFactory/engine/worldos-core/src/universe.rs#921-942), `stats`), và các mối quan hệ phức tạp ([religions](file:///c:/projects/IPFactory/backend/app/Models/Actor.php#74-81), [factions](file:///c:/projects/IPFactory/backend/app/Models/Actor.php#96-102), `causal_beliefs`).
*   **Narrative Integration**: Kết nối với `SupremeEntity` cho các "Vĩ nhân" (Great Persons) và tích hợp vào hệ thống Social Graph thông qua [RelationalGraphProvider](file:///c:/projects/IPFactory/backend/app/Modules/SocialGraph/Services/RelationalGraphProvider.php#11-192).
*   **API Interface**: [ActorController](file:///c:/projects/IPFactory/backend/app/Modules/WorldOS/Http/Controllers/Api/ActorController.php#13-54) cung cấp các endpoint để theo dõi sự kiện ([events](file:///c:/projects/IPFactory/backend/app/Models/Actor.php#63-67)), quyết định ([decisions](file:///c:/projects/IPFactory/backend/app/Modules/WorldOS/Http/Controllers/Api/ActorController.php#35-43)) và trạng thái của Actor từ Frontend.

### 2. Engine Layer (Rust)
Nằm tại `engine/worldos-core`.

*   **ActorTable (SoA)**: Sử dụng cấu trúc **Struct of Arrays** (SoA) trong `types.rs` để tối ưu hóa hiệu năng Cache CPU, cho phép mô phỏng hàng triệu Actor đồng thời.
*   **Trait Vector (17D)**: Mỗi `Agent` sở hữu một vector 17 chiều đại diện cho tính cách (Thống trị, Tham vọng, Tham chiếu xã hội, Nhận thức, Cảm xúc).
*   **Behavior Pipeline**: Các Engine con trong `universe.rs` xử lý các lớp hành vi khác nhau:
    *   `EmotionFieldEngine`: Cảm xúc lan truyền.
    *   `BeliefSystemEngine`: Hệ thống niềm tin.
    *   `PowerStructureEngine`: Cấu trúc quyền lực và cưỡng chế.
    *   `MassBehaviorEngine`: Hành vi đám đông (Crows).
    *   `BehaviorGraphEngine`: Ra quyết định dựa trên đồ thị hành vi.
*   **Micro Mode**: Một cơ chế đặc biệt cho phép "zoom" vào chi tiết một Zone khi có khủng hoảng, mô phỏng hành vi Actor ở mức độ chi tiết hơn trước khi tổng hợp ngược lại kết quả (Macro Delta).

## 🔄 Luồng Tương Tác (Cross-Layer)
1.  **Spawn**: Laravel tạo Actor và đồng bộ dữ liệu ban đầu sang Engine.
2.  **Tick**: Engine thực hiện mô phỏng hàng loạt (Decision making, Trait drift, Vocation updates).
3.  **Sync**: Kết quả (Events, Decisions, Metrics changed) được đẩy ngược lại Laravel để lưu trữ và hiển thị.
4.  **Narrative Influence**: Các sự kiện Narrative từ Laravel có thể tạo ra "tác động" (Influences) trực tiếp vào Engine (ví dụ: tạo Dark Attractor hoặc Emotion Spike).

## 📊 Impact Analysis
*   **Files Affected**: `app/Models/Actor.php`, `app/Modules/WorldOS/Http/Controllers/Api/ActorController.php`, `engine/worldos-core/src/types.rs`, `engine/worldos-core/src/universe.rs`, `engine/worldos-core/src/agent.rs`.
*   **Runtime Risk**: Thấp. Đây là review hiện trạng, không thay đổi logic.
*   **Data Risk**: Không có rủi ro dữ liệu.
*   **Rollback Strategy**: N/A.

## 💡 Đánh Giá & Đề Xuất
*   **Ưu điểm**: Tách biệt hiệu năng (SoA trong Rust) và nghiệp vụ (Eloquent trong Laravel) rất tốt. Hệ thống Trait 17D đủ sâu để tạo ra các hành vi đa dạng.
*   **Lưu ý**: Cần đảm bảo tính đồng nhất giữa `traits_mask` trong `ActorTable` (Rust) và `traits` (JSON array) trong Laravel Model khi mở rộng tính năng.

---
*Người thực hiện: Antigravity*
*Ngày: 2026-03-25*
