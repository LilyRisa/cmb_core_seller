# Từ điển thuật ngữ

> Dùng đúng từ. Trong code, DB, tài liệu, UI — gọi cùng một thứ bằng cùng một tên.

| Thuật ngữ | Tiếng Anh (dùng trong code) | Nghĩa |
|---|---|---|
| Nhà bán / Workspace | **Tenant** | Một tài khoản tổ chức trong hệ thống; sở hữu mọi dữ liệu nghiệp vụ. Mọi bảng nghiệp vụ có `tenant_id`. |
| Thành viên | **Member** / `tenant_user` | Một `User` thuộc một `Tenant` với một `Role` (owner/admin/staff_order/staff_warehouse/accountant/viewer). |
| Sàn | **Channel** / **Provider** | TikTok Shop, Shopee, Lazada (và "manual" cho đơn tự tạo). Mã: `tiktok`, `shopee`, `lazada`, `manual`. |
| Gian hàng (kết nối) | **Channel Account** / **Shop Connection** | Một shop cụ thể của một nhà bán trên một sàn, kèm access/refresh token. Một tenant có nhiều channel account. |
| Đầu nối sàn | **ChannelConnector** | Lớp adapter implement hợp đồng chung để gọi API một sàn; trả về DTO chuẩn. Thêm sàn = thêm 1 connector. |
| Đầu nối ĐVVC | **CarrierConnector** | Lớp adapter cho một đơn vị vận chuyển (GHN/GHTK/J&T...). |
| Sản phẩm gốc | **Product** (master) | Sản phẩm trong kho của nhà bán (nội bộ), độc lập với sàn. |
| SKU gốc | **SKU** (master SKU) | Đơn vị tồn kho nhỏ nhất của nhà bán (vd "Áo thun đỏ size M"). **Đây là một nguồn sự thật về tồn kho.** Có `sku_code` duy nhất theo tenant. |
| Listing trên sàn | **Channel Listing** | Một SP/biến thể đang bán trên một gian hàng (có `external_product_id`, `external_sku_id`, `seller_sku`...). |
| Mã SKU người bán | **seller_sku** | Mã SKU mà người bán tự đặt khi đăng bán trên sàn. Dùng để auto-match với `sku_code`. |
| Ghép SKU | **SKU Mapping** | Liên kết `channel_listing` ↔ `sku` (có `quantity` để hỗ trợ combo/bundle: 1 listing = N master SKU). |
| Tồn theo kho | **Inventory Level** | Bản ghi (`sku_id`, `warehouse_id`) với `on_hand`, `reserved`, `available`, `safety_stock`. |
| Sổ cái tồn kho | **Inventory Movement** | Bản ghi bất biến cho mỗi thay đổi tồn (đặt giữ / nhả / xuất / nhập / điều chỉnh / hoàn). Audit trail. |
| Đẩy tồn lên sàn | **Push stock / Stock sync out** | Job đồng bộ `available` của master SKU lên `channel_listing.channel_stock`. |
| Trạng thái chuẩn | **Standard order status** | Tập trạng thái nội bộ thống nhất; mỗi sàn map từ `raw_status` → trạng thái này. |
| Trạng thái gốc | **raw_status** | Chuỗi trạng thái nguyên bản từ sàn (lưu kèm để debug & hiển thị chi tiết). |
| Lịch sử trạng thái | **Order status history** | Mỗi lần đổi trạng thái → 1 dòng (from/to/raw/source/time). |
| Vận đơn / kiện | **Shipment / Package** | Một kiện hàng của một đơn (đơn có thể tách nhiều kiện); có `tracking_no`, `carrier`, `label_url`, `status`. |
| Khách hàng (sổ khách) | **Customer** *(Phase 2)* | Hồ sơ người mua **nội bộ tenant**, khớp đơn qua **SĐT chuẩn hoá**. Một customer có nhiều `orders` (cross-channel + đơn manual). Lưu lifetime stats, ghi chú, reputation. Không bao giờ chia sẻ chéo tenant. |
| Số điện thoại chuẩn hoá | **Normalized phone / Canonical phone** | SĐT sau khi strip ký tự, đưa về dạng `0xxxxxxxxx` (VN) hoặc `+xxxxxx` (E.164 khác). Quy tắc ở `03-domain/customers-and-buyer-reputation.md` §2. SĐT bị mask (`****`) ⇒ không chuẩn hoá được ⇒ không khớp khách được. |
| Phone hash | **`phone_hash`** | `sha256(normalized_phone)` hex 64 ký tự. Khoá khớp duy nhất giữa các đơn cùng khách. Deterministic + không reverse được ⇒ index an toàn. Unique `(tenant_id, phone_hash)`. |
| Điểm tin cậy khách | **Reputation score / label** | 0–100 (heuristic rule-based, KHÔNG ML), map sang label `ok` / `watch` / `risk` / `blocked`. Tính từ lifetime stats: trừ điểm huỷ/giao thất bại/trả hàng, cộng điểm hoàn thành. Là **gợi ý cho NV** — không tự động hành động (việc đó của rules engine Phase 6). |
| Cờ tự động | **Auto-note** | Ghi chú hệ thống tự thêm vào `customer_notes` khi khách vượt ngưỡng (vd 2 đơn huỷ, 3 đơn trả). Dedupe theo ngưỡng — không lặp. Phân biệt với **manual note** (NV gõ). |
| Lô lấy hàng | **Pickup Batch** | Nhóm shipment cùng gian hàng/ĐVVC để bàn giao một lần. |
| In hàng loạt | **Bulk print / Print Job** | Job ghép nhiều label/picking/packing thành một file PDF để in. |
| Phiếu soạn hàng | **Picking List** | Danh sách SKU cần lấy ra để soạn cho một nhóm đơn (gom theo SKU). |
| Phiếu đóng gói | **Packing List** | Phiếu kèm trong kiện hàng, theo từng đơn. |
| Quét đóng gói | **Scan-to-pack / Scan-to-ship** | Quét barcode trên vận đơn → đối chiếu đơn → xác nhận đóng/bàn giao → trừ tồn → đẩy trạng thái. |
| Template in | **Print Template** | Định nghĩa khổ giấy/layout/logo/trường hiển thị cho phiếu in. |
| Đối soát | **Settlement** | Bảng kê tiền sàn trả cho nhà bán theo kỳ, gồm các khoản phí. `settlement_lines` = chi tiết theo đơn. |
| Lợi nhuận đơn | **Order profit** | Doanh thu − giá vốn − phí sàn − phí ship − chiết khấu − chi phí khác. |
| Đơn mua / Nhập hàng | **Purchase Order (PO)** | Đơn đặt hàng từ NCC; nhận hàng → nhập kho → cập nhật giá vốn. |
| Đối soát phí ship | — | So phí vận chuyển thực tế (ĐVVC/sàn báo) với ước tính. |
| Hạn mức sử dụng | **Usage Counter** | Bộ đếm denormalized (v1 chỉ có `channel_accounts`) để hiển thị "đã dùng N/M" + lưới an toàn. Hạn mức cứng kiểm bằng query trực tiếp ở middleware. |
| Gói thuê bao | **Plan** *(Phase 6.4 — SPEC-0018)* | Một trong 4 tier: `trial · starter · pro · business`. Lưu DB (admin sửa được), gồm `price_monthly/yearly` bigint VND, `limits {max_channel_accounts}`, `features {procurement, fifo_cogs, profit_reports, finance_settlements, demand_planning, mass_listing, automation_rules, priority_support}`. |
| Đăng ký gói | **Subscription** *(Phase 6.4)* | Một tenant tại một thời điểm có 1 subscription "alive" (partial unique index `WHERE status IN trialing/active/past_due`). State machine: `trialing → active → past_due → expired` (+ `cancelled` chạy đến hết `cancel_at`). Hết hạn ⇒ grace 7 ngày → fallback `trial` vĩnh viễn (không khoá data). |
| Hoá đơn | **Invoice** *(Phase 6.4)* | Mã `INV-YYYYMM-NNNN` unique per tenant. Status `draft → pending → paid` (`void`/`refunded` ngoại lệ). Snapshot `billing_profile` vào `customer_snapshot` khi tạo (immutable). |
| Cổng thanh toán | **Payment Gateway / PaymentGatewayConnector** *(Phase 6.4)* | Adapter chuẩn hoá cho mỗi cổng (SePay/VNPay/MoMo). Resolve qua `PaymentRegistry`. Hai biến thể UX: `method=bank_transfer` (SePay QR + memo) vs `method=redirect` (VNPay/MoMo). |
| Quy tắc tự động | **Automation Rule** | Điều kiện → hành động (vd "đơn TikTok COD < 200k → tự xác nhận + gán kho A"). |
| Đường găng | **Critical path** | Việc mà chậm nó là chậm cả dự án — ở đây là duyệt API Shopee/Lazada. |
