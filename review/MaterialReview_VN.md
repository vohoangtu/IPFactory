# Đánh giá Hệ thống Materials (Vật chất) trong IPFactory

## 📋 Tổng quan
Hệ thống Materials trong IPFactory chịu trách nhiệm mô phỏng sự tồn tại, tác động và biến đổi của các loại "vật chất" (bao gồm cả vật lý, xã hội và biểu tượng) trong một Universe. Khác với Actor, Materials đóng vai trò là các **thực thể nền tảng** tạo ra áp suất và cung cấp nguồn lực cho môi trường.

## 🏗️ Kiến trúc Hệ thống

### 1. Phân loại Bản thể học (Ontology)
Materials được phân loại thành 4 nhóm chính trong Backend (Laravel):
*   **Physical**: Vật chất vật lý thông thường.
*   **Institutional**: Các thực thể định chế/tổ chức.
*   **Symbolic**: Các khái niệm biểu tượng (ví dụ: đức tin, tư tưởng).
*   **Behavioral**: Các khuôn mẫu hành vi quy mô lớn.

### 2. Mô phỏng Hiệu năng cao (Rust Engine)
Phần lớn logic tính toán của Materials diễn ra trong `worldos-core`:
*   **Active Materials**: Các instance vật chất đang hoạt động trong một zone.
*   **Pressure Coefficients**: Mỗi loại vật chất có các hệ số tác động lên:
    *   **Entropy**: Làm tăng hoặc giảm sự hỗn loạn.
    *   **Order**: Khả năng duy trì cấu trúc.
    *   **Innovation**: Thúc đẩy tri thức.
    *   **Growth**: Thúc đẩy năng lượng tự do.

## ⚙️ Các Cơ chế Đặc biệt

### 1. Cộng hưởng Vật chất (Material Resonance)
Đây là một cơ chế quan trọng trong WorldOS V6:
*   **Quy tắc**: Nếu trong cùng một Zone có từ 2 instance vật chất cùng loại (cùng slug) trở lên, hiệu ứng của chúng sẽ được **khuếch đại 1.5x**.
*   **Ý nghĩa**: Khuyến khích sự tập trung vật chất để tạo ra những thay đổi mang tính đột biến trong môi trường.

### 2. Áp suất Vật chất (Material Stress)
*   Materials tạo ra "áp suất" lên Zone. Chỉ số [material_stress](file:///c:/projects/IPFactory/engine/worldos-core/src/types.rs#581-592) ảnh hưởng trực tiếp đến khả năng tồn tại và sự ổn định của các nền văn minh.
*   **Stress quá cao (>0.75)**: Có thể gây ra sự sụp đổ cấu trúc xã hội hoặc làm giảm chỉ số "Ý nghĩa" (Meaning) của nền văn minh.

### 3. Lõi đệ quy (Recursive Core)
Đây là một tính năng cao cấp cho phép một loại vật chất chứa đựng một mô phỏng con bên trong nó:
*   **Feedback Loop**: Kết quả của mô phỏng con (Virtual Knowledge/Entropy) sẽ phản hồi ngược lại Zone cha.
*   **Ví dụ**: Một "Thư viện cổ" có thể được mô phỏng như một vật chất có Recursive Core, liên tục tạo ra kiến trúc tri thức cho thế giới thực thông qua vòng lặp phản hồi.

## 🔄 Quy trình Lifecycle
1.  **Seed/Spawn**: Tạo ra instance từ Backend hoặc thông qua các sự kiện ngẫu nhiên trong Engine.
2.  **Simulation**: Engine Rust tính toán tác động mỗi tick (Resonance, Stress, Feedback).
3.  **Mutation/Decay**: Vật chất có thể biến đổi sang loại khác hoặc biến mất khi Entropy quá cao (hiện tại phần này đang được hoàn thiện trong Backend thông qua [MaterialMutationDag](file:///c:/projects/IPFactory/backend/app/Modules/World/Services/MaterialMutationDag.php#8-15)).

## 📡 Đánh giá chung
Hệ thống Materials hiện tại có **phần lõi mô phỏng trong Rust rất mạnh mẽ và linh hoạt**, hỗ trợ cả các khái niệm trừu tượng thông qua Recursive Core. Phần quản lý logic nghiệp vụ và chuyển đổi trạng thái trong Backend (Laravel) đang ở giai đoạn khung (skeleton) và cần được bổ sung các quy tắc cụ thể (Mutations, Lifecycle rules) để khai thác hết tiềm năng của Engine.

---
*Người thực hiện: Antigravity*
*Ngày: 2026-03-25*
