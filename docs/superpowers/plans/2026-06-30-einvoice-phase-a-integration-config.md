# Hóa đơn điện tử — Phần A: Trục tích hợp + Cấu hình nhà cung cấp (MISA meInvoice) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dựng trục integration `Integrations/EInvoice/` (Connector+Registry theo ADR-0004) với connector MISA meInvoice, model `EInvoiceAccount` lưu credentials per-tenant (mã hóa), RBAC + plan-gate, và UI cấu hình + "Kiểm tra kết nối" — để seller khai báo tài khoản MISA và xác thực được (chưa phát hành hóa đơn — đó là Phần B).

**Architecture:** Trục integration mới mirror `Payments`/`Carriers`: `EInvoiceRegistry` resolve connector theo code, connector nhận `array $account` (credentials per-tenant) + `array $config` (base_url/http), core không biết tên nhà cung cấp. Module nghiệp vụ `Modules/EInvoice/` chứa `EInvoiceAccount` (mirror `CarrierAccount`) + controller CRUD/verify. Giao tiếp HTTP cô lập sau `MisaClient` (token cache 14 ngày, parse envelope 2 tầng). INERT tới khi bật `INTEGRATIONS_EINVOICE` + có credentials.

**Tech Stack:** Laravel 11 (PHP 8.3), `Illuminate\Support\Facades\Http`, `encrypted:array` cast, React 18 + Ant Design 5 + TanStack Query, PHPUnit (SQLite memory), Pint + PHPStan level 5.

## Global Constraints

- Mọi lệnh PHP/Node chạy từ `app/` (không phải repo root).
- Namespace `CMBcoreSeller\` → `app/app/`. (vd `CMBcoreSeller\Integrations\EInvoice\EInvoiceRegistry` → `app/app/Integrations/EInvoice/EInvoiceRegistry.php`).
- `Integrations/` KHÔNG được `use` `Modules/*` (chỉ DTO/interface chuẩn). Core không có `if ($provider === 'misa')`.
- Tiền = integer VND đồng (không float). Không dùng `env()` ngoài file config — dùng `config()`.
- Envelope thành công `{ "data": ... }`; lỗi `{ "error": { "code", "message", ... } }` (chuẩn hóa ở `bootstrap/app.php`).
- Resource API KHÔNG BAO GIỜ lộ `credentials` thô — chỉ `credential_keys`.
- UI: chuỗi hiển thị tiếng Việt; icon từ `@ant-design/icons` (không emoji); ưu tiên `Radio.Group`/`Segmented` thay `Select` cho tập lựa chọn nhỏ.
- Test chạy từ `app/`: `php artisan test --filter=<Name>`. Test đặt ở `app/tests/Feature/EInvoice/`. Tạo Order/Customer trong test bằng `Model::query()->create([... 'tenant_id' => ...])` (không có factory cho chúng).
- Quality gate: `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test`, `npm run lint && npm run typecheck && npm run build`.
- Lưu ý baseline: 7 test GHN/fulfillment fail sẵn trên main — không phải do thay đổi của bạn.

---

### Task 1: DTO + Exceptions cho trục EInvoice

**Files:**
- Create: `app/app/Integrations/EInvoice/DTO/CompanyInfoDTO.php`
- Create: `app/app/Integrations/EInvoice/DTO/TemplateDTO.php`
- Create: `app/app/Integrations/EInvoice/Exceptions/UnsupportedOperation.php`
- Create: `app/app/Integrations/EInvoice/Exceptions/EInvoiceNotConfigured.php`
- Create: `app/app/Integrations/EInvoice/Exceptions/EInvoiceProviderError.php`
- Test: `app/tests/Feature/EInvoice/EInvoiceDtoTest.php`

**Interfaces:**
- Produces:
  - `CompanyInfoDTO::__construct(string $companyName, string $taxCode, ?string $address, bool $isInvoiceWithCode, ?string $email = null, ?string $bankAccount = null, ?string $bankName = null)` + `toArray(): array` + `static fromMisa(array $raw): self`.
  - `TemplateDTO::__construct(string $templateId, string $templateName, string $invSeries, int $invoiceType, bool $isPublished, bool $inactive)` + `toArray(): array` + `static fromMisa(array $raw): self`.
  - `UnsupportedOperation::for(string $provider, string $operation): self`.
  - `EInvoiceNotConfigured::for(string $provider, string $missing): self`.
  - `EInvoiceProviderError` — `public readonly string $errorCode`, `public readonly string $errorClass` ('retryable'|'non_retryable'); `__construct(string $errorCode, string $message, string $errorClass = 'non_retryable')`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\EInvoice;

use CMBcoreSeller\Integrations\EInvoice\DTO\CompanyInfoDTO;
use CMBcoreSeller\Integrations\EInvoice\DTO\TemplateDTO;
use CMBcoreSeller\Integrations\EInvoice\Exceptions\EInvoiceProviderError;
use CMBcoreSeller\Integrations\EInvoice\Exceptions\UnsupportedOperation;
use Tests\TestCase;

class EInvoiceDtoTest extends TestCase
{
    public function test_company_info_maps_from_misa_raw(): void
    {
        $dto = CompanyInfoDTO::fromMisa([
            'CompanyName' => 'Công ty ABC', 'CompanyTaxCode' => '0105922241',
            'CompanyAddress' => 'Hà Nội', 'IsInvoiceWithCode' => true, 'CompanyEmail' => 'a@b.vn',
        ]);
        $this->assertSame('Công ty ABC', $dto->companyName);
        $this->assertTrue($dto->isInvoiceWithCode);
        $this->assertSame('0105922241', $dto->toArray()['tax_code']);
    }

    public function test_template_maps_from_misa_raw(): void
    {
        $dto = TemplateDTO::fromMisa([
            'IPTemplateID' => 'guid-1', 'TemplateName' => '01GTKT', 'InvSeries' => '1C25TAA',
            'InvoiceType' => 1, 'IsPublished' => true, 'Inactive' => false,
        ]);
        $this->assertSame('guid-1', $dto->templateId);
        $this->assertSame('1C25TAA', $dto->toArray()['inv_series']);
        $this->assertSame(1, $dto->invoiceType);
    }

    public function test_unsupported_operation_message_is_vietnamese(): void
    {
        $e = UnsupportedOperation::for('misa', 'adjust');
        $this->assertStringContainsString('misa', $e->getMessage());
        $this->assertStringContainsString('adjust', $e->getMessage());
    }

    public function test_provider_error_carries_code_and_class(): void
    {
        $e = new EInvoiceProviderError('TokenExpiredCode', 'Token hết hạn', 'retryable');
        $this->assertSame('TokenExpiredCode', $e->errorCode);
        $this->assertSame('retryable', $e->errorClass);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=EInvoiceDtoTest`
Expected: FAIL — class `CompanyInfoDTO` not found.

- [ ] **Step 3: Write the DTOs and exceptions**

`app/app/Integrations/EInvoice/DTO/CompanyInfoDTO.php`:
```php
<?php

namespace CMBcoreSeller\Integrations\EInvoice\DTO;

/** Thông tin công ty trả về từ nhà cung cấp HĐĐT (GetCompanyInfo). */
final class CompanyInfoDTO
{
    public function __construct(
        public readonly string $companyName,
        public readonly string $taxCode,
        public readonly ?string $address,
        public readonly bool $isInvoiceWithCode,
        public readonly ?string $email = null,
        public readonly ?string $bankAccount = null,
        public readonly ?string $bankName = null,
    ) {}

    public static function fromMisa(array $raw): self
    {
        return new self(
            companyName: (string) ($raw['CompanyName'] ?? ''),
            taxCode: (string) ($raw['CompanyTaxCode'] ?? ''),
            address: $raw['CompanyAddress'] ?? null,
            isInvoiceWithCode: (bool) ($raw['IsInvoiceWithCode'] ?? false),
            email: $raw['CompanyEmail'] ?? null,
            bankAccount: $raw['BankAccount'] ?? null,
            bankName: $raw['BankName'] ?? null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'company_name' => $this->companyName,
            'tax_code' => $this->taxCode,
            'address' => $this->address,
            'is_invoice_with_code' => $this->isInvoiceWithCode,
            'email' => $this->email,
            'bank_account' => $this->bankAccount,
            'bank_name' => $this->bankName,
        ];
    }
}
```

`app/app/Integrations/EInvoice/DTO/TemplateDTO.php`:
```php
<?php

namespace CMBcoreSeller\Integrations\EInvoice\DTO;

/** Một mẫu hóa đơn (InvoiceTemplate). */
final class TemplateDTO
{
    public function __construct(
        public readonly string $templateId,
        public readonly string $templateName,
        public readonly string $invSeries,
        public readonly int $invoiceType,
        public readonly bool $isPublished,
        public readonly bool $inactive,
    ) {}

    public static function fromMisa(array $raw): self
    {
        return new self(
            templateId: (string) ($raw['IPTemplateID'] ?? ''),
            templateName: (string) ($raw['TemplateName'] ?? ''),
            invSeries: (string) ($raw['InvSeries'] ?? ''),
            invoiceType: (int) ($raw['InvoiceType'] ?? 0),
            isPublished: (bool) ($raw['IsPublished'] ?? false),
            inactive: (bool) ($raw['Inactive'] ?? false),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'template_id' => $this->templateId,
            'template_name' => $this->templateName,
            'inv_series' => $this->invSeries,
            'invoice_type' => $this->invoiceType,
            'is_published' => $this->isPublished,
            'inactive' => $this->inactive,
        ];
    }
}
```

`app/app/Integrations/EInvoice/Exceptions/UnsupportedOperation.php`:
```php
<?php

namespace CMBcoreSeller\Integrations\EInvoice\Exceptions;

use RuntimeException;

/** Nhà cung cấp HĐĐT không hỗ trợ thao tác này. Mirror pattern Payments/Channels. */
class UnsupportedOperation extends RuntimeException
{
    public static function for(string $provider, string $operation): self
    {
        return new self("Nhà cung cấp HĐĐT `{$provider}` không hỗ trợ thao tác `{$operation}`.");
    }
}
```

`app/app/Integrations/EInvoice/Exceptions/EInvoiceNotConfigured.php`:
```php
<?php

namespace CMBcoreSeller\Integrations\EInvoice\Exceptions;

use RuntimeException;

