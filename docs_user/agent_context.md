# Agent Context — OmniSell / CMBcoreSeller

> Nạp file này để AI hiểu hệ thống ngay. Nguồn sự thật chi tiết: `system-overview.md`, `business-rules.md`, `api-reference.md`, `frontend-guide.md`, `user-manual.md`, `faq.md`, `troubleshooting.md`.

## 1. Hệ thống là gì
- **OmniSell / CMBcoreSeller** — SaaS quản lý bán hàng đa sàn cho thị trường Việt Nam. Đồng bộ đơn/tồn/tem, đối soát tiền sàn, hộp thư tin nhắn có AI, kế toán TT133, gói thuê bao 4 cấp.
- **Sàn**: TikTok Shop, Lazada (live), Shopee (chờ duyệt API), `manual`. **ĐVVC**: GHN (live), GHTK/J&T (mẫu). **Thanh toán**: SePay, VNPay (MoMo skeleton). **Tin nhắn**: Facebook Page + chat Shopee/TikTok/Lazada. **AI**: Anthropic / OpenAI-compatible / custom HTTP / manual (super-admin quản, tenant chọn 1).
- **Kỹ thuật**: Laravel 11 monolith module hoá + React SPA (Ant Design). Connector + Registry → **lõi không biết tên sàn**. Horizon (queue), Gotenberg (PDF), MinIO/S3 (file), Reverb (realtime), Sentry.

## 2. Bất biến cốt lõi (luôn đúng)
1. Mọi bảng có `tenant_id`; không truy vấn xuyên tenant (cô lập tenant).
2. **Tồn = SKU gốc** là nguồn sự thật duy nhất. **Trạng thái đơn = máy trạng thái chuẩn**.
3. Tiền = **số nguyên VND** (không float). Thời gian API = ISO-8601 UTC.
4. Webhook không tin tưởng → verify chữ ký + luôn có polling; luôn fetch lại chi tiết trước khi lưu.
5. Job đồng bộ **idempotent** (dedupe theo khoá duy nhất).
6. Trạng thái trả về: `code` + `status_label` (VN) + `raw_status` (gốc sàn).
7. Thêm sàn/ĐVVC/cổng = connector + 1 dòng đăng ký + config; không sửa lõi; không `if ($provider === 'tiktok')`.

## 3. Module
Tenancy (nền: tenant/user/role/audit) · Channels (gian hàng, OAuth, webhook, sync) · Orders (đơn, máy trạng thái, đơn thủ công) · Customers (sổ khách theo phone hash, uy tín) · Inventory (SKU gốc, tồn, ghép SKU, đẩy tồn, FIFO) · Products (listing, mass listing) · Fulfillment (vận đơn, in, quét, lô lấy hàng) · Procurement (NCC, PO, nhận hàng, đề xuất nhập) · Finance (đối soát, lợi nhuận) · Reports (báo cáo, export) · Billing (gói, hoá đơn, thanh toán, gating) · Accounting (TT133, sổ kép, AR/AP, BCTC, MISA) · Settings (rule, thông báo, system_settings) · Messaging (hộp thư hợp nhất, auto-reply, AI/RAG) · Notifications (email thương hiệu).

## 4. Vai trò (tenant RBAC)
`owner` (toàn quyền + billing + xoá/chuyển tenant) · `admin` (toàn nghiệp vụ, không billing/xoá tenant) · `staff_order` (đơn + fulfillment + nhắn tin) · `staff_warehouse` (kho + nhận hàng + quét) · `staff_cs` (tin nhắn + xem đơn/khách) · `accountant` (đối soát + báo cáo + kế toán) · `viewer` (chỉ xem).
Quyền dạng chuỗi: `orders.*`, `inventory.*`, `fulfillment.*`, `messaging.*`, `accounting.*`, `finance.*`, `billing.*`, `channels.*`, `procurement.*`, `customers.*`, `reports.*`, `tenant.*`, `dashboard.view`. **Super-admin** = guard `admin_web` riêng, xuyên tenant, không phải vai trò tenant.

## 5. Gói & gating
| Gói | Tháng (VND) | Gian hàng | Tính năng thêm |
|---|---|---|---|
| trial/starter | 0 / 99k | 2 | cơ bản |
| pro | 199k | 5 | procurement, fifo_cogs, profit_reports, finance_settlements, demand_planning, accounting_basic, messaging_inbox |
| business | 399k | 10 | + mass_listing, automation_rules, accounting_advanced, messaging_ai, priority_support |
- Sub: `trialing→active→past_due→expired` + grace 7 ngày → trial vĩnh viễn. **Không khoá dữ liệu.** Không giới hạn số đơn.
- Gating: `plan.limit:channel_accounts` → 402 PLAN_LIMIT_REACHED; `plan.feature:<f>` → 402 PLAN_FEATURE_LOCKED; over-quota >2 ngày → 402 PLAN_QUOTA_EXCEEDED.

## 6. Trạng thái đơn (mã → nhãn)
`unpaid` Chờ thanh toán · `pending` Chờ xử lý · `processing` Đang xử lý · `ready_to_ship` Chờ bàn giao · `shipped` Đang vận chuyển · `delivered` Đã giao · `completed` Hoàn tất · `delivery_failed` Giao thất bại · `returning` Đang trả/hoàn · `returned_refunded` Đã trả/hoàn · `cancelled` Đã huỷ.
Quy tắc: dữ liệu sàn là nguồn sự thật (không bị chặn transition; lùi bất thường → `has_issue`); thao tác người dùng theo cạnh hợp lệ; `ready_to_ship` chỉ qua thao tác nội bộ; trừ tồn khi `shipped`; "Chuẩn bị hàng" chặn nếu âm kho.

