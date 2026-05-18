# Shipping Label Designer — drag/drop template alias cho đơn manual

- **Trạng thái:** Design draft (2026-05-18)
- **Phase:** Phase 7 — mở rộng SPEC 0006 §3.3 (custom print templates — đã note follow-up từ v1) và SPEC 0013 (phiếu giao hàng tự tạo).
- **Module backend chính:** `Fulfillment` (mở rộng) + `Orders` (cột `warehouse_id`).
- **Module FE chính:** `resources/js` (user app), pages mới trong `/settings/shipping-labels`.
- **Liên quan:** `SettingsPrintPage.tsx` (khổ giấy global hiện tại), `PrintService::renderDeliverySlip`, `PrintTemplates::deliverySlip` (giữ làm fallback), `Warehouse` model.

## 1. Vấn đề & mục tiêu

Hiện tại đơn manual khi in phiếu giao hàng dùng đúng một template HTML hard-coded (`PrintTemplates::deliverySlip`) với khổ giấy lấy từ `tenant.settings.print.label_size` (1 khổ duy nhất cho cả shop). Khi shop muốn:
- In tem nhiệt 100×150mm cho đơn nội thành + A6 cho đơn liên tỉnh.
- Dán logo shop / thay đổi vị trí QR / ẩn block COD khi đơn không COD.
- Lưu 2–3 template chuẩn để nhân viên chọn theo ĐVVC.

…đều không làm được — phải sửa code Blade rồi deploy.

**Mục tiêu:**

1. Cho phép owner/admin tạo các **template alias** (đặt tên, đặt làm mặc định) cho phiếu giao hàng đơn manual.
2. Editor visual **drag/drop + resize** trên Konva canvas; toạ độ tính theo **mm** để render PDF chính xác.
3. Hỗ trợ tập field rộng (QR, barcode, text, image, carrier logo/name, sender/recipient/order fields, items list, divider, rectangle) qua **registry pattern** — thêm field type sau này = 3 file mới, zero sửa renderer.
4. Khi in đơn manual, nhân viên **chọn alias** qua picker (nhớ last-used + tenant default + fallback template cũ).
5. **Sender info đồng bộ từ warehouse** đã chọn lúc tạo đơn (`orders.warehouse_id` mới).

## 2. Trong / ngoài phạm vi

**Trong (v0):**

- Bảng mới `shipping_label_templates` (tenant-scoped, soft-delete, JSON schema versioned).
- Cột mới `orders.warehouse_id` (nullable) + UI ManualOrderService chọn kho khi tạo đơn.
- 8 field type v0: `qr`, `barcode`, `text`, `image`, `data`, `items_list`, `divider`, `rectangle`. Trong đó `data` cover 17 dynamic keys (carrier_logo, carrier_name, sender_name/phone/address, recipient_name/phone/address, recipient_address_detail, recipient_address_admin, order_number, tracking_no, cod, weight, print_note, created_at, total_qty).
- `FieldTypeRegistry` BE (PHP) + FE (TS) — đối xứng key.
- `LabelRenderer` + `LabelDataResolver` + `DataContext` — pipeline render độc lập, n+1-free.
- 8 REST endpoint mới + 1 param `template_id` cho `POST /print-jobs`.
- React pages: `SettingsShippingLabelsPage` (list) + `ShippingLabelEditorPage` (Konva editor).
- Tích hợp `TemplateAliasPicker` vào `OrderProcessing.printDelivery` (đơn lẻ + bulk).
- Sample profiles (3 preset) cho preview PDF không cần đơn thật.
- Snapshot test golden HTML render; feature test endpoint + permission.
- Seed 2 template mẫu cho onboarding (`A6 đơn giản`, `100×150mm chuẩn ĐVVC`).

**Ngoài (phase sau):**

- Upload custom carrier logo / template ảnh nền.
- Template cho `type=label/picking/packing/invoice` — v0 chỉ áp `type=delivery`.
- Template chia sẻ giữa các tenant (marketplace template).
- Versioning lịch sử template (rollback).
- Migrate user-facing wizard giữa template cũ → tự xây từ canvas trắng.
- Test runner FE (jest/vitest) — repo hiện không có, không giới thiệu infra mới.

