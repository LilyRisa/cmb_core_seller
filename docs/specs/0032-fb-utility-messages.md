# SPEC 0032: Facebook Utility Messages — nền tảng template + migrate tin xác nhận đơn

- **Trạng thái:** Draft
- **Phase:** 7.x (Messaging) — mở rộng
- **Module backend liên quan:** Messaging (chính), Integrations/Messaging (connector), đọc mềm Orders
- **Tác giả / Ngày:** Claude · 2026-06-07
- **Liên quan:** SPEC 0024 (Omnichannel Messaging), SPEC 0030 (link tra cứu công khai), SPEC 0031 (tin xác nhận đơn — **được spec này thay đổi**), SPEC 0033 (inbox composer nhận biết cửa sổ), `extensibility-rules.md`, ADR-0017

## 1. Vấn đề & mục tiêu

Meta đã **khai tử message tag** trên Messenger (toàn cầu, các mốc T1–T2/2026). Các tag `POST_PURCHASE_UPDATE`, `CONFIRMED_EVENT_UPDATE`, `ACCOUNT_UPDATE` **không còn gửi được** — Graph API trả `code 100 / error_subcode 1893061` ("Không được phép dùng thẻ tin nhắn không còn hỗ trợ"). Tin xác nhận đơn (SPEC 0031) đang gửi kèm `message_tag=POST_PURCHASE_UPDATE` nên **fail 100% trên production** khi gửi ngoài cửa sổ 24h.

Cơ chế thay thế của Meta là **Utility Messages**: doanh nghiệp đăng ký **template phân loại `UTILITY`**, Meta **duyệt trước**, sau đó gửi tham chiếu template đã duyệt (kèm biến) — gửi được **ngoài** cửa sổ 24h. Khả dụng ở **US, Việt Nam, Philippines** (VN được hỗ trợ). Cần quyền **`pages_utility_messaging`** (App Review).

**Mục tiêu:**
1. Gỡ lỗi production ngay: bỏ tag chết; tin tự động không bao giờ kèm tag không hợp lệ.
2. Thêm **năng lực utility-template** vào tầng Integration (đúng luật vàng — không branch theo tên sàn).
3. Cho **tenant tự quản** utility template (tạo → submit Meta → theo dõi duyệt) trong user app.
4. Tin xác nhận đơn dùng utility template đã duyệt khi có; fallback an toàn khi chưa có.

## 2. Trong / ngoài phạm vi

- **Trong:**
  - Interface năng lực `UtilityTemplateConnector` + DTO + capability `outbound.utility_template`; `FacebookPageConnector` implement (Graph `message_templates` + gửi).
  - Bỏ 3 tag chết khỏi `outboundWindow()`; chỉ giữ `HUMAN_AGENT`. Nâng `OutboundWindowPolicyDTO`.
  - Thêm scope OAuth `pages_utility_messaging`.
  - Bảng `utility_templates` (per-tenant, per-Page) + service submit/sync trạng thái + job đồng bộ.
  - `Message::KIND_UTILITY_TEMPLATE` + `queueUtilityTemplate` + nhánh gửi trong `SendMessage`.
  - Rewire `OrderConfirmationNotifier` dùng utility template (fallback in-window/skip).
  - Endpoint quản lý template + trang Settings (FE) tạo/submit/theo dõi duyệt.
- **Ngoài (spec khác / sau):**
  - Inbox composer nhận biết cửa sổ 7 ngày + khóa text ngoài cửa sổ + chọn template khi gửi tay → **SPEC 0033**.
  - Các loại utility khác (giao hàng, tài khoản) ngoài "xác nhận đơn" — model đủ tổng quát nhưng chỉ wire `order_confirmation` ở spec này.
  - Tin utility cho sàn (TikTok/Shopee/Lazada): không áp dụng (khái niệm riêng Messenger).

## 3. Luồng chính

### 3.1 Tenant đăng ký template (Settings)
1. Chủ shop vào **Cài đặt → Tin nhắn → Mẫu tin tiện ích**, chọn Page (channel account FB), bấm "Tạo mẫu".
2. Nhập: `code` (vd `order_confirmation`), tên, ngôn ngữ (`vi`), nội dung body có biến `{{1}}…`, nút (tùy chọn). Lưu `draft`.
3. Bấm "Gửi duyệt" → `UtilityTemplateService::submit()` gọi `connector->createUtilityTemplate()` (Graph `POST /{page_id}/message_templates`, category UTILITY) → lưu `external_template_id`, status `pending`.
4. Job `SyncUtilityTemplateStatus` (định kỳ) cập nhật `approved`/`rejected` (+ `reject_reason`). FE hiển thị trạng thái; có nút "Đồng bộ" thủ công.

