# Quan sát & Sao lưu / Khôi phục

**Status:** Stable · **Cập nhật:** 2026-05-11

## 1. Logging
- Log có cấu trúc (JSON ở staging/prod), kèm `tenant_id`, `channel_account_id`, `request_id`/`trace_id`, `job` (nếu trong queue). Không log token/secret/PII đầy đủ (mask SĐT, không log payload chứa thông tin nhạy cảm trừ khi cần debug và có kiểm soát).
- Mức log: `error` (cần xem ngay), `warning` (bất thường nhưng tự xử lý — vd lùi trạng thái, retry), `info` (sự kiện nghiệp vụ quan trọng — kết nối shop, sync run, push tồn), `debug` (chi tiết, tắt ở prod).
- Tập trung log (Phase sau): đẩy về một nơi (Loki/ELK/cloud logging) để tra cứu.

## 2. Lỗi & cảnh báo
- **Sentry** (hoặc tương đương): bắt mọi exception chưa xử lý (web + queue) kèm `trace_id`, breadcrumb. Cảnh báo khi xuất hiện lỗi mới / spike.
- **Cảnh báo nghiệp vụ** (gửi kênh nội bộ — email/Telegram/Slack admin):
  - `channel_account.status` chuyển `expired`/`revoked` (token hỏng).
  - Tỉ lệ lỗi sync đơn / push tồn tăng đột biến.
  - Queue tồn đọng vượt ngưỡng; nhiều failed jobs.
  - Webhook một sàn ngừng đến trong X giờ (`CheckWebhookHeartbeat`).
  - Đối soát lệch chưa giải thích (Phase 6).
  - Sắp đầy disk DB / MinIO.

## 3. Metrics
- Hạ tầng: CPU/RAM/disk app & worker & DB & redis; kết nối DB; độ trễ DB; hit-rate cache; độ sâu queue; throughput Horizon.
- Nghiệp vụ: đơn đồng bộ/giờ theo sàn; độ trễ webhook→xử lý; số đơn theo trạng thái; số listing `sync_status=error`; số token sắp hết hạn; thời gian sinh bulk label.
- (Phase sau) Prometheus + Grafana hoặc dịch vụ metrics; trước mắt xem qua Horizon + log + truy vấn DB.

## 4. Health checks
- `GET /api/v1/health`: kiểm DB, Redis, MinIO, Gotenberg, queue (có worker đang chạy không), thời gian sync gần nhất. Dùng cho load balancer + giám sát ngoài (uptime monitor).

## 5. Sao lưu (Backup)
- **PostgreSQL**: `pg_dump` hằng ngày + giữ WAL để **PITR** (point-in-time recovery); lưu bản sao ra nơi tách biệt (object storage khác / vùng khác). Giữ: 7 ngày daily + 4 weekly + vài monthly (chốt chính sách lưu trữ ở đây).
- **MinIO/S3** (label PDF, ảnh, export): bật versioning hoặc snapshot/replicate sang bucket khác. File label có giá trị pháp lý/giao hàng ⇒ giữ ít nhất theo thời hạn nghiệp vụ.
- **Cấu hình & secrets**: lưu trong secret manager (có backup riêng), không nằm trong DB dump.
- Redis: chủ yếu là cache/queue ⇒ không cần backup chặt; nếu mất ⇒ job đang chờ có thể mất → có cơ chế re-drive (webhook_events lưu DB, sync chạy lại theo cursor) nên hệ thống tự phục hồi.

## 6. Khôi phục (DR) — RULES
1. **Test khôi phục định kỳ** (ít nhất mỗi quý): dựng môi trường từ backup, chạy smoke test. Backup không test = không có backup.
2. Có runbook khôi phục: thứ tự dựng (Postgres → migrate? không, restore dump → Redis trống → MinIO → app → worker), kiểm tra tính nhất quán.
3. RPO mục tiêu: ≤ 1 giờ (nhờ WAL/PITR). RTO mục tiêu: vài giờ (giai đoạn đầu) — ghi rõ con số cam kết ở đây khi chốt.
4. Sự cố mất dữ liệu một phần: ưu tiên dùng `webhook_events` + polling theo cursor + sổ cái `inventory_movements` để tái dựng trạng thái.
5. Mọi sự cố lớn ⇒ viết post-mortem (không đổ lỗi cá nhân), bổ sung phòng ngừa.

## 7. Việc Phase 0
- [x] Tích hợp Sentry (web + queue) — `sentry/sentry-laravel` đã cài; `config/sentry.php` published; `\Sentry\Laravel\Integration::handles($exceptions)` trong `bootstrap/app.php`. Bật bằng cách đặt `SENTRY_LARAVEL_DSN` (không có DSN ⇒ no-op).
- [x] Log JSON + `trace_id` middleware — channel `json` (stdout, `Monolog\Formatter\JsonFormatter`) ở `config/logging.php`; middleware `AssignRequestId` (chạy đầu pipeline) gắn `request_id` vào log context (`Log::shareContext`), Sentry scope tag, header `X-Request-Id`, và trường `error.trace_id` trong envelope lỗi. Docker đặt `LOG_CHANNEL=stack`, `LOG_STACK=json`.
- [x] `GET /api/v1/health` — `HealthController`: kiểm DB (critical), cache, Redis, queue (Horizon master supervisor nếu queue chạy trên Redis); trả `200` khi mọi check *critical* "ok", `503` nếu không; không bao giờ ném exception.
- [x] Script backup Postgres + MinIO + cron — `scripts/backup.sh` (`pg_dump` qua `docker compose exec postgres` + `mc mirror` bucket `omnisell`; prune bản local > 14 ngày; có ví dụ crontab); khôi phục: `scripts/restore.sh <pg-dump.sql.gz> [<minio-dir>]` (theo thứ tự ở §6). *Còn lại ở môi trường thật:* bật WAL/PITR cho Postgres, versioning/replicate MinIO sang bucket khác vùng, đẩy backup off-site, test khôi phục định kỳ.
- [ ] Cấu hình cảnh báo cơ bản (Sentry + queue depth) — *cấu hình ở môi trường thật (Sentry alert rules + giám sát độ sâu queue qua Horizon), không phải trong repo*.