## 3. Kiến trúc tổng thể

```
app/
├── Modules/Fulfillment/
│   ├── Models/ShippingLabelTemplate.php                 (mới)
│   ├── Database/Migrations/
│   │   ├── 2026_05_18_110000_create_shipping_label_templates_table.php
│   │   └── 2026_05_18_110001_add_warehouse_id_to_orders.php
│   ├── Http/
│   │   ├── Controllers/ShippingLabelTemplateController.php
│   │   ├── Requests/StoreShippingLabelTemplateRequest.php
│   │   ├── Requests/UpdateShippingLabelTemplateRequest.php
│   │   └── Resources/ShippingLabelTemplateResource.php
│   ├── Policies/ShippingLabelTemplatePolicy.php
│   ├── Services/
│   │   ├── PrintService.php                              (sửa: route theo template_id)
│   │   └── LabelRendering/
│   │       ├── LabelRenderer.php
│   │       ├── LabelDataResolver.php
│   │       ├── DataContext.php
│   │       ├── FieldTypeRegistry.php
│   │       ├── FieldRenderHelpers.php
│   │       ├── Contracts/FieldType.php
│   │       └── Fields/{Qr,Barcode,Text,Image,Data,ItemsList,Divider,Rectangle}Field.php
│   ├── Database/Seeders/ShippingLabelTemplateSeederSamples.php
│   └── FulfillmentServiceProvider.php                    (register registry singleton)
└── Modules/Orders/Services/ManualOrderService.php       (sửa: validate + persist warehouse_id)

resources/js/
├── pages/
│   ├── SettingsShippingLabelsPage.tsx
│   └── ShippingLabelEditorPage.tsx
├── components/shipping-labels/
│   ├── LabelCanvas.tsx                                   (react-konva Stage+Layer+Transformer)
│   ├── FieldNode.tsx                                     (dispatch theo registry)
│   ├── FieldPalette.tsx
│   ├── FieldInspector.tsx
│   ├── PaperSettings.tsx
│   ├── TemplateAliasPicker.tsx
│   └── fieldTypes/
│       ├── index.ts                                      (registry export)
│       └── {Qr,Barcode,Text,Image,Data,ItemsList,Divider,Rectangle}FieldDef.tsx
├── lib/
│   ├── shippingLabels.tsx                                (react-query hooks)
│   ├── labelEditor/
│   │   ├── editorStore.ts                                (zustand store + history slice)
│   │   ├── coords.ts                                     (mm↔px, snap, clamp)
│   │   └── sampleData.ts                                 (3 sample profiles)
│   └── shippingLabelTypes.ts                             (TS types shared, mirror BE field schema)
└── (sửa) components/OrderProcessing.tsx                  (gắn TemplateAliasPicker vào printDelivery)
└── (sửa) pages/OrdersPage.tsx                            (bulk print → picker)
└── (sửa) pages/CreateOrderPage.tsx                       (chọn warehouse cho đơn)
└── (sửa) app.tsx                                         (route mới /settings/shipping-labels[/...])
```

## 4. Data model

### 4.1 `shipping_label_templates`

```
id              bigint PK
tenant_id       bigint, indexed, FK tenants
name            string(120)
paper           string(16)        -- 'A4'|'A5'|'A6'|'100x150mm'|'80mm'|'custom'
paper_w_mm      smallint unsigned -- denormalized cho mọi paper, bắt buộc
paper_h_mm      smallint unsigned -- 0 nếu khổ cuộn (auto height)
schema_version  unsigned tinyint default 1
schema          json
is_default      boolean default false
created_by      bigint nullable, FK users
created_at, updated_at, deleted_at (soft-delete)

UNIQUE(tenant_id, name) WHERE deleted_at IS NULL    -- enforce ở app layer cho MySQL ≤ 8.0.16
INDEX(tenant_id, is_default)
INDEX(tenant_id, deleted_at)
```

**"1 default / tenant"** enforce trong `ShippingLabelTemplateService::setDefault()` qua DB transaction (clear → set), không partial unique.

