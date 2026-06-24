# Cấu hình Realtime (Laravel Reverb) — hướng dẫn chi tiết

> Realtime hộp thư messaging theo ADR-0021. Code đã xong (commit `a622cbe`). Tài liệu này
> hướng dẫn **bật thật** ở production (và test cục bộ). Nếu KHÔNG cấu hình, hệ thống vẫn
> chạy bình thường bằng **polling fallback** (FE tự refetch 10–20s) — không vỡ gì.
>
> Client **app mobile** (bearer token) xem riêng: [`docs/05-api/mobile-messaging-websocket.md`](../05-api/mobile-messaging-websocket.md).

## 1. Kiến trúc (ai nói chuyện với ai)

```
Browser (pusher-js)  ──wss──►  Reverse Proxy (NPM/Caddy)  ──ws──►  cmb-reverb:8080
   lib/echo.ts                  route /app  +  Upgrade header        (php artisan reverb:start)
        │                                                                  ▲
        │ POST /broadcasting/auth (cookie SPA + CSRF)                      │ Pusher HTTP API NỘI BỘ
        ▼                                                                  │ http://reverb:8080/apps/...
   cmb-web (nginx) ──► cmb-app (php-fpm)  ──── broadcast(event) ───────────┘
                                            (REVERB_HOST=reverb, nội bộ — KHÔNG qua proxy)
```

- **Browser → Reverb:** WebSocket `wss://<domain>/app/{key}`. Đây là đường DUY NHẤT cần
  reverse proxy route ra ngoài (kèm header `Upgrade`).
- **App (PHP) → Reverb:** khi có tin mới, php-fpm/worker bắn event tới Reverb qua Pusher
  HTTP API **nội bộ** `http://reverb:8080/apps/...` (vì `REVERB_HOST=reverb`). KHÔNG đi qua
  proxy, KHÔNG cần expose `/apps` ra ngoài.
- **Auth private channel:** browser POST `/broadcasting/auth` (đi vào cmb-web→cmb-app, dùng
  cookie Sanctum SPA + CSRF). Server kiểm `tenant.{id}.messaging` qua
  `MessagingChannelAuthorizer` (thành viên tenant + quyền `messaging.view`).

## 2. Biến môi trường

| Biến | Vai trò | Giá trị prod |
|---|---|---|
| `BROADCAST_CONNECTION` | App bắn event đi đâu | `reverb` (đặt `log` để tắt → FE polling) |
| `REVERB_APP_ID` / `REVERB_APP_KEY` / `REVERB_APP_SECRET` | Credential app Reverb (server + reverb container DÙNG CHUNG) | chuỗi bí mật |
| `REVERB_HOST` | App→Reverb (nội bộ) | `reverb` (tên service) |
| `REVERB_PORT` / `REVERB_SCHEME` | App→Reverb | `8080` / `http` |
| `REVERB_SERVER_HOST` / `REVERB_SERVER_PORT` | Reverb container bind | `0.0.0.0` / `8080` |
| `VITE_REVERB_APP_KEY` | **Build-arg web image** — browser key (= `REVERB_APP_KEY`) | chuỗi |
| `VITE_REVERB_HOST` | **Build-arg** — domain CÔNG KHAI browser nối wss | `app.cmbcore.com` |
| `VITE_REVERB_PORT` / `VITE_REVERB_SCHEME` | **Build-arg** | `443` / `https` |

> ⚠️ `VITE_*` được **nhúng vào bundle JS lúc `npm run build`** (build-time, không phải
> runtime). Đổi giá trị ⇒ phải **build lại web image**. Khác `REVERB_HOST` (runtime, nội bộ).
> Thiếu `VITE_REVERB_APP_KEY` lúc build ⇒ `getEcho()` trả null ⇒ FE polling fallback.

Tất cả đã khai sẵn trong `docker-compose.prod.yml` (x-app-env + service `reverb` + build-args
của `web`). Chỉ cần đặt giá trị bí mật ở Portainer và (nếu domain khác) `VITE_REVERB_HOST`.

## 3. Bật ở Production (Portainer / docker compose)

1. **Đặt biến ở Portainer** (Stack → Environment variables) — hoặc `./.env` ở repo root:
   ```
   REVERB_APP_ID=<sinh ngẫu nhiên, vd 6 chữ số>
   REVERB_APP_KEY=<chuỗi ngẫu nhiên 20+ ký tự>
   REVERB_APP_SECRET=<chuỗi ngẫu nhiên 20+ ký tự>
   VITE_REVERB_HOST=app.cmbcore.com      # domain thật
   # BROADCAST_CONNECTION đã default 'reverb' trong compose; VITE_REVERB_PORT/SCHEME default 443/https
   ```
   (Sinh nhanh: `php artisan key:generate --show` rồi cắt phần base64, hoặc `openssl rand -hex 16`.)

2. **Redeploy stack** — quan trọng: web image phải **build lại** để nhúng `VITE_*` mới:
   ```bash
   docker compose -f docker-compose.prod.yml up -d --build web reverb app worker
   ```
   (Portainer: bật "Re-pull image and rebuild" / "Build the stack".)