### 3.2 Gửi tin xác nhận đơn (tự động — đổi từ SPEC 0031)
1. Nhân viên tạo đơn trong khung chat → `link-order` (`notify_customer=true`) → `OrderConfirmationNotifier::notify()`.
2. Notifier resolve **utility template approved** `code='order_confirmation'` cho channel account của hội thoại:
   - **Có** → `OutboundMessageService::queueUtilityTemplate()` với vars (link tra cứu, mã đơn) → `SendMessage` → `connector->sendUtilityTemplate()`. Gửi được mọi lúc, đúng policy cho tin tự động.
   - **Không có** (chưa tạo/đang chờ duyệt/từ chối) → fallback: còn trong 24h ⇒ `queueText` (RESPONSE, **không tag**); ngoài 24h ⇒ skip êm (best-effort như SPEC 0031). → không còn lỗi `1893061`.

## 4. Hành vi & quy tắc nghiệp vụ

- **Luật vàng (extensibility-rules.md):** core KHÔNG `instanceof FacebookPageConnector`. Gate qua `instanceof UtilityTemplateConnector && supports('outbound.utility_template')`. Connector khác chừa sẵn (không implement).
- **Tin tự động KHÔNG mượn `HUMAN_AGENT`.** Tag `HUMAN_AGENT` chỉ dành cho tin nhân viên người thật gõ tay (xử lý ở SPEC 0033). Tin automation ngoài cửa sổ ⇒ chỉ utility template.
- **Cửa sổ:** `OutboundWindowPolicyDTO` của Facebook: `freeWindowHours=24`, `humanAgentWindowHours=168`, `allowedTags=['HUMAN_AGENT']` (đã bỏ 3 tag chết), `requiresTag=true`, `templateOnlyOutsideWindow=true`.
- **Idempotent gửi:** giữ nguyên cơ chế SPEC 0031 (`conversation.meta.order_confirmation_order_ids`) + idempotent `SendMessage`.
- **Submit template idempotent:** mỗi `(tenant, channel_account, code, language)` 1 bản ghi (unique). Submit lại khi đã có `external_template_id` ⇒ chỉ re-sync, không tạo trùng trên Meta.
- **Thiếu năng lực khi gửi:** message `KIND_UTILITY_TEMPLATE` mà connector không hỗ trợ ⇒ fail VĨNH VIỄN (`utility_template_unsupported`), không retry (giống `interactive_unsupported`).
- **Phân quyền:** quản lý template cần `messaging.manage` (hoặc quyền messaging hiện có); notifier theo `link-order` hiện tại.

## 5. Dữ liệu

**Bảng mới `utility_templates`:**

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | bigint PK | |
| `tenant_id` | bigint, index | `BelongsToTenant` |
| `channel_account_id` | bigint, FK | Page FB sở hữu template |
| `code` | string | vd `order_confirmation` |
| `name` | string | tên hiển thị nội bộ |
| `language` | string(8) | vd `vi` |
| `body` | text | nội dung có `{{1}}…` |
| `buttons` | json nullable | `[{type:'url'|'postback', title, url?, payload?}]` |
| `variables` | json nullable | map `{{n}}` → nguồn (vd `tracking_url`, `order_number`) |
| `external_template_id` | string nullable | id template phía Meta |
| `status` | string | `draft`/`pending`/`approved`/`rejected` |
| `reject_reason` | string nullable | lý do Meta từ chối |
| `enabled` | bool default true | |
| `created_by` | bigint nullable | |
| timestamps + `deleted_at` | | softdelete |

- **Unique:** `(tenant_id, channel_account_id, code, language)` (chống trùng, kèm `deleted_at` theo pattern repo).
- Migration reversible. Không partition (bảng nhỏ).
- Không event domain mới (gọi trực tiếp trong module Messaging).

## 6. API & UI

**Endpoints** (`/api/v1/messaging/utility-templates`, quyền messaging + tenant — cập nhật `05-api/endpoints.md`):
- `GET /` — list (lọc theo `channel_account_id`, `status`).
- `POST /` — tạo `draft` (FormRequest validate body/buttons/variables).
- `GET /{id}` — chi tiết.
- `PATCH /{id}` — sửa khi còn `draft`/`rejected`.
- `POST /{id}/submit` — submit lên Meta (→ pending).
- `POST /{id}/sync` — đồng bộ trạng thái duyệt thủ công.
- `DELETE /{id}` — xoá mềm.

Envelope chuẩn `{data,meta}`; lỗi connector → 422 `UTILITY_TEMPLATE_*`.

**Job** (cập nhật `07-infra/queues-and-scheduler.md`): `SyncUtilityTemplateStatus` — queue `messaging`, scheduler mỗi ~15 phút quét template `pending`; idempotent.

