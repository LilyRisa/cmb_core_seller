# Tổng quan hệ thống — OmniSell / CMBcoreSeller

> Tài liệu dành cho: khách hàng tra cứu, nhân viên support, AI Agent, lập trình viên onboard.
> Góc nhìn: **người sử dụng hệ thống** (không đi vào chi tiết code).

---

## 1. Hệ thống là gì?

**OmniSell** (tên tầm nhìn / marketing) — còn gọi **CMBcoreSeller** (tên thương hiệu trong sản phẩm & email) — là phần mềm **quản lý bán hàng đa sàn (SaaS)** cho thị trường Việt Nam.

Hệ thống giúp nhà bán **gom mọi việc về một nơi** thay vì đăng nhập từng seller center: đồng bộ đơn hàng, quản lý tồn kho theo SKU gốc, in tem/phiếu giao hàng hàng loạt, đóng gói bằng quét mã, nhập hàng và tính giá vốn FIFO, đối soát tiền sàn trả về và tính lợi nhuận thực, hộp thư tin nhắn hợp nhất có AI trả lời tự động, kế toán đầy đủ theo chuẩn Việt Nam (TT133), và quản lý gói thuê bao 4 cấp.

**Sàn/đối tác hỗ trợ:**

| Trục | Đang chạy | Có sẵn code, bật theo môi trường | Đang chờ |
|---|---|---|---|
| **Sàn TMĐT** | TikTok Shop, Lazada; `manual` (đơn thủ công) luôn bật | Shopee (đủ cấu hình, chờ duyệt API) | Shopee |
| **Đơn vị vận chuyển (ĐVVC)** | GHN (live); mẫu sẵn cho GHTK, J&T | (cấu hình theo môi trường) | ViettelPost, NinjaVan, SPX, VNPost... |
| **Cổng thanh toán** | SePay (chuyển khoản qua webhook), VNPay (redirect + IPN) | — | MoMo (mới skeleton) |
| **Tin nhắn** | Facebook Page Messenger + chat Shopee/TikTok/Lazada | — | — |
| **AI** | Đa adapter: Anthropic, OpenAI-compatible, custom HTTP, manual | — | — |

> **Lưu ý "đang bật" ≠ "đã code":** môi trường dev mặc định chỉ bật `manual,tiktok`. Lazada/Shopee/SePay/VNPay đã có code, được bật/tắt theo từng môi trường qua biến cấu hình `INTEGRATIONS_*`.

---

## 2. Ai dùng hệ thống?

**Phía nhà bán (tenant — mỗi gian hàng/workspace là 1 tenant):**

| Vai trò | Nhãn | Dùng để làm gì |
|---|---|---|
| `owner` | Chủ sở hữu | Toàn quyền, gồm thanh toán/gói, xoá/chuyển workspace |
| `admin` | Quản trị | Toàn bộ nghiệp vụ + quản lý nhân viên; **không** đụng thanh toán/xoá workspace |
| `staff_order` | NV xử lý đơn | Xem/sửa/tạo đơn, chuẩn bị hàng, in, bàn giao; xem khách & nhắn tin |
| `staff_warehouse` | NV kho | Tồn kho, điều chỉnh/chuyển/kiểm kê, ghép SKU, nhận hàng, quét đóng gói |
| `staff_cs` | NV chăm sóc khách | Hộp thư tin nhắn, mẫu tin; xem đơn/khách (không sửa đơn/kho) |
| `accountant` | Kế toán | Đối soát, báo cáo, kế toán (định khoản/đóng kỳ), xem hoá đơn gói |
| `viewer` | Chỉ xem | Chỉ đọc đơn/kho/sản phẩm/khách/dashboard |

**Phía vận hành SaaS:**
- **Super-admin** — nhân viên vận hành CMBcoreSeller, làm việc xuyên tenant. Đăng nhập riêng (`/admin`), bảng `admin_users` + guard `admin_web` riêng, **không** phải vai trò trong tenant.

---

## 3. Các module chính

Hệ thống là **monolith module hoá**. Mỗi module sở hữu dữ liệu của mình và giao tiếp với module khác qua interface/sự kiện.

