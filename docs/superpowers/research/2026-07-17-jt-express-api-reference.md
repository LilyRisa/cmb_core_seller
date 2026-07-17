# Tài liệu tham khảo: J&T Express VN Open API (chuẩn bị tích hợp làm ĐVVC)

- **Ngày:** 2026-07-17 · **Tác giả:** lilyrisa
- **Nguồn:** `https://open.jtexpress.vn/apiDoc/index` (trang SPA render JS, đã crawl bằng trình duyệt thật, bản tiếng Anh — trang có 3 ngôn ngữ: 中文/English/Tiếng Việt, chọn English cho đồng nhất field). Đã đối chiếu cả bản gốc tiếng Trung ở vài chỗ dịch thiếu.
- **Phạm vi:** liệt kê **đầy đủ, nguyên trạng** mọi endpoint public documented trên site — dùng làm nguồn duy nhất khi viết `Integrations/Carriers/JtExpress` connector sau này. Đây là tài liệu tra cứu, **không phải spec thiết kế** — spec sẽ viết riêng ở `docs/specs/00XX-jt-express-carrier-integration.md` theo mẫu SPEC-0034 (Viettel Post) khi bắt tay code, tham khảo `01-architecture/extensibility-rules.md`.
- Interface `CarrierConnector` (`app/app/Integrations/Carriers/Contracts/CarrierConnector.php`) đã có sẵn comment ví dụ mã carrier `'jt'` — tức đã được tính trước trong kiến trúc.

---

## 0. Tóm tắt nhanh (TL;DR)

J&T VN Open API gồm **7 endpoint nghiệp vụ** (REST, `application/x-www-form-urlencoded`, JSON qua field `bizContent`) chia 5 nhóm menu:

| Nhóm | API | Method path | Khớp `CarrierConnector` |
|---|---|---|---|
| Order Service | New Order | `POST /api/order/addOrder` | `createShipment()` |
| Order Service | Cancel Order | `POST /api/order/cancelOrder` | `cancel()` |
| Get Delivery Quotes | Get Delivery Quotes | `POST /api/spmComCost/getComCost` | `quote()` |
| Waybill Service | Print Waybill (1 đơn) | `POST /api/order/printOrder` | `getLabel()` |
| Waybill Service | Batch printing (≤200 đơn) | `POST /api/print/printOrders` | `getLabel()` bulk / tiện ích riêng |
| Tracking | Tracking Query (kéo, ≤30 mã/lần) | `POST /api/logistics/trace` | `getTracking()` |
| Tracking | Webhook (đẩy, do J&T cấu hình theo URL mình cung cấp) | J&T POST tới URL của mình | `parseWebhook()` |

**Không có** endpoint "list services" riêng — danh sách dịch vụ suy ra từ enum `productType` (`EXPRESS`/`FAST`/`SUPER`) cố định, không phải tra cứu động → `services()` sẽ hard-code 3 giá trị này (không phạm luật "core biết tên carrier" vì đây là dữ liệu của chính connector J&T).

**Điểm đáng chú ý nhất cho sau này** — J&T hỗ trợ **2 hệ địa chỉ song song** giống hệt gotcha đã gặp với GHN (`ghn-no-2level-address-district-required`):
- `selfAddress=0` (mặc định): dùng danh mục địa chỉ **riêng của J&T** (3 cấp: `prov`/`city`/`area`, `area` có hậu tố mã nội bộ vd `"Phường Nguyễn Thái Bình-028QQ107"`).
- `selfAddress=1`: dùng **danh mục hành chính quốc gia mới** (2 cấp, theo `https://danhmuchanhchinh.gso.gov.vn/`) — field `city` lúc này **không cần điền** ("新地址无需填该字段" — bản Trung ghi rõ, bản Anh dịch sót).

→ Khi viết `JtAddressResolver` sau này, nhớ tái dùng cùng nguồn dữ liệu/khái niệm crosswalk đã làm cho GHN thay vì tự chế lại.

---

## 1. Xác thực & quy ước chung

### 1.1 Hai lớp xác thực khác nhau — đừng nhầm

