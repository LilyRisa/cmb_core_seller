# ADR-0004: Connector + Registry pattern cho sàn & ĐVVC; core không bao giờ biết tên một sàn/ĐVVC cụ thể

- **Trạng thái:** Accepted
- **Ngày:** 2026-05-11
- **Người quyết định:** Team

## Bối cảnh

Sản phẩm sống/chết theo khả năng **thêm sàn (TikTok → Shopee → Lazada → …) và thêm ĐVVC (GHN, GHTK, J&T, …)** mà không đụng vào nghiệp vụ lõi. Rủi ro lớn nhất: `if ($provider === 'shopee')` rải khắp code.

## Quyết định

- Hai trục mở rộng, mỗi trục một **interface + registry**:
  - Sàn TMĐT: `ChannelConnector` ↔ `ChannelRegistry` (`app/Integrations/Channels/<Name>/`).
  - ĐVVC: `CarrierConnector` ↔ `CarrierRegistry` (`app/Integrations/Carriers/<Name>/`).
  - (sau) cổng thanh toán, hoá đơn điện tử: tương tự.
- **Core chỉ làm việc với DTO chuẩn** (`OrderDTO`, `TokenDTO`, `ShopInfoDTO`, `WebhookEventDTO`, …). Mọi cái riêng của một sàn (HTTP, ký, version API, map status, parse webhook, capability) nằm trong connector của nó. Connector không hỗ trợ một thao tác ⇒ ném `UnsupportedOperation`; core kiểm `supports()` trước (capability map). **Không** thêm `if/switch` theo tên sàn ở module nghiệp vụ — thêm vào capability map của connector đó.
- Thêm sàn/ĐVVC mới = **1 thư mục connector + 1 dòng `register('<code>', XConnector::class)` + 1 dòng trong `config/integrations.php`** + contract test với fixtures + doc channel. Đăng ký từ service provider, danh sách bật/tắt đọc từ config (không sửa code để bật/tắt).
- `"manual"` cũng là một `ChannelConnector` rỗng (`ManualConnector`) để code đối xử mọi nguồn đơn đồng nhất.

## Hệ quả

- Tích cực: thêm sàn/ĐVVC không phải sửa `OrderUpsertService`, state machine, DTO chuẩn; đổi API ngoài chỉ ảnh hưởng tầng mapper của connector đó; bật/tắt sàn bằng config.
- Đánh đổi: nếu thực sự cần một field mới chỉ một sàn có ⇒ phải thêm vào DTO chuẩn + các connector khác trả `null` + ghi ADR; phải duy trì capability map cho mỗi connector; cần contract test cho từng connector.
- Liên quan: `01-architecture/extensibility-rules.md` (★), `04-channels/README.md`, `app/Integrations/*`.
