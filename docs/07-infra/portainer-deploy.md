# Triển khai trên Portainer (prod) — Runbook

**Status:** Living document · **Cập nhật:** 2026-05-15

> Áp dụng cho stack **`docker-compose.prod.yml`** chạy qua **Portainer Git stack**. Đọc kèm: [`environments-and-docker.md`](environments-and-docker.md) (môi trường + Docker), [`queues-and-scheduler.md`](queues-and-scheduler.md) (queue/Horizon/scheduler), [`observability-and-backup.md`](observability-and-backup.md).

## 0. TL;DR — sau MỖI lần deploy

1. Portainer → Stack → **Update the stack** (kéo Git mới + build image + recreate containers).
2. Containers → **`cmb-app` → Console → Connect** (`/bin/sh`):
   ```sh
   php artisan migrate --force
   php artisan horizon:status     # phải in "Horizon is running."
   php artisan queue:failed       # rỗng là tốt
   ```
3. `curl -s http://localhost/api/v1/health` trong `cmb-web` (hoặc `https://<domain>/api/v1/health` từ ngoài) → `ok`.
4. Kiểm `cmb-worker` & `cmb-scheduler` đang **running/healthy** trong Portainer.

> **`RUN_MIGRATIONS=false` ở prod là cố ý** (tránh nhiều replica đua nhau migrate) ⇒ app **không tự migrate**. Quên bước 2 ⇒ app lỗi `column/table not found` (mỗi đợt feature thường kèm migration mới).

## 1. Webhook chạy qua đâu — và vì sao "im"

```
TikTok ──HTTPS POST /webhook/tiktok──▶ reverse proxy (NPM/Caddy, network "proxy")
                                          └▶ cmb-web (nginx :80) ──▶ cmb-app (php-fpm :9000)
                                                 ├─ verify chữ ký → ghi 1 dòng webhook_events (status=pending)
                                                 └─ TRẢ 200 NGAY + đẩy job ProcessWebhookEvent lên queue "webhooks" (Redis)
                                          ▼
                                    cmb-worker (php artisan horizon)
                                       └─ chạy ProcessWebhookEvent → cập nhật trạng thái đơn → status=processed
```

- Request webhook chỉ **ghi log + đẩy job** rồi trả 200 trong vài ms. Việc cập nhật đơn nằm ở **job**, do **`cmb-worker` (Horizon)** chạy.
- `cmb-worker` không chạy ⇒ `webhook_events` đọng `status=pending` ⇒ đơn **không bao giờ** chuyển trạng thái. Đây là nguyên nhân #1 của "có lịch sử webhook nhưng đơn không đổi".
- *(Từ 2026-05-15)* có **fast-path**: nếu push mang theo `data.order_status` (lưu vào `webhook_events.order_raw_status`) và đơn đã tồn tại ⇒ trạng thái được áp ngay (`OrderUpsertService::applyStatusFromWebhook`) kể cả khi `fetchOrderDetail` API tạm lỗi. **Vẫn cần `cmb-worker` chạy** để xử lý job đó.

### Container bắt buộc sống & khoẻ
| Container | Lệnh | Vai trò |
|---|---|---|
| `cmb-web` | nginx | nhận HTTP (gồm webhook + SPA + API) |
| `cmb-app` | php-fpm | xử lý PHP |
| `cmb-redis` | redis-server | backend queue / cache / session |
| `cmb-worker` | `php artisan horizon` | **xử lý job**: `webhooks`, `orders-sync`, `tokens`, `inventory-push`, `listings`, `customers`, … |
| `cmb-scheduler` | `php artisan schedule:work` | cron 1 instance: polling đơn (~10'), refresh token (30'), `db:partitions:ensure`, `customers:recompute-stale`, prune… |
| `cmb-postgres` | postgres | dữ liệu |
| `cmb-minio` (+`minio-init`) | minio | object storage (label/phiếu in — Phase 3) |
| `cmb-gotenberg` | gotenberg | HTML→PDF (Phase 3) |

Webhook hoạt động cần: `cmb-web` + `cmb-app` + `cmb-redis` + **`cmb-worker`**. `cmb-scheduler` không bắt buộc cho webhook nhưng thiếu nó ⇒ mất polling backup (mất webhook = mất đơn) và token không tự gia hạn.

