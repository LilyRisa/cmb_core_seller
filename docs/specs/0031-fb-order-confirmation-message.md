# SPEC 0031: Tự động gửi tin xác nhận đơn khi tạo đơn trong khung chat

- **Trạng thái:** Reviewed
- **Phase:** 7.x (Messaging) — mở rộng
- **Module backend liên quan:** Messaging (chính), đọc mềm Orders (Order)
- **Tác giả / Ngày:** Claude · 2026-06-05
- **Liên quan:** SPEC 0024 (Omnichannel Messaging), SPEC 0030 (link tra cứu công khai), `extensibility-rules.md`

## 1. Vấn đề & mục tiêu
Khi nhân viên **tạo đơn trực tiếp từ khung chat Facebook** (khách đã nhắn thông tin trong Messenger), sau khi tạo đơn xong hệ thống **tự gửi tin nhắn xác nhận** cho khách, kèm **link tra cứu đơn công khai** (SPEC 0030) và **nút bấm** mở link (nếu kênh hỗ trợ):

```
Xác nhận đơn đặt hàng
Bạn có thể xem trực tiếp tại {link đơn hàng}
[Xem đơn hàng]   ← nút, nếu connector hỗ trợ interactive
```

## 2. Trong / ngoài phạm vi
- **Trong:** Notifier gửi 1 tin xác nhận (idempotent) khi đơn vừa-tạo được gắn vào hội thoại; tái dùng `OutboundMessageService` + connector hiện có; nút bấm qua `InteractiveMessagingConnector` (Facebook), fallback text cho kênh khác.
- **Ngoài:** Tin xác nhận cho đơn sàn (TikTok/Shopee/Lazada tự đồng bộ). Tự gửi khi đổi trạng thái đơn (đã có auto-reply rule riêng — SPEC 0024). Soạn nội dung tuỳ biến (cố định theo yêu cầu). Gửi vào comment thread.

## 3. Luồng chính
1. Trong khung chat (cột phải `ConversationOrderPanel`), nhân viên bấm "Tạo đơn" → `POST /orders` (`sub_source='facebook'`).
2. FE gắn đơn: `POST /messaging/conversations/{id}/link-order` với **`notify_customer: true`**.
3. BE `linkOrder` set `conversations.order_id`, rồi (nếu `notify_customer`) gọi `OrderConfirmationNotifier::notify($conv, $order)`.
4. Notifier (best-effort, không bao giờ làm hỏng link):
   - Gate: thread là **DM** (`thread_type='message'`), đơn **manual** có `order_number`, connector provider **hỗ trợ `outbound.text`**.
   - Idempotent: bỏ qua nếu (conversation, order) đã gửi (đánh dấu trong `conversation.meta.order_confirmation_order_ids`).
   - Dựng `url = {app.url}/tracking?code={order_number}` và body cố định.
   - Nếu connector `instanceof InteractiveMessagingConnector && supports('outbound.interactive')` ⇒ `queueInteractive` (button template, nút web_url "Xem đơn hàng"); ngược lại `queueText` (link trong body).
   - `message_tag = POST_PURCHASE_UPDATE` ⇒ gửi được cả ngoài cửa sổ 24h (Facebook).
5. `OutboundMessageService` ghi `Message` pending + dispatch `SendMessage` (async) → connector gọi Graph API `/me/messages` như mọi tin outbound khác.