### 4.2 `orders.warehouse_id`

```
warehouse_id    bigint nullable, FK warehouses, indexed
```

`ManualOrderService::create($data)` đọc `$data['warehouse_id']` (nếu thiếu → `Warehouse::defaultFor($tenantId)->id`). Đơn cũ chạy migration giữ nguyên `null`; `LabelDataResolver` xử lý null = fallback `Warehouse::defaultFor`.

### 4.3 `print_jobs.meta` — thêm key mềm

Không sửa schema cột. Khi `type=delivery` và request có `template_id`: meta lưu thêm `{ template_id, template_name }`. `template_name` là snapshot để display ở popup download ngay cả khi template bị xoá sau đó.

### 4.4 JSON schema field (version 1)

```ts
type FieldBase = { id: string; x: number; y: number; w: number; h: number; rotation?: number };

type QrField        = FieldBase & { type: 'qr';        source: 'tracking_no'|'order_number'; ecc?: 'L'|'M'|'Q'|'H' };
type BarcodeField   = FieldBase & { type: 'barcode';   source: 'tracking_no'|'order_number'; format?: 'code128'; showText?: boolean };
type TextField      = FieldBase & { type: 'text';      text: string; style: TextStyle };
type ImageField     = FieldBase & { type: 'image';     assetPath: string; fit?: 'contain'|'cover' };
type DataField      = FieldBase & { type: 'data';      key: DataKey; style: TextStyle; prefix?: string; suffix?: string };
type ItemsListField = FieldBase & { type: 'items_list'; style: { fontSize: number; lineHeight?: number }; format?: 'bullet'|'numbered'; maxRows?: number };
type DividerField   = FieldBase & { type: 'divider';   thickness?: number; color?: string };
type RectangleField = FieldBase & { type: 'rectangle'; borderThickness?: number; borderColor?: string; cornerRadius?: number; fillColor?: string };

type TextStyle = {
  fontSize: number;                // 6..48 pt-equivalent (1pt ≈ 0.353mm)
  fontWeight?: 400|600|700;
  align?: 'left'|'center'|'right';
  color?: string;                  // #RRGGBB
};

type DataKey =
  | 'carrier_logo' | 'carrier_name'
  | 'sender_name' | 'sender_phone' | 'sender_address'
  | 'recipient_name' | 'recipient_phone' | 'recipient_address'
  | 'recipient_address_detail' | 'recipient_address_admin'
  | 'order_number' | 'tracking_no'
  | 'cod' | 'weight' | 'print_note' | 'created_at' | 'total_qty';
```

Toạ độ đơn vị **mm**. `id` = nanoid 8 ký tự. Validation BE bắt buộc: `x ≥ 0`, `y ≥ 0`, `x+w ≤ paper_w_mm`, `y+h ≤ paper_h_mm` (trừ khổ 80mm với `paper_h_mm=0` thì không check Y trên).

## 5. Backend — module Fulfillment

### 5.1 `FieldType` contract

```php
interface FieldType
{
    /** Khoá định danh, đối xứng với FE registry. */
    public function key(): string;

    /** Validate + normalize props (gọi bởi Store/Update request). Throws ValidationException. */
    public function validateProps(array $props): array;

    /**
     * Khai báo các DataContext key field này dùng (giúp resolver chỉ load đúng thứ cần).
     * @return array<string>
     */
    public function dataKeys(): array;

    /** Render thành 1 div absolute-position trên trang HTML PDF. */
    public function renderHtml(array $field, DataContext $ctx, FieldRenderHelpers $h): string;
}
```

### 5.2 `LabelRenderer`

```php
public function renderOne(Order $order, ShippingLabelTemplate $tpl): string;     // 1 trang body (chưa shell)
public function renderBatch(Collection $orders, ShippingLabelTemplate $tpl): string; // shell + n trang
```

Foreach field trong `tpl->schema['fields']`:
- Lookup `FieldTypeRegistry::get($type)`; `null` → `continue` (forward-compat).
- Try-catch quanh `renderHtml`; exception → `report($e)`, skip field, không vỡ trang.

