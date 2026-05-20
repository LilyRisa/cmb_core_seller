# ADR-0021: Realtime messaging dùng Laravel Reverb; fallback polling khi Reverb chưa bật

- **Trạng thái:** Proposed
- **Ngày:** 2026-05-19
- **Người quyết định:** Team (chờ duyệt SPEC-0024 + quyết định "kéo Reverb từ Phase 8 vào trước")
- **Liên quan:** SPEC-0024, `tech-stack.md` (Realtime dự kiến: Reverb), `06-frontend/overview.md` §4.11

## Bối cảnh

Inbox messaging **bắt buộc** phải realtime để UX dùng được — NV thường mở app, đợi tin mới. Polling > 5s đã đủ làm UX kém ("trễ hiện tin"). Yêu cầu SPEC-0024 §1: tin từ sàn về app ≤ 10s.

`tech-stack.md` đã chốt Laravel Reverb (WebSocket) là phương án realtime nhưng đẩy sang "(sau)". Roadmap Phase 8+ ghi "realtime UI (Reverb)".

**Phương án đã cân nhắc:**

A. **Polling thuần** (FE poll mỗi 5–10s) — đơn giản nhất.
   - ✗ Trễ. 100 NV mở inbox đồng thời = 100 req/5s = 20 req/s baseline → hơn 1.7M req/ngày chỉ cho inbox.
   - ✗ Battery drain mobile.
   - ✗ Reverb dù sao cũng phải làm Phase 8 — trì hoãn hơn = công nợ kỹ thuật.

B. **Server-Sent Events (SSE)** — đơn giản hơn WebSocket.
   - ✓ HTTP/1.1 chuẩn, qua proxy thường.
   - ✗ 1 chiều (server → client). Outbound vẫn phải dùng REST → 2 protocol song song.
   - ✗ Không phải pattern Laravel chuẩn → phải custom.

C. **Laravel Reverb (WebSocket)** — kéo từ Phase 8 vào.
   - ✓ Pattern Laravel chuẩn (`broadcast(new MessageReceived(...))`).
   - ✓ 2 chiều, mở rộng được cho features khác (đơn mới, in xong, notification).
   - ✓ Laravel Echo bên FE tích hợp Vite/TS sẵn.
   - ✗ Thêm 1 container `reverb` vào docker-compose; thêm proxy WebSocket cấu hình.
   - ✗ Team chưa từng vận hành — cần thử nghiệm staging.

D. **Mặt phẳng kép: Reverb mặc định; polling fallback nếu deploy chưa bật Reverb.**
   - ✓ Production tốt; dev/staging không bị chặn khi chưa setup Reverb.
   - ✓ Single codebase, FE detect qua `meta.realtime_enabled` flag.

## Quyết định

Chọn **D — Reverb mặc định, polling fallback có cờ**.

Chi tiết:
- Thêm container `reverb` vào `docker-compose.yml` + `docker-compose.prod.yml`.
- Backend:
  - Cài `laravel/reverb`. Config `config/broadcasting.php` driver `reverb`.
  - Events broadcast (`ShouldBroadcastNow` cho ưu tiên thấp dùng `ShouldBroadcast`): `Messaging\Events\MessageReceived`, `MessageSent`, `MessageFailed`, `ConversationUpdated`.
  - Private channel: `tenant.{tenantId}.messaging` (authorize qua `TenantBroadcastChannel` — kiểm `user` thuộc tenant + có `messaging.view`).
  - Per-conversation channel (cho typing indicator, future): `tenant.{tenantId}.messaging.conversation.{id}` (chỉ assigned_user hoặc user có permission).
- Frontend:
  - Cài `laravel-echo` + `pusher-js` (Reverb dùng Pusher protocol).
  - `lib/echo.ts` setup Echo instance. Tự reconnect.
  - `useInbox` hook subscribe `tenant.{tenantId}.messaging` → listen `MessageReceived` → React Query `setQueryData` update list + invalidate `['messaging','conversation',id]`.
  - **Fallback polling khi `meta.realtime_enabled === false`**: hook fallback to `useQuery` với `refetchInterval: 10_000` cho list conversation, `refetchInterval: 5_000` cho thread đang mở. UI hiện badge "⚡ Realtime" (Reverb) hoặc "🔄 Polling" (fallback) — admin biết degraded.
- Cờ kích hoạt: `BROADCAST_DRIVER=reverb` (prod) | `BROADCAST_DRIVER=null` (dev nhẹ → polling fallback). `/api/v1/messaging/stats` trả `meta.realtime_enabled` để FE biết.

Phạm vi mở rộng (cho phép vì là feature nền):
- Sau khi Reverb đã trong stack, các module khác (Orders, Fulfillment) **có thể** dùng để bắn event "đơn mới về", "tiến độ in xong" — nhưng KHÔNG bắt buộc trong scope SPEC-0024. Chỉ Messaging dùng v1.

Authorization (private channel):
- `TenantBroadcastChannel` middleware: user phải có session active + middleware tenant + permission `messaging.view`. Sai ⇒ Echo reject join.
- Không bắn event chứa PII đầy đủ — chỉ `{message_id, conversation_id, preview, kind}`; FE re-fetch chi tiết qua REST.

## Hệ quả

**Tích cực:**
- UX realtime đúng yêu cầu. Tin về app ≤ 1–2s thực tế.
- Một stack realtime dùng được cho nhiều feature sau (đơn mới, in xong, notification badge).
- Fallback polling đảm bảo môi trường dev không bị chặn nếu chưa muốn chạy Reverb.

**Tiêu cực / đánh đổi:**
- Thêm container + cấu hình proxy WebSocket (nginx `proxy_set_header Upgrade $http_upgrade; proxy_set_header Connection 'upgrade';`).
- Vận hành: cần monitor connection count, bộ nhớ Reverb. Sentry/log thêm.
- Authorization channel phải kiểm policy đúng — sai = lộ tin nhắn tenant khác. Test bắt buộc.
- Phụ thuộc thêm: Pusher protocol stable nhưng cần ý nếu sau này muốn upgrade lib.

**Việc phải làm theo sau:**
- Cập nhật `tech-stack.md`: Realtime = Reverb (bật từ SPEC-0024, không "(sau)" nữa).
- Cập nhật `01-architecture/overview.md`: thêm container `reverb` vào sơ đồ.
- Cập nhật `07-infra/environments-and-docker.md`: cách dựng Reverb (port, nginx proxy upgrade, `BROADCAST_DRIVER`).
- Cập nhật `06-frontend/overview.md` §4.11: realtime đã enabled — Reverb private channel pattern.
- Cập nhật `roadmap.md` Phase 8+: chuyển "Reverb realtime" sang "Done (Phase 7.x cùng SPEC-0024)".
- ADR-0007 ghi nhận: realtime FE qua Reverb không thay thế webhook+polling backend (vẫn áp).