## 4. Hành vi & quy tắc nghiệp vụ
- **Chỉ tự gửi khi `notify_customer=true`** (FE panel tạo-đơn-trong-chat đặt cờ này) ⇒ link đơn cũ thủ công KHÔNG kích hoạt gửi.
- **Idempotent**: mỗi (conversation, order) chỉ gửi 1 lần; tạo nhiều đơn khác nhau trong cùng hội thoại ⇒ mỗi đơn 1 tin.
- **Capability-gated, KHÔNG theo tên sàn** (extensibility-rules.md): nút bấm chỉ khi `InteractiveMessagingConnector` + `supports('outbound.interactive')`; gửi text khi `supports('outbound.text')`. Kênh không gửi được ⇒ bỏ qua êm.
- **Best-effort**: mọi lỗi (connector chưa bật, hết quyền, ngoài 24h…) chỉ log; KHÔNG ném ra `linkOrder`.
- **Cửa sổ 24h Facebook**: dùng tag `POST_PURCHASE_UPDATE` (hợp lệ cho thông báo sau mua) ⇒ gửi được kể cả khi khách nhắn đã lâu. `messaging_type=MESSAGE_TAG` do connector tự set khi có tag.
- **Phân quyền**: như `linkOrder` hiện tại (`messaging.view` + tenant).

## 5. Dữ liệu
- **Không bảng/cột mới.** Đánh dấu idempotency trong `conversations.meta` (json đã có): `order_confirmation_order_ids: int[]`. Tin lưu thêm `meta.system_kind='order_confirmation'`, `meta.order_id` để audit.
- Không event domain mới (gọi trực tiếp trong `linkOrder` — cùng module Messaging).

## 6. API & UI
- **`POST /api/v1/messaging/conversations/{id}/link-order`** — thêm field **optional** `notify_customer: boolean` (mặc định false; backward-compatible). Khi true & đủ điều kiện ⇒ gửi tin xác nhận.
- **FE:** `ConversationOrderPanel.handleSaved` truyền `notifyCustomer: true` vào `useLinkConversationOrder`; hook đính `notify_customer: true` vào body. Không thêm UI mới.
- Tin gửi qua `OutboundMessageService.queueText|queueInteractive` (không codepath gửi mới). Connector Facebook map `{type:'url'}` → web_url button (đã có).

## 7. Edge case & lỗi
- Đơn không phải manual / thiếu `order_number` ⇒ không gửi.
- Comment thread ⇒ không gửi (recipient không phải PSID).
- Provider chưa bật trong `INTEGRATIONS_MESSAGING` / connector không hỗ trợ text ⇒ bỏ qua.
- Gọi `link-order` lại (retry FE) ⇒ không gửi trùng.
- Send thực tế lỗi (ngoài 24h không tag hợp lệ, page mất quyền, khách chặn) ⇒ `SendMessage` đánh dấu message `failed` như bình thường; không ảnh hưởng đơn.

## 8. Bảo mật & PII
- Tin gửi tới đúng PSID của hội thoại (recipient = `external_conversation_id` của DM). Link công khai đã mask PII (SPEC 0030).
- Tuân chính sách Messenger: tag `POST_PURCHASE_UPDATE` đúng mục đích thông báo sau mua; không dùng cho marketing.

## 9. Kiểm thử
- **Feature:** `link-order` với `notify_customer=true` trên hội thoại facebook_page (DM) + đơn manual ⇒ tạo 1 `Message` outbound interactive chứa link `/tracking?code=`, `message_tag=POST_PURCHASE_UPDATE`, `meta.system_kind=order_confirmation`; gọi lại ⇒ không gửi trùng. `notify_customer` vắng ⇒ không gửi. Comment thread ⇒ không gửi. (Queue::fake để không gọi Graph API thật.)
- **FE:** typecheck + build.

## 10. Acceptance
- [ ] Tạo đơn trong khung chat FB ⇒ khách nhận tin "Xác nhận đơn đặt hàng" + link tra cứu + nút "Xem đơn hàng".
- [ ] Không gửi cho: đơn link thủ công (không cờ), comment thread, đơn không-manual, kênh không hỗ trợ.
- [ ] Idempotent; không làm hỏng luồng link đơn khi gửi lỗi.
- [ ] `docs/05-api/endpoints.md` cập nhật `notify_customer`.

## 11. Câu hỏi mở
- (Sau) Cho phép tuỳ biến nội dung tin xác nhận theo tenant (template) — hiện cố định theo yêu cầu.
