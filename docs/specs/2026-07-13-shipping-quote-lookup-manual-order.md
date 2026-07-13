# SPEC: Nút tra cứu cước vận chuyển (tham khảo) ở tạo đơn thủ công

- **Trạng thái:** Design
- **Module backend liên quan:** Fulfillment (`ShipmentController`/`ShipmentService`) + Integration layer (`Integrations/Carriers/{Ghn,Ghtk,ViettelPost}`)
- **Tác giả / Ngày:** lilyrisa · 2026-07-13
- **Liên quan:** SPEC 0006 (Fulfillment/shipments), `carrier-payment-and-inspection-mapping` (memory), commit 82bebe24 (đã gỡ nút "Gợi ý phí GHTK" cũ — spec này thay thế bằng bản tổng quát 3 ĐVVC).

## 1. Vấn đề & mục tiêu

Form tạo đơn thủ công không có cách nào để người bán xem trước cước vận chuyển ước tính trước khi tạo đơn — trước đây có nút "Gợi ý phí GHTK" (chỉ 1 ĐVVC) nhưng đã bị gỡ (commit 82bebe24, lý do "không cần thiết" khi chỉ có 1 sàn). Nay cần bản tổng quát: tra cứu cước **tất cả ĐVVC tenant đang có tài khoản** cùng lúc, hiển thị tham khảo (không ghi vào đâu).

Rà tài liệu chính thức của 3 ĐVVC xác nhận:
- **GHTK** (`api.ghtk.vn/docs/submit-order/calculate-shipping-fee/`): `GET /services/shipment/fee` — đã có sẵn (`GhtkConnector::quote()`), khớp tài liệu, trả **1 mức giá** (`fee.name`: area1/2/3 tự động theo vùng, không chọn được).
- **ViettelPost** (`partner2.viettelpost.vn/document/get-list-service-by-address-id`): `POST /v2/order/getPriceAll` — đã có sẵn (`ViettelPostConnector::quote()`), trả về **mảng nhiều gói dịch vụ** (mã/tên/giá/thời gian mỗi gói, vd "Nội tỉnh tiết kiệm") cho cùng 1 tuyến — nhưng backend hiện chỉ giữ phần tử đầu (`$quotes[0]`), bỏ các gói còn lại.
- **GHN** (`api.ghn.vn/home/docs/detail?id=95`): `POST /v2/shipping-order/fee` — **chưa có code nào implement** (connector không có `quote()`, không có trong `capabilities()`). Tham số chỉ nhận `service_type_id` (2=hàng nhẹ/5=hàng nặng) — **không có tham số để tách giá theo tên gói Nhanh/Chuẩn/Tiết kiệm** dù API riêng `available-services` (id=77) có liệt kê tên các gói này. ⇒ GHN chỉ trả được **1 mức giá** qua tài liệu chính thức.

## 2. Trong / ngoài phạm vi

- **Trong:**
  - Backend: endpoint mới `POST /api/v1/fulfillment/quote-all` — lặp qua **mọi carrier_account đang active** của tenant, gọi `connector->quote()` từng cái, trả **nguyên mảng kết quả** (không cắt còn 1 như endpoint `quote` cũ).
  - Backend: thêm `GhnConnector::quote()` + `GhnClient::fee()` mới (theo tài liệu id=95).
  - Cân nặng/kích thước dùng để tính cước lấy từ **cấu hình mặc định của từng tài khoản ĐVVC** (`carrier_account.meta.defaults.package` + `goods_type`, đã có sẵn từ SPEC "cài đặt giao hàng mặc định theo tài khoản ĐVVC") — **không** phụ thuộc giỏ hàng/sản phẩm đã thêm.
  - Frontend: nút "Tra cứu cước vận chuyển" trong card Thanh toán (`CreateOrderPage.tsx`), chỉ hiện khi địa chỉ nhận đã đủ tỉnh + (quận/huyện hoặc phường/xã). Bấm mở `Modal` mới, gọi API, liệt kê từng dòng theo tài khoản (VTP có thể nhiều dòng/tài khoản).
  - Modal **thuần tham khảo** — không có nút "Áp dụng", không ghi giá trị vào ô nào của form. Đóng bằng nút Đóng/X.
  - Tài khoản lỗi/không phủ tuyến hiện dòng báo lỗi riêng, không làm hỏng cả modal.
