# Thiết kế: Xuất hóa đơn điện tử cho đơn hàng (MISA meInvoice)

- Ngày: 2026-06-30
- Trạng thái: Design (chờ duyệt → writing-plans)
- Spec chính thức (tạo khi lập plan): `docs/specs/0041-einvoice-misa-meinvoice.md`
- ADR mới (tạo khi lập plan): `docs/01-architecture/adr/0028-einvoice-connector-registry.md`
- Liên quan: ADR-0004 (Connector+Registry — đã dự liệu "hoá đơn điện tử"), SPEC 0012 (in PDF phiếu đơn — KHÁC), SPEC 0019 (Accounting/VAT/MISA AMIS export — KHÁC), SPEC 0025 (đơn hoàn), SPEC 0018 (Billing/plan-gate).

## 1. Mục tiêu & bối cảnh

Cho phép seller phát hành **hóa đơn điện tử hợp pháp** cho đơn hàng qua **MISA meInvoice Open API v3**, kèm: tùy chọn cấu hình theo seller, thời điểm xuất (tự động + thủ công), hóa đơn bổ sung cho đơn hoàn (hủy/thay thế/điều chỉnh), và thống kê.

**Hiện trạng**: codebase CHƯA có tích hợp HĐĐT. ADR-0004 đã nêu đích danh "hoá đơn điện tử" là một trục Connector+Registry tương lai. Đây là tính năng hoàn toàn mới, build đúng pattern integration hiện hữu (mirror Payments/Carriers).

**Phân biệt rõ** với cái đã có:
- `Billing/Invoice` = hóa đơn SaaS (CMBcoreSeller thu phí seller) — KHÔNG phải HĐ bán cho khách.
- SPEC 0012 "in hóa đơn" = in PDF phiếu đơn — KHÔNG phải HĐĐT pháp lý.
- `Accounting/MisaExportService` = export CSV cho MISA AMIS — KHÔNG gọi API meInvoice.

## 2. Quyết định nghiệp vụ (đã chốt với chủ dự án)

1. **Kiểu phát hành**: connector đa kiểu **HSM + MTT**; cấu hình theo seller. "Thường (USB token + SignedService cục bộ)" **để ngỏ sau** (không phù hợp SaaS cloud).
2. **Ánh xạ nguồn đơn → kiểu**:
   - Đơn **sàn** (tiktok/shopee/lazada) → **MTT** (cố định). Lý do: bán lẻ, người mua thường không có MST, PII bị sàn che.
   - Đơn **manual (tự tạo)** → **cấu hình theo seller** (mặc định HSM hoặc MTT do seller chọn).
3. **Thời điểm xuất**: **tự động** theo trạng thái cấu hình được **+** **nút thủ công** trên đơn.
4. **Đơn hoàn → HĐ bổ sung**: seller **tự chọn nghiệp vụ** Hủy / Thay thế / Điều chỉnh; hệ kiểm **ràng buộc trạng thái MISA** và chặn thao tác sai.
5. **Thông tin người mua**: **snapshot + tùy chọn** — thêm trường HĐ vào Customer (tái dùng) + cho ghi đè theo đơn + **snapshot** vào bản ghi HĐ khi phát hành.
6. **Plan-gate**: tính năng HĐĐT khóa sau **gói cao nhất** (gói có đầy đủ bộ kế toán). Feature key `einvoice`.

## 3. Tham chiếu MISA meInvoice API v3 (đã nghiên cứu 22 trang doc)