1. **Header ký số (cấp ứng dụng / App Management)** — bắt buộc ở **mọi** request:
   - `apiAccount` (Number) — lấy trong App Management trên Console J&T.
   - `timestamp` (Number) — mili-giây, **timezone UTC+7**.
   - `digest` (String) — chữ ký: `digest = base64( md5(JSON(bizContent) + privateKey) )`.
     Quy trình đúng theo tài liệu: MD5 trước ra **mảng byte**, rồi base64-encode mảng byte đó (không phải base64 của hex string).
     Ví dụ minh họa trong doc: `MD5({"customerCode"："024E000014","key":"Z354nbj1"})` — tức chuỗi để hash là JSON của `bizContent` **nối thêm** `privateKey` (cách nối chính xác — có thể là field `key` chèn vào cuối JSON hay đơn giản là `json + privateKey` dạng string — **cần thử nghiệm thực tế ở UAT để chốt**, tài liệu không cho ví dụ code mẫu đầy đủ).
   - `privateKey` — cặp với `apiAccount`, cũng lấy ở Console, **không** gửi trong request (chỉ dùng để tính `digest`).
2. **Business login (cấp merchant, nằm trong `bizContent`)** — bắt buộc ở hầu hết business params:
   - `customerCode` (String(30)) — "Customer ID", do IT/Sales J&T VN cấp (không tự đăng ký).
   - `password` (String) — đi kèm `customerCode`. Trong các response mẫu password xuất hiện dưới dạng chuỗi mã hoá khác nhau (`"123456"` ở 1 ví dụ, `"AF798EA591C460FC633D4567EC88E3FB"` ở ví dụ khác, `"FbuEF5bDUc65+TN0HxnO+g=="` ở ví dụ Tracking Query) → **nghi vấn password cần được hash/encrypt trước khi gửi**, cách encode cụ thể **không được tài liệu hoá rõ** — cần hỏi support J&T hoặc dò qua Postman/SDK mẫu khi làm connector thật.

### 1.2 Request/response envelope chung

- Request: `POST`, `Content-Type: application/x-www-form-urlencoded`, body gồm 3 field cấp ngoài `apiAccount`, `digest`, `timestamp` + 1 field `bizContent` (String) = JSON.stringify của business params.
- Response luôn: `{"code": "1"|other, "msg": "success"|error text, "data": {...}}`. `code = "1"` → thành công; khác `"1"` → thất bại, đọc `msg`.
- Response type: JSON.

### 1.3 Môi trường & tài khoản test (UAT)

| | UAT (sandbox) | Production |
|---|---|---|
| Base URL | `https://demoopenapi.jtexpress.vn/webopenplatformapi` | `https://ylopenapi.jtexpress.vn/webopenplatformapi` |
| `apiAccount` mẫu | `669375073659916329` | (cấp riêng khi ký hợp đồng) |
| `privateKey` mẫu | `6e93e0d4344e47f0a4af7e4e75af955e` | (cấp riêng) |

Toàn bộ 7 endpoint dùng chung 1 cặp `apiAccount`/`privateKey` UAT ở trên (test app), nhưng `customerCode`/`password` (business login) khác nhau tùy ví dụ từng trang — có vẻ là seed data test khác nhau, không phải 1 tài khoản cố định.

### 1.4 Đối tác cần liên hệ để lên production

Theo "Process" ở trang Introduction: (1) đăng ký user → (2) đăng ký làm developer → (3) xác thực developer (đủ quyền order/e-waybill) → (4) liên hệ kỹ thuật J&T để test UAT → (5) thông báo J&T trước khi lên production. Tức là **không self-service hoàn toàn** — vẫn cần liên hệ người của J&T để cấp `customerCode`/`password` merchant thật + apiAccount production, giống quy trình VTP/GHN.

---

## 2. Order Service

### 2.1 New Order — `POST /api/order/addOrder`

Tạo vận đơn. UAT: `https://demoopenapi.jtexpress.vn/webopenplatformapi/api/order/addOrder`.

**Business Parameters (`bizContent`):**