/** Nhà cung cấp HĐĐT thiếu credentials. Caller bắt → 422. */
class EInvoiceNotConfigured extends RuntimeException
{
    public static function for(string $provider, string $missing): self
    {
        return new self("Nhà cung cấp HĐĐT `{$provider}` chưa cấu hình — thiếu `{$missing}`.");
    }
}
```

`app/app/Integrations/EInvoice/Exceptions/EInvoiceProviderError.php`:
```php
<?php

namespace CMBcoreSeller\Integrations\EInvoice\Exceptions;

use RuntimeException;

/** Lỗi do nhà cung cấp HĐĐT trả về. Mang mã lỗi gốc + phân loại retry. */
class EInvoiceProviderError extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly string $errorClass = 'non_retryable',
    ) {
        parent::__construct($message);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=EInvoiceDtoTest`
Expected: PASS (4 tests).

- [ ] **Step 5: Commit**

```bash
cd app && git add app/Integrations/EInvoice/DTO app/Integrations/EInvoice/Exceptions tests/Feature/EInvoice/EInvoiceDtoTest.php
git commit -m "feat(einvoice): DTO + exceptions cho trục tích hợp HĐĐT"
```

---

### Task 2: Bảng mã lỗi MISA (`MisaErrorMap`)

**Files:**
- Create: `app/app/Integrations/EInvoice/MisaMeInvoice/Support/MisaErrorMap.php`
- Test: `app/tests/Feature/EInvoice/MisaErrorMapTest.php`

**Interfaces:**
- Produces: `MisaErrorMap::classify(string $code): string` ('retryable'|'non_retryable'); `MisaErrorMap::message(string $code): string` (thông điệp tiếng Việt; fallback dùng chính `$code`).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\EInvoice;

use CMBcoreSeller\Integrations\EInvoice\MisaMeInvoice\Support\MisaErrorMap;
use Tests\TestCase;

class MisaErrorMapTest extends TestCase
{
    public function test_transient_codes_are_retryable(): void
    {
        $this->assertSame('retryable', MisaErrorMap::classify('TokenExpiredCode'));
        $this->assertSame('retryable', MisaErrorMap::classify('InvoiceNumberNotContinuous'));
        $this->assertSame('retryable', MisaErrorMap::classify('Exception'));
    }

    public function test_business_codes_are_non_retryable(): void
    {
        $this->assertSame('non_retryable', MisaErrorMap::classify('InvalidTaxCode'));
        $this->assertSame('non_retryable', MisaErrorMap::classify('LicenseInfo_OutOfInvoice'));
        $this->assertSame('non_retryable', MisaErrorMap::classify('SomethingUnknown'));
    }

    public function test_message_is_vietnamese_with_fallback(): void
    {
        $this->assertStringContainsString('Token', MisaErrorMap::message('TokenExpiredCode'));
        $this->assertSame('XYZ_UNKNOWN', MisaErrorMap::message('XYZ_UNKNOWN'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=MisaErrorMapTest`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement `MisaErrorMap`**

```php
<?php

namespace CMBcoreSeller\Integrations\EInvoice\MisaMeInvoice\Support;

/**
 * Mã lỗi MISA meInvoice → phân loại retry + thông điệp tiếng Việt.
 * Nguồn: https://doc.meinvoice.vn/api/ (ErrorCode).
 */
final class MisaErrorMap
{
    /** Lỗi tạm thời — job có thể retry. */
    private const RETRYABLE = [
        'TokenExpiredCode', 'InvalidTokenCode', 'InvoiceNumberNotContinuous',
        'InvoiceDuplicated', 'DuplicateInvoiceRefID', 'Exception',
    ];

    /** @var array<string, string> */
    private const MESSAGES = [
        'TokenExpiredCode' => 'Token đã hết hạn, hệ thống sẽ tự lấy lại.',
        'InvalidTokenCode' => 'Token không hợp lệ, cần đăng nhập lại.',
        'UnAuthorize' => 'Sai tài khoản hoặc mật khẩu MISA.',
        'InvalidAppID' => 'AppID không hợp lệ — liên hệ MISA để cấp.',
        'InactiveAppID' => 'AppID chưa được kích hoạt.',
        'InvoiceNumberNotContinuous' => 'Số hóa đơn không liên tục, sẽ thử lại.',
        'InvoiceDuplicated' => 'Hóa đơn đã được phát hành trước đó.',
        'DuplicateInvoiceRefID' => 'Trùng mã tham chiếu hóa đơn (RefID).',
        'InvalidTaxCode' => 'Mã số thuế không hợp lệ.',
        'InvalidInvoiceDate' => 'Ngày hóa đơn không hợp lệ (nhỏ hơn hóa đơn cuối).',
        'LicenseInfo_OutOfInvoice' => 'Đã hết số lượng hóa đơn — cần mua thêm.',
        'LicenseInfo_Expired' => 'Gói hóa đơn đã hết hạn/chưa thanh toán.',
        'LicenseInfo_NotBuy' => 'Chưa mua gói hóa đơn.',
        'InvalidXMLData' => 'Dữ liệu hóa đơn không hợp lệ.',
        'InvoiceQuantityTooLarge' => 'Vượt quá 50 hóa đơn mỗi lần gửi.',
    ];

    public static function classify(string $code): string
    {
        return in_array($code, self::RETRYABLE, true) ? 'retryable' : 'non_retryable';
    }

    public static function message(string $code): string
    {
        return self::MESSAGES[$code] ?? $code;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=MisaErrorMapTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
cd app && git add app/Integrations/EInvoice/MisaMeInvoice/Support/MisaErrorMap.php tests/Feature/EInvoice/MisaErrorMapTest.php
git commit -m "feat(einvoice): bảng mã lỗi MISA + phân loại retry"
```

---

### Task 3: Config block + `EInvoiceRegistry`

**Files:**
- Modify: `app/config/integrations.php` (thêm block `einvoice` — đặt cạnh block `payments`)
- Create: `app/app/Integrations/EInvoice/EInvoiceRegistry.php`
- Test: `app/tests/Feature/EInvoice/EInvoiceRegistryTest.php`

**Interfaces:**
- Consumes: `EInvoiceConnector` (Task 4 — interface). Để tránh phụ thuộc vòng khi test Task 3, test ở đây chỉ kiểm `has()/providers()` với một class giả implement interface tối thiểu sau khi Task 4 xong. **Thứ tự thực thi: làm Task 4 trước nếu chạy tuần tự, hoặc giữ test này chỉ kiểm cấu trúc registry không cần connector thật.**
- Produces:
  - `EInvoiceRegistry::__construct(Container $container)`; `register(string $provider, string $connectorClass): void`; `has(string $provider): bool`; `providers(): array`; `for(string $provider): EInvoiceConnector`.
  - `config('integrations.einvoice.enabled')` = CSV; `config('integrations.einvoice.misa.base_url')`, `.misa.http.timeout|retries|retry_sleep_ms`, `.misa.token_ttl_days`.

- [ ] **Step 1: Thêm block config**

Trong `app/config/integrations.php`, ngay sau block `'payments' => [ ... ],` thêm:
```php
/*
|--------------------------------------------------------------------------
| Hóa đơn điện tử (SPEC 0041 — EInvoice). Trục Connector+Registry mới.
|--------------------------------------------------------------------------
|
| Thêm nhà cung cấp = 1 dòng class-string trong IntegrationsServiceProvider
| + 1 block ở đây. `enabled` CSV quyết định provider nào nạp vào EInvoiceRegistry.
| Credentials KHÔNG ở đây — lưu per-tenant trong bảng einvoice_accounts (mã hóa).
| INERT tới khi set INTEGRATIONS_EINVOICE. base_url mặc định là sandbox test.
*/
'einvoice' => [
    'enabled' => array_filter(explode(',', (string) env('INTEGRATIONS_EINVOICE', ''))),

    'misa' => [
        'base_url' => env('EINVOICE_MISA_BASE_URL', 'https://testapi.meinvoice.vn/api/v3'),
        'token_ttl_days' => (int) env('EINVOICE_MISA_TOKEN_TTL_DAYS', 14),
        'http' => [
            'timeout' => (int) env('EINVOICE_MISA_HTTP_TIMEOUT', 30),
            'retries' => (int) env('EINVOICE_MISA_HTTP_RETRIES', 2),
            'retry_sleep_ms' => (int) env('EINVOICE_MISA_HTTP_RETRY_SLEEP_MS', 800),
        ],
    ],
],
```

- [ ] **Step 2: Write the failing test**

```php
<?php

namespace Tests\Feature\EInvoice;

use CMBcoreSeller\Integrations\EInvoice\EInvoiceRegistry;
use CMBcoreSeller\Integrations\EInvoice\MisaMeInvoice\MisaMeInvoiceConnector;
use Tests\TestCase;

class EInvoiceRegistryTest extends TestCase
{
    public function test_registry_registers_and_resolves_provider(): void
    {
        $registry = new EInvoiceRegistry($this->app);
        $this->app->bind(MisaMeInvoiceConnector::class, fn () => new MisaMeInvoiceConnector(
            (array) config('integrations.einvoice.misa', [])
        ));
        $registry->register('misa', MisaMeInvoiceConnector::class);

        $this->assertTrue($registry->has('misa'));
        $this->assertContains('misa', $registry->providers());
        $this->assertInstanceOf(MisaMeInvoiceConnector::class, $registry->for('misa'));
    }

    public function test_unknown_provider_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new EInvoiceRegistry($this->app))->for('nope');
    }
}
```

- [ ] **Step 3: Run test to verify it fails**

Run: `php artisan test --filter=EInvoiceRegistryTest`
Expected: FAIL — `EInvoiceRegistry` not found (và `MisaMeInvoiceConnector` chưa có — Task 4/5). Nếu chạy tuần tự, hoàn tất Task 4 & 5 trước khi chạy lại bước này.

- [ ] **Step 4: Implement `EInvoiceRegistry`**

```php
<?php

namespace CMBcoreSeller\Integrations\EInvoice;

use CMBcoreSeller\Integrations\EInvoice\Contracts\EInvoiceConnector;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Tập các nhà cung cấp HĐĐT đã đăng ký, key theo provider code.
 * Đăng ký trong IntegrationsServiceProvider, gated bởi config('integrations.einvoice.enabled').
 * Module EInvoice PHẢI đi qua registry — không `new MisaMeInvoiceConnector()` trực tiếp.
 */
class EInvoiceRegistry
{
    /** @var array<string, class-string<EInvoiceConnector>> */
    protected array $connectors = [];

    public function __construct(protected Container $container) {}

    /** @param class-string<EInvoiceConnector> $connectorClass */
    public function register(string $provider, string $connectorClass): void
    {
        $this->connectors[$provider] = $connectorClass;
    }

    public function has(string $provider): bool
    {
        return isset($this->connectors[$provider]);
    }

    /** @return list<string> */
    public function providers(): array
    {
        return array_keys($this->connectors);
    }

    public function for(string $provider): EInvoiceConnector
    {
        if (! $this->has($provider)) {
            throw new InvalidArgumentException("Chưa đăng ký nhà cung cấp HĐĐT [{$provider}].");
        }

        return $this->container->make($this->connectors[$provider]);
    }
}
```

- [ ] **Step 5: Run test to verify it passes** (sau khi Task 4 & 5 xong)

Run: `php artisan test --filter=EInvoiceRegistryTest`
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
cd app && git add config/integrations.php app/Integrations/EInvoice/EInvoiceRegistry.php tests/Feature/EInvoice/EInvoiceRegistryTest.php
git commit -m "feat(einvoice): config block + EInvoiceRegistry"
```

---

### Task 4: `EInvoiceConnector` interface

**Files:**
- Create: `app/app/Integrations/EInvoice/Contracts/EInvoiceConnector.php`
- Test: (không cần test riêng — interface; được kiểm gián tiếp qua Task 5)

**Interfaces:**
- Produces (CHỈ các method của Phần A; Phần B sẽ MỞ RỘNG interface thêm `issue/preview/cancel/adjust/replace/status/download` + DTO tương ứng):
  - `code(): string`
  - `displayName(): string`
  - `capabilities(): array<string,bool>`
  - `supports(string $capability): bool`
  - `assertConfigured(array $account): void`
  - `verifyCredentials(array $account): array{ok:bool, message:string, expires_at?:?string, error_code?:string}`
  - `getCompanyInfo(array $account): CompanyInfoDTO`
  - `templates(array $account, int $year): array` (list `TemplateDTO`)

- [ ] **Step 1: Write the interface**

```php
<?php

namespace CMBcoreSeller\Integrations\EInvoice\Contracts;

use CMBcoreSeller\Integrations\EInvoice\DTO\CompanyInfoDTO;
use CMBcoreSeller\Integrations\EInvoice\Exceptions\EInvoiceNotConfigured;
use CMBcoreSeller\Integrations\EInvoice\Exceptions\UnsupportedOperation;

/**
 * Hợp đồng mọi nhà cung cấp hóa đơn điện tử phải implement (MISA, VNPT, Viettel...).
 *
 * QUY TẮC VÀNG (như ChannelConnector/CarrierConnector/PaymentGatewayConnector):
 *   - Core không biết tên cụ thể của nhà cung cấp HĐĐT.
 *   - Thêm provider = 1 class + 1 dòng register trong EInvoiceRegistry + 1 block config.
 *   - Không `if ($provider === 'misa')` trong module EInvoice.
 *
 * Credentials per-tenant truyền qua tham số đầu `array $account` (giống CarrierConnector),
 * KHÔNG bake vào config. Thao tác không hỗ trợ ⇒ ném {@see UnsupportedOperation};
 * thiếu cấu hình ⇒ ném {@see EInvoiceNotConfigured}.
 *
 * (Phần B mở rộng interface này: issue/preview/cancel/adjust/replace/status/download.)
 */
interface EInvoiceConnector
{
    /** Stable code: 'misa'. */
    public function code(): string;

    public function displayName(): string;

    /** @return array<string, bool> vd ['verify'=>true,'company_info'=>true,'templates'=>true,'issue_hsm'=>false,'issue_mtt'=>false]. */
    public function capabilities(): array;

    public function supports(string $capability): bool;

    /**
     * @param array<string, mixed> $account
     * @throws EInvoiceNotConfigured
     */
    public function assertConfigured(array $account): void;

    /**
     * @param array<string, mixed> $account
     * @return array{ok:bool, message:string, expires_at?:?string, error_code?:string}
     */
    public function verifyCredentials(array $account): array;

    /** @param array<string, mixed> $account */
    public function getCompanyInfo(array $account): CompanyInfoDTO;

    /**
     * @param array<string, mixed> $account
     * @return list<\CMBcoreSeller\Integrations\EInvoice\DTO\TemplateDTO>
     */
    public function templates(array $account, int $year): array;
}
```

- [ ] **Step 2: Commit** (cùng với Task 5 — interface một mình chưa test được)

---

### Task 5: `MisaClient` + `MisaMeInvoiceConnector`

**Files:**
- Create: `app/app/Integrations/EInvoice/MisaMeInvoice/MisaClient.php`
- Create: `app/app/Integrations/EInvoice/MisaMeInvoice/MisaMeInvoiceConnector.php`
- Test: `app/tests/Feature/EInvoice/MisaConnectorTest.php`

**Interfaces:**
- Consumes: `EInvoiceConnector` (Task 4), DTOs/Exceptions (Task 1), `MisaErrorMap` (Task 2).
- Produces:
  - `MisaClient::__construct(array $config, array $credentials)`; `token(): string`; `companyInfoRaw(): array`; `templatesRaw(int $year): array`.
  - `MisaMeInvoiceConnector::__construct(array $config)` implements `EInvoiceConnector`.

- [ ] **Step 1: Write the failing test** (dùng `Http::fake` theo URL glob)

```php
<?php

namespace Tests\Feature\EInvoice;

use CMBcoreSeller\Integrations\EInvoice\MisaMeInvoice\MisaMeInvoiceConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MisaConnectorTest extends TestCase
{
    private function account(): array
    {
        return ['credentials' => [
            'appid' => 'APP1', 'taxcode' => '0105922241', 'username' => 'user', 'password' => 'pass',
        ]];
    }

    private function connector(): MisaMeInvoiceConnector
    {
        return new MisaMeInvoiceConnector(['base_url' => 'https://testapi.meinvoice.vn/api/v3', 'token_ttl_days' => 14, 'http' => ['timeout' => 30, 'retries' => 0, 'retry_sleep_ms' => 0]]);
    }

    public function test_verify_credentials_ok_when_token_and_company_succeed(): void
    {
        Http::fake([
            '*/auth/token' => Http::response(['Success' => true, 'Data' => 'TOKEN123', 'ErrorCode' => '']),
            '*/company*' => Http::response(['Success' => true, 'ErrorCode' => '', 'Data' => json_encode([
                'CompanyName' => 'Cty ABC', 'CompanyTaxCode' => '0105922241', 'IsInvoiceWithCode' => true,
            ])]),
        ]);

        $r = $this->connector()->verifyCredentials($this->account());
        $this->assertTrue($r['ok']);
        $this->assertStringContainsString('ABC', $r['message']);

        Http::assertSent(fn ($req) => str_contains($req->url(), '/auth/token')
            && ($req->data()['appid'] ?? null) === 'APP1');
    }

    public function test_verify_credentials_fails_on_unauthorize(): void
    {
        Http::fake([
            '*/auth/token' => Http::response(['Success' => false, 'ErrorCode' => 'UnAuthorize', 'Data' => null]),
        ]);

        $r = $this->connector()->verifyCredentials($this->account());
        $this->assertFalse($r['ok']);
        $this->assertSame('invalid_credentials', $r['error_code']);
    }

    public function test_verify_credentials_fails_fast_when_missing_field(): void
    {
        $r = $this->connector()->verifyCredentials(['credentials' => ['appid' => 'A']]);
        $this->assertFalse($r['ok']);
        $this->assertSame('invalid_credentials', $r['error_code']);
    }

    public function test_templates_parses_stringified_data_array(): void
    {
        Http::fake([
            '*/auth/token' => Http::response(['Success' => true, 'Data' => 'TOKEN123', 'ErrorCode' => '']),
            '*/itg/InvoicePublishing/templates*' => Http::response(['Success' => true, 'ErrorCode' => '', 'Data' => json_encode([
                ['IPTemplateID' => 'g1', 'TemplateName' => '01GTKT', 'InvSeries' => '1C25TAA', 'InvoiceType' => 1, 'IsPublished' => true, 'Inactive' => false],
            ])]),
        ]);

        $list = $this->connector()->templates($this->account(), 2026);
        $this->assertCount(1, $list);
        $this->assertSame('1C25TAA', $list[0]->invSeries);
    }

    public function test_capabilities_phase_a(): void
    {
        $c = $this->connector();
        $this->assertSame('misa', $c->code());
        $this->assertTrue($c->supports('company_info'));
        $this->assertFalse($c->supports('issue_hsm'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=MisaConnectorTest`
Expected: FAIL — `MisaMeInvoiceConnector` not found.

- [ ] **Step 3: Implement `MisaClient`**

```php
<?php

namespace CMBcoreSeller\Integrations\EInvoice\MisaMeInvoice;

use CMBcoreSeller\Integrations\EInvoice\Exceptions\EInvoiceProviderError;
use CMBcoreSeller\Integrations\EInvoice\MisaMeInvoice\Support\MisaErrorMap;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * HTTP client cô lập cho MISA meInvoice Open API v3.
 * - Token cache theo (appid, taxcode, username), TTL = token_ttl_days - 1.
 * - Parse envelope 2 tầng: {Success, Data(JSON string), ErrorCode, Errors}.
 */
final class MisaClient
{
    public function __construct(
        private readonly array $config,        // base_url, token_ttl_days, http{timeout,retries,retry_sleep_ms}
        private readonly array $credentials,   // appid, taxcode, username, password
    ) {}

    private function baseUrl(): string
    {
        return rtrim((string) ($this->config['base_url'] ?? ''), '/');
    }

    private function timeout(): int
    {
        return (int) ($this->config['http']['timeout'] ?? 30);
    }

    private function cred(string $k): string
    {
        return (string) ($this->credentials[$k] ?? '');
    }

    public function token(): string
    {
        $ttl = max(1, (int) ($this->config['token_ttl_days'] ?? 14) - 1);
        $key = 'einvoice:misa:token:'.md5($this->cred('appid').'|'.$this->cred('taxcode').'|'.$this->cred('username'));

        return Cache::remember($key, now()->addDays($ttl), function () {
            $res = Http::baseUrl($this->baseUrl())->timeout($this->timeout())->acceptJson()
                ->post('/auth/token', [
                    'appid' => $this->cred('appid'),
                    'taxcode' => $this->cred('taxcode'),
                    'username' => $this->cred('username'),
                    'password' => $this->cred('password'),
                ]);
            $token = $this->unwrap($res);
            if (! is_string($token) || $token === '') {
                throw new EInvoiceProviderError('UnAuthorize', 'MISA không trả về token.');
            }

            return $token;
        });
    }

    private function http(): PendingRequest
    {
        $retries = (int) ($this->config['http']['retries'] ?? 0);
        $sleep = (int) ($this->config['http']['retry_sleep_ms'] ?? 0);
        $req = Http::baseUrl($this->baseUrl())
            ->withToken($this->token())
            ->withHeaders(['CompanyTaxCode' => $this->cred('taxcode')])
            ->timeout($this->timeout())->acceptJson();

        return $retries > 0 ? $req->retry($retries, $sleep) : $req;
    }

    /** Parse envelope 2 tầng; ném EInvoiceProviderError nếu Success=false hoặc ErrorCode ngoài != ''. */
    private function unwrap(Response $res): mixed
    {
        $body = $res->json();
        if (! is_array($body)) {
            throw new EInvoiceProviderError('Exception', 'MISA trả phản hồi không hợp lệ.', 'retryable');
        }
        $success = (bool) ($body['Success'] ?? false);
        $errorCode = (string) ($body['ErrorCode'] ?? '');
        if (! $success || $errorCode !== '') {
            $code = $errorCode !== '' ? $errorCode : 'Exception';
            throw new EInvoiceProviderError($code, MisaErrorMap::message($code), MisaErrorMap::classify($code));
        }
        $data = $body['Data'] ?? null;
        if (is_string($data)) {
            $decoded = json_decode($data, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $data;
        }

        return $data;
    }

    /** GET /company?taxcode= — trả mảng thông tin công ty. */
    public function companyInfoRaw(): array
    {
        $data = $this->unwrap($this->http()->get('/company', ['taxcode' => $this->cred('taxcode')]));

        return is_array($data) ? $data : [];
    }

    /** GET /itg/InvoicePublishing/templates?invyear= — list mẫu HĐ. */
    public function templatesRaw(int $year): array
    {
        $data = $this->unwrap($this->http()->get('/itg/InvoicePublishing/templates', ['invyear' => $year]));

        return is_array($data) ? array_values($data) : [];
    }
}
```

- [ ] **Step 4: Implement `MisaMeInvoiceConnector`**

```php
<?php

namespace CMBcoreSeller\Integrations\EInvoice\MisaMeInvoice;

use CMBcoreSeller\Integrations\EInvoice\Contracts\EInvoiceConnector;
use CMBcoreSeller\Integrations\EInvoice\DTO\CompanyInfoDTO;
use CMBcoreSeller\Integrations\EInvoice\DTO\TemplateDTO;
use CMBcoreSeller\Integrations\EInvoice\Exceptions\EInvoiceNotConfigured;
use CMBcoreSeller\Integrations\EInvoice\Exceptions\EInvoiceProviderError;

/** Connector MISA meInvoice. Phần A: verify + company info + templates. */
final class MisaMeInvoiceConnector implements EInvoiceConnector
{
    public function __construct(protected array $config) {}

    public function code(): string
    {
        return 'misa';
    }

    public function displayName(): string
    {
        return 'MISA meInvoice';
    }

    public function capabilities(): array
    {
        return [
            'verify' => true, 'company_info' => true, 'templates' => true,
            // Phần B bật: 'issue_hsm', 'issue_mtt', 'preview', 'status', 'download', 'cancel', 'adjust', 'replace'.
            'issue_hsm' => false, 'issue_mtt' => false,
        ];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    public function assertConfigured(array $account): void
    {
        $cred = (array) ($account['credentials'] ?? []);
        foreach (['appid', 'taxcode', 'username', 'password'] as $k) {
            if (empty($cred[$k])) {
                throw EInvoiceNotConfigured::for('misa', $k);
            }
        }
    }

    public function verifyCredentials(array $account): array
    {
        try {
            $this->assertConfigured($account);
        } catch (EInvoiceNotConfigured $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'error_code' => 'invalid_credentials', 'expires_at' => null];
        }

        try {
            $info = $this->client($account)->companyInfoRaw();
            $name = (string) ($info['CompanyName'] ?? $info['CompanyTaxCode'] ?? '');

            return ['ok' => true, 'message' => 'Kết nối MISA meInvoice OK'.($name !== '' ? ' — '.$name : '').'.', 'expires_at' => null];
        } catch (EInvoiceProviderError $e) {
            return [
                'ok' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->errorClass === 'retryable' ? 'network' : 'invalid_credentials',
                'expires_at' => null,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Lỗi kết nối MISA: '.$e->getMessage(), 'error_code' => 'network', 'expires_at' => null];
        }
    }

    public function getCompanyInfo(array $account): CompanyInfoDTO
    {
        $this->assertConfigured($account);

        return CompanyInfoDTO::fromMisa($this->client($account)->companyInfoRaw());
    }

    public function templates(array $account, int $year): array
    {
        $this->assertConfigured($account);

        return array_map(
            static fn (array $raw) => TemplateDTO::fromMisa($raw),
            $this->client($account)->templatesRaw($year),
        );
    }

    private function client(array $account): MisaClient
    {
        return new MisaClient($this->config, (array) ($account['credentials'] ?? []));
    }
}
```

- [ ] **Step 5: Run tests**

Run: `php artisan test --filter=MisaConnectorTest`
Expected: PASS (5 tests). Then run `--filter=EInvoiceRegistryTest` → PASS.

- [ ] **Step 6: Commit**

```bash
cd app && git add app/Integrations/EInvoice/Contracts app/Integrations/EInvoice/MisaMeInvoice tests/Feature/EInvoice/MisaConnectorTest.php
git commit -m "feat(einvoice): MisaClient + MisaMeInvoiceConnector (verify/company/templates)"
```

---

### Task 6: Wiring connector vào `IntegrationsServiceProvider`

**Files:**
- Modify: `app/app/Integrations/IntegrationsServiceProvider.php`
- Test: `app/tests/Feature/EInvoice/EInvoiceWiringTest.php`

**Interfaces:**
- Produces: `app(EInvoiceRegistry::class)` resolve được; khi `config('integrations.einvoice.enabled')` chứa `misa` thì `has('misa')` true.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\EInvoice;

use CMBcoreSeller\Integrations\EInvoice\EInvoiceRegistry;
use CMBcoreSeller\Integrations\EInvoice\MisaMeInvoice\MisaMeInvoiceConnector;
use Tests\TestCase;

class EInvoiceWiringTest extends TestCase
{
    public function test_registry_resolved_from_container_with_misa_enabled(): void
    {
        config(['integrations.einvoice.enabled' => ['misa']]);
        $this->refreshApplication(); // re-run providers with new config
        config(['integrations.einvoice.enabled' => ['misa']]);

        $registry = $this->app->make(EInvoiceRegistry::class);
        $this->assertTrue($registry->has('misa'));
        $this->assertInstanceOf(MisaMeInvoiceConnector::class, $registry->for('misa'));
    }
}
```

> Lưu ý: vì singleton đọc config lúc khởi tạo, set config TRƯỚC khi `make`. Nếu `refreshApplication()` gây phiền, thay bằng: trong test set `config([...])` rồi `$this->app->forgetInstance(EInvoiceRegistry::class)` trước `make`.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=EInvoiceWiringTest`
Expected: FAIL — registry not bound / has('misa') false.

- [ ] **Step 3: Thêm wiring**

Trong `IntegrationsServiceProvider.php`:

(a) Thêm imports đầu file:
```php
use CMBcoreSeller\Integrations\EInvoice\EInvoiceRegistry;
use CMBcoreSeller\Integrations\EInvoice\MisaMeInvoice\MisaMeInvoiceConnector;
```

(b) Thêm property (cạnh `$paymentConnectors`):
```php
/**
 * Nhà cung cấp HĐĐT (SPEC 0041). EInvoiceRegistry chỉ nạp provider có trong
 * config('integrations.einvoice.enabled').
 *
 * @var array<string, class-string>
 */
protected array $einvoiceConnectors = [
    'misa' => MisaMeInvoiceConnector::class,
];
```

(c) Trong `register()`, cạnh block `singleton(PaymentRegistry::class ...)`:
```php
// Hóa đơn điện tử (SPEC 0041).
$this->app->singleton(EInvoiceRegistry::class, function ($app) {
    $registry = new EInvoiceRegistry($app);
    foreach ((array) config('integrations.einvoice.enabled', []) as $code) {
        $code = trim((string) $code);
        if ($code !== '' && isset($this->einvoiceConnectors[$code])) {
            $registry->register($code, $this->einvoiceConnectors[$code]);
        }
    }

    return $registry;
});
```

(d) Cạnh các `bind(SePayConnector::class ...)`:
```php
$this->app->bind(MisaMeInvoiceConnector::class, fn () => new MisaMeInvoiceConnector(
    (array) config('integrations.einvoice.misa', [])
));
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=EInvoiceWiringTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
cd app && git add app/Integrations/IntegrationsServiceProvider.php tests/Feature/EInvoice/EInvoiceWiringTest.php
git commit -m "feat(einvoice): wire EInvoiceRegistry + MISA connector vào IntegrationsServiceProvider"
```

---

### Task 7: Model `EInvoiceAccount` + migration

**Files:**
- Create: `app/app/Modules/EInvoice/Models/EInvoiceAccount.php`
- Create: `app/app/Modules/EInvoice/Database/Migrations/2026_06_30_100001_create_einvoice_accounts_table.php`
- Test: `app/tests/Feature/EInvoice/EInvoiceAccountModelTest.php`

**Interfaces:**
- Produces: `EInvoiceAccount` (fillable: tenant_id, provider, name, credentials, is_invoice_with_code, default_mode, templates, seller_info, auto_issue, meta, is_default, is_active); casts `credentials => encrypted:array`, json arrays, booleans; `toConnectorArray(): array{id,provider,credentials,meta}`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\EInvoice;

use CMBcoreSeller\Modules\EInvoice\Models\EInvoiceAccount;
use CMBcoreSeller\Modules\Tenancy\Database\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EInvoiceAccountModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_credentials_are_encrypted_and_connector_array_shaped(): void
    {
        $acc = EInvoiceAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => 1, 'provider' => 'misa', 'name' => 'MISA chính',
            'credentials' => ['appid' => 'A', 'taxcode' => '010', 'username' => 'u', 'password' => 'p'],
            'default_mode' => 'hsm',
        ]);

        // Cột thô trong DB là chuỗi mã hóa, không phải plaintext JSON.
        $raw = \DB::table('einvoice_accounts')->where('id', $acc->id)->value('credentials');
        $this->assertStringNotContainsString('appid', (string) $raw);

        $arr = $acc->toConnectorArray();
        $this->assertSame('misa', $arr['provider']);
        $this->assertSame('A', $arr['credentials']['appid']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=EInvoiceAccountModelTest`
Expected: FAIL — model/table not found.

- [ ] **Step 3: Write the migration**

`...2026_06_30_100001_create_einvoice_accounts_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** einvoice_accounts — credentials per-tenant cho nhà cung cấp HĐĐT (MISA...). SPEC 0041. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('einvoice_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('provider');                       // 'misa'
            $table->string('name');                           // alias gợi nhớ
            $table->text('credentials')->nullable();          // encrypted:array (appid/taxcode/username/password)
            $table->boolean('is_invoice_with_code')->nullable(); // cache từ company info
            $table->string('default_mode')->default('hsm');   // 'hsm' | 'mtt' — mặc định đơn manual
            $table->json('templates')->nullable();            // {hsm:{template_id,inv_series}, mtt:{...}}
            $table->json('seller_info')->nullable();          // thông tin người bán mặc định
            $table->json('auto_issue')->nullable();           // cấu hình tự động (Phần B/P2)
            $table->json('meta')->nullable();                 // last_verified_at...
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'provider', 'name']);
            $table->index(['tenant_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('einvoice_accounts');
    }
};
```

- [ ] **Step 4: Write the model**

`app/app/Modules/EInvoice/Models/EInvoiceAccount.php`:
```php
<?php

namespace CMBcoreSeller\Modules\EInvoice\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Credentials của một tenant cho một nhà cung cấp HĐĐT (MISA...). `credentials`
 * mã hóa at-rest. Mirror CarrierAccount. SPEC 0041.
 */
class EInvoiceAccount extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'provider', 'name', 'credentials', 'is_invoice_with_code',
        'default_mode', 'templates', 'seller_info', 'auto_issue', 'meta', 'is_default', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'is_invoice_with_code' => 'boolean',
            'templates' => 'array',
            'seller_info' => 'array',
            'auto_issue' => 'array',
            'meta' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** Shape truyền vào EInvoiceConnector (tham số `$account`). */
    public function toConnectorArray(): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'credentials' => $this->credentials ?? [],
            'meta' => $this->meta ?? [],
        ];
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=EInvoiceAccountModelTest`
Expected: PASS. (Migration tự chạy nhờ `RefreshDatabase` + `loadMigrationsFrom` ở Task 8 — nếu provider chưa đăng ký, test có thể không thấy migration; vì vậy Task 8 đăng ký provider phải xong để migration được nạp. Nếu chạy tuần tự, hoàn tất Task 8 rồi chạy lại bước này.)

- [ ] **Step 6: Commit**

```bash
cd app && git add app/Modules/EInvoice/Models app/Modules/EInvoice/Database tests/Feature/EInvoice/EInvoiceAccountModelTest.php
git commit -m "feat(einvoice): model + migration einvoice_accounts (credentials mã hóa)"
```

---

### Task 8: `EInvoiceServiceProvider` + đăng ký + routes file rỗng

**Files:**
- Create: `app/app/Modules/EInvoice/EInvoiceServiceProvider.php`
- Create: `app/app/Modules/EInvoice/Http/routes.php` (khung — route thật ở Task 11)
- Modify: `app/bootstrap/providers.php`
- Test: `app/tests/Feature/EInvoice/EInvoiceAccountModelTest.php` (đã có; giờ phải PASS nhờ migration được nạp)

**Interfaces:**
- Produces: provider `loadMigrationsFrom` + `loadRoutesFrom` (guard `is_file`).

- [ ] **Step 1: Write the provider**

```php
<?php

namespace CMBcoreSeller\Modules\EInvoice;

use Illuminate\Support\ServiceProvider;

class EInvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Phần B: bind Contracts → Services (IssueInvoiceContract...) + Event::listen.
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        if (is_file(__DIR__.'/Http/routes.php')) {
            $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        }
    }
}
```

- [ ] **Step 2: Tạo routes file khung**

`app/app/Modules/EInvoice/Http/routes.php`:
```php
<?php

use Illuminate\Support\Facades\Route;

// Routes HĐĐT — group + controller được thêm ở Task 11.
Route::middleware(['api', 'auth:sanctum', 'verified', 'tenant', 'plan.feature:einvoice'])
    ->prefix('api/v1/einvoice')->group(function () {
        // (Task 11) account routes
    });
```

- [ ] **Step 3: Đăng ký provider**

Trong `app/bootstrap/providers.php`: thêm `use CMBcoreSeller\Modules\EInvoice\EInvoiceServiceProvider;` và thêm `EInvoiceServiceProvider::class,` vào mảng return (cạnh `AccountingServiceProvider::class`).

- [ ] **Step 4: Run test**

Run: `php artisan test --filter=EInvoiceAccountModelTest`
Expected: PASS (migration giờ được nạp).

- [ ] **Step 5: Commit**

```bash
cd app && git add app/Modules/EInvoice/EInvoiceServiceProvider.php app/Modules/EInvoice/Http/routes.php bootstrap/providers.php
git commit -m "feat(einvoice): EInvoiceServiceProvider + đăng ký module"
```

---

### Task 9: RBAC — quyền `einvoice.*`

**Files:**
- Modify: `app/app/Modules/Tenancy/Support/PermissionCatalog.php`
- Test: `app/tests/Feature/EInvoice/EInvoicePermissionCatalogTest.php`

**Interfaces:**
- Produces: nhóm quyền `einvoice` với keys `einvoice.view`, `einvoice.config`, `einvoice.issue`, `einvoice.manage`; có trong `PermissionCatalog::all()`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\EInvoice;

use CMBcoreSeller\Modules\Tenancy\Support\PermissionCatalog;
use Tests\TestCase;

class EInvoicePermissionCatalogTest extends TestCase
{
    public function test_einvoice_permissions_registered(): void
    {
        $all = PermissionCatalog::all();
        foreach (['einvoice.view', 'einvoice.config', 'einvoice.issue', 'einvoice.manage'] as $k) {
            $this->assertContains($k, $all, "Thiếu quyền {$k}");
        }
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=EInvoicePermissionCatalogTest`
Expected: FAIL.

- [ ] **Step 3: Thêm group vào `PermissionCatalog::groups()`**

Trong mảng trả về của `groups()`, thêm (cạnh group `accounting`):
```php
['key' => 'einvoice', 'label' => 'Hóa đơn điện tử', 'permissions' => [
    ['key' => 'einvoice.view', 'label' => 'Xem hóa đơn điện tử', 'type' => 'view'],
    ['key' => 'einvoice.config', 'label' => 'Cấu hình nhà cung cấp HĐĐT', 'type' => 'action'],
    ['key' => 'einvoice.issue', 'label' => 'Phát hành hóa đơn', 'type' => 'action'],
    ['key' => 'einvoice.manage', 'label' => 'Hủy/Điều chỉnh/Thay thế hóa đơn', 'type' => 'action'],
]],
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=EInvoicePermissionCatalogTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
cd app && git add app/Modules/Tenancy/Support/PermissionCatalog.php tests/Feature/EInvoice/EInvoicePermissionCatalogTest.php
git commit -m "feat(einvoice): quyền RBAC einvoice.* trong PermissionCatalog"
```

---

### Task 10: Plan-gate feature `einvoice` (BE seeder + resync migration + 4 nơi FE)

**Files:**
- Modify: `app/app/Modules/Billing/Database/Seeders/BillingPlanSeeder.php` (thêm `'einvoice'` vào `featureKeys()`)
- Create: `app/app/Modules/Billing/Database/Migrations/2026_06_30_100002_resync_plan_features_einvoice.php`
- Modify: `app/resources/js/lib/billing.tsx` (interface `PlanFeatures` thêm `einvoice: boolean;`)
- Modify: `app/resources/js/admin/pages/tenants/AdminPlansPage.tsx` (`KNOWN_FEATURES` thêm `'einvoice'`)
- Modify: `app/resources/js/pages/PlansPage.tsx` (`FEATURE_ROWS` thêm `{ key: 'einvoice', label: 'Hóa đơn điện tử' }`)
- Modify: `app/resources/js/lib/planFeatures.ts` (`PLAN_FEATURE_LABELS` thêm `einvoice: 'Hóa đơn điện tử'`)
- Test: `app/tests/Feature/EInvoice/EInvoicePlanFeatureTest.php`

**Interfaces:**
- Produces: gói Pro `hasFeature('einvoice') === true`; gói starter/trial `false`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\EInvoice;

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EInvoicePlanFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_pro_plan_has_einvoice_feature(): void
    {
        $this->seed(BillingPlanSeeder::class);
        $pro = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        $this->assertTrue($pro->hasFeature('einvoice'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=EInvoicePlanFeatureTest`
Expected: FAIL — feature key missing.

- [ ] **Step 3: Thêm `'einvoice'` vào `BillingPlanSeeder::featureKeys()`** (cuối mảng).

- [ ] **Step 4: Tạo migration resync**

`...2026_06_30_100002_resync_plan_features_einvoice.php`:
```php
<?php

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Database\Seeders\TestUnlimitedPlanSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\App;

/** Resync catalog plan features sau khi thêm feature key 'einvoice'. SPEC 0041. */
return new class extends Migration
{
    public function up(): void
    {
        if (App::runningUnitTests()) {
            return;
        }
        (new BillingPlanSeeder)->run();
        (new TestUnlimitedPlanSeeder)->run();
    }

    public function down(): void
    {
        // catalog data — không revert.
    }
};
```

- [ ] **Step 5: Sửa 4 nơi FE** (theo danh sách Files ở trên — thêm `einvoice` vào interface, KNOWN_FEATURES, FEATURE_ROWS, PLAN_FEATURE_LABELS).

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=EInvoicePlanFeatureTest`
Expected: PASS.

- [ ] **Step 7: Verify FE typecheck**

Run: `cd app && npm run typecheck`
Expected: PASS (không lỗi type về `PlanFeatures.einvoice`).

- [ ] **Step 8: Commit**

```bash
cd app && git add app/Modules/Billing/Database resources/js/lib/billing.tsx resources/js/admin/pages/tenants/AdminPlansPage.tsx resources/js/pages/PlansPage.tsx resources/js/lib/planFeatures.ts tests/Feature/EInvoice/EInvoicePlanFeatureTest.php
git commit -m "feat(einvoice): plan-gate feature einvoice (seeder + resync + FE catalog)"
```

---

### Task 11: Controller + Resource + routes (CRUD + verify + company-info + templates)

**Files:**
- Create: `app/app/Modules/EInvoice/Http/Controllers/EInvoiceAccountController.php`
- Create: `app/app/Modules/EInvoice/Http/Resources/EInvoiceAccountResource.php`
- Modify: `app/app/Modules/EInvoice/Http/routes.php`
- Test: `app/tests/Feature/EInvoice/EInvoiceAccountApiTest.php`
- Test helper: `app/tests/Feature/EInvoice/EInvoiceTestHelpers.php`

**Interfaces:**
- Consumes: `EInvoiceRegistry` (Task 3/6), `EInvoiceAccount` (Task 7), plan-gate (Task 10), RBAC (Task 9).
- Produces endpoints (prefix `/api/v1/einvoice`):
  - `GET accounts` → `{data: EInvoiceAccountResource[]}` (`einvoice.view`)
  - `POST accounts` → `{data}` 201 (`einvoice.config`)
  - `PATCH accounts/{id}` → `{data}` (`einvoice.config`)
  - `DELETE accounts/{id}` → `{data:{deleted:true}}` (`einvoice.config`)
  - `POST accounts/{id}/verify` → `{data:{ok,message,error_code,expires_at,account}}` (`einvoice.config`)
  - `GET accounts/{id}/company-info` → `{data: CompanyInfoDTO}` (`einvoice.config`)
  - `GET accounts/{id}/templates?year=` → `{data: TemplateDTO[]}` (`einvoice.config`)
- `EInvoiceAccountResource`: KHÔNG lộ credentials; trả `credential_keys`.

- [ ] **Step 1: Write the test helper**

`app/tests/Feature/EInvoice/EInvoiceTestHelpers.php`:
```php
<?php

namespace Tests\Feature\EInvoice;

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Database\Scopes\TenantScope;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Support\Enums\Role;
use CMBcoreSeller\Models\User;

trait EInvoiceTestHelpers
{
    protected Tenant $tenant;
    protected User $owner;
    protected User $viewer;

    protected function setUpEInvoiceTenant(): void
    {
        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'EInvShop']);
        $this->owner = User::factory()->create(['email' => 'owner-'.uniqid().'@e.test']);
        $this->viewer = User::factory()->create(['email' => 'view-'.uniqid().'@e.test']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->tenant->users()->attach($this->viewer->getKey(), ['role' => Role::Viewer->value]);
        $this->activatePlan(Plan::CODE_PRO);
        config(['integrations.einvoice.enabled' => ['misa'], 'integrations.einvoice.misa.base_url' => 'https://testapi.meinvoice.vn/api/v3']);
        $this->app->forgetInstance(\CMBcoreSeller\Integrations\EInvoice\EInvoiceRegistry::class);
    }

    protected function activatePlan(string $code): void
    {
        Subscription::withoutGlobalScope(TenantScope::class)->where('tenant_id', $this->tenant->getKey())->delete();
        $plan = Plan::query()->where('code', $code)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->copy()->addMonth(),
        ]);
    }

    protected function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }
}
```

> Kiểm chứng tên: `Role::Viewer`, `Plan::CODE_PRO`, `Subscription::STATUS_ACTIVE/CYCLE_MONTHLY`, `User` namespace — đối chiếu `AccountingTestHelpers.php` thực tế trước khi chạy; sửa cho khớp nếu khác.

- [ ] **Step 2: Write the failing API test**

```php
<?php

namespace Tests\Feature\EInvoice;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EInvoiceAccountApiTest extends TestCase
{
    use EInvoiceTestHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpEInvoiceTenant();
    }

    private function payload(): array
    {
        return ['provider' => 'misa', 'name' => 'MISA chính', 'default_mode' => 'hsm',
            'credentials' => ['appid' => 'A', 'taxcode' => '0105922241', 'username' => 'u', 'password' => 'p']];
    }

    public function test_create_account_never_exposes_credentials(): void
    {
        Http::fake(['*' => Http::response(['Success' => true, 'Data' => 'TOKEN', 'ErrorCode' => ''])]);
        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/einvoice/accounts', $this->payload())->assertCreated();

        $resp->assertJsonPath('data.provider', 'misa')
            ->assertJsonPath('data.name', 'MISA chính');
        $this->assertContains('appid', $resp->json('data.credential_keys'));
        $this->assertArrayNotHasKey('credentials', $resp->json('data'));
    }

    public function test_verify_returns_ok_with_fake_misa(): void
    {
        Http::fake([
            '*/auth/token' => Http::response(['Success' => true, 'Data' => 'TOKEN', 'ErrorCode' => '']),
            '*/company*' => Http::response(['Success' => true, 'ErrorCode' => '', 'Data' => json_encode(['CompanyName' => 'Cty ABC', 'IsInvoiceWithCode' => true])]),
        ]);
        $create = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/einvoice/accounts', $this->payload())->assertCreated();
        $id = $create->json('data.id');

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/einvoice/accounts/{$id}/verify")
            ->assertOk()->assertJsonPath('data.ok', true);
    }

    public function test_viewer_cannot_configure(): void
    {
        $this->actingAs($this->viewer)->withHeaders($this->h())
            ->postJson('/api/v1/einvoice/accounts', $this->payload())
            ->assertStatus(403);
    }

    public function test_plan_locked_returns_402(): void
    {
        $this->activatePlan(\CMBcoreSeller\Modules\Billing\Models\Plan::CODE_STARTER);
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/einvoice/accounts')
            ->assertStatus(402)->assertJsonPath('error.code', 'PLAN_FEATURE_LOCKED');
    }
}
```

> `Plan::CODE_STARTER` — xác nhận hằng số gói không có `einvoice`; nếu starter cũng full thì dùng gói trial/free phù hợp.

- [ ] **Step 3: Run test to verify it fails**

Run: `php artisan test --filter=EInvoiceAccountApiTest`
Expected: FAIL — routes/controller chưa có.

- [ ] **Step 4: Write the Resource**

```php
<?php

namespace CMBcoreSeller\Modules\EInvoice\Http\Resources;

use CMBcoreSeller\Modules\EInvoice\Models\EInvoiceAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EInvoiceAccount — KHÔNG BAO GIỜ lộ credentials thô. */
class EInvoiceAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'name' => $this->name,
            'is_invoice_with_code' => $this->is_invoice_with_code,
            'default_mode' => $this->default_mode,
            'templates' => $this->templates ?? [],
            'seller_info' => $this->seller_info ?? [],
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'meta' => $this->meta ?? [],
            'credential_keys' => array_keys((array) ($this->credentials ?? [])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
```

- [ ] **Step 5: Write the controller**

```php
<?php

namespace CMBcoreSeller\Modules\EInvoice\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\EInvoice\EInvoiceRegistry;
use CMBcoreSeller\Modules\EInvoice\Http\Resources\EInvoiceAccountResource;
use CMBcoreSeller\Modules\EInvoice\Models\EInvoiceAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EInvoiceAccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('einvoice.view'), 403, 'Bạn không có quyền xem HĐĐT.');

        return response()->json(['data' => EInvoiceAccountResource::collection(
            EInvoiceAccount::query()->orderByDesc('is_default')->orderBy('id')->get()
        )]);
    }

    public function store(Request $request, EInvoiceRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('einvoice.config'), 403, 'Bạn không có quyền cấu hình HĐĐT.');
        $data = $request->validate([
            'provider' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:120'],
            'credentials' => ['sometimes', 'nullable', 'array'],
            'default_mode' => ['sometimes', 'in:hsm,mtt'],
            'templates' => ['sometimes', 'nullable', 'array'],
            'seller_info' => ['sometimes', 'nullable', 'array'],
            'is_default' => ['sometimes', 'boolean'],
        ]);
        abort_unless($registry->has($data['provider']), 422, 'Nhà cung cấp HĐĐT không được hỗ trợ.');

        $account = DB::transaction(function () use ($data) {
            if (! empty($data['is_default'])) {
                EInvoiceAccount::query()->update(['is_default' => false]);
            }

            return EInvoiceAccount::query()->create([
                'provider' => $data['provider'], 'name' => $data['name'],
                'credentials' => $data['credentials'] ?? [],
                'default_mode' => $data['default_mode'] ?? 'hsm',
                'templates' => $data['templates'] ?? null, 'seller_info' => $data['seller_info'] ?? null,
                'is_default' => (bool) ($data['is_default'] ?? false), 'is_active' => true,
            ]);
        });

        $this->runVerify($registry, $account);

        return response()->json(['data' => new EInvoiceAccountResource($account->refresh())], 201);
    }

    public function update(Request $request, int $id, EInvoiceRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('einvoice.config'), 403, 'Bạn không có quyền.');
        $account = EInvoiceAccount::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'credentials' => ['sometimes', 'nullable', 'array'],
            'default_mode' => ['sometimes', 'in:hsm,mtt'],
            'templates' => ['sometimes', 'nullable', 'array'],
            'seller_info' => ['sometimes', 'nullable', 'array'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $credChanged = false;
        DB::transaction(function () use ($account, &$data, &$credChanged) {
            if (array_key_exists('is_default', $data) && $data['is_default']) {
                EInvoiceAccount::query()->where('id', '!=', $account->getKey())->update(['is_default' => false]);
            }
            if (array_key_exists('credentials', $data)) {
                $incoming = array_filter((array) ($data['credentials'] ?? []), fn ($v) => $v !== null && $v !== '');
                $credChanged = $incoming !== [];
                $data['credentials'] = array_merge((array) ($account->credentials ?? []), $incoming);
            }
            $account->forceFill($data)->save();
        });

        if ($credChanged) {
            $this->runVerify($registry, $account->refresh());
        }

        return response()->json(['data' => new EInvoiceAccountResource($account->refresh())]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('einvoice.config'), 403, 'Bạn không có quyền.');
        EInvoiceAccount::query()->findOrFail($id)->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    public function verify(Request $request, int $id, EInvoiceRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('einvoice.config'), 403, 'Bạn không có quyền.');
        $account = EInvoiceAccount::query()->findOrFail($id);
        $result = $this->runVerify($registry, $account);

        return response()->json(['data' => [
            'ok' => $result['ok'], 'message' => $result['message'],
            'error_code' => $result['error_code'] ?? null, 'expires_at' => $result['expires_at'] ?? null,
            'verified_at' => now()->toIso8601String(),
            'account' => new EInvoiceAccountResource($account->refresh()),
        ]]);
    }

    public function companyInfo(Request $request, int $id, EInvoiceRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('einvoice.config'), 403, 'Bạn không có quyền.');
        $account = EInvoiceAccount::query()->findOrFail($id);
        abort_unless($registry->has($account->provider), 422, 'Nhà cung cấp chưa đăng ký.');
        $info = $registry->for($account->provider)->getCompanyInfo($account->toConnectorArray());
        // Cache IsInvoiceWithCode để mapper Phần B chọn path /code đúng.
        $account->forceFill(['is_invoice_with_code' => $info->isInvoiceWithCode])->save();

        return response()->json(['data' => $info->toArray()]);
    }

    public function templates(Request $request, int $id, EInvoiceRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('einvoice.config'), 403, 'Bạn không có quyền.');
        $account = EInvoiceAccount::query()->findOrFail($id);
        abort_unless($registry->has($account->provider), 422, 'Nhà cung cấp chưa đăng ký.');
        $year = (int) ($request->query('year') ?: now()->year);
        $list = $registry->for($account->provider)->templates($account->toConnectorArray(), $year);

        return response()->json(['data' => array_map(fn ($t) => $t->toArray(), $list)]);
    }

    /** @return array{ok:bool,message:string,error_code?:?string,expires_at?:?string} */
    private function runVerify(EInvoiceRegistry $registry, EInvoiceAccount $account): array
    {
        if (! $registry->has($account->provider)) {
            return ['ok' => false, 'message' => 'Nhà cung cấp HĐĐT chưa được đăng ký.', 'error_code' => 'unregistered', 'expires_at' => null];
        }
        try {
            $result = $registry->for($account->provider)->verifyCredentials($account->toConnectorArray());
        } catch (\Throwable $e) {
            $result = ['ok' => false, 'message' => 'Lỗi kiểm tra: '.$e->getMessage(), 'error_code' => 'network', 'expires_at' => null];
        }
        $meta = (array) ($account->meta ?? []);
        $meta['last_verified_at'] = now()->toIso8601String();
        $meta['last_verify_ok'] = (bool) $result['ok'];
        $meta['last_verify_error'] = $result['ok'] ? null : ($result['message'] ?? null);
        $account->forceFill(['meta' => $meta])->save();

        return $result;
    }
}
```

- [ ] **Step 6: Khai routes** (thay khung Task 8)

```php
<?php