## 7. Tồn kho (công thức quan trọng)
`available = max(0, on_hand − reserved − safety_stock)` → số đẩy lên sàn.
Vòng đời: pending/processing → reserve; cancel-trước-ship → release; ship → reserved−, on_hand−; refund-sau-ship hàng về → on_hand+; nhận PO → on_hand+ + tạo cost_layer. Listing chưa ghép không đẩy tồn + đơn `has_issue`. Combo = min(floor(available/qty)) thành phần. Đẩy tồn debounce 5–15s. COGS FIFO ghi bất biến vào `order_costs` khi ship.

## 8. Fulfillment & tin nhắn (điểm hay hỏi)
- Vận đơn: `pending→created→packed→picked_up→in_transit→delivered|failed`. `packed` chưa trừ tồn; trừ ở handover (`shipped`). Luồng A (logistics sàn) / Luồng B (ĐVVC riêng). Tem: dùng đúng PDF của ĐVVC. Phiếu lấy hàng gom theo SKU; phiếu đóng gói theo đơn.
- Facebook: cửa sổ 24h + message tag (`OUTBOUND_WINDOW_CLOSED` nếu vi phạm). Comment private reply **1 lần/comment** → lỗi 10900 xử lý idempotent. Modal nhắn riêng nhiều phần = best-effort (phần đầu private reply, phần sau qua PSID + HUMAN_AGENT). Like comment cần `pages_manage_engagement`.
- Auto-reply 4 trigger: schedule / order_status / away_no_response / first_message; chống spam bằng cooldown + idempotent window. AI: gợi ý (mặc định) vs auto-mode (opt-in, intent-classify chặn complaint/refund/urgent/legal/abuse, PII redaction).

## 9. Kế toán & tài chính
- Sổ kép TT133, VND, năm dương lịch. Bút toán **bất biến** (sửa = đảo). Kỳ: open→closed→locked (ghi vào kỳ đóng → `PERIOD_CLOSED`). Tự định khoản: nhận hàng Nợ156/Có331; chuyển kho 156↔156; kiểm kê 156↔711/811. Idempotency_key.
- Đối soát: phí thực theo đơn (10 loại chuẩn); lợi nhuận = doanh thu − COGS(FIFO) − phí − ship − giảm − khác.

## 10. API & lỗi (tóm tắt cho agent)
- Base `/api/v1`; Sanctum cookie + header `X-Tenant-Id`; envelope `{data,meta}` / `{error:{code,message,trace_id,details}}`.
- Endpoint hay dùng: `GET /orders`, `POST /orders`, `POST /orders/{id}/ship`, `POST /shipments/{pack,handover}`, `GET /inventory/levels`, `POST /sku-mappings/auto-match`, `GET /settlements`, `POST /settlements/{id}/reconcile`, `POST /billing/checkout`, `GET /messaging/conversations`, `POST /messaging/conversations/{id}/messages`, `POST /accounting/setup`, `POST /accounting/periods/{code}/close`.
- Mã lỗi: `EMAIL_NOT_VERIFIED`, `TENANT_REQUIRED/FORBIDDEN`, `PLAN_LIMIT_REACHED`, `PLAN_FEATURE_LOCKED`, `PLAN_QUOTA_EXCEEDED`, `DOWNGRADE_NOT_ALLOWED`, `ALREADY_ON_PLAN`, `OUTBOUND_WINDOW_CLOSED`, `ATTACHMENT_INVALID`, `PERIOD_CLOSED`, `ACCOUNTING_UNBALANCED`, `ACCOUNTING_ACCOUNT_IN_USE`, `SKU_CODE_TAKEN`, `UNKNOWN_PROVIDER`.

## 11. Thuật ngữ (glossary nhanh)
SKU gốc (đơn vị tồn, nguồn sự thật) · listing (sản phẩm trên 1 shop) · ghép SKU (mapping listing↔SKU) · tồn khả dụng (available) · đặt giữ/nhả (reserve/release) · gian hàng (channel account) · vận đơn (shipment) · phiếu giao hàng (label) · đối soát (settlement) · giá vốn FIFO (COGS) · kỳ kế toán (fiscal period) · định khoản (journal entry) · hộp thư hợp nhất (unified inbox) · private reply / message tag · over-quota lock · raw_status (trạng thái gốc sàn).

## 12. Cách trả lời người dùng (gợi ý cho agent)
- Dùng nhãn tiếng Việt của màn hình/nút (vd "Chuẩn bị hàng", "Đối soát sàn", "Khởi tạo hệ thống tài khoản theo TT133").
- Khi nói về tính năng nâng cao, nhắc gói yêu cầu (Pro/Business) và quyền cần có.
- Khi lỗi, ánh xạ mã lỗi → nguyên nhân → cách xử lý (xem `troubleshooting.md`).
- Khi không chắc, ưu tiên mô tả nghiệp vụ + trỏ tới file tài liệu liên quan, tránh bịa endpoint/route.