| Field | Kiểu | Bắt buộc | Mô tả |
|---|---|---|---|
| `customerCode` | String(30) | Y | Customer ID (J&T cấp) |
| `password` | String | Y | Mật khẩu tài khoản merchant |
| `txlogisticId` | String | Y | Mã đơn của mình — **không được trùng** (idempotency key) |
| `orderType` | int | Y | `1`=Normal order, `2`=Return order (mặc định 1) |
| `selfAddress` | int | N | `0`=dùng địa chỉ J&T cung cấp, `1`=dùng địa chỉ hành chính quốc gia mới (xem §0) |
| `serviceType` | int | Y | `1`=Pickup (J&T tới lấy), `6`=Drop off (mình mang tới bưu cục) |
| `payType` | String | Y | `PP_PM`=Trả sau theo kỳ (Monthly Statement), `PP_CASH`=Trả trước (Prepaid), `CC_CASH`=Thu hộ COD |
| `productType` | String | Y | `EXPRESS` \| `FAST` \| `SUPER` |
| `goodsType` | String | Y | Loại hàng: `bm000001`=Document(tài liệu), `bm000010`=Goods(hàng hóa), `bm000011`=Fresh(hàng tươi sống) |
| `partSign` | String(1) | N | `1`=Partial Signed (ký nhận 1 phần), `0`=Signed |
| `deliveryType` | int | Y | `1`=Normal delivery, `2`=Self Pickup (khách tự đến lấy) |
| `isExchange` | string | N | `1`=cần dịch vụ đổi/trả hàng, `0`=không cần (mặc định) — *(chỉ có bản Trung, bản Anh chưa dịch)* |
| `isCallBeforeReturn` | int(4) | N | `0`=không bật, `1`=gọi điện xác nhận người gửi trước khi hoàn hàng sau khi phát thất bại — *(chỉ có bản Trung)* |
| `stayWarehouseDays` | int(4) | N | Số ngày lưu kho thêm trước khi hoàn về người gửi, tối đa 7 ngày — *(chỉ có bản Trung)* |
| `sender` | Object | Y | Xem §2.1.1 |
| `receiver` | Object | Y | Xem §2.1.1 |
| `courierReceipt` | Object | N | Thông tin nhận "hồi báo/biên nhận hoàn" (COD hoặc chứng từ trả về) — xem §2.1.1, *(chỉ có bản Trung)* |
| `items` | Array | N | Danh sách sản phẩm — xem §2.1.2 |
| `packageInfo` | Object | Y | Kích thước/khối lượng kiện hàng — xem §2.1.3 |
| `sendStartTime` / `sendEndTime` | String | N | Khung giờ lấy hàng mong muốn, `yyyy-MM-dd HH:mm:ss` 24h |
| `isInsured` | int | Y | `0`/`1` — có mua bảo hiểm hàng hóa hay không |
| `goodsValue` | double | Y | Giá trị hàng, `0 ≤ giá trị ≤ 30.000.000 VND` (mặc định VND) — dùng tính phí bảo hiểm khi `isInsured=1` |
| `codMoney` | String(20) | N | Số tiền thu hộ COD, mặc định VND |
| `transportType` | String(20) | N | Loại hình vận chuyển (Express Type) — giá trị cụ thể chưa liệt kê trên trang |
| `remark` | String(200) | N | Ghi chú |

**2.1.1 `sender` / `receiver` (Object, mỗi field bắt buộc trừ khi ghi chú):**

| Field | Kiểu | Bắt buộc | Mô tả |
|---|---|---|---|
| `name` | String(40) | Y | Tên |
| `mobile` | String(30) | Y | SĐT |
| `prov` | String(40) | Y | Tỉnh/Thành |
| `city` | String(40) | N | Quận/Huyện — **bỏ trống nếu dùng địa chỉ mới (2 cấp)** |
| `area` | String(40) | Y | Phường/Xã (kèm mã nội bộ J&T, vd `"Phường Nguyễn Thái Bình-028QQ107"`) |
| `address` | String(250) | Y | Địa chỉ chi tiết |

`courierReceipt` (回单收件信息对象 — đối tượng thông tin người nhận biên nhận hoàn) có cùng cấu trúc field như trên (`name`/`mobile`/`prov`/`city`(N)/`area`/`address`), chỉ khác optional ở cấp cha (`N`).

**2.1.2 `items[]` (Array, N ở cấp cha nhưng mỗi item có field bắt buộc riêng):**

| Field | Kiểu | Bắt buộc | Mô tả |
|---|---|---|---|
| `itemName` | String(500) | Y | Tên hàng |
| `englishName` | String(100) | Y | Tên tiếng Anh |
| `number` | String(20) | Y | Số lượng |
| `itemValue` | String(10) | Y | Đơn giá (mặc định VND) |
| *(weight — ghi chú lẫn trong bảng)* | String(80) | N | Tổng khối lượng, mặc định Kg, `0.01kg ≤ weight ≤ 999.00kg` |