`paperRule(ShippingLabelTemplate $tpl)` build CSS `@page { size:{w}mm {h}mm; margin:0 }` (margin 0 — designer toàn quyền padding).

### 5.3 `LabelDataResolver`

```php
public function resolve(Order $order): DataContext;
```

Eager-load duy nhất 1 lần: `order.warehouse`, `order.shipments` (newest first), `order.items`. Trả về `DataContext` chứa mọi field cần render (xem 5.4). `LabelRenderer::renderBatch` resolve cho từng order rồi merge — không query bên trong field type.

### 5.4 `DataContext` (value object, readonly)

Mọi key đều `?string|int` đã format sẵn cho hiển thị. Field type chỉ đọc, không format ngày/tiền/địa chỉ. Tách concern: resolver = "biết DB", field = "biết layout".

### 5.5 `FieldRenderHelpers`

- `positionedBox(array $field, array $extraStyle, string $innerHtml): string` — wrap chuẩn.
- `textStyle(TextStyle $s): array` — convert sang CSS style array.
- `escape(string $s): string` — `htmlspecialchars(ENT_QUOTES, UTF-8)`.
- `qrPng(string $payload, int $w_mm, ErrorCorrectionLevel $ecc): string` — base64 data URL từ `bacon/bacon-qr-code`.
- `barcodePng(string $payload, int $w_mm, int $h_mm, bool $withText): string` — từ `picqer/php-barcode-generator`.
- `carrierLogoImg(?string $carrier, int $w_mm, int $h_mm): string`.
- `carrierFullName(?string $carrier): string` — map giữ giống `PrintTemplates::$carrierMeta` (move sang helper, dùng chung).
- `formatVnd(int $amount): string`, `formatDate(?Carbon $t): string`.

### 5.6 `FieldTypeRegistry`

Singleton register tại `FulfillmentServiceProvider::register()`. Method:
- `register(FieldType $t): void` — throw nếu key đã đăng ký.
- `get(string $key): ?FieldType`.
- `keys(): array<string>` — dùng cho controller validate input `type`.

### 5.7 Endpoints (qua `auth:sanctum` + tenant scope)

| Method | Path | Action | Policy |
|---|---|---|---|
| GET | `/api/v1/shipping-label-templates` | index — mỗi item kèm `is_default`; FE đọc default từ item có `is_default=true` | view (tenant member) |
| POST | `/api/v1/shipping-label-templates` | store | manage (tenant.settings) |
| GET | `/api/v1/shipping-label-templates/{id}` | show | view |
| PUT | `/api/v1/shipping-label-templates/{id}` | update | manage |
| DELETE | `/api/v1/shipping-label-templates/{id}` | destroy (soft) | manage |
| POST | `/api/v1/shipping-label-templates/{id}/set-default` | setDefault | manage |
| POST | `/api/v1/shipping-label-templates/{id}/duplicate` | duplicate | manage |
| POST | `/api/v1/shipping-label-templates/{id}/preview` | preview (template đã save — dùng schema hiện tại của DB) | view (rate-limit 10/phút/user) |
| POST | `/api/v1/shipping-label-templates/preview` | preview-inline (schema gửi trong body — cho editor chưa save) | manage (rate-limit 10/phút/user) |

`POST /api/v1/print-jobs` thêm field optional `template_id`. Chỉ chấp nhận khi `type='delivery'`. Validate template cùng tenant + chưa soft-delete.

### 5.8 `PrintService::renderDeliverySlip` (sửa)

```php
private function renderDeliverySlip(PrintJob $job): array
{
    $templateId = (int) data_get($job->meta, 'template_id') ?: null;
    $orders = $this->loadDeliveryOrders($job);                   // logic cũ tách ra method
    if ($templateId) {
        $tpl = ShippingLabelTemplate::withTrashed()->where('tenant_id', $job->tenant_id)->findOrFail($templateId);
        $html = $this->labelRenderer->renderBatch($orders, $tpl);
        return [$this->gotenberg->htmlToPdf($html), ['orders' => $orders->count(), 'template_id' => $tpl->id, 'template_name' => $tpl->name]];
    }
    // Fallback cũ — giữ nguyên SPEC 0021.
    return [$this->gotenberg->htmlToPdf(PrintTemplates::deliverySlip($orders, ..., $this->paperSize($job->tenant_id), $this->skuMapFor($orders))), [...]];
}
```