- **Ngoài (không làm):**
  - Không cho chọn ĐVVC/gói cụ thể để áp dụng vào đơn — việc chọn ĐVVC thực tế vẫn ở bước "Chuẩn bị hàng" (kiến trúc hiện tại, xem comment trong `CreateOrderPage.tsx`: "Đơn manual chọn ĐVVC ở bước Chuẩn bị hàng qua CarrierAccountPicker").
  - Không sửa `ShipmentService::createForOrder`/luồng đẩy đơn thật — chỉ thêm 1 API tra cứu read-only.
  - Không thêm tên gói (Nhanh/Chuẩn/Tiết kiệm) cho GHN vì không có giá riêng để đối chiếu — tránh hiển thị sai/gây hiểu nhầm.
  - **Xoá endpoint đơn-tài-khoản cũ** `POST /fulfillment/quote` (`ShipmentController::quote()`, `ShipmentService::quoteShippingFee()`, hook FE `useShippingQuote()`) — đã xác nhận **0 call site** sau khi gỡ nút GHTK (commit 82bebe24), và giữ lại sẽ mâu thuẫn với việc đổi chữ ký `quote()` connector (mục 3.1: bỏ `weight_grams` khỏi `$request`) — endpoint cũ vẫn validate `weight_grams` required nhưng connector sẽ không dùng tới nữa, gây hiểu nhầm. `quote-all` thay thế hoàn toàn.

## 3. Luồng chính

### 3.1 Backend

**`GhnClient::fee(array $payload): array`** (mới) — `POST /v2/shipping-order/fee` (dùng `$this->http()` sẵn có, header Token+ShopId tự động). Payload: `service_type_id, from_district_id, from_ward_code, to_district_id, to_ward_code, weight, length, width, height, insurance_value`. Trả `data` (`total, service_fee, insurance_fee, ...`). Lỗi (`code !== 200`) → `RuntimeException('GHN tính phí lỗi: ...')`.

**`GhnConnector::quote(array $account, array $request): array`** (mới, thêm `'quote'` vào `capabilities()`):
1. Resolve sender codes từ `account.meta.from_address` (`district_id`, `ward_code` — đã có sẵn, dùng lại như `createShipment`).
2. Resolve recipient codes qua `GhnAddressResolver` có sẵn (giống `ViettelPostConnector::quote()` đang làm với `ViettelPostAddressResolver`).
3. `service_type_id` suy từ `account.meta.defaults.goods_type` (`heavy`→5, còn lại→2) — cùng logic `buildGhnPayload` đang dùng lúc tạo đơn thật.
4. Cân nặng/kích thước lấy từ `account.meta.defaults.package` (`weight_grams/length_cm/width_cm/height_cm`), KHÔNG từ `$request` (request chỉ còn `recipient`, xem 3.1 tiếp theo).
5. Trả `[{ carrier: 'ghn', fee: total, insurance_fee, name: null }]` (1 phần tử, `name: null` — không có tên gói, theo quyết định ở mục 2).
6. Thiếu địa chỉ/không resolve được → trả `[]` (không throw) để caller hiển thị "không lấy được cước", nhất quán với cách `ViettelPostConnector::quote()` đang xử lý thiếu dữ liệu.

**Đổi chữ ký `quote()` của cả 3 connector** — bỏ `weight_grams`/`value` khỏi `$request` (không còn truyền từ FE), **mỗi connector tự đọc cân nặng/kích thước từ `account.meta.defaults.package`**. `$request` chỉ còn `{ recipient: {...} }`. Đây là thay đổi hành vi nhất quán cho cả 3 connector (không chỉ GHN) — vì mục 2 đã chốt "cân nặng lấy từ cấu hình tài khoản, không phải giỏ hàng".

**`ShipmentService::quoteAllShippingFees(int $tenantId, array $recipient): array`** (mới, cạnh `quoteShippingFee()` cũ — giữ nguyên `quoteShippingFee()`, không xoá):
```php
public function quoteAllShippingFees(int $tenantId, array $recipient): array
{
    $accounts = CarrierAccount::query()->where('tenant_id', $tenantId)->where('is_active', true)->get();
    $out = [];
    foreach ($accounts as $account) {
        $connector = $this->carriers->has($account->carrier) ? $this->carriers->for($account->carrier) : null;
        if (! ($connector instanceof AbstractCarrierConnector) || ! $connector->supports('quote')) {
            continue; // ĐVVC không hỗ trợ tính phí (vd carrier thủ công) — bỏ qua, không báo lỗi.
        }
        try {
            $quotes = $connector->quote($account->toConnectorArray(), ['recipient' => $recipient]);
            if ($quotes === []) {
                $out[] = ['carrier_account_id' => $account->id, 'carrier' => $account->carrier, 'carrier_name' => $connector->displayName(), 'account_name' => $account->name, 'error' => 'Không lấy được cước cho tuyến này.'];
                continue;
            }
            foreach ($quotes as $q) {
                $out[] = [
                    'carrier_account_id' => $account->id, 'carrier' => $account->carrier, 'carrier_name' => $connector->displayName(),
                    'account_name' => $account->name, 'service_name' => $q['name'] ?? null,
                    'fee' => (int) ($q['fee'] ?? 0), 'insurance_fee' => (int) ($q['insurance_fee'] ?? 0), 'eta' => $q['eta'] ?? null,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('shipment.quote_all_failed', ['tenant' => $tenantId, 'carrier_account_id' => $account->id, 'error' => $e->getMessage()]);
            $out[] = ['carrier_account_id' => $account->id, 'carrier' => $account->carrier, 'carrier_name' => $connector->displayName(), 'account_name' => $account->name, 'error' => 'Không lấy được cước cho tuyến này.'];
        }
    }
    return $out;
}
```
(Mã minh hoạ cho bản thiết kế — kế hoạch triển khai sẽ viết đúng field/type thật của `CarrierAccount`, `CarrierRegistry`.)