- Base URL: test `https://testapi.meinvoice.vn/api/v3`, prod `https://api.meinvoice.vn/api/v3`.
- Headers: `Content-Type: application/json`, `Authorization: Bearer <token>`, `CompanyTaxCode: <MST>`.
- **Token**: `POST /auth/token` (`appid/taxcode/username/password`) → token, **hạn 15 ngày**; refresh `POST /auth/refreshtoken`. Cache & refresh đầu phiên.
- **Envelope 2 tầng** (SỐNG CÒN): `{Success, Data, ErrorCode, Errors}`; `Data` thường là **chuỗi JSON stringify**, mỗi phần tử có `ErrorCode` riêng. Thành công = `Success=true` + `ErrorCode` ngoài rỗng + `ErrorCode` từng phần tử rỗng.
- **Có mã / không mã CQT**: `GetCompanyInfo.IsInvoiceWithCode` quyết định dùng path tiền tố `/code/...` hay không.
- **Phát hành**:
  - HSM: `POST /itg/invoicepublishing/publishhsm` (hoặc `/code/...`) — đẩy `OriginalInvoiceData` (object thô), MISA dựng XML + ký HSM.
  - MTT: `POST /code/itg/invoice-calculating/invoiceandpublish` — `OrgInvoiceData` có `IsInvoiceCalculatingMachine=true`.
  - (Thường: `POST /itg/invoicepublishing` — XML đã ký client; KHÔNG làm v1.)
  - Body = **mảng tối đa 50 HĐ**. Idempotency = `RefID` (GUID). Phát hành **tuần tự theo ký hiệu**, số HĐ phải liên tục (`InvoiceNumberNotContinuous` → retry/delay).
- **Điều chỉnh/thay thế**: thêm vào object: `ReferenceType` (1=thay thế, 2=điều chỉnh), `OrgInvoiceType/OrgInvTemplateNo/OrgInvSeries/OrgInvNo/OrgInvDate`, `InvoiceNote` (lý do). Ràng buộc: không thay thế HĐ đã hủy; HĐ đã có HĐ điều chỉnh thì không thay thế; v.v.
- **Hủy**: `POST /itg/invoicepublished/cancel` (`TransactionID, InvNo, RefDate, CancelReason`; `InvoiceType=10` cho MTT/POS).
- **Trạng thái**: theo RefID `POST /invoicepublished/invoice-status/refid` (MTT: `/code/itg/invoice-calculating-published/invoice-status/refid`); theo TransactionID `POST /itg/invoicepublished/invoicestatus`. Tối đa 50 mã/lần. Trả `PublishStatus, EInvoiceStatus, ReferenceType, SendTaxStatus, ReceivedStatus, IsDelete...`.
- **Tải**: `POST /itg/invoicepublished/downloadinvoice?downloadDataType=XML|PDF|ALL` (MTT: `/code/itg/invoice-calculating-published/download`).
- **Xem trước (chưa phát hành)**: `POST /itg/invoicepublishing/invoicelinkview?type=1` → URL.
- **Gửi email**: `POST /itg/emails` (`SendEmailDatas[]`, `IsInvoiceCode`, `IsInvoiceCalculatingMachine`).
- **Mẫu HĐ**: `GET /itg/InvoicePublishing/templates?invyear=` → `IPTemplateID/InvSeries/InvoiceType/...`.
- **Công thức tiền** (mức dòng): `Amount = UnitPrice×Quantity`; `AmountWithoutVAT = Amount − DiscountAmount`; `VATAmount = AmountWithoutVAT × VATRate`. Mức HĐ: tổng theo dòng + `TaxRateInfo` theo từng thuế suất (số phần tử phải khớp số thuế suất phân biệt). `OptionUserDefined` quy định số chữ số thập phân (BẮT BUỘC).
- `VATRateName`: `0% | 5% | 8% | 10% | KCT | KKKNT | KHAC:AB.CD%`. (8% cần `IsTaxReduction43` ở master.)
- **Validate** trước phát hành: MST đúng định dạng (10 số hoặc 14 ký tự chi nhánh); email đúng mẫu; nếu có `BuyerTaxCode` thì bắt buộc `BuyerLegalName` + `BuyerAddress`; đủ trường tiền tệ master & dòng.
- **⚠ NEEDS-VERIFY**: chưa có credentials sandbox MISA. Implement theo doc, đánh dấu cần kiểm thử protocol khi có tài khoản (mirror cách làm Zalo Phase 1). Lưu ý mâu thuẫn nhỏ giữa các nguồn doc (`/api/integration` vs `/api/v3`) — lấy `/api/v3` từ doc.meinvoice.vn làm chuẩn, xác minh khi tích hợp thật.

## 4. Kiến trúc

### 4.1 Trục integration mới — `app/app/Integrations/EInvoice/`

Mirror `Payments`/`Carriers`. Core KHÔNG biết tên nhà cung cấp.