### 5.9 Audit

`ShippingLabelTemplateController` middleware ghi `audit_logs` với `subject_type='shipping_label_template'`, `action ∈ {create,update,delete,set_default,duplicate}`, `meta={name, paper, fields_count}`. Dùng audit pattern hiện có.

## 6. Frontend — editor + tích hợp

### 6.1 Routes mới (`app.tsx`)

- `/settings/shipping-labels` — list (gắn vào sidebar Settings).
- `/settings/shipping-labels/new` — editor (template trống).
- `/settings/shipping-labels/:id` — editor (load template).

### 6.2 `editorStore` (zustand)

```ts
type EditorState = {
  meta: { id: number|null; name: string; paper: Paper; paper_w_mm: number; paper_h_mm: number; is_default: boolean };
  fields: Field[];
  selection: string[];
  history: { past: Snapshot[]; future: Snapshot[] };
  sampleProfile: SampleProfileKey;
  zoom: number;
  grid: 0 | 1 | 2 | 5;
};

type EditorActions = {
  init(payload: TemplateDTO|null): void;
  addField(type: string, x: number, y: number): void;
  updateField(id: string, patch: Partial<Field>): void;
  commitTransform(id: string, box: { x; y; w; h; rotation }): void;     // push history
  removeFields(ids: string[]): void;
  setPaper(p: Paper, w?: number, h?: number): void;                     // hỏi confirm nếu fields overflow
  setSelection(ids: string[]): void;
  undo(): void;
  redo(): void;
  setSampleProfile(k: SampleProfileKey): void;
  toJson(): { name; paper; paper_w_mm; paper_h_mm; schema };
};
```

`history.past` ring buffer 50 snapshot. Action `*Transform`/`add`/`remove`/`updateProps` commit; drag intermediate KHÔNG commit (chỉ commit `transformEnd`). Auto-save draft vào `localStorage.shippingLabelDraft:<id|new>` mỗi 5s.

### 6.3 `LabelCanvas` (react-konva)

- `<Stage>` width = `paper_w_mm × zoom`, height = `paper_h_mm × zoom` (paper 80mm = render fixed 200mm cao trong editor, runtime auto).
- `<Layer>` background trắng + lưới chấm theo `grid`.
- `<Layer>` fields — render mỗi field qua `<FieldNode field={f} />` đọc `fieldTypes[f.type].KonvaRenderer`.
- `<Layer>` UI — `<Transformer>` gắn vào selection node(s).
- Drop zone: `onDrop` capture `dataTransfer.getData('field-type')` → `addField(type, dropX_mm, dropY_mm)`.

### 6.4 `FieldPalette`

8 button drag-source (`draggable` + `onDragStart` set field-type). Group theo nhóm:
- **Mã**: QR, Barcode
- **Hiển thị**: Text, Image
- **Trường động**: Data (mở submenu chọn key) — render qua AntD `<Popover>` + `<Radio.Group>` (tuân `ui-avoid-select-prefer-radio`).
- **Khung**: Divider, Rectangle
- **Danh sách**: Items list

Icons từ `@ant-design/icons`: `QrcodeOutlined`, `BarcodeOutlined`, `FontSizeOutlined`, `PictureOutlined`, `DatabaseOutlined`, `UnorderedListOutlined`, `MinusOutlined`, `BorderOutlined`.

### 6.5 `FieldInspector`

Sidebar phải. Render qua `fieldTypes[selectedField.type].InspectorPanel({ field, onChange })`. Trường hợp nhiều field selected → chỉ hiện inspector chung (x/y/w/h/rotation + delete + align tools).

### 6.6 `PaperSettings`