## 2. Cấu hình stack trên Portainer (lần đầu)

- **Compose path** = `docker-compose.prod.yml`. **KHÔNG** trỏ vào `docker-compose.yml` — file đó là DEV (tự merge `docker-compose.override.yml` ⇒ bind-mount source, publish port DB/Redis, chạy vite/mailpit — sai cho prod).
- **Network ngoài**: `docker network create proxy` trước. Phải có reverse proxy (Nginx Proxy Manager / Caddy / Traefik) **trên network `proxy`**, terminate TLS, map `https://<domain>` → `http://cmb-web:80`. Service `cmb-web` đã gắn sẵn vào network `proxy`.
- **Environment variables** (điền trong mục "Environment variables" của Portainer):

| Biến | Bắt buộc | Ghi chú |
|---|---|---|
| `APP_KEY` | ✅ | Sinh sẵn: `docker run --rm cmbcoreseller-app:latest php artisan key:generate --show` rồi dán vào. **Đừng** để app tự sinh ở prod. |
| `APP_URL` | ✅ | `https://<domain>` — phải HTTPS public (TikTok redirect callback + gửi webhook về đây) |
| `SANCTUM_STATEFUL_DOMAINS` / `SESSION_DOMAIN` | ✅ | = `<domain>` |
| `DB_PASSWORD` | ✅ | |
| `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` | ✅ | đồng thời là root user/pass của MinIO |
| `AWS_BUCKET` | — | mặc định `omnisell` |
| `MAIL_HOST` / `MAIL_PORT` / `MAIL_USERNAME` / `MAIL_PASSWORD` / `MAIL_FROM_ADDRESS` | nên có | gửi mail (mời thành viên…) |
| `SENTRY_LARAVEL_DSN` | nên có | giám sát lỗi |
| `TIKTOK_APP_KEY` / `TIKTOK_APP_SECRET` / `TIKTOK_SERVICE_ID` | ✅ (để dùng TikTok) | từ TikTok Shop Partner Center |
| `TIKTOK_SANDBOX` | — | `false` ở prod |
| `INTEGRATIONS_CHANNELS` | — | mặc định `manual,tiktok` |
| `LOG_LEVEL` | — | mặc định `info` |
| `APP_TAG` | — | tag image (mặc định `latest`) |

- **TikTok Shop Partner Center** (phía sàn, không phải code):
  - Webhook URL = `https://<domain>/webhook/tiktok`
  - Authorization Redirect URL = `https://<domain>/oauth/tiktok/callback`
  - Bật scopes: Authorization, Shop, Order, Webhook (và Product khi dùng đẩy tồn — Phase 2).
  - Domain phải public + HTTPS (TikTok không gọi vào IP nội bộ / `http://`).

## 3. Sau khi cập nhật env / code trên Portainer

`entrypoint.sh` chạy `config:cache` + `route:cache` **mỗi lần container khởi động** ⇒ recreate container = nạp lại env + code đầy đủ. Hệ quả:

- Đổi Environment variables trong Portainer **mà không redeploy** ⇒ container vẫn dùng env cũ + config cache cũ. Sau khi đổi env (đặc biệt `TIKTOK_APP_SECRET`, `APP_URL`, `SANCTUM_STATEFUL_DOMAINS`) → **Update/Redeploy the stack** (hoặc ít nhất recreate `cmb-app`, `cmb-worker`, `cmb-scheduler`, `cmb-web`).
- "Update the stack" recreate `cmb-worker` từ image mới ⇒ Horizon tự khởi động lại với code mới (không cần `horizon:terminate` thủ công).
- Migration **không** tự chạy — vẫn phải `php artisan migrate --force` trong `cmb-app` (xem §0).

## 4. Chạy artisan trên Portainer

Portainer → Containers → chọn **`cmb-app`** → tab **Console** → Command `/bin/sh` (hoặc `bash`) → Connect. Một số lệnh hữu ích:

```sh
php artisan migrate --force
php artisan migrate:status
php artisan storage:link
php artisan horizon:status
php artisan queue:failed
php artisan queue:retry all
php artisan about
php artisan tinker          # ví dụ debug bên dưới
```

(Đừng chạy artisan trong `cmb-worker`/`cmb-scheduler` — dùng `cmb-app`.)

## 5. Kiểm tra khi webhook "không hoạt động"