- `EInvoiceRegistry`: `register(code, class)`, `has(code)`, `for(code)` (resolve qua container), `providers()`.
- `Contracts/EInvoiceConnector`:
  - `code(): string` (vd `'misa'`), `displayName(): string`
  - `capabilities(): array` (vd `['issue_hsm'=>true,'issue_mtt'=>true,'cancel'=>true,'replace'=>true,'adjust'=>true,'query'=>true,'download'=>true,'send_email'=>true,'preview'=>true]`)
  - `supports(string $cap): bool`
  - `assertConfigured(array $account): void` (ném `EInvoiceNotConfigured`)
  - `verifyCredentials(array $account): array{ok,message,expires_at?,error_code?}` (cho UI test kết nối)
  - `getCompanyInfo(array $account): CompanyInfoDTO`
  - `templates(array $account, int $year): TemplateDTO[]`
  - `preview(array $account, InvoiceDTO $inv): string` (URL)
  - `issue(array $account, InvoiceDTO $inv, IssueOptions $opt): IssueResultDTO` (tự chọn HSM/MTT theo `$opt->mode`)
  - `cancel(array $account, CancelRequestDTO $req): IssueResultDTO`
  - `adjust(...) / replace(...)`: nhận `InvoiceDTO` + tham chiếu HĐ gốc
  - `status(array $account, string[] $refIds, string $mode): InvoiceStatusDTO[]`
  - `download(array $account, string[] $lookupCodes, string $type, string $mode): string`
  - `sendEmail(array $account, ...): void`
- **DTO chuẩn** (`Integrations/EInvoice/DTO/`): `InvoiceDTO`, `InvoiceLineDTO`, `TaxRateLineDTO`, `IssueOptions`, `IssueResultDTO`, `InvoiceStatusDTO`, `CompanyInfoDTO`, `TemplateDTO`, `CancelRequestDTO`. Tiền = **integer VND**; connector tự đổi sang decimal khi build payload MISA.
- `Exceptions/`: `UnsupportedOperation`, `EInvoiceNotConfigured`, `EInvoiceProviderError` (mang `code` + thông điệp gốc).
- `MisaMeInvoice/MisaMeInvoiceConnector` (code `'misa'`) + `Support/`: `MisaHttpClient` (envelope 2 tầng, retry `InvoiceNumberNotContinuous`, path `/code` theo `IsInvoiceWithCode`), `MisaTokenStore` (cache token 15 ngày per-account), `MisaInvoicePayloadMapper` (InvoiceDTO → object MISA + `OptionUserDefined` + `TaxRateInfo`), `MisaErrorMap` (mã lỗi → thông điệp tiếng Việt + phân loại retryable). Nhận `array $account` (credentials per-tenant); KHÔNG import `app/Modules/*` (ngoài DTO/interface chuẩn).
- Đăng ký: `IntegrationsServiceProvider` (block giống Payments) + `config/integrations.php` → `einvoice` (enabled CSV `INTEGRATIONS_EINVOICE`, `misa` base URL test/prod, timeout). **INERT** tới khi bật + có credentials.

### 4.2 Module nghiệp vụ mới — `app/app/Modules/EInvoice/`

- **Models**:
  - `EInvoice` (`einvoices`): `tenant_id`, `order_id?`, `order_return_id?`, `provider`, `mode` (hsm/mtt), `ref_id` (uuid, unique per tenant — idempotency), `transaction_id?`, `inv_series?`, `inv_no?`, `inv_code?` (mã CQT), `inv_date?`, `status` (enum), `reference_type` (enum), `original_einvoice_id?` (self-FK), buyer snapshot (`buyer_name/buyer_tax_code/buyer_company/buyer_address/buyer_email`), seller snapshot, tổng tiền (`amount_without_vat/vat_amount/discount_amount/total_amount` — bigint VND), `tax_summary` (json TaxRateInfo), `lookup_code?`, `pdf_url?`, `send_tax_status?`, `received_status?`, `error_code?`, `error_message?`, `raw_request` (json), `raw_response` (json), `issued_at?`, `cancelled_at?`, `cancel_reason?`. `BelongsToTenant`.
  - `EInvoiceLine` (`einvoice_lines`): `einvoice_id`, `line_number`, `item_type`, `item_code?`, `name`, `unit?`, `quantity`, `unit_price`, `discount_rate`, `discount_amount`, `amount`, `vat_rate_name`, `vat_amount`.
  - `EInvoiceAccount` (`einvoice_accounts`): per-tenant, `provider`, `credentials` (**`encrypted:array`**: appid/taxcode/username/password), `is_invoice_with_code?` (cache từ company info), `default_mode`, `templates` (json: template_id/series mỗi kiểu), `seller_info` (json), `auto_issue` (json: bật/tắt + trạng thái trigger + manual source→mode), `meta` (json), `active`. Mirror `CarrierAccount` (`toConnectorArray()`).