| Module | Vai trò (một dòng) |
|---|---|
| **Tenancy** | Tenant, User, thành viên, vai trò/quyền, phân tách dữ liệu theo tenant, audit log. Nền tảng. |
| **Channels** | Gian hàng đã kết nối, OAuth, `ChannelRegistry`, nhận webhook, job đồng bộ đơn/listing, nhật ký đồng bộ. |
| **Orders** | Đơn từ mọi nguồn, máy trạng thái chuẩn, lịch sử trạng thái, đơn thủ công, gộp/tách, tag/note, lọc/tìm. |
| **Customers** | Sổ khách hàng nội bộ; match đơn theo SĐT chuẩn hoá, thống kê vòng đời, ghi chú, điểm uy tín, ẩn danh. |
| **Inventory** | SKU gốc, kho, tồn theo (SKU,kho), sổ cái biến động tồn, đặt giữ/nhả, ghép SKU, đẩy tồn lên sàn, giá vốn FIFO. |
| **Products** | Sản phẩm gốc, listing sàn, đăng bán hàng loạt, sao chép/sửa hàng loạt, đồng bộ category/attribute. |
| **Fulfillment** | Vận đơn/kiện, lô lấy hàng, `CarrierRegistry`, lấy label, in hàng loạt, mẫu in, quét đóng gói, đối soát phí ship. |
| **Procurement** | Nhà cung cấp, bảng giá nhập, đơn mua (PO), nhận hàng → nhập kho → giá vốn, đề xuất nhập hàng. |
| **Finance** | Kéo đối soát từ sàn, phân bổ phí theo đơn, tính lợi nhuận, đối chiếu tiền sàn trả. |
| **Reports** | Báo cáo bán hàng/lợi nhuận/tồn + export Excel/CSV. Chỉ đọc. |
| **Billing** | Gói thuê bao 4 cấp, dùng thử/gia hạn/grace, hoá đơn, cổng thanh toán VN, đếm hạn mức + gating. |
| **Accounting** | Kế toán đầy đủ theo TT133: hệ thống TK, kỳ + khoá, sổ cái kép bất biến, AR/AP, quỹ/ngân hàng, BCTC, VAT, export MISA. |
| **Settings** | Quy tắc tự động hoá, thông báo, cấu hình tenant; super-admin `system_settings`. |
| **Messaging** | Hộp thư hợp nhất (Shopee/TikTok/Lazada/Facebook), inbox realtime 3 cột, mẫu tin, auto-reply 4 trigger, AI + RAG. |
| **Notifications** | Email thương hiệu (xác thực/welcome/reset mật khẩu); nền cho Zalo/in-app sau này. |

---

## 4. Sitemap (toàn bộ trang & route)

### Ứng dụng người dùng (tenant SPA — `app.tsx`)

**Công khai (chưa đăng nhập):**

```
/login                  Đăng nhập
/register               Đăng ký
/email-verified         Callback xác thực email
/forgot-password        Quên mật khẩu
/password-reset         Đặt lại mật khẩu
/404                    Không tìm thấy
```

**Sau đăng nhập (trong AppLayout):**

```
/                       Bảng điều khiển (Dashboard)
/orders                 Đơn hàng
/orders/new             Tạo đơn thủ công
/orders/:id             Chi tiết đơn
/orders/:id/edit        Sửa đơn (đơn thủ công)
/returns                Hoàn & Hủy
/channels               Gian hàng (kết nối sàn)
/customers              Khách hàng
/customers/:id          Chi tiết khách hàng
/products  →  redirect → /inventory?tab=skus   (Sản phẩm & SKU)
/inventory              Tồn kho
/inventory/skus/new     Thêm SKU
/inventory/skus/:id/edit Sửa SKU
/procurement/suppliers          Nhà cung cấp
/procurement/purchase-orders    Đơn mua hàng
/procurement/demand-planning    Đề xuất nhập hàng
/reports                Báo cáo
/finance/settlements    Đối soát sàn
/accounting/journals            Sổ nhật ký
/accounting/chart-of-accounts   Hệ thống tài khoản
/accounting/periods             Kỳ kế toán
/accounting/balances            Cân đối phát sinh
/accounting/ar                  Công nợ phải thu
/accounting/ap                  Công nợ phải trả
/accounting/cash                Quỹ & Ngân hàng
/accounting/reports             Báo cáo tài chính
/sync-logs              Nhật ký đồng bộ
```

**Tin nhắn:**

```
/messaging                      Hộp thư
/messaging/channels             Kết nối kênh (Facebook)
/messaging/templates            Mẫu tin
/messaging/auto-rules           Tự động trả lời
/messaging/flows                Kịch bản tự động
/messaging/flows/:id/edit       Trình thiết kế kịch bản
/messaging/knowledge            AI training (RAG)
```

**Cài đặt (`/settings/*`):**