- `<Segmented options={['A4','A5','A6','100×150mm','80mm','Custom']} />`.
- Khi chọn `Custom` → 2 `<InputNumber>` width/height (mm). Validate 30..420mm, 50..1200mm.
- Đổi paper với fields overflow → AntD Modal: "Co lại vừa khổ mới" (scale proportional) / "Giữ nguyên".

### 6.7 `TemplateAliasPicker` (in)

AntD Modal. Default resolve order: `localStorage['lastShippingLabelTemplateId:'+tenantId]` → item có `is_default=true` từ GET list → `null` (= fallback `PrintTemplates::deliverySlip`). UI:
- `<Radio.Group>` nếu ≤ 5 alias, `<Select showSearch>` nếu > 5 (vẫn tuân memory: prefer Radio cho small sets, Select chỉ khi nhiều).
- Mỗi option: tên · paper tag · "Mặc định" tag nếu is_default.
- Nút "Xem trước" (gọi preview với 1 sample profile) — mở tab mới.
- Confirm → lưu `lastShippingLabelTemplateId` + gọi `useCreatePrintJob.mutate({type:'delivery', order_ids, template_id})`.

### 6.8 Tích hợp `OrderProcessing.tsx`

Hàm `printDelivery()` hiện tại gọi thẳng `createPrint.mutate({type:'delivery', order_ids:[order.id]})`. Sửa:
1. Mở `TemplateAliasPicker` (state local).
2. On confirm → gọi `createPrint.mutate({type:'delivery', order_ids:[order.id], template_id})`.
3. Logic warning "đã in N lần" giữ nguyên — hỏi trước khi mở picker.

Bulk in (`OrdersPage`) tương tự, áp 1 template cho tất cả selected.

### 6.9 `CreateOrderPage` (sửa)

Thêm field "Kho gửi" trong block địa chỉ:
- `<Radio.Group>` các warehouse (1 dòng / kho); auto-select default. Mặc định ẩn dòng "Kho mặc định" nếu chỉ có 1 kho.
- Submit kèm `warehouse_id`. Backend `ManualOrderService` lưu vào order.

### 6.10 Sample data — 3 profile

- `'one_item_short_address'`: 1 sản phẩm, địa chỉ 1 dòng — test layout tem nhỏ.
- `'three_items_long_address'`: 3 sản phẩm, địa chỉ dài (ward+district+province dài) — test wrap.
- `'cod_with_print_note'`: 1 sản phẩm, COD 500k, print_note 200 ký tự — test multi-line note.

User chuyển sample profile bằng `<Segmented>` trên topbar — canvas update realtime, không round-trip server.

## 7. Data flow chi tiết

### 7.1 Tạo / sửa alias

```
Editor mount
  → init(template?)            // load nếu :id, else trống
Drag từ palette → drop trên canvas
  → addField(type, x_mm, y_mm)
  → registry[type].defaultProps() merged
Click field → Transformer attach
  → drag/resize → onTransformEnd → commitTransform → history.push
Inspector input thay đổi
  → updateField(id, patch) → history.push (debounce 300ms cho text input)
Click Lưu
  → useUpdateTemplate.mutate(editorStore.toJson())
  → PUT /shipping-label-templates/:id
  → BE: Form Request validate shape → controller foreach field gọi FieldType.validateProps
  → 200 → toast + history.clear; 422 → highlight field path
```

### 7.2 Preview PDF

```
Click "Xem trước PDF"
  → POST /shipping-label-templates/:id/preview { schema_inline, sample_profile }
  → BE: build dummy Order từ sample_profile → LabelRenderer.renderBatch([dummy], tplFromInline)
  → Gotenberg → bytes → store tmp/labels/preview/{ulid}.pdf TTL 5 phút
  → trả { url }
FE window.open(url)
```

### 7.3 In phiếu giao hàng