**Route + Controller:** `POST /api/v1/fulfillment/quote-all` → `ShipmentController::quoteAll()`, validate `recipient.province` required + `recipient.district`/`recipient.ward` (1 trong 2, giống validate của `quote()` cũ), quyền giống `quote()` cũ (`orders.create` hoặc `fulfillment.ship`). Trả `{ data: QuoteAllItem[] }`.

**Xoá dead code (endpoint cũ, 0 call site):** route `POST fulfillment/quote`, `ShipmentController::quote()`, `ShipmentService::quoteShippingFee()`, hook FE `useShippingQuote()` + type `ShippingQuote` (`lib/fulfillment.tsx`).

### 3.2 Frontend

**Hook mới** `useShippingQuoteAll()` (`lib/fulfillment.tsx`, mutation) — `POST /fulfillment/quote-all { recipient }` → `QuoteAllItem[]`.

**`CreateOrderPage.tsx`:** nút "Tra cứu cước vận chuyển" (icon `CalculatorOutlined`) cạnh ô "Phí vận chuyển", điều kiện hiện = địa chỉ nhận hợp lệ (tái dùng biến `ok` đang tính cho `AddressPicker` status). Bấm → mở `ShippingQuoteModal` (component mới, `components/ShippingQuoteModal.tsx`, theo mẫu `OrderDetailModal.tsx`) với prop `recipient` hiện tại; modal tự gọi `useShippingQuoteAll()` khi mở (`open && ...`), hiện `Skeleton` lúc chờ, sau đó bảng: **Cột** ĐVVC (logo qua `ChannelLogo`/tương tự cho carrier) + tên tài khoản, Tên gói (chỉ hiện nếu `service_name` khác null — VTP), Cước, Phí khai giá, Thời gian giao (nếu có). Dòng có `error` hiện text đỏ nhạt thay vì số. Không có action nào khác ngoài đóng modal.

## 4. Edge case

- Tenant không có tài khoản ĐVVC active nào → modal hiện trạng thái rỗng "Chưa có tài khoản ĐVVC nào".
- Carrier không hỗ trợ `quote` (vd carrier thủ công/tự giao) → bị lọc bỏ hoàn toàn khỏi kết quả (không hiện dòng lỗi), vì đây là carrier không có khái niệm "tính phí online".
- Địa chỉ nhận đổi liên tục trong lúc modal đang mở → không tự fetch lại (fetch 1 lần lúc mở modal); đóng mở lại nút mới fetch lại theo địa chỉ mới nhất.
- VTP trả nhiều gói cho 1 tài khoản → mỗi gói 1 dòng riêng, cùng ghi tên tài khoản VTP đó ở cột đầu (rowSpan hoặc lặp lại tên — quyết định UI cụ thể ở bước viết kế hoạch).

## 5. Testing

- Backend (Unit, giống `ViettelPostConnectorDeliveryOptionsTest`): `GhnConnectorQuoteTest` — mock `GhnClient`/HTTP fake, kiểm `service_type_id` suy đúng theo `goods_type`, payload dùng `meta.defaults.package`, trả đúng shape `[{carrier, fee, insurance_fee, name:null}]`; thiếu địa chỉ → `[]`.
- Backend (Feature): `ShipmentQuoteAllTest` — tạo 2-3 carrier_account (GHN/GHTK/VTP) active + 1 inactive (bị loại), gọi `/fulfillment/quote-all`, kiểm số dòng trả về đúng (VTP nhiều dòng), tài khoản lỗi có field `error`, tenant khác không lọt vào.
- Frontend: không có JS test runner (theo `test-verify-baseline`) — verify thủ công: nhập địa chỉ nhận đủ → nút hiện; bấm → modal hiện đúng số dòng theo số tài khoản ĐVVC dev; tắt 1 tài khoản → biến mất khỏi modal.