use CMBcoreSeller\Modules\EInvoice\Http\Controllers\EInvoiceAccountController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum', 'verified', 'tenant', 'plan.feature:einvoice'])
    ->prefix('api/v1/einvoice')->group(function () {
        Route::get('accounts', [EInvoiceAccountController::class, 'index'])->name('einvoice.accounts.index');
        Route::post('accounts', [EInvoiceAccountController::class, 'store'])->name('einvoice.accounts.store');
        Route::patch('accounts/{id}', [EInvoiceAccountController::class, 'update'])->whereNumber('id')->name('einvoice.accounts.update');
        Route::delete('accounts/{id}', [EInvoiceAccountController::class, 'destroy'])->whereNumber('id')->name('einvoice.accounts.destroy');
        Route::post('accounts/{id}/verify', [EInvoiceAccountController::class, 'verify'])->whereNumber('id')->name('einvoice.accounts.verify');
        Route::get('accounts/{id}/company-info', [EInvoiceAccountController::class, 'companyInfo'])->whereNumber('id')->name('einvoice.accounts.company-info');
        Route::get('accounts/{id}/templates', [EInvoiceAccountController::class, 'templates'])->whereNumber('id')->name('einvoice.accounts.templates');
    });
```

- [ ] **Step 7: Run test to verify it passes**

Run: `php artisan test --filter=EInvoiceAccountApiTest`
Expected: PASS (4 tests).

- [ ] **Step 8: Quality gate**

Run: `cd app && vendor/bin/pint --test && vendor/bin/phpstan analyse`
Expected: PASS (sửa cho tới khi sạch).

- [ ] **Step 9: Add endpoints to docs + commit**

Cập nhật `docs/05-api/endpoints.md` thêm nhóm `einvoice/accounts*`. Sau đó:
```bash
cd app && git add app/Modules/EInvoice/Http tests/Feature/EInvoice/EInvoiceAccountApiTest.php tests/Feature/EInvoice/EInvoiceTestHelpers.php ../docs/05-api/endpoints.md
git commit -m "feat(einvoice): API cấu hình tài khoản MISA (CRUD/verify/company-info/templates)"
```

---

### Task 12: Frontend — `lib/einvoice.tsx` hooks (tài khoản)

**Files:**
- Create: `app/resources/js/lib/einvoice.tsx`
- Test: (FE không có test runner — kiểm bằng `npm run typecheck` + dùng thật ở Task 13)

**Interfaces:**
- Produces: `useEInvoiceAccounts()`, `useCreateEInvoiceAccount()`, `useUpdateEInvoiceAccount()`, `useDeleteEInvoiceAccount()`, `useVerifyEInvoiceAccount()`, `useEInvoiceCompanyInfo(id)`, `useEInvoiceTemplates(id, year)`; interface `EInvoiceAccount`.

- [ ] **Step 1: Implement hooks** (mirror `lib/accounting.tsx`)

```tsx
import { useMemo } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