```
Click "In phiếu giao hàng"
  → Modal TemplateAliasPicker
    → fetch GET /shipping-label-templates (cached)
    → default = localStorage.lastShippingLabelTemplateId ?? tenant default ?? null
  → Confirm
    → localStorage.set(lastShippingLabelTemplateId, id)
    → POST /print-jobs { type:'delivery', order_ids, template_id }

BE PrintJobController.store
  → validate type=delivery + order_ids manual + template_id cùng tenant
  → tạo PrintJob status=pending, meta={template_id, template_name}
  → RenderPrintJob dispatch queue 'labels'

RenderPrintJob → PrintService.render(job)
  → match type=delivery → renderDeliverySlip(job)
    → có template_id?
      yes → load template (withTrashed) → LabelRenderer.renderBatch
      no  → PrintTemplates::deliverySlip (cũ)
  → mediaUploader.storeBytes → file_url
  → status=done

FE poll print-job/:id → done → popup "Mở để in"
  → User mở PDF tab mới → confirm → POST mark-printed → print_count++
```

### 7.4 Đặt default

```
POST /shipping-label-templates/:id/set-default
  → ShippingLabelTemplateService.setDefault($tenantId, $id)
    → DB::transaction:
        UPDATE shipping_label_templates SET is_default=false WHERE tenant_id=? AND id<>? AND deleted_at IS NULL
        UPDATE shipping_label_templates SET is_default=true  WHERE id=? AND tenant_id=?
  → audit_logs +1 entry
```

## 8. Error handling

| Layer | Tình huống | Xử lý |
|---|---|---|
| Form Request | Schema sai shape | 422, FE hiển thị message bên cạnh tên alias |
| FieldType.validateProps | 1 field props lỗi | 422 path `schema.fields.<index>.<prop>` → editor highlight |
| LabelRenderer foreach field | Render throw | `report($e)`, skip field, vẫn xuất PDF |
| LabelDataResolver | Thiếu data (tracking, warehouse) | Field fallback "—" hoặc encode `order_number`; warehouse null → `Warehouse::defaultFor` |
| PrintService | template_id thuộc tenant khác | Controller pre-filter `where tenant_id` → 404 |
| PrintService | template soft-deleted giữa job | Đọc `withTrashed()` → vẫn render được; UI list không hiện |
| Editor | drag ngoài paper | Konva `boundBoxFunc` clamp |
| Editor | resize quá nhỏ | min 5mm chung, QR/barcode min 15mm |
| Editor | đổi paper, overflow | Modal: scale proportional / giữ nguyên |
| Editor | mất kết nối | localStorage auto-save 5s; on mount → modal restore |
| Editor | 2 user cùng sửa | Optimistic lock `updated_at` → 409 conflict |
| Preview | spam | Rate limit 10 req/phút/user (RouteRateLimiter) |
| Print | bulk có đơn sàn | PrintService chặn (logic cũ) |

## 9. Permission

| Permission | Hành vi |
|---|---|
| `tenant.settings` (owner/admin) | CRUD template, set default, duplicate |
| (mọi member) | View list, view detail, preview, in (cần `fulfillment.print`) |
| Super-admin | Không động — admin app không có entry point cho shipping-labels |

## 10. Testing strategy

### 10.1 BE

```
tests/Unit/Fulfillment/LabelRendering/
├── FieldTypeRegistryTest.php
├── Fields/QrFieldTest.php
├── Fields/BarcodeFieldTest.php
├── Fields/TextFieldTest.php
├── Fields/ImageFieldTest.php
├── Fields/DataFieldTest.php
├── Fields/ItemsListFieldTest.php
├── Fields/DividerFieldTest.php
├── Fields/RectangleFieldTest.php
├── LabelDataResolverTest.php
├── LabelRendererTest.php                     ← golden HTML snapshot
└── FieldRenderHelpersTest.php

tests/Feature/Fulfillment/
├── ShippingLabelTemplateCrudTest.php         ← 6 endpoint × permission
├── SetDefaultTransactionTest.php             ← 2 concurrent → 1 winner
├── DuplicateTemplateTest.php
├── PreviewTemplateTest.php                   ← rate-limit + 3 sample profiles
├── PrintDeliveryWithTemplateTest.php         ← E2E with fake Gotenberg
├── PrintDeliveryNoTemplateBackwardCompatTest.php
└── ManualOrderWarehouseIdTest.php
```