**FE:** trang `features/messaging` (Settings shell) — bảng template theo Page, form tạo/sửa, badge trạng thái duyệt, nút "Gửi duyệt"/"Đồng bộ". Dùng `@ant-design/icons` (không emoji), tránh `<Select>` khi tập lựa chọn nhỏ (dùng `Radio`/`Segmented`) theo guideline. TanStack Query + `lib/api.ts`.

**Connector methods** (`UtilityTemplateConnector`): `createUtilityTemplate`, `syncUtilityTemplateStatus`, `sendUtilityTemplate`. **Không** thêm logic theo tên sàn ở core.

> ⚠️ **Rủi ro đã biết:** payload Graph chính xác để **gửi** utility template (Messenger, không phải WhatsApp) phải đối chiếu trang chính thức `developers.facebook.com/docs/messenger-platform/send-messages/utility-messages` lúc code (trang render JS, nguồn thứ ba che API thật). Mô hình "template duyệt trước" thì chắc chắn. Connector cô lập payload này; nếu sai chỉ sửa 1 chỗ.

## 7. Edge case & lỗi

- Chưa có template approved ⇒ fallback in-window/skip (mục 3.2) — production không lỗi.
- Page chưa cấp `pages_utility_messaging` ⇒ `createUtilityTemplate`/`send` lỗi quyền → service đánh dấu/log; FE báo cần cấp lại quyền. Notifier rơi vào fallback.
- Vùng không hỗ trợ utility ⇒ Meta từ chối template (status `rejected`); notifier fallback.
- Submit lại template đã có `external_template_id` ⇒ re-sync, không tạo trùng.
- Token hết hạn / rate-limit (code 80006) ⇒ job/connector backoff như hiện tại.
- Gửi `KIND_UTILITY_TEMPLATE` qua connector không hỗ trợ ⇒ fail vĩnh viễn, không retry.
- Template bị Meta gỡ sau khi approved ⇒ gửi trả lỗi → message `failed`; sync hạ trạng thái về `rejected`.

## 8. Bảo mật & PII

- Template do tenant tạo, scope theo `tenant_id` + `channel_account_id` (Page của chính họ). Không lộ chéo tenant.
- Tin gửi tới đúng PSID hội thoại; link công khai đã mask PII (SPEC 0030).
- Tuân policy Messenger: category UTILITY cho thông báo giao dịch, không marketing.
- Token Page lưu `channel_accounts` (đã có); không log token.

## 9. Kiểm thử

- **Unit:** `OutboundWindowGuard` với policy mới (24h/7d/templateOnly). `UtilityTemplateService::resolveApproved` (chọn đúng template approved theo channel account + code + language).
- **Feature:**
  - `OrderConfirmationNotifier`: có template approved ⇒ tạo `Message` `KIND_UTILITY_TEMPLATE` (meta `utility_template_id` + vars), KHÔNG có `message_tag`. Không có template + trong 24h ⇒ `queueText` RESPONSE. Ngoài 24h + không template ⇒ không gửi. (Queue::fake)
  - Endpoints utility-template: tạo/submit/sync/list theo tenant; cô lập chéo tenant.
  - `SendMessage` nhánh `KIND_UTILITY_TEMPLATE`: connector hỗ trợ ⇒ gửi; không hỗ trợ ⇒ `utility_template_unsupported`.
- **Contract (connector):** fixture Graph cho create/sync/send (Http::fake) — assert payload + map status.
- **FE:** typecheck + build.

## 10. Tiêu chí hoàn thành

- [ ] Production hết lỗi `1893061`: tin xác nhận không bao giờ kèm tag chết.
- [ ] `outboundWindow()` chỉ còn `HUMAN_AGENT`; policy DTO có `humanAgentWindowHours`, `templateOnlyOutsideWindow`.
- [ ] `UtilityTemplateConnector` + DTO + FB implement; scope `pages_utility_messaging` thêm vào OAuth.
- [ ] Bảng `utility_templates` + service + job sync + endpoints (thin controller → service → resource).
- [ ] `OrderConfirmationNotifier` gửi qua utility template khi có, fallback đúng khi không.
- [ ] FE Settings quản lý template + trạng thái duyệt.
- [ ] Quality gate xanh (pint/phpstan/test; FE lint/typecheck/build). Docs `05-api/endpoints.md` + jobs cập nhật.

## 11. Câu hỏi mở

- Có plan-gate quản lý utility template không? (mặc định: KHÔNG — đây là tin giao dịch, không phải marketing). Chờ xác nhận.
- Tự động seed sẵn 1 template `order_confirmation` mặc định cho tenant khi connect Page? (hiện: tenant tự tạo).