export interface EInvoiceAccount {
    id: number;
    provider: string;
    name: string;
    is_invoice_with_code: boolean | null;
    default_mode: 'hsm' | 'mtt';
    templates: Record<string, unknown>;
    seller_info: Record<string, unknown>;
    is_default: boolean;
    is_active: boolean;
    meta: Record<string, unknown> & { last_verified_at?: string; last_verify_ok?: boolean; last_verify_error?: string | null };
    credential_keys: string[];
    created_at: string | null;
}

export interface VerifyResult {
    ok: boolean;
    message: string;
    error_code: string | null;
    expires_at: string | null;
    verified_at: string;
    account: EInvoiceAccount;
}

export interface CompanyInfo {
    company_name: string;
    tax_code: string;
    address: string | null;
    is_invoice_with_code: boolean;
    email: string | null;
}

export interface InvoiceTemplate {
    template_id: string;
    template_name: string;
    inv_series: string;
    invoice_type: number;
    is_published: boolean;
    inactive: boolean;
}

export function useEInvoiceAccounts() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['einvoice', tenantId, 'accounts'],
        enabled: api != null,
        retry: (n, err) => {
            const s = (err as { response?: { status?: number } })?.response?.status;
            return s !== 402 && s !== 403 && n < 2;
        },
        queryFn: async () => {
            const { data } = await api!.get<{ data: EInvoiceAccount[] }>('/einvoice/accounts');
            return data.data;
        },
    });
}