Gotenberg fake: `bind GotenbergClient` về `FakeGotenbergClient` trả `('PDF-BYTES:'.$htmlHash)`. Golden HTML snapshot — file `tests/fixtures/labels/kitchen-sink.html`; PR đụng renderer phải update có chủ ý.

n+1 detection: `DB::listen` count queries trong `LabelRendererTest::test_render_batch_does_not_n_plus_one_on_items`.

### 10.2 FE

- `tsc --noEmit` pass cho registry shape (FieldDef generic enforce mỗi field type props match BE schema TS types).
- Smoke checklist thủ công:
  1. Tạo template trắng → drop 8 field type → save → reload → khớp.
  2. Đổi paper với fields overflow → cảnh báo.
  3. Undo / redo 10 bước.
  4. Auto-save → reload trang → restore draft.
  5. Preview PDF mở tab mới.
  6. CreateOrderPage chọn warehouse → đơn lưu warehouse_id.
  7. OrderProcessing → in → picker → PDF có sender từ warehouse đã chọn.
  8. Bulk print 5 đơn manual cùng template → 5 trang.
  9. Set default → reload list → default tag.
 10. Soft-delete template đang là default → list không hiện; nếu là last-used cá nhân → picker auto chọn default mới.

### 10.3 Performance baseline

- Editor 50 field: drag 60fps, history step < 4ms.
- Render alias 10 đơn manual: end-to-end < 3s (queue + Gotenberg).

## 11. Migration & rollout

1. Migration `add_warehouse_id_to_orders` — nullable, safe trên prod (không backfill cần thiết).
2. Migration `create_shipping_label_templates_table`.
3. Seeder optional `ShippingLabelTemplateSeederSamples` — chạy thủ công per tenant qua command `php artisan shipping-labels:seed-samples {tenant_id}` (không auto cho tenant cũ).
4. Deploy BE → endpoints hoạt động.
5. Deploy FE — picker mặc định `null` → fallback hệ thống cũ; tenant không tạo template nào vẫn in được như trước.
6. Tài liệu hướng dẫn vận hành cho support team.

Backward compat: 100%. Đơn cũ + tenant chưa tạo template → in y như trước.

## 12. Memory rules tuân thủ

- **Icons font, không emoji**: tất cả icon từ `@ant-design/icons` (Qrcode/Barcode/Picture/Database/UnorderedList/Border/Minus + status icons).
- **Tránh `<Select>` cho option nhỏ**: paper preset, grid snap, font weight, align, data-key dùng `<Segmented>` / `<Radio.Group>`. `<Select>` chỉ cho TemplateAliasPicker khi > 5 alias và FieldInspector `assetPath` (chọn ảnh nhiều).

## 13. Extensibility roadmap (định hướng phase sau)

- **Phase 2 field types**: signature box, table (phụ phí), conditional block (ẩn COD khi không COD), insurance value.
- **Phase 2 template types**: `picking`/`packing`/`invoice` áp dụng cùng registry — chỉ thêm `DataKey` cho từng type, không động `LabelRenderer`.
- **Phase 2 custom carrier logo**: thêm `CarrierAccount.logo_path` + `ImageField.source='carrier_logo_custom'` — không đụng JSON schema.
- **Phase 2 user-uploaded image asset**: lib `media` đã có; field `ImageField.assetPath` đã accept R2 key, chỉ cần thêm uploader UI.

## 14. Dependencies thêm

| Layer | Package | Mục đích |
|---|---|---|
| FE | `react-konva` + `konva` | Editor canvas |
| FE | `zustand` | Editor state |
| FE | `nanoid` | Generate field id |

(BE: không thêm — `bacon/bacon-qr-code` + `picqer/php-barcode-generator` đã có.)

## 15. Câu hỏi mở (không cản v0)

- Có cần versioning lịch sử template (rollback) không? — phase 2 nếu user yêu cầu.
- Có nên cho phép template share giữa các tenant của cùng owner? — phase 2.
- `paper_h_mm = 0` cho khổ 80mm: Gotenberg `size:80mm auto` có ổn không trong mọi orientation? — kiểm tra trong implementation, fallback cố định 200mm nếu cần.