**2.1.3 `packageInfo` (Object, Y):**

| Field | Kiểu | Bắt buộc | Mô tả |
|---|---|---|---|
| `weight` | String(80) | Y | Khối lượng, `0.01kg ≤ weight ≤ 999.00kg` |
| `height` | String(20) | N | cm, `1.00 ≤ height ≤ 180.00` |
| `length` | String(20) | N | cm, `1.00 ≤ length ≤ 320.00` |
| `width` | String(20) | N | cm, `1.00 ≤ width ≤ 100.00` |
| `volume` | String(20) | N | Khối lượng quy đổi = `length × height × width / 6000`, làm tròn 2 số thập phân (làm tròn lên) |

**Response `data`:**

| Field | Kiểu | Mô tả |
|---|---|---|
| `txlogisticId` | String(20) | Mã đơn của mình (echo lại) |
| `billCode` | String(20) | **Mã vận đơn J&T cấp** (tracking_no) |
| `sortLine` | String(20) | Mã tuyến phân loại nội bộ (3-segment code) |
| `inquiryFee` | BigDecimal | Phí COD, `0 ≤ x ≤ 30.000.000 VND` |
| `codFee` | BigDecimal | Phí bảo hiểm |
| `insuranceFee` | BigDecimal | Tổng phí |

*(Chú ý: tên field response `inquiryFee`/`codFee`/`insuranceFee` bị lệch với mô tả — theo mô tả gốc thì thứ tự thực tế nên đọc là: field 1 = "COD fee", field 2 = "Insured payment", field 3 = "Total fee"; tài liệu UI hiển thị mô tả canh lệch dòng khi crawl. **Cần verify bằng response JSON thật ở UAT**, đừng tin tên field suông.)*

**Request Example (nguyên văn từ doc):**
```json
{
  "customerCode": "024E000014",
  "txlogisticId": "12345644555553389101",
  "productType": "EXPRESS",
  "orderType": "1",
  "serviceType": "1",
  "deliveryType": "1",
  "selfAddress": 0,
  "sender": {
    "name": "zhan",
    "mobile": "02323232323",
    "prov": "Hồ Chí Minh",
    "city": "Quận 1",
    "area": "Phường Nguyễn Thái Bình-028QQ107",
    "address": "test"
  },
  "receiver": {
    "name": "ffff",
    "mobile": "0378456963",
    "prov": "Hồ Chí Minh",
    "city": "Quận 1",
    "area": "Phường Nguyễn Thái Bình-028QQ107",
    "address": "test"
  },
  "payType": "PP_CASH",
  "goodsType": "bm000010",
  "goodsValue": "20000",
  "codMoney": "20000",
  "remark": "123",
  "password": "AF798EA591C460FC633D4567EC88E3FB",
  "packageInfo": {
    "weight": "3",
    "length": 10,
    "width": 10,
    "height": 10,
    "volume": "10"
  },
  "itemsValue": "1000",
  "totalQuantity": 1,
  "items": [
    {"itemName": "goodsName-test", "englishName": "Test", "number": "1", "itemValue": 20}
  ]
}
```
*(Lưu ý: ví dụ thực tế có thêm 2 field `itemsValue`, `totalQuantity` không xuất hiện trong bảng field mô tả — trang doc thiếu sót, giữ nguyên để tham khảo khi test thật.)*

**Respond Example:**
```json
{
  "code": "1",
  "msg": "success",
  "data": {
    "txlogisticId": "123456789101",
    "billCode": "802400616352",
    "sortLine": "800-028A04-",
    "inquiryFee": 15,
    "codFee": 0,
    "insuranceFee": 0
  }
}
```

---

### 2.2 Cancel Order — `POST /api/order/cancelOrder`

"Dùng để hủy đơn **trước khi lấy hàng** (before pickup)". UAT: `.../api/order/cancelOrder`.

**Business Parameters:**

| Field | Kiểu | Bắt buộc | Mô tả |
|---|---|---|---|
| `customerCode` | String(30) | Y | |
| `password` | String | Y | |
| `txlogisticId` | String | Y | Mã đơn của mình |
| `billCode` | String | N | Mã vận đơn J&T (tracking no.) |
| `reason` | String | Y | Lý do hủy |