- **Enums** (`app/app/Support/Enums/`): `EInvoiceStatus` (draft/issuing/issued/failed/cancelled/replaced/adjusted), `EInvoiceReferenceType` (original/replacement/adjustment), `EInvoiceSendTaxStatus`.
- **Services**:
  - `OrderInvoiceMapper`: Order(+items, customer, override) → `InvoiceDTO`. Tính VAT từng dòng theo `order_items.tax_rate_bps` (mặc định theo seller config), build `TaxRateInfo`, đối chiếu tổng với `orders.grand_total`.
  - `IssueEInvoiceService`: chọn `EInvoiceAccount` → xác định mode (sàn=MTT, manual=theo seller) → build DTO → `EInvoiceRegistry->for(provider)->issue(...)` → persist `EInvoice`+lines → cập nhật trạng thái + raw. Idempotent theo `ref_id`.
  - `EInvoiceAdjustmentService`: từ `OrderReturn` + HĐ gốc → build cancel/replace/adjust theo nghiệp vụ seller chọn; kiểm ràng buộc trạng thái (chặn sai); persist HĐ mới link `original_einvoice_id`.
  - `EInvoiceStatusSyncService`: polling backup theo refid, cập nhật `send_tax_status/received_status/inv_no/inv_code`.
  - `EInvoiceStatsService`: thống kê (mục 5 spec gốc).
- **Jobs idempotent** (đăng ký queue trong **Horizon supervisor**, nếu không job kẹt im lặng): `IssueEInvoiceJob`, `SyncEInvoiceStatusJob`, `IssueAdjustmentJob`.
- **Listener** `AutoIssueOnOrderStatusChanged`: nghe event Orders; gate theo `auto_issue` (bật + trạng thái khớp) + account active + **chưa có HĐ** cho đơn (re-check idempotency lúc gửi). Đơn hoàn KHÔNG auto (nghiệp vụ thủ công).
- **Giao tiếp module**: EInvoice phụ thuộc Tenancy; đọc dữ liệu Orders/Customers **chỉ qua domain event + read-contract** (đúng luật module, theo pattern listener của Accounting `PostOnOrder*`). Phơi `Contracts/IssueInvoiceContract` nếu module khác cần kích hoạt.
- **HTTP**: `EInvoiceController` (index/show/issue/preview/cancel/adjust/replace/retry/download/resend-email/sync-status), `EInvoiceAccountController` (CRUD + verifyCredentials + fetchTemplates + companyInfo), `EInvoiceStatsController`. Thin controller: FormRequest → Service → Resource. Routes per-module. Provider đăng ký ở `bootstrap/providers.php`. Endpoint mới thêm vào `docs/05-api/endpoints.md`.
- **RBAC**: `einvoice.view`, `einvoice.issue`, `einvoice.manage` (hủy/điều chỉnh), `einvoice.config`. **Plan-gate** feature key `einvoice` (khai đủ 4 nơi như chuẩn marketing) ở **gói cao nhất**.

### 4.3 Bổ sung schema (CẦN migrate khi deploy)

- `order_items.tax_rate_bps` (smallint, nullable) — thuế suất từng dòng; default theo seller config khi map.
- `customers`: `tax_code` (string?), `company_name` (string?), `company_address` (string?). (email đã có, encrypted.)
- `orders.invoice_info` (json, nullable) — ghi đè thông tin xuất HĐ theo đơn (buyer override + mode override).

### 4.4 Frontend (`app/resources/js/`)

