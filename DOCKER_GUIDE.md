# Hướng dẫn sử dụng Docker cho WorldOS V6

Tài liệu này hướng dẫn chi tiết cách thiết lập và vận hành môi trường phát triển WorldOS V6 sử dụng Docker.

## 1. Yêu cầu (Prerequisites)

Trước khi bắt đầu, hãy đảm bảo máy tính của bạn đã cài đặt:

-   **Docker Desktop** (Windows/Mac) hoặc **Docker Engine** (Linux).
-   **Docker Compose** (thường đi kèm với Docker Desktop).
-   **Git** (để clone repository).

## 2. Cấu trúc Docker

Các tệp cấu hình Docker nằm trong thư mục `deployment/`:

-   `docker-compose.prod.yml`: Cấu hình cho môi trường phát triển (Development).
-   `backend.prod.Dockerfile`: Dockerfile cho backend (Laravel/PHP).
-   `engine.prod.Dockerfile`: Dockerfile cho engine mô phỏng (Rust).
-   `nginx/`: Cấu hình Nginx reverse proxy.

## 3. Khởi chạy môi trường phát triển

Để khởi chạy toàn bộ hệ thống, mở terminal tại thư mục gốc của dự án (`c:\projects\IPFactory`) và chạy lệnh sau:

```powershell
docker compose -f deployment/docker-compose.prod.yml up -d --build
```

Lệnh này sẽ:
1.  Build các image cho `backend` và `engine`.
2.  Tải các image có sẵn (`postgres`, `redis`, `nginx`, `centrifugo`).
3.  Khởi động các container ở chế độ background (`-d`).

### Quá trình khởi tạo tự động

Container `backend` được cấu hình để tự động thực hiện các bước sau khi khởi động lần đầu:
-   Copy `.env.example` sang `.env` (nếu chưa có).
-   Cài đặt dependencies (`composer install`).
-   Tạo key ứng dụng (`key:generate`).
-   Chạy migration (`migrate`).
-   Seed dữ liệu mẫu và chạy kịch bản demo (`worldos:demo-scenario`) nếu chưa có file đánh dấu `.demo_inited`.

Bạn có thể theo dõi quá trình này bằng lệnh logs:

```powershell
docker compose -f deployment/docker-compose.prod.yml logs -f backend
```

## 4. Các dịch vụ chính

Sau khi khởi chạy thành công, các dịch vụ sẽ hoạt động tại các cổng sau:

| Dịch vụ | Cổng Host | Mô tả |
| :--- | :--- | :--- |
| **Nginx (Web Server)** | `8080` | Cổng chính truy cập API Backend (http://localhost:8080). |
| **Backend (PHP-FPM)** | `9000` | Xử lý logic nghiệp vụ Laravel. |
| **Engine (Rust)** | `50052` | Simulation Engine (HTTP bridge). |
| **Postgres (TimescaleDB)** | `5432` | Cơ sở dữ liệu chính & Time-series. |
| **Redis** | `6379` | Cache & Queue. |
| **Centrifugo** | `8000` | Real-time WebSocket server. |

Nếu dùng `deployment/docker-compose.prod.yml`, hệ thống có thêm service **Redpanda** (Kafka API + REST). Để bật event stream Kafka: đặt env `WORLDOS_EVENT_STREAM_KAFKA_ENABLED=true` và `WORLDOS_EVENT_STREAM_REST_PROXY_URL=http://redpanda:8082`; consumer: `php artisan worldos:kafka-consume-events`. Chi tiết: `backend/docs/EVENT_STREAM_SCHEMA.md`.

## 5. Thao tác thường gặp

### Truy cập Shell của Backend

Để chạy các lệnh `php artisan` hoặc `composer`, bạn nên truy cập vào shell của container `backend`:

```powershell
docker compose -f deployment/docker-compose.prod.yml exec backend bash
```

Sau khi vào shell, bạn có thể chạy:
```bash
# Ví dụ: Chạy test
php artisan test

# Ví dụ: Tạo migration mới
php artisan make:migration create_new_table
```

### Xem Logs

Xem logs của tất cả các dịch vụ:
```powershell
docker compose -f deployment/docker-compose.prod.yml logs -f
```

Xem logs của một dịch vụ cụ thể (ví dụ `engine`):
```powershell
docker compose -f deployment/docker-compose.prod.yml logs -f engine
```

### Reset Database

Nếu muốn xóa sạch dữ liệu và khởi tạo lại từ đầu:

1.  Truy cập shell backend:
    ```powershell
    docker compose -f deployment/docker-compose.prod.yml exec backend bash
    ```
2.  Chạy lệnh reset và seed lại:
    ```bash
    php artisan migrate:fresh --seed
    # Hoặc chạy demo scenario
    php artisan worldos:demo-scenario
    ```

### Dừng hệ thống

Để dừng và xóa các container (dữ liệu trong volume vẫn được giữ lại):

```powershell
docker compose -f deployment/docker-compose.prod.yml down
```

Để dừng và **xóa sạch dữ liệu** (volumes):

```powershell
docker compose -f deployment/docker-compose.prod.yml down -v
```

## 6. Xử lý sự cố (Troubleshooting)

-   **Lỗi cổng (Port already in use)**: Đảm bảo các cổng 8080, 5432, 6379 không bị chiếm dụng bởi các ứng dụng khác trên máy host (ví dụ: XAMPP, Laragon, hoặc dịch vụ Postgres cài trực tiếp).
-   **Lỗi kết nối DB**: Kiểm tra logs của `postgres` để đảm bảo database đã khởi động xong (`ready to accept connections`) trước khi backend kết nối. Backend có cơ chế `depends_on` với `healthcheck` nhưng đôi khi vẫn cần chờ thêm.
-   **Thay đổi code không cập nhật**:
    -   Với `backend`: Code được mount từ host vào container nên thay đổi sẽ cập nhật ngay lập tức.
    -   Với `engine`: Cần restart container để binary được build lại (hoặc cấu hình `cargo watch` nếu có).

---
**Lưu ý**: Luôn chạy các lệnh `docker compose` từ thư mục gốc của dự án (`c:\projects\IPFactory`) để đường dẫn `deployment/...` được giải quyết chính xác.