**Response `data`:** `txlogisticId`, `billCode`.

```json
// Request
{"customerCode":"084LC00010","password":"123456","txlogisticId":"JTVN991344789470","reason":"测试取消接口"}
// Response
{"code":"1","msg":"success","data":{"txlogisticId":"JTVN991344789470","billCode":"JTVN991344789470"}}
```

Không có ràng buộc trạng thái nào ghi rõ trên trang doc (khác VTP có `ORDER_STATUS < 200`) — nhưng mô tả API chỉ nói "trước khi lấy hàng", nên **có khả năng** hủy đơn đã pickup sẽ trả lỗi `999010010 order status can not be cancel` (xem bảng lỗi §5) — cần verify UAT.

---

## 3. Get Delivery Quotes — `POST /api/spmComCost/getComCost`

"Hỗ trợ khách hàng ước tính phí vận chuyển trước khi đặt đơn." UAT: `.../api/spmComCost/getComCost`.

**Business Parameters:**

| Field | Kiểu | Bắt buộc | Mô tả |
|---|---|---|---|
| `customerCode` | String(30) | Y | |
| `password` | String | Y | |
| `weight` | BigDecimal | Y | Khối lượng, đơn vị Kg |
| `selfAddress` | int | N | Giống New Order §0 |
| `isInsured` | int | Y | 0/1 |
| `goodsValue` | double | Y | `0 ≤ x ≤ 30.000.000 VND` |
| `codMoney` | String(20) | N | |
| `length`/`width`/`height` | String(20) | N | Cùng giới hạn như packageInfo §2.1.3 |
| `goodsType` | String | Y | Cùng enum `bm0000xx` |
| `productType` | String | Y | Mặc định `EXPRESS` — *(nhưng ví dụ thực tế lại gửi `"productType":"1"` — không khớp enum EXPRESS/FAST/SUPER đã công bố, khả năng trang có lỗi tài liệu, cần verify)* |
| `sender` | Object | Y | Chỉ cần `prov`/`city`(N)/`area`, KHÔNG cần `name`/`mobile`/`address` |
| `receiver` | Object | Y | Tương tự `sender` |

**Response `data`:** field 1 = "Shipping Revenue" (String(40)), field 2 = "COD fee" (String(20)), field 3 = "Insurance Fee" (String(20)) — theo response example đặt tên `price`/`codFee`/`insuranceFee`.

```json
// Request
{
  "customerCode":"LC00001113","password":"123456","weight":10,
  "productType":"1","goodsType":"bm000010","goodsValue":500,"codMoney":500,
  "sender":{"prov":"Hồ Chí Minh","city":"Quận 1","area":"Phường Bến Nghé-028QQ101"},
  "receiver":{"prov":"Hà Nội","city":"Huyện Mê Linh","area":"Thị trấn Chi Đông-024HML01"}
}
// Response
{"code":"1","msg":"success","data":{"price":100000,"codFee":0,"insuranceFee":5}}
```

---

## 4. Waybill Service

### 4.1 Print Waybill (1 đơn) — `POST /api/order/printOrder`

"Lấy thông tin mẫu tem điện tử theo mã đơn của khách hàng." UAT: `.../api/order/printOrder`.

**Business Parameters:** `customerCode` (Y), `password` (Y), `txlogisticId` (Y, mã đơn của mình).

**Response `data`:** `txlogisticId`, `billCode`, và field thứ 3 (String, Y) = **nội dung tem dạng base64** ("The content in base64") — tên field không hiện rõ trong bảng nhưng response example gọi là `base64EncodeContent`.

```json
// Request
{"customerCode":"LC00001113","password":"123456","txlogisticId":"88444113455554442433771"}
// Response
{"code":"1","msg":"success","data":{
  "txlogisticId":"88444113455554442433771",
  "billCode":"871000023848",
  "base64EncodeContent":"base64文件内容"
}}
```
→ Không nói rõ định dạng file bên trong base64 (PDF hay PNG/HTML) — cần giải mã thử ở UAT (khả năng cao là PDF theo khổ tem chuẩn, tương tự các connector khác trong hệ thống dùng Gotenberg nếu ra HTML).

### 4.2 Batch printing of labels (≤200 đơn) — `POST /api/print/printOrders`