3. **Kiểm container reverb chạy:**
   ```bash
   docker compose -f docker-compose.prod.yml logs -f reverb
   # mong đợi: "Starting server on 0.0.0.0:8080"
   ```

## 4. Cấu hình Reverse Proxy (BẮT BUỘC cho WebSocket)

Mặc định proxy route toàn bộ domain → `cmb-web`. Cần thêm route `/app` → `cmb-reverb:8080`
**có WebSocket upgrade**. Reverb container đã nằm trên network `proxy`.

### 4a. Nginx Proxy Manager (NPM — stack đang dùng)

Vào **Proxy Hosts → (host app.cmbcore.com) → Edit**:

1. Tab **Details**: bật **"Websockets Support"** (ON).
2. Tab **Advanced** — dán custom location (NPM chưa hỗ trợ "/app" qua UI Custom Locations với
   upgrade tốt, nên dùng Advanced):
   ```nginx
   location /app {
       proxy_pass http://cmb-reverb:8080;
       proxy_http_version 1.1;
       proxy_set_header Upgrade $http_upgrade;
       proxy_set_header Connection "Upgrade";
       proxy_set_header Host $host;
       proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
       proxy_set_header X-Forwarded-Proto $scheme;
       proxy_read_timeout 3600s;   # WS giữ kết nối lâu
       proxy_send_timeout 3600s;
   }
   ```
   > Tên container `cmb-reverb` theo service `reverb` (đặt `container_name` nếu cần cố định;
   > nếu NPM ở network `proxy`, dùng tên service `reverb` resolves được). Kiểm:
   > `docker compose -f docker-compose.prod.yml ps` để lấy tên thật.

3. Save → NPM reload nginx.

### 4b. Caddy (nếu dùng)

```caddy
app.cmbcore.com {
    @reverb path /app /app/*
    reverse_proxy @reverb cmb-reverb:8080
    reverse_proxy cmb-web:80
}
```
(Caddy tự xử lý WebSocket upgrade, không cần khai header.)

> Chỉ `/app` cần ra ngoài. KHÔNG cần route `/apps` (đó là API nội bộ app→reverb).

## 5. Kiểm tra hoạt động

1. Mở app, vào **Hộp thư**, mở DevTools → **Network → WS**: phải thấy 1 kết nối
   `wss://app.cmbcore.com/app/<key>` trạng thái **101 Switching Protocols** (không phải 400/404).
2. Console không có lỗi `/broadcasting/auth` 403/419.
3. Mở 2 tab (hoặc nhờ khách nhắn thật) → tin mới hiện **tức thời** (≤1–2s), không đợi 10–20s.
4. `docker compose ... logs reverb` thấy connection + message khi có tin.

## 6. Test cục bộ (dev)

```bash
cd app
# .env: đổi BROADCAST_CONNECTION=reverb (dev mặc định 'log')
#       REVERB_HOST=localhost, REVERB_PORT=8080, REVERB_SCHEME=http (reverb:install đã set)
#       VITE_REVERB_HOST=localhost (đã set)
php artisan reverb:start --debug      # terminal 1
npm run dev                            # terminal 2 (vite đọc VITE_* từ .env)
php artisan serve & php artisan queue:work   # hoặc: composer dev
```
Gửi tin test → thấy event ở terminal reverb + UI cập nhật tức thời.

## 7. Troubleshooting

| Triệu chứng | Nguyên nhân & cách sửa |
|---|---|
| WS `400 Bad Request` / không lên `101` | Proxy thiếu header `Upgrade`/`Connection` cho `/app`. Xem §4. |
| WS nối được nhưng không có tin | App không bắn được tới reverb: kiểm `BROADCAST_CONNECTION=reverb` + `REVERB_HOST=reverb` (nội bộ) + reverb container up + cùng `REVERB_APP_*`. `docker logs cmb-app` xem lỗi broadcast. |
| `/broadcasting/auth` **419** | Thiếu CSRF — `lib/echo.ts` đã gọi `ensureCsrf()`; đảm bảo `GET /sanctum/csrf-cookie` truy cập được + `SANCTUM_STATEFUL_DOMAINS`/`SESSION_DOMAIN` đúng domain. |
| `/broadcasting/auth` **403** | User không thuộc tenant / thiếu `messaging.view` (đúng theo `MessagingChannelAuthorizer`). |
| FE không hề mở WS (chỉ polling) | Bundle build thiếu `VITE_REVERB_APP_KEY` ⇒ `getEcho()` null. Build lại web image với build-arg (§3 bước 2). |
| Mixed content / `ws://` trên trang `https` | `VITE_REVERB_SCHEME=https` + `VITE_REVERB_PORT=443` (browser dùng wss). |

## 8. Tắt khẩn cấp

Đặt `BROADCAST_CONNECTION=log` (Portainer) + redeploy `app`/`worker` → ngừng bắn realtime,
FE tự rơi về polling. Không cần đụng FE. Reverb container có thể để chạy hoặc dừng.