export function useCreateEInvoiceAccount() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { provider: string; name: string; default_mode?: 'hsm' | 'mtt'; credentials?: Record<string, string>; is_default?: boolean }) => {
            const { data } = await api!.post<{ data: EInvoiceAccount }>('/einvoice/accounts', vars);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['einvoice', tenantId, 'accounts'] }),
    });
}

export function useUpdateEInvoiceAccount() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, ...patch }: { id: number } & Partial<Pick<EInvoiceAccount, 'name' | 'default_mode' | 'is_default' | 'is_active' | 'templates' | 'seller_info'>> & { credentials?: Record<string, string> }) => {
            const { data } = await api!.patch<{ data: EInvoiceAccount }>(`/einvoice/accounts/${id}`, patch);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['einvoice', tenantId, 'accounts'] }),
    });
}

export function useDeleteEInvoiceAccount() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => {
            await api!.delete(`/einvoice/accounts/${id}`);
            return id;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['einvoice', tenantId, 'accounts'] }),
    });
}

export function useVerifyEInvoiceAccount() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => {
            const { data } = await api!.post<{ data: VerifyResult }>(`/einvoice/accounts/${id}/verify`);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['einvoice', tenantId, 'accounts'] }),
    });
}

export function useEInvoiceCompanyInfo() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (id: number) => {
            const { data } = await api!.get<{ data: CompanyInfo }>(`/einvoice/accounts/${id}/company-info`);
            return data.data;
        },
    });
}