"Lấy thông tin mẫu tem điện tử theo lô, bằng customerCode." UAT: `.../api/print/printOrders`.

**Business Parameters:** `customerCode` (Y), `password` (Y), `txlogisticIds` (List\<String\>, Y, **tối đa 200 mã đơn**).

**Response `data`:** String — **link PDF** tổng hợp tất cả tem (không phải base64 như bản đơn lẻ), có `Expires`/`Signature` kiểu presigned URL (OSS/S3-style, TTL ngắn) → phải tải ngay, không cache link lâu dài (giống cách hệ thống hiện xử lý link tạm của các sàn khác).

```json
// Response
{"code":"1","msg":"success","data":"https://uat-jmsvn-file.jtexpress.vn/osb1del/.../xxx.pdf?Expires=...&OSSAccessKeyId=...&Signature=...&response-content-type=%2A%2F%2A"}
```

---

## 5. Tracking

### 5.1 Tracking Query (kéo, polling) — `POST /api/logistics/trace`

"Tra cứu thông tin hành trình đơn theo mã vận đơn." UAT: `.../api/logistics/trace`.

**Business Parameters:** `customerCode` (Y), `password` (Y), `txlogisticId` (Y, có thể để rỗng theo ví dụ), `billcodes` (String(500), Y — **nhiều mã cách nhau bằng dấu phẩy, tối đa 30 mã/lần**).

**Response `data`:** mảng, mỗi phần tử `{billCode, details: [...]}`. Mỗi `detail`:

| Field | Kiểu | Mô tả |
|---|---|---|
| `scanTime` | String | Thời điểm quét |
| `desc` | String | Mô tả hành trình (template có chèn tên trạm/nhân viên) |
| `scanTypeCode` | int | Mã loại quét — xem bảng §5.3 |
| `scanTypeName` | String | Tên loại quét |
| `scanNetworkName`/`scanNetworkId` | String | Tên/ID bưu cục xử lý |
| `staffName` | String | Tên nhân viên giao/nhận |
| `staffContact` | String (N) | SĐT nhân viên |
| `scanNetworkContact` | String (N) | SĐT bưu cục |
| `scanNetworkProvince`/`scanNetworkCity`/`scanNetworkArea` | String | Vị trí bưu cục |
| `pictureUrl` | Array (N) | Ảnh chụp tại điểm quét (POD...) |

### 5.2 Webhook (đẩy, do J&T cấu hình sẵn theo URL mình cung cấp)

**Không có endpoint cố định** — "URL: Developer provide for J&T set up", tức mình gửi URL webhook cho J&T rồi họ cấu hình thủ công ở phía họ (không self-service qua Console như GHN/VTP) — **cần liên hệ support J&T để đăng ký URL webhook**, đây là điểm khác biệt quan trọng so với GHN/VTP.

J&T sẽ `POST` tới URL đó với cùng khuôn `apiAccount`/`digest`/`timestamp` header + `bizContent`, business params:

| Field | Kiểu | Bắt buộc | Mô tả |
|---|---|---|---|
| `billCode` | String(30) | Y | Mã vận đơn |
| `txlogisticId` | String | Y | Mã đơn của mình |
| `details` | Object\|Array | Y | 1 hoặc nhiều event — cùng cấu trúc §5.1 detail, thêm field `weight` (khối lượng thực đo, N), `scanByCode`/`scanByContact`/`scanByName` (người thao tác quét), `scanNetworkCode` |

**App phải trả về đúng khuôn `{"code":"1","msg":"success","data":null}` để ack** — giống nguyên tắc "webhook phải ack 200/code=1" đã áp dụng cho Zalo (`zalo-webhook-must-ack-200`) và các webhook khác trong hệ thống; nếu không J&T nhiều khả năng sẽ retry/disable.

```json
// J&T → mình (ví dụ)
{
  "billCode": "801100195141",
  "details": [{
    "scanByCode": "0848881999", "scanByContact": "15225714573", "scanByName": "小钱",
    "scanNetworkArea": "Phường Nguyễn Thái Bình-028QQ107", "scanNetworkCity": "Quận 1",
    "scanNetworkCode": "084888", "scanNetworkContact": "000101010", "scanNetworkName": "TEST-888",
    "scanNetworkProvince": "Hồ Chí Minh", "scanTime": "2024-06-21T13:23:56",
    "scanTypeCode": 10, "scanTypeName": "快件揽收",
    "waybillId": "801100195141", "weight": 666.66
  }]
}
// Mình → J&T (bắt buộc trả để ack)
{"code":"1","msg":"success","data":null}
```

