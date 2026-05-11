# Bản đồ Domain Module & Luật phụ thuộc

**Status:** Stable · **Cập nhật:** 2026-05-11

> Mục tiêu: nhìn vào là biết "code của tính năng X nằm ở module nào", và "module nào được phép gọi module nào". Vi phạm luật phụ thuộc = từ chối PR.

## 1. Danh sách module (`app/Modules/<Name>`)

| Module | Trách nhiệm | Model chính |
|---|---|---|
| **Tenancy** | Tenant, User, Member, Role/permission, global scope theo tenant, mời/quản lý thành viên, audit log | `Tenant`, `User`, `TenantUser`, `Role`, `AuditLog` |
| **Channels** | Gian hàng đã kết nối, OAuth connect/disconnect/refresh token, `ChannelRegistry`, nhận & điều phối webhook, job đồng bộ đơn/listing từ sàn, `sync_runs` | `ChannelAccount`, `OAuthState`, `WebhookEvent`, `SyncRun` |
| **Orders** | Đơn hàng (mọi nguồn), `OrderUpsertService`, **state machine trạng thái chuẩn**, lịch sử trạng thái, tạo đơn thủ công, gộp/tách đơn, tag/note, lọc/tìm | `Order`, `OrderItem`, `OrderStatusHistory` |
| **Inventory** | SKU master, kho, tồn theo (SKU,kho), **sổ cái biến động tồn**, đặt giữ/nhả tồn, ghép SKU (mapping), **đẩy tồn lên sàn**, nhập/xuất/điều chuyển/kiểm kê, giá vốn | `Sku`, `Warehouse`, `InventoryLevel`, `InventoryMovement`, `SkuMapping`, `StockTransfer`, `StockTake`, `CostLayer` |
| **Products** | Sản phẩm gốc, `ChannelListing`, đăng bán đa sàn (mass listing), sao chép listing, sửa hàng loạt, đồng bộ category/attribute của sàn | `Product`, `ChannelListing`, `ListingDraft`, `ChannelCategory` |
| **Fulfillment** | Vận đơn/kiện, lô lấy hàng, `CarrierRegistry`, gọi sàn/ĐVVC tạo vận chuyển + lấy label, **in hàng loạt** (PrintJob), template in, **quét đóng gói** (scan-to-pack/ship), đối soát phí ship | `Shipment`, `PickupBatch`, `PrintJob`, `PrintTemplate`, `CarrierAccount` |
| **Procurement** | Nhà cung cấp, bảng giá nhập, đơn mua (PO), nhận hàng → nhập kho → cập nhật giá vốn, đề xuất nhập hàng | `Supplier`, `SupplierPrice`, `PurchaseOrder`, `GoodsReceipt` |
| **Finance** | Kéo đối soát/settlement từ sàn, phân bổ phí theo đơn, tính lợi nhuận, đối chiếu tiền sàn trả | `Settlement`, `SettlementLine`, `OrderCost`, `ProfitSnapshot` |
| **Reports** | Báo cáo bán hàng/lợi nhuận/tồn/hiệu suất, export Excel/CSV (đọc từ read replica/bảng tổng hợp) | (không sở hữu bảng nghiệp vụ; có bảng cache báo cáo nếu cần) |
| **Billing** | Gói thuê bao, đăng ký/dùng thử/gia hạn, hoá đơn, cổng thanh toán VN, **đếm hạn mức** (usage counter) | `Plan`, `Subscription`, `Invoice`, `Payment`, `UsageCounter` |
| **Settings** | Quy tắc tự động hoá (rules engine), thông báo & kênh thông báo, cấu hình chung của tenant | `AutomationRule`, `Notification`, `NotificationChannel`, `TenantSetting` |

## 2. Sơ đồ phụ thuộc cho phép (mũi tên = "được phép phụ thuộc vào")

```
                         ┌──────────┐
                         │  Tenancy │  ← mọi module dùng (tenant scope, user, audit)
                         └────▲─────┘
        ┌──────────────┬──────┴───────┬───────────────┬──────────────┐
   ┌────┴────┐   ┌─────┴────┐    ┌────┴─────┐    ┌────┴─────┐   ┌────┴────┐
   │ Channels│   │ Inventory│    │ Products │    │ Billing  │   │ Settings│
   └────┬────┘   └────▲──┬──┘    └────▲─────┘    └──────────┘   └─────────┘
        │             │  │            │
   ┌────▼─────────────┴──▼────────────┴───┐
   │            Orders                     │  ← phụ thuộc Channels, Inventory, Products
   └────┬──────────────────────────────────┘
        │
   ┌────▼────────┐        ┌──────────────┐        ┌──────────────┐
   │ Fulfillment │        │  Procurement  │        │   Finance    │
   └─────────────┘        └──────┬───────┘        └──────┬───────┘
   (dùng Orders,Inventory,        │ (dùng Inventory)      │ (dùng Orders, Channels)
    Channels, Carrier reg.)       ▼                       ▼
                            ┌──────────┐           ┌──────────┐
                            │Inventory │           │ Reports  │ ← chỉ ĐỌC mọi module qua interface
                            └──────────┘           └──────────┘
```

## 3. Luật phụ thuộc (RULES — bắt buộc)

1. **Tenancy là nền** — mọi module được phụ thuộc vào `Tenancy` (model `Tenant`, `User`, scope, audit). `Tenancy` không phụ thuộc module nghiệp vụ nào.
2. **Module chỉ gọi nhau qua `Contracts/`** — interface đặt trong `app/Modules/<X>/Contracts/`. Không `use App\Modules\Orders\Services\...InternalService` từ module khác. Bind interface → implementation trong service provider của module.
3. **Hoặc giao tiếp qua domain event** — module phát event (`OrderUpdated`, `InventoryChanged`, `ShipmentCreated`...); module khác đăng ký listener. Listener phải idempotent (có thể chạy lại).
4. **Cấm phụ thuộc vòng** — nếu A cần B và B cần A → tách phần chung ra module/interface thứ ba hoặc dùng event.
5. **`Reports` chỉ đọc** — không ghi vào bảng của module khác; truy vấn qua interface đọc hoặc view DB; ưu tiên read replica/bảng tổng hợp.
6. **Integration layer ⟂ module** — `app/Integrations/Channels|Carriers` **không** import gì từ `app/Modules/*` ngoài DTO chuẩn & interface trong `Integrations/*/Contracts`. Module gọi connector qua `ChannelRegistry`/`CarrierRegistry`, không `new TikTokConnector` trực tiếp.
7. **Controller mỏng** — Controller chỉ: validate (FormRequest) → gọi 1 Service của 1 module → trả Resource. Không nghiệp vụ trong controller.
8. **Migration & model thuộc module sở hữu nó** — module khác không tạo migration đụng bảng của module khác; muốn thêm cột → đổi ở module sở hữu.

## 4. Khi thêm tính năng mới — quyết định module
- Nó về **đơn**? → `Orders`. Về **tồn/kho/SKU**? → `Inventory`. Về **vận đơn/in/ĐVVC**? → `Fulfillment`. Về **đăng bán/listing**? → `Products`. Về **kết nối sàn/webhook/đồng bộ**? → `Channels`. Về **tiền sàn trả/lợi nhuận**? → `Finance`. Về **gói/thanh toán SaaS**? → `Billing`.
- Nếu rơi vào nhiều module → đặt phần lõi ở module "sở hữu dữ liệu chính", phần còn lại gọi qua interface/event.
- Tính năng lớn → viết spec trong `docs/specs/` trước (xem `docs/specs/README.md`).
