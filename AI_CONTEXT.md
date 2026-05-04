# WorldOS V6 - AI Instruction & Context

Tài liệu này dành cho các AI Agent (Antigravity, Cursor, etc.) để hiểu cách tương tác với dự án này.

## 1. Môi trường hệ thống (Environment)
- **Runtime:** Dự án chạy hoàn toàn trên **Docker**.
- **Backend:** PHP 8.3 (Laravel 12) - **Orchestrator**.
- **Engine:** Rust - **Core Engine**.
- **Frontend:** Next.js (React 19).
- **Real-time:** Centrifugo (Port 8000).

## 2. Quy tắc quan trọng (CRITICAL RULES)
- **KHÔNG tự ý chạy `composer install` hoặc `npm install` trên máy Host.** 
- Tất cả các lệnh artisan/composer PHẢI chạy thông qua Docker:
  `docker compose -f deployment/docker-compose.prod.yml exec backend [command]`
- **PHP Requirement:** Luôn giữ `composer.json` ở mức `php: ^8.3` để tương thích với Docker image hiện tại.
- **DDD & Patterns:** Tuân thủ Domain Driven Design, Event Driven, Repository và Action pattern cho PHP.

## 3. Giao tiếp (Communication)
- **Ngôn ngữ:** Trình bày bằng **Tiếng Việt**.
- **Phong cách:** Proactive (chủ động) nhưng phải Cautious (thận trọng) với Docker volume. Luôn kiểm tra file trước khi sửa.
- **Duy trì Ngữ cảnh:** Mọi phiên làm việc phải kết thúc bằng việc cập nhật trạng thái vào file `.dev_status.md` ở thư mục gốc để hỗ trợ làm việc trên nhiều máy.

## 4. Cấu trúc thư mục chính
- `backend/`: **Orchestrator** (Laravel).
- `engine/`: **Core Engine** (Rust).
- `frontend/`: Mã nguồn Next.js.