### 5.3 Bảng mã trạng thái (`scanTypeCode`) — tổng hợp

Trang Webhook liệt kê **đầy đủ nhất** (Tracking Query thiếu 103/104/105/121):

| Code | Tên (EN) | Diễn giải |
|---|---|---|
| 103 | Order Placed | Đã tạo đơn |
| 104 | Pickup Failure | Lấy hàng thất bại |
| 105 | Cancel Order | Đã hủy đơn |
| 106 | Picked Up | Đã lấy hàng (Package collection) |
| 109 | Departure | Rời kho (Sending scan) |
| 110 | Arrival | Đến kho (Scan to) |
| 112 | On Delivery | Đang giao (Delivery scan) |
| 113 | Delivered | Đã giao (Package delivery) |
| 116 | Returning | Xác nhận hoàn (Return Confirmation) |
| 117 | Returned Sign | Đã ký nhận hoàn (Return Sign) |
| 118 | Delivery Problem | Sự cố khi giao |
| 120 | Return Problem | Sự cố khi hoàn |
| 121 | FINISH | Kết thúc hành trình |

**⚠️ Cảnh báo dữ liệu thực tế lệch với bảng công bố:** response mẫu thật của Tracking Query (§5.1) dùng các `scanTypeCode` **không nằm trong bảng trên**: `10` ("Nhận hàng"/Ký nhận), `50` ("Gửi hàng"), `92` ("Hàng đến"), `94` ("Quét phát hàng"), `100` ("Ký nhận" — trùng tên với 10 nhưng khác code). Tức J&T có **một bộ scan-code nội bộ chi tiết hơn nhiều** so với bộ 103–121 "tóm tắt" ở trang doc — rất giống việc GHN/VTP có status code nội bộ dày hơn bảng public. **Khi viết `JtStatusMap`, đừng chỉ dựa bảng 103–121 — phải log toàn bộ `scanTypeCode` gặp thực tế ở UAT rồi bổ sung dần**, và ưu tiên field `scanTypeName` (text) làm fallback khi gặp code lạ.

---

## 6. Error Code — bảng đầy đủ trên trang

| Code | Ý nghĩa |
|---|---|
| 1 | success |
| 145003052 | digest is empty! |
| 145003051 | apiAccount is empty! |
| 145003053 | timestamp is empty! |
| 145003010 | API account does not exist |
| 145003050 | Illegal parameters |
| 145003030 | headers signature verification failed |
| 145003012 | API account has no interface permissions |
| 999011010 | customer not exist |
| 999011020 | customer is disable |
| 999011030 | customer is locked |
| 999001030 | customerCode or password is wrong |
| 145005004 | This delivery address is temporarily suspended due to objective reasons |
| 999009010 | No corresponding origin information was found (không tìm thấy tỉnh/thành gửi) |
| 999009020 | No corresponding destination information was found (không tìm thấy tỉnh/thành nhận) |
| 1450033315 | Please fill in the correct sending/receiving district |
| 145003204 | Duplicate order please update customer order number |
| 145005005 | billcode acquisition failed |
| 145003100 | Illegal waybill number |
| 145003502 | The quantity of the waybill number exceeds 30 (giới hạn Tracking Query) |
| 999010010 | order status can not be cancel |
| 999002000 | Data not found |
| 999001010 | Input parameter error, please see msg for details |

---

## 7. Enum tổng hợp (tiện tra cứu nhanh khi code)

- **`orderType`**: `1`=Normal, `2`=Return
- **`selfAddress`**: `0`=địa chỉ J&T, `1`=địa chỉ hành chính quốc gia mới (GSO)
- **`serviceType`**: `1`=Pickup, `6`=Drop off
- **`payType`**: `PP_PM`=trả sau theo kỳ, `PP_CASH`=trả trước, `CC_CASH`=COD
- **`productType`**: `EXPRESS` | `FAST` | `SUPER`
- **`goodsType`**: `bm000001`=Document, `bm000010`=Goods, `bm000011`=Fresh
- **`partSign`**: `1`=ký nhận 1 phần, `0`=ký đủ
- **`deliveryType`**: `1`=Normal, `2`=Self Pickup
- **`isExchange`**: `1`=cần đổi/trả, `0`=không (mặc định)
- **`isCallBeforeReturn`**: `0`=tắt, `1`=gọi xác nhận trước khi hoàn
- **`isInsured`**: `0`/`1`