1. **Portainer → Containers**: `cmb-worker` phải xanh/healthy. Mở **Logs** — lúc khởi động phải thấy Horizon spawn supervisors (`supervisor-critical`, `supervisor-sync`, `supervisor-default`…). Crash-loop ⇒ đọc log: thường là Redis không tới được hoặc DB chưa migrate.
2. **Trong app — trang "Nhật ký đồng bộ"** (sidebar) → tab **Webhook** (= `webhook_events`):
   - đọng `status=pending` ⇒ `cmb-worker` không chạy / queue `webhooks` nghẽn.
   - `status=failed` + cột `error` ⇒ đọc lý do (`signature` sai = sai `TIKTOK_APP_SECRET`; lỗi API sàn = bình thường ở sandbox — fast-path vẫn áp trạng thái từ payload). Bấm **"Xử lý lại"** để re-drive.
   - `status=processed` nhưng đơn không đổi ⇒ xem cột `order_raw_status` (null = TikTok không gửi `data.order_status` ⇒ phụ thuộc re-fetch API).
3. **Horizon dashboard**: `https://<domain>/horizon` (gated — đăng nhập owner/admin). Xem throughput, failed jobs, recent jobs.
4. **CLI** (`cmb-app`):
   ```sh
   php artisan tinker --execute="\CMBcoreSeller\Modules\Channels\Models\WebhookEvent::latest()->take(5)->get(['id','event_type','status','order_raw_status','error'])"
   php artisan queue:failed
   ```
5. **Phía TikTok**: Partner Center có log gửi webhook — kiểm họ nhận HTTP gì từ URL của bạn: `401` = chữ ký; `502`/timeout = proxy/`cmb-web` không tới được; `404` = sai path/route.

## 6. Bảng lỗi hay gặp (Portainer)

| Triệu chứng | Nguyên nhân | Fix |
|---|---|---|
| `webhook_events` đầy `pending`, đơn không đổi | `cmb-worker` không chạy / unhealthy | Xem log worker; thường do Redis hoặc chưa `migrate --force` |
| App lỗi 500 `column/table not found` sau deploy | Chưa chạy migration (`RUN_MIGRATIONS=false`) | `php artisan migrate --force` trong `cmb-app` |
| Đổi `TIKTOK_APP_SECRET`/`APP_URL`/`SANCTUM_*` nhưng không ăn | Container chưa recreate, config:cache cũ | Redeploy stack (hoặc recreate `cmb-app`+`cmb-worker`+`cmb-scheduler`+`cmb-web`) |
| TikTok webhook trả 401 | `TIKTOK_APP_SECRET` sai / không khớp app key | Lấy lại secret ở Partner Center → set env → redeploy |
| TikTok không gọi được webhook (timeout/404/502) | `APP_URL` không HTTPS public / `cmb-web` chưa ở network `proxy` / proxy chưa route / DNS sai | Cấu hình reverse proxy + DNS; kiểm `docker network create proxy` |
| OAuth callback `/oauth/tiktok/callback` lỗi | Redirect URL ở Partner Center không khớp `APP_URL`, hoặc app thiếu scope | Sửa Redirect URL = `https://<domain>/oauth/tiktok/callback`; bật scopes; ủy quyền lại |
| Polling đơn không chạy, token không tự refresh | `cmb-scheduler` không chạy | Bật lại service `scheduler`; xem log |
| `cmb-minio` healthcheck đỏ / upload PDF lỗi | `AWS_*` env sai, bucket chưa tạo | Kiểm `AWS_ACCESS_KEY_ID`/`AWS_SECRET_ACCESS_KEY`; `minio-init` tạo bucket lúc start — xem log của nó |
| `key:generate` log "generating one (dev only)" ở prod | Quên set `APP_KEY` | Set `APP_KEY` trong env Portainer → redeploy |

## 7. Backup / khôi phục

Volume cần backup: `pgdata` (Postgres), `miniodata` (MinIO), (tuỳ) `redisdata`. Chi tiết runbook + script: [`observability-and-backup.md`](observability-and-backup.md) và `scripts/backup.sh` / `scripts/restore.sh` ở gốc repo. Trên Portainer có thể dùng tính năng backup volume, hoặc chạy script trong một container `--rm` mount các volume đó.
