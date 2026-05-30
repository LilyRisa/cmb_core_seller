# Thiết kế: Tách cài đặt AI auto-reply (sàn vs Facebook) + ưu tiên với automation flow

- **Ngày:** 2026-05-30
- **Liên quan:** ADR-0022, SPEC-0024, ADR-0018
- **Phương án:** A (surgical) — đã được chủ shop duyệt.

## 1. Mục tiêu

1. Tách cấu hình "AI tự động trả lời" thành **2 nhóm kênh**: Sàn TMĐT vs Facebook.
2. Đặt quy tắc ưu tiên: **tin nhắn đầu tiên** và **tin chứa từ khoá** (flow hoặc rule) xử lý trước; nếu khớp ⇒ **không** dùng AI cho tin đó.
3. Loại trừ lẫn nhau giữa **AI auto-reply (tất cả)** và **flow `inbox_any` (Mọi tin nhắn)**: bật cái này ⇒ cảnh báo + tắt cái kia.
4. FE có cảnh báo + hướng dẫn ngắn gọn, dễ hiểu.

## 2. Mô hình 2 tầng (xem ADR-0022 §2)

- **Tầng 1 (luôn ưu tiên):** `first_message`, `keyword` (flow + rule), và flow run `active`/`waiting`.
- **Tầng 2 (loại trừ):** AI auto-reply-all **XOR** flow `inbox_any`, theo từng nhóm kênh.

Nguyên tắc cốt lõi: cổng AI kiểm tra Tầng 1 **có khớp** (không phải "đã trả lời") ⇒ tất định, tránh race giữa các queue.

## 3. Thay đổi backend

### 3.1. Schema + model
- Migration: `messaging_settings` thêm `auto_mode_marketplace`, `auto_mode_facebook` (boolean, default false); backfill = `auto_mode` cũ. Giữ `auto_mode` (deprecated).
- `MessagingSetting`: thêm 2 cột vào `$fillable` + `casts`.
- Helper mới `Modules/Messaging/Support/MessagingChannelGroup` (`forProvider`): `facebook_page → facebook`, còn lại → `marketplace`.

### 3.2. Cổng ưu tiên AI (`AiAutoModeOnInbound`)
- Chọn toggle theo `MessagingChannelGroup::forProvider($conv->provider)`.
- Trước khi `autoRespond`, gọi `higherPriorityClaims($conv, $body)`:
  - Có `FlowRun` `active`/`waiting` cho conversation ⇒ true.
  - `FlowMatcher::matching($conv, [INBOX_FIRST_MESSAGE, INBOX_KEYWORD], $body)` không rỗng ⇒ true.
  - `AutoReplyEngine::matches($conv, FIRST_MESSAGE|KEYWORD, ['inbound_body'=>$body])` ⇒ true.
- Nếu true ⇒ log + return (nhường Tầng 1).

### 3.3. `AutoReplyEngine::matches()` (predicate mới, không fire)
- Tái dùng `matchesFilter` + `conditionMet` (+ `countMatchedKeywords` cho keyword). Bỏ qua cooldown/idempotency. Trả bool.

### 3.4. Loại trừ Tầng 2 — `AiFlowExclusionService`
- `pauseFacebookCatchAllFlows(tenantId): int` — set `status=paused` cho flow `inbox_any` + `provider=facebook_page` + `status=active`.
- `disableFacebookAiAuto(tenantId): bool` — `auto_mode_facebook=false`.
- `MessagingSettingsController::update`: nếu FB AI auto false→true ⇒ gọi pause; trả `meta.paused_catch_all_flows`.
- `AutomationFlowController::publish` (và `update` khi kết quả là flow `inbox_any` facebook đang active) ⇒ gọi disable; trả `meta.disabled_facebook_ai`.
- Một chiều: không auto-restore.

## 4. Thay đổi frontend

### 4.1. `MessagingSettingsPage`
- Thay 1 switch `auto_mode` bằng **2 switch**: "AI tự động trả lời — Sàn TMĐT" và "— Facebook".
- `Alert` (info) hướng dẫn: AI chỉ trả lời khi **không** khớp tin-đầu/từ-khoá/luồng đang chạy; bật AI Facebook sẽ **tạm dừng** luồng "Mọi tin nhắn".
- Khi bật FB AI auto mà đang có flow `inbox_any` active (đọc qua `useFlows`) ⇒ `Modal.confirm` cảnh báo số luồng sẽ bị tạm dừng.

### 4.2. `MessagingFlowsPage`
- `Alert` mô tả quy tắc ưu tiên ngắn gọn.
- Khi FB AI auto đang bật (qua `useMessagingSettings`): cảnh báo trên dòng flow `inbox_any` (sẽ tắt AI Facebook khi xuất bản) hoặc khi tạo flow `inbox_any`.

### 4.3. `messagingConfig.tsx`
- `MessagingSettings`: thêm `auto_mode_marketplace`, `auto_mode_facebook`.
- `useSaveMessagingSettings`: cho phép gửi 2 field mới.

## 5. Kiểm thử
- Gate: flow/rule first_message/keyword khớp ⇒ AI bị bỏ qua; flow run waiting ⇒ AI bỏ qua; không khớp ⇒ AI chạy.
- Toggle theo nhóm: FB on / marketplace off ⇒ chỉ inbound facebook gọi AI.
- Loại trừ: bật FB AI auto ⇒ flow inbox_any active → paused; publish flow inbox_any ⇒ auto_mode_facebook=false.

## 6. Ngoài phạm vi (YAGNI)
- Unified dispatcher; khử trùng lặp keyword-rule vs keyword-flow; loại trừ cho nhóm marketplace (flow chưa mở cho sàn).