---

## 8. Gợi ý ánh xạ vào kiến trúc hiện tại (chỉ để tham khảo, KHÔNG phải quyết định thiết kế cuối)

- `code: 'jt'` trong `CarrierRegistry` (đã có sẵn ví dụ comment trong `CarrierConnector.php`).
- `createShipment()` → `addOrder`; lưu `tracking_no = billCode`, `fee` có thể cần cộng `inquiryFee + codFee + insuranceFee` (cần verify tên field thật ở §2.1 trước).
- `cancel()` → `cancelOrder`; cần field `reason` bắt buộc — connector phải tự sinh lý do mặc định (vd "Khách hủy đơn") nếu UI chưa có ô nhập lý do, giống cách VTP/GHTK xử lý field bắt buộc tương tự.
- `getLabel()` → ưu tiên `printOrder` (base64, 1 đơn/lần, khớp interface hiện tại trả `{filename,mime,bytes}`); `printOrders` (batch, trả link PDF presigned) hợp hơn cho tính năng "in hàng loạt" nếu module Fulfillment có riêng luồng bulk (xem `fulfillment-prepare-sync-blocking-bottleneck` — nên làm async).
- `getTracking()` → `logistics/trace`, giới hạn 30 mã/lần → cần batch theo lô khi polling nhiều đơn (tương tự `SyncShipmentTracking` đã carrier-agnostic — xem `sync-shipment-tracking-missing-tenant-context`).
- `parseWebhook()` → J&T **không tự cấu hình được qua Console**, phải gửi URL cho support J&T đăng ký thủ công — cần note vào bước "thêm tài khoản" cho người dùng biết đây là bước ngoài app (khác GHN có thể tự nhập webhook URL, giống VTP dùng `webhook_secret` nhưng ở đây J&T không thấy cơ chế secret/verify header nào công bố công khai — **cần hỏi support về cách verify tính xác thực webhook** trước khi launch, tránh lỗ hổng giả mạo webhook).
- `quote()` → `spmComCost/getComCost`.
- `services()` → hard-code `['EXPRESS','FAST','SUPER']`, không có API động.
- `verifyCredentials()` → chưa có endpoint "whoami"/"ping" riêng; tạm dùng `getComCost` với payload tối thiểu (hoặc 1 call nhẹ tương tự) làm phép thử xác thực `customerCode`/`password`, đọc `code`/`msg` để suy ra `invalid_credentials`.

---

## 9. Câu hỏi mở / cần làm rõ với J&T trước khi code thật

1. Cách encode chính xác field `password` trong `bizContent` (plaintext / MD5 / MD5+base64 theo `privateKey`?) — ví dụ trong doc không nhất quán.
2. Công thức `digest` chính xác: `privateKey` nối vào JSON ở đâu (cuối chuỗi? field riêng?) — cần thử trực tiếp ở UAT với tool ký mẫu nếu J&T có cung cấp SDK/Postman collection (trang "Support Center" có thể có, chưa crawl — xem thêm nếu cần).
3. Cách đăng ký & xác thực (verify chữ ký / IP whitelist / secret) cho **webhook** — trang doc không đề cập cơ chế bảo mật nào ngoài request/response format.
4. Định dạng file thật trong `base64EncodeContent` của `printOrder` (PDF/PNG/HTML?).
5. Giá trị hợp lệ của field `transportType` (không liệt kê enum).
6. Ràng buộc hủy đơn theo trạng thái (chỉ suy đoán từ error code `999010010`, chưa có bảng trạng thái nào cho phép/không cho phép hủy).
7. Danh mục địa chỉ J&T riêng (`selfAddress=0`) lấy qua API nào để cascading Tỉnh→Quận→Phường trên form — trang doc **không có** endpoint "list provinces/districts" như GHN/VTP có; có thể phải xin riêng hoặc chỉ hỗ trợ `selfAddress=1` (địa chỉ hành chính quốc gia, đã có sẵn trong hệ thống) để né vấn đề này.