- `lib/einvoice.tsx` — hooks React Query qua `tenantApi` (`useEInvoices`, `useIssueEInvoice`, `usePreview`, `useCancel`, `useAdjust`, `useEInvoiceAccount`, `useTemplates`, `useEInvoiceStats`). Dùng `useCurrentTenantId()`.
- `pages/settings/EInvoiceSettingsPage.tsx` (mirror `CarrierAccountsPage`): nhập credentials MISA + **test kết nối** + chọn template/series mỗi kiểu + default mode (đơn manual) + cấu hình auto-issue (trạng thái trigger) + source→mode + thông tin người bán.
- `components/OrderDetailBody.tsx`: mục **"Xuất HĐĐT"** — nút xuất (mode auto theo nguồn), xem trước, trạng thái + mã CQT, tải PDF, sửa thông tin người mua (HĐ GTGT). Toolbar phẳng luôn hiện (validate-by-disable). Icon @ant-design/icons, ưu tiên Radio/Segmented thay Select.
- UI aftersales (đơn hoàn): tạo HĐ Hủy/Thay thế/Điều chỉnh — picker nghiệp vụ + phản hồi ràng buộc trạng thái.
- `pages/einvoice/EInvoicesPage.tsx`: danh sách HĐ (lọc theo kỳ/trạng thái/nguồn/kiểu) + **dashboard thống kê**.

## 5. Phạm vi thống kê (CHỐT)

- Số HĐ theo kỳ (ngày/tháng) & theo **trạng thái** (phát hành / hủy / điều chỉnh / lỗi / chờ CQT).
- Tổng **doanh thu trước thuế** + **VAT** + **thanh toán** theo kỳ.
- **Đơn đủ điều kiện nhưng CHƯA xuất HĐ** (đối soát Orders ↔ EInvoice).
- Trạng thái **gửi CQT** (chấp nhận / từ chối / lỗi) & số HĐ **người mua đã nhận**.
- **Tồn quota** số HĐ còn lại (nếu API trả `LicenseInfo`).
- Bóc tách theo **nguồn đơn** (sàn/manual) & **kiểu** (MTT/HSM).

## 6. Lộ trình triển khai (1 spec, chia phase)

- **P1 — Lõi**: trục integration + `MisaMeInvoiceConnector` (HSM+MTT) + DTO + `EInvoiceAccount` + cấu hình/test kết nối + **xuất thủ công** cho đơn + bổ sung schema + danh sách HĐ + tải PDF. Plan-gate + RBAC.
- **P2**: auto-issue theo trạng thái (listener + job) + polling đồng bộ trạng thái CQT + gửi email.
- **P3**: đơn hoàn → hủy/thay thế/điều chỉnh (kiểm ràng buộc) + UI aftersales.
- **P4**: dashboard thống kê.

## 7. Rủi ro & xử lý

- **Chưa có sandbox MISA** → protocol NEEDS-VERIFY; cô lập I/O sau interface để dễ test bằng fixtures; INERT tới khi cấu hình.
- **Số HĐ liên tục / trùng RefID** → phát hành tuần tự theo ký hiệu, lock per-account-series, retry có delay, idempotency theo `ref_id`.
- **PII người mua đơn sàn bị che** → MTT bán lẻ không cần MST người mua; map an toàn, không cố lấy PII.
- **Token 15 ngày** → cache + refresh đầu phiên, không lấy token mỗi giao dịch.
- **Thuế suất từng dòng** → bổ sung `tax_rate_bps`; nếu thiếu, fallback default seller; cảnh báo khi tổng VAT lệch `orders.tax`.
- **Đối chiếu tổng tiền** → mapper kiểm `Σ dòng == grand_total`, chặn phát hành nếu lệch ngoài ngưỡng làm tròn.

## 8. Ngoài phạm vi (v1)

- Kiểu phát hành "Thường" (USB token + SignedService cục bộ).
- Hóa đơn đầu vào (nhà cung cấp) — dùng API `/api2` `/inbot/*` khác.
- Các loại HĐ đặc thù: PXK điều chuyển nội bộ, vé, vận tải, đồng hồ điện/nước/xăng (ClockInfo) — connector để khung mở rộng nhưng UI không làm v1.
- Đẩy bút toán HĐĐT sang Accounting (có thể nối SPEC 0019 ở phase sau).