```
/settings/profile               Hồ sơ cá nhân
/settings/workspace             Thông tin gian hàng
/settings/plan                  Gói & nâng cấp
/settings/members               Nhân viên & vai trò
/settings/carriers              Đơn vị vận chuyển
/settings/orders                Cài đặt đơn hàng (phí sàn để ước tính lợi nhuận)
/settings/messaging             Cấu hình AI tin nhắn (vào từ menu Tin nhắn)
/settings/print                 Mẫu in (khổ tem + ghi chú)
/settings/shipping-labels       Mẫu phiếu giao hàng
/settings/shipping-labels/new|:id  Trình thiết kế phiếu (kéo–thả)
/settings/accounting/post-rules Quy tắc hạch toán
```

### Ứng dụng super-admin (`admin.tsx`)

```
/admin/login            Đăng nhập admin
/admin                  Tổng quan
/admin/tenants          Tenants (quản lý nhà bán)
/admin/users            Người dùng (admin + tenant users)
/admin/vouchers         Voucher
/admin/plans            Gói thuê bao
/admin/broadcasts       Broadcast email
/admin/settings         Cấu hình hệ thống (system_settings)
/admin/ai-providers     Nhà cung cấp AI
/admin/audit-logs       Nhật ký thao tác
```

### Cấu trúc menu (sidebar người dùng)

- **Tổng quan**: Bảng điều khiển
- **Bán hàng**: Đơn hàng · Hoàn & Hủy · Khách hàng · **Tin nhắn** (Hộp thư, Kết nối kênh, Mẫu tin, Tự động trả lời, Kịch bản tự động, AI training) · Gian hàng · Sản phẩm & SKU
- **Kho & Mua hàng**: Tồn kho · Đề xuất nhập hàng · Nhà cung cấp · Đơn mua hàng
- **Báo cáo & Kế toán**: Báo cáo · Đối soát sàn · Sổ nhật ký · Hệ thống TK · Cân đối phát sinh · Công nợ phải thu · Công nợ phải trả · Quỹ & Ngân hàng · Báo cáo tài chính · Kỳ kế toán
- **Hệ thống**: Nhật ký đồng bộ · Cài đặt

---

## 5. Nền tảng kỹ thuật (tóm tắt cho người dùng)

- **Backend**: Laravel 11 (PHP), kiến trúc monolith module hoá. Lớp tích hợp dùng mẫu **Connector + Registry** — lõi không bao giờ biết tên sàn cụ thể; thêm sàn mới = thêm 1 connector + 1 dòng đăng ký + 1 khối cấu hình.
- **Frontend**: React 18 + Vite + Ant Design (SPA), 2 bundle: người dùng (`app.tsx`) và admin (`admin.tsx`).
- **Hàng đợi**: Laravel Horizon (Redis ở prod). **PDF**: Gotenberg (tem, phiếu lấy/đóng hàng). **Lưu trữ file**: MinIO/S3. **Realtime tin nhắn**: Laravel Reverb. **Theo dõi lỗi**: Sentry.
- **Bảo mật & dữ liệu**: xác thực Sanctum (cookie SPA); chọn tenant qua header `X-Tenant-Id`; mọi bảng nghiệp vụ có `tenant_id` (cô lập dữ liệu theo tenant); tiền = **số nguyên VND** (không dùng số thực); thời gian API = ISO-8601 UTC.

---

## 6. Nguyên tắc bất biến toàn hệ thống

1. **Cô lập tenant**: mọi bảng có `tenant_id`, dùng global scope tự động — không truy vấn xuyên tenant.
2. **Một nguồn sự thật**: tồn kho = **SKU gốc**; trạng thái đơn = **máy trạng thái chuẩn**.
3. **Webhook không tin tưởng**: luôn xác minh chữ ký + luôn có polling dự phòng; webhook chỉ là tín hiệu, luôn fetch lại chi tiết trước khi lưu.
4. **Job đồng bộ idempotent**: chạy lại không nhân đôi dữ liệu (dedupe theo khoá duy nhất).
5. **Tiền = số nguyên VND**: không số thực ở bất kỳ đâu (đơn, đối soát, sổ cái, hoá đơn).
6. **Quy tắc mở rộng tối thượng**: lõi không biết tên sàn/ĐVVC/cổng thanh toán; thêm mới chỉ qua connector + config.

> Tài liệu liên quan: [frontend-guide.md](frontend-guide.md) · [business-rules.md](business-rules.md) · [api-reference.md](api-reference.md) · [user-manual.md](user-manual.md) · [faq.md](faq.md) · [troubleshooting.md](troubleshooting.md) · [agent_context.md](agent_context.md)