export function useEInvoiceTemplates() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async ({ id, year }: { id: number; year?: number }) => {
            const { data } = await api!.get<{ data: InvoiceTemplate[] }>(`/einvoice/accounts/${id}/templates`, { params: year ? { year } : {} });
            return data.data;
        },
    });
}
```

- [ ] **Step 2: Typecheck**

Run: `cd app && npm run typecheck`
Expected: PASS.

- [ ] **Step 3: Commit**

```bash
cd app && git add resources/js/lib/einvoice.tsx
git commit -m "feat(einvoice): FE hooks lib/einvoice.tsx (tài khoản nhà cung cấp)"
```

---

### Task 13: Frontend — `EInvoiceSettingsPage` + route + menu

**Files:**
- Create: `app/resources/js/pages/settings/EInvoiceSettingsPage.tsx`
- Modify: `app/resources/js/routes/appRoutes.tsx` (import + `<Route path="einvoice" element={<EInvoiceSettingsPage />} />` dưới `settings`)
- Modify: `app/resources/js/components/SettingsLayout.tsx` (`buildSections()` thêm item + `KEYS` thêm `/settings/einvoice`; render theo quyền `einvoice.config` qua `useCan`)
- Test: `npm run typecheck` + `npm run build`

**Interfaces:**
- Consumes: hooks Task 12, `useCan('einvoice.config')`.

- [ ] **Step 1: Write the page** (mirror `CarrierAccountsPage`; form credentials MISA + nút "Kiểm tra kết nối" + chọn `default_mode` bằng `Radio.Group`)

```tsx
import { useState } from 'react';
import { App as AntApp, Alert, Button, Card, Empty, Form, Input, Modal, Radio, Result, Space, Switch, Tag, Typography } from 'antd';
import { CheckCircleFilled, CloseCircleFilled, KeyOutlined, PlusOutlined, ReloadOutlined, ThunderboltOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import {
    useEInvoiceAccounts, useCreateEInvoiceAccount, useUpdateEInvoiceAccount,
    useDeleteEInvoiceAccount, useVerifyEInvoiceAccount, type EInvoiceAccount,
} from '@/lib/einvoice';

const CRED_FIELDS = [
    { key: 'appid', label: 'AppID (MISA cấp)', required: true },
    { key: 'taxcode', label: 'Mã số thuế', required: true },
    { key: 'username', label: 'Tài khoản meInvoice', required: true },
    { key: 'password', label: 'Mật khẩu', required: true },
];

export function EInvoiceSettingsPage() {
    const { message } = AntApp.useApp();
    const canConfig = useCan('einvoice.config');
    const { data: accounts, isFetching, isError, refetch } = useEInvoiceAccounts();
    const create = useCreateEInvoiceAccount();
    const verify = useVerifyEInvoiceAccount();
    const del = useDeleteEInvoiceAccount();
    const [open, setOpen] = useState(false);
    const [form] = Form.useForm();

    if (isError) {
        return <Result status="error" title="Không tải được cấu hình HĐĐT" extra={<Button onClick={() => refetch()}>Thử lại</Button>} />;
    }

    const submit = () => form.validateFields().then((v) => {
        const credentials: Record<string, string> = {};
        CRED_FIELDS.forEach((f) => { if (v[`cred_${f.key}`]) credentials[f.key] = v[`cred_${f.key}`]; });
        create.mutate(
            { provider: 'misa', name: v.name, default_mode: v.default_mode ?? 'hsm', credentials, is_default: true },
            {
                onSuccess: () => { message.success('Đã thêm tài khoản MISA. Đang kiểm tra kết nối...'); form.resetFields(); setOpen(false); },
                onError: (e) => message.error(errorMessage(e)),
            },
        );
    });

    const onVerify = (acc: EInvoiceAccount) => verify.mutate(acc.id, {
        onSuccess: (r) => (r.ok ? message.success : message.error)(`${acc.name}: ${r.message}`),
        onError: (e) => message.error(errorMessage(e)),
    });

    return (
        <div>
            <PageHeader
                title="Hóa đơn điện tử (MISA meInvoice)"
                subtitle="Khai báo tài khoản MISA để phát hành hóa đơn cho đơn hàng."
                extra={
                    <Space>
                        <Button icon={<ReloadOutlined />} onClick={() => refetch()} loading={isFetching}>Làm mới</Button>
                        {canConfig && <Button type="primary" icon={<PlusOutlined />} onClick={() => setOpen(true)}>Thêm tài khoản</Button>}
                    </Space>
                }
            />

            {(accounts ?? []).length === 0 ? (
                <Empty description="Chưa có tài khoản HĐĐT" />
            ) : (
                <Space direction="vertical" style={{ width: '100%' }}>
                    {(accounts ?? []).map((acc) => {
                        const ok = acc.meta?.last_verify_ok;
                        return (
                            <Card key={acc.id} size="small"
                                title={<Space><KeyOutlined />{acc.name}{acc.is_default && <Tag color="blue">Mặc định</Tag>}</Space>}
                                extra={canConfig && (
                                    <Space>
                                        <Button size="small" icon={<ThunderboltOutlined />} loading={verify.isPending} onClick={() => onVerify(acc)}>Kiểm tra kết nối</Button>
                                        <Button size="small" danger onClick={() => del.mutate(acc.id)}>Xóa</Button>
                                    </Space>
                                )}>
                                <Space direction="vertical" size={4}>
                                    <Typography.Text type="secondary">Kiểu mặc định (đơn tự tạo): {acc.default_mode === 'hsm' ? 'HSM' : 'Máy tính tiền (MTT)'}</Typography.Text>
                                    <span>
                                        {ok === true && <Tag icon={<CheckCircleFilled />} color="success">Kết nối OK</Tag>}
                                        {ok === false && <Tag icon={<CloseCircleFilled />} color="error">Lỗi kết nối</Tag>}
                                        {acc.meta?.last_verify_error && <Typography.Text type="danger">{acc.meta.last_verify_error}</Typography.Text>}
                                    </span>
                                </Space>
                            </Card>
                        );
                    })}
                </Space>
            )}

            <Modal open={open} title="Thêm tài khoản MISA meInvoice" okText="Thêm" onCancel={() => setOpen(false)}
                onOk={submit} confirmLoading={create.isPending} destroyOnClose width={560}>
                <Alert type="info" showIcon style={{ marginBottom: 16 }}
                    message="Đơn sàn xuất hóa đơn máy tính tiền (MTT); đơn tự tạo theo kiểu mặc định bên dưới." />
                <Form form={form} layout="vertical" preserve={false}>
                    <Form.Item name="name" label="Tên gợi nhớ" rules={[{ required: true, message: 'Nhập tên gợi nhớ' }, { max: 120 }]}>
                        <Input placeholder="MISA - cửa hàng chính" />
                    </Form.Item>
                    <Form.Item name="default_mode" label="Kiểu phát hành mặc định cho đơn tự tạo" initialValue="hsm">
                        <Radio.Group optionType="button" buttonStyle="solid">
                            <Radio value="hsm">HSM (HĐ GTGT đầy đủ)</Radio>
                            <Radio value="mtt">Máy tính tiền (MTT)</Radio>
                        </Radio.Group>
                    </Form.Item>
                    {CRED_FIELDS.map((f) => (
                        <Form.Item key={f.key} name={`cred_${f.key}`} label={f.label}
                            rules={f.required ? [{ required: true, message: `Nhập ${f.label}` }] : []}>
                            <Input.Password={f.key === 'password' ? true : undefined} placeholder={f.label} />
                        </Form.Item>
                    ))}
                </Form>
            </Modal>
        </div>
    );
}
```

> Sửa nhỏ khi gõ: dùng `<Input.Password />` cho field password, `<Input />` cho còn lại (tách map hoặc điều kiện) — đảm bảo JSX hợp lệ (đoạn `Input.Password={...}` ở trên là pseudo, viết lại bằng điều kiện thật khi implement).

- [ ] **Step 2: Khai route** trong `appRoutes.tsx`: thêm `import { EInvoiceSettingsPage } from '@/pages/settings/EInvoiceSettingsPage';` và trong block `<Route path="settings" ...>` thêm `<Route path="einvoice" element={<EInvoiceSettingsPage />} />`.

- [ ] **Step 3: Menu** trong `SettingsLayout.tsx` `buildSections()`: thêm (conditional theo `useCan('einvoice.config')`):
```tsx
...(canEInvoice ? [{ key: '/settings/einvoice', icon: <FileTextOutlined />, label: <Link to="/settings/einvoice">Hóa đơn điện tử</Link> }] : []),
```
và thêm `'/settings/einvoice'` vào mảng `KEYS`; import `FileTextOutlined` + thêm `const canEInvoice = useCan('einvoice.config');`.

- [ ] **Step 4: Build**

Run: `cd app && npm run typecheck && npm run build`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
cd app && git add resources/js/pages/settings/EInvoiceSettingsPage.tsx resources/js/routes/appRoutes.tsx resources/js/components/SettingsLayout.tsx
git commit -m "feat(einvoice): trang cấu hình MISA + route + menu cài đặt"
```

---

## Self-Review

**Spec coverage (Phần A của spec §4.1/4.2/4.3 cấu hình + RBAC + plan-gate):**
- Trục integration (Registry+Connector+DTO+Exceptions+MISA connector+config+wiring): Task 1–6 ✓
- `EInvoiceAccount` credentials mã hóa: Task 7 ✓
- Module provider + đăng ký: Task 8 ✓
- RBAC `einvoice.*`: Task 9 ✓
- Plan-gate (4 nơi + resync): Task 10 ✓
- API cấu hình + verify + company-info + templates (không lộ credentials): Task 11 ✓
- FE hooks + trang cấu hình + test kết nối: Task 12–13 ✓
- NGOÀI Phần A (để Phần B): InvoiceDTO/lines, einvoices tables, OrderInvoiceMapper, IssueEInvoiceService, issue/preview/cancel/adjust/status/download, OrderDetailBody card, danh sách HĐ, thống kê, đơn hoàn, auto-issue, khóa đơn, eligibility. Đã ghi rõ ở từng chỗ là Phần B/P2/P3/P4.

**Placeholder scan:** Không có TODO/TBD. Hai chỗ ghi chú "viết lại khi implement" (test wiring `refreshApplication` thay thế; `Input.Password` trong map FE) là hướng dẫn cụ thể, không phải placeholder logic.

**Type consistency:** `EInvoiceConnector` method names (`verifyCredentials/getCompanyInfo/templates/capabilities/supports/assertConfigured`) khớp giữa interface (Task 4) và connector (Task 5) và controller (Task 11). `toConnectorArray()` shape `{id,provider,credentials,meta}` khớp model (Task 7) ↔ connector `client()` đọc `$account['credentials']`. DTO `toArray()` keys khớp FE interface (`company_name`, `inv_series`...). Registry methods (`has/for/providers/register`) khớp Task 3 ↔ wiring Task 6 ↔ controller.

**Điểm cần xác minh khi thực thi (đánh dấu để người thực thi đối chiếu thực tế):**
- Tên hằng `Plan::CODE_PRO/CODE_STARTER`, `Role::Viewer`, `Subscription::STATUS_ACTIVE/CYCLE_MONTHLY`, namespace `User` — đối chiếu `AccountingTestHelpers.php`.
- `plan.feature` trả mã `PLAN_FEATURE_LOCKED` + HTTP 402 — đối chiếu `EnforcePlanFeature`.
- Tên 4 file/biến FE plan-gate (`PlanFeatures`, `KNOWN_FEATURES`, `FEATURE_ROWS`, `PLAN_FEATURE_LABELS`) — đối chiếu báo cáo điều tra.
- `useCurrentTenantId` export từ `@/lib/tenant`; `useCan` từ `@/lib/tenant`.
- MISA endpoint thật khi có credentials (NEEDS-VERIFY): `/auth/token`, `/company?taxcode=`, `/itg/InvoicePublishing/templates?invyear=` + envelope 2 tầng — kiểm bằng tài khoản sandbox trước khi bật prod.
