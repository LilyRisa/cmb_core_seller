# Customer Prepaid Wallet — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm ví trả trước cho khách: nạp tiền (hạch toán Dr tiền/Cr 131), áp ví khi tạo đơn manual (partial + toggle trừ ship), trừ ví khi tạo đơn, hoàn ví khi huỷ/hoàn; COD theo `prepaid_amount` (sẵn có).

**Architecture:** Customers module sở hữu ví (`customers.prepaid_balance` + `customer_wallet_transactions`). GL đi qua **Contract** `CustomerAdvanceLedger` (Accounting impl, tái dùng `CustomerReceiptService`) — Customers KHÔNG gọi service nội bộ Accounting trực tiếp. Trừ ví trong transaction tạo đơn (ManualOrderService) qua contract của Customers. Hoàn ví qua listener `OrderStatusChanged`.

**Tech Stack:** Laravel 11, PHPUnit, Pint, Larastan; React 18 + Vite + AntD + TanStack Query.

## Global Constraints

- Lệnh chạy từ `app/`. Namespace `CMBcoreSeller\` → `app/app/`.
- Module communicate qua Contracts/ hoặc domain events — KHÔNG `use` Services nội bộ module khác (PR-blocking).
- Money = integer VND. Mọi bảng có `tenant_id` + `BelongsToTenant`. Job/listener tài chính phải idempotent.
- Status qua `StandardOrderStatus`/`OrderStatusSync`. UI: `@ant-design/icons` (không emoji), hạn chế `<Select>` (Radio/Segmented).
- Test baseline: BE chưa green toàn cục — chỉ chạy test liên quan Customers/Orders/Accounting (memory `test-verify-baseline`).
- Hạch toán: nạp = Dr 1111/1121 / Cr 131 (advance, party=customer) qua post-rule `cash.receipt.from_customer`/`bank.receipt.from_customer` (sẵn có); doanh thu khi giao qua `PostOnOrderShipped` (KHÔNG sửa).

---

## File Structure

**Backend tạo mới:**
- `app/app/Modules/Customers/Database/Migrations/2026_06_26_000001_add_prepaid_balance_to_customers.php`
- `app/app/Modules/Customers/Database/Migrations/2026_06_26_000002_create_customer_wallet_transactions.php`
- `app/app/Modules/Customers/Models/CustomerWalletTransaction.php`
- `app/app/Modules/Customers/Services/CustomerWalletService.php`
- `app/app/Modules/Customers/Http/Controllers/CustomerWalletController.php`
- `app/app/Modules/Customers/Http/Resources/CustomerWalletTransactionResource.php`
- `app/app/Modules/Customers/Listeners/RefundWalletOnOrderCancelled.php`
- `app/app/Modules/Accounting/Contracts/CustomerAdvanceLedger.php` (interface)
- `app/app/Modules/Accounting/Services/CustomerAdvanceLedgerService.php` (impl, dùng CustomerReceiptService)

**Backend sửa:**
- `app/app/Modules/Customers/Models/Customer.php` — fillable + cast `prepaid_balance`.
- `app/app/Modules/Customers/Http/Resources/CustomerResource.php` — thêm `prepaid_balance`.
- `app/app/Modules/Customers/CustomersServiceProvider.php` — bind không cần; đăng ký listener refund.
- `app/app/Modules/Accounting/AccountingServiceProvider.php` — bind `CustomerAdvanceLedger` → impl.
- `app/app/Modules/Orders/Services/ManualOrderService.php` — trừ ví khi tạo đơn (qua contract Customers).
- `app/app/Modules/Orders/Http/Controllers/OrderController.php` — validate `customer_id`,`wallet_amount`.
- `app/routes/api.php` — route topup + transactions.

**Backend Contract (Customers, để Orders gọi):**
- `app/app/Modules/Customers/Contracts/CustomerWallet.php` (interface: deductForOrder/refundForOrder/balance) + bind trong CustomersServiceProvider.

**FE sửa:**
- `app/resources/js/lib/customers.ts(x)` — hook topup + transactions + type prepaid_balance.
- Customer detail page — panel ví + nút Nạp tiền + lịch sử.
- Create-order page — panel ví (số dư, dùng ví, toggle trừ ship, COD realtime).

**Docs:** `docs/05-api/endpoints.md`.

---

## Task 1: Migration + Customer model field

**Files:**
- Create: `app/app/Modules/Customers/Database/Migrations/2026_06_26_000001_add_prepaid_balance_to_customers.php`
- Create: `app/app/Modules/Customers/Database/Migrations/2026_06_26_000002_create_customer_wallet_transactions.php`
- Modify: `app/app/Modules/Customers/Models/Customer.php`
- Create: `app/app/Modules/Customers/Models/CustomerWalletTransaction.php`
- Test: `app/tests/Feature/Customers/CustomerWalletTest.php`

**Interfaces:**
- Produces: `customers.prepaid_balance` (bigint, default 0); table `customer_wallet_transactions`; model `CustomerWalletTransaction` (BelongsToTenant) with consts `TYPE_TOPUP='topup'`, `TYPE_ORDER_PAYMENT='order_payment'`, `TYPE_REFUND='refund'`, `TYPE_ADJUSTMENT='adjustment'`.

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/Customers/CustomerWalletTest.php`:

```php
<?php

namespace Tests\Feature\Customers;

use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Customers\Models\CustomerWalletTransaction;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_has_prepaid_balance_and_wallet_transactions_table(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);
        $c = Customer::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'phone_hash' => str_repeat('a', 64),
            'name' => 'Khách', 'first_seen_at' => now(), 'last_seen_at' => now(),
        ]);
        $this->assertSame(0, (int) $c->prepaid_balance);

        CustomerWalletTransaction::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'customer_id' => $c->getKey(),
            'type' => CustomerWalletTransaction::TYPE_TOPUP, 'amount' => 100000, 'balance_after' => 100000,
        ]);
        $this->assertDatabaseHas('customer_wallet_transactions', ['customer_id' => $c->getKey(), 'amount' => 100000]);
    }
}
```

- [ ] **Step 2: Run test — fails**

Run: `cd app && php artisan test --filter=CustomerWalletTest`
Expected: FAIL — column `prepaid_balance` / table `customer_wallet_transactions` missing.

- [ ] **Step 3: Migration — add column**

Create `...000001_add_prepaid_balance_to_customers.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->bigInteger('prepaid_balance')->default(0)->after('reputation_label');
        });
    }

    public function down(): void
    {
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn('prepaid_balance'));
    }
};
```

- [ ] **Step 4: Migration — wallet transactions table**

Create `...000002_create_customer_wallet_transactions.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('type', 24);              // topup|order_payment|refund|adjustment
            $table->bigInteger('amount');            // signed: + nạp/hoàn, − trừ đơn
            $table->bigInteger('balance_after');
            $table->string('payment_method', 16)->nullable(); // cash|bank|ewallet (topup)
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->string('note', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['tenant_id', 'customer_id', 'id']);
            // Idempotency: tối đa 1 order_payment + 1 refund mỗi đơn.
            $table->unique(['order_id', 'type'], 'cwt_order_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_wallet_transactions');
    }
};
```

Lưu ý: unique `(order_id, type)` với order_id NULL (topup) — Postgres coi mỗi NULL là distinct ⇒ nhiều topup order_id=NULL không vi phạm. OK.

- [ ] **Step 5: Customer model — fillable + cast**

Trong `Customer.php`, thêm `'prepaid_balance'` vào `$fillable` (sau `'reputation_label'`) và vào `casts()`:

```php
'prepaid_balance' => 'integer',
```

- [ ] **Step 6: CustomerWalletTransaction model**

Create `app/app/Modules/Customers/Models/CustomerWalletTransaction.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Customers\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Sổ giao dịch ví trả trước của khách (append-only). Số dư denormalized ở customers.prepaid_balance.
 * SPEC 2026-06-26. amount: + nạp/hoàn, − trừ đơn. Idempotency unique (order_id,type).
 */
class CustomerWalletTransaction extends Model
{
    use BelongsToTenant;

    public const TYPE_TOPUP = 'topup';

    public const TYPE_ORDER_PAYMENT = 'order_payment';

    public const TYPE_REFUND = 'refund';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const UPDATED_AT = null; // append-only

    protected $fillable = [
        'tenant_id', 'customer_id', 'order_id', 'type', 'amount', 'balance_after',
        'payment_method', 'journal_entry_id', 'note', 'created_by', 'created_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'balance_after' => 'integer', 'created_at' => 'datetime'];
    }
}
```

- [ ] **Step 7: Run test — passes**

Run: `cd app && php artisan test --filter=CustomerWalletTest`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add app/app/Modules/Customers/Database/Migrations/2026_06_26_0000*.php app/app/Modules/Customers/Models/Customer.php app/app/Modules/Customers/Models/CustomerWalletTransaction.php app/tests/Feature/Customers/CustomerWalletTest.php
git commit -m "feat(customers): schema ví trả trước (prepaid_balance + customer_wallet_transactions)"
```

---

## Task 2: Accounting Contract `CustomerAdvanceLedger` (GL cho nạp ví)

**Files:**
- Create: `app/app/Modules/Accounting/Contracts/CustomerAdvanceLedger.php`
- Create: `app/app/Modules/Accounting/Services/CustomerAdvanceLedgerService.php`
- Modify: `app/app/Modules/Accounting/AccountingServiceProvider.php` (bind)
- Test: `app/tests/Feature/Accounting/CustomerAdvanceLedgerTest.php`

**Interfaces:**
- Produces: `interface CustomerAdvanceLedger { public function recordTopup(int $tenantId, int $customerId, int $amount, string $paymentMethod, string $memo, ?int $userId): int; }` — trả `journal_entry_id`. Dùng `CustomerReceiptService` (create draft advance + confirm → Dr 1111/1121 Cr 131).

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/Accounting/CustomerAdvanceLedgerTest.php`:

```php
<?php

namespace Tests\Feature\Accounting;

use CMBcoreSeller\Modules\Accounting\Contracts\CustomerAdvanceLedger;
use CMBcoreSeller\Modules\Accounting\Database\Seeders\AccountingSeeder; // xem seeder chart+rules thực tế
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAdvanceLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_topup_posts_dr_cash_cr_131(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);
        $this->seedAccounting((int) $tenant->getKey()); // chart accounts + post rules (xem cách AccountingTest seed)

        $jeId = app(CustomerAdvanceLedger::class)->recordTopup(
            (int) $tenant->getKey(), customerId: 1, amount: 200000, paymentMethod: 'cash', memo: 'Nạp ví', userId: 1
        );

        $this->assertGreaterThan(0, $jeId);
        $this->assertDatabaseHas('journal_lines', ['cr_amount' => 200000, 'account_code' => '131', 'party_type' => 'customer', 'party_id' => 1]);
        $this->assertDatabaseHas('journal_lines', ['dr_amount' => 200000, 'account_code' => '1111']);
    }
}
```

Lưu ý: kiểm cách các test Accounting hiện có seed chart_accounts + post_rules (tìm `tests/Feature/Accounting/*`, helper seed). Tái dùng đúng helper đó cho `seedAccounting()`; account code thực tế (131, 1111, 1121) lấy từ `AccountingPostRulesSeeder`/chart seeder.

- [ ] **Step 2: Run test — fails**

Run: `cd app && php artisan test --filter=CustomerAdvanceLedgerTest`
Expected: FAIL — interface `CustomerAdvanceLedger` chưa tồn tại / chưa bind.

- [ ] **Step 3: Interface**

Create `app/app/Modules/Accounting/Contracts/CustomerAdvanceLedger.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Accounting\Contracts;

/**
 * GL cho ví trả trước của khách (advance). Module khác (Customers) post GL nạp ví QUA contract này
 * — KHÔNG gọi service nội bộ Accounting trực tiếp (module rule). SPEC 2026-06-26.
 */
interface CustomerAdvanceLedger
{
    /** Nạp ví: Dr 1111|1121 / Cr 131 (party=customer). Trả journal_entry_id. */
    public function recordTopup(int $tenantId, int $customerId, int $amount, string $paymentMethod, string $memo, ?int $userId): int;
}
```

- [ ] **Step 4: Implementation**

Create `app/app/Modules/Accounting/Services/CustomerAdvanceLedgerService.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Accounting\Services;

use CMBcoreSeller\Modules\Accounting\Contracts\CustomerAdvanceLedger;

/**
 * Tái dùng phiếu thu (advance — không applied_orders) ⇒ confirm post Dr 1111|1121 / Cr 131 (party=customer).
 */
class CustomerAdvanceLedgerService implements CustomerAdvanceLedger
{
    public function __construct(private readonly CustomerReceiptService $receipts) {}

    public function recordTopup(int $tenantId, int $customerId, int $amount, string $paymentMethod, string $memo, ?int $userId): int
    {
        $method = in_array($paymentMethod, ['cash', 'bank', 'ewallet'], true) ? $paymentMethod : 'cash';
        $receipt = $this->receipts->create($tenantId, [
            'customer_id' => $customerId,
            'received_at' => now()->toIso8601String(),
            'amount' => $amount,
            'payment_method' => $method,
            'memo' => $memo,
        ], (int) ($userId ?? 0));
        $confirmed = $this->receipts->confirm($receipt, (int) ($userId ?? 0));

        return (int) $confirmed->journal_entry_id;
    }
}
```

- [ ] **Step 5: Bind in provider**

Trong `AccountingServiceProvider.php` (method `register`), thêm:

```php
$this->app->bind(
    \CMBcoreSeller\Modules\Accounting\Contracts\CustomerAdvanceLedger::class,
    \CMBcoreSeller\Modules\Accounting\Services\CustomerAdvanceLedgerService::class,
);
```

- [ ] **Step 6: Run test — passes**

Run: `cd app && php artisan test --filter=CustomerAdvanceLedgerTest`
Expected: PASS. Nếu account code khác (vd '1111' vs '111'), chỉnh assertion theo chart thực tế (KHÔNG sửa logic).

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Accounting/Contracts/CustomerAdvanceLedger.php app/app/Modules/Accounting/Services/CustomerAdvanceLedgerService.php app/app/Modules/Accounting/AccountingServiceProvider.php app/tests/Feature/Accounting/CustomerAdvanceLedgerTest.php
git commit -m "feat(accounting): contract CustomerAdvanceLedger — post nạp ví Dr tiền/Cr 131"
```

---

## Task 3: `CustomerWalletService` + Contract `CustomerWallet` (topup/deduct/refund)

**Files:**
- Create: `app/app/Modules/Customers/Contracts/CustomerWallet.php`
- Create: `app/app/Modules/Customers/Services/CustomerWalletService.php`
- Modify: `app/app/Modules/Customers/CustomersServiceProvider.php` (bind contract)
- Test: `app/tests/Feature/Customers/CustomerWalletServiceTest.php`

**Interfaces:**
- Produces: `interface CustomerWallet { public function topup(int $tenantId,int $customerId,int $amount,string $paymentMethod,?string $note,?int $userId): CustomerWalletTransaction; public function deductForOrder(int $tenantId,int $customerId,int $orderId,int $amount,?int $userId): CustomerWalletTransaction; public function refundForOrder(int $tenantId,int $customerId,int $orderId,?int $userId): ?CustomerWalletTransaction; }`
- Consumes: `CustomerAdvanceLedger` (Task 2).
- Throws: `RuntimeException` "Số dư ví không đủ." khi deduct > balance.

- [ ] **Step 1: Write failing test**

Create `app/tests/Feature/Customers/CustomerWalletServiceTest.php`:

```php
<?php

namespace Tests\Feature\Customers;

use CMBcoreSeller\Modules\Customers\Contracts\CustomerWallet;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Customers\Models\CustomerWalletTransaction;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerWalletServiceTest extends TestCase
{
    use RefreshDatabase;

    private function customer(Tenant $t): Customer
    {
        return Customer::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t->getKey(), 'phone_hash' => str_repeat('b', 64), 'name' => 'K',
            'first_seen_at' => now(), 'last_seen_at' => now(),
        ]);
    }

    public function test_topup_then_deduct_then_refund(): void
    {
        $t = Tenant::create(['name' => 'S']);
        $this->seedAccounting((int) $t->getKey()); // như Task 2
        $c = $this->customer($t);
        $w = app(CustomerWallet::class);

        $w->topup((int) $t->getKey(), (int) $c->getKey(), 300000, 'cash', 'Nạp', 1);
        $this->assertSame(300000, (int) $c->refresh()->prepaid_balance);

        $w->deductForOrder((int) $t->getKey(), (int) $c->getKey(), 777, 120000, 1);
        $this->assertSame(180000, (int) $c->refresh()->prepaid_balance);

        $w->refundForOrder((int) $t->getKey(), (int) $c->getKey(), 777, 1);
        $this->assertSame(300000, (int) $c->refresh()->prepaid_balance);
    }

    public function test_deduct_more_than_balance_throws(): void
    {
        $t = Tenant::create(['name' => 'S']);
        $this->seedAccounting((int) $t->getKey());
        $c = $this->customer($t);
        $this->expectException(\RuntimeException::class);
        app(CustomerWallet::class)->deductForOrder((int) $t->getKey(), (int) $c->getKey(), 1, 50000, 1);
    }

    public function test_deduct_is_idempotent_per_order(): void
    {
        $t = Tenant::create(['name' => 'S']);
        $this->seedAccounting((int) $t->getKey());
        $c = $this->customer($t);
        $w = app(CustomerWallet::class);
        $w->topup((int) $t->getKey(), (int) $c->getKey(), 300000, 'cash', null, 1);
        $w->deductForOrder((int) $t->getKey(), (int) $c->getKey(), 9, 100000, 1);
        $w->deductForOrder((int) $t->getKey(), (int) $c->getKey(), 9, 100000, 1); // gọi lại — không trừ lần 2
        $this->assertSame(200000, (int) $c->refresh()->prepaid_balance);
        $this->assertSame(1, CustomerWalletTransaction::withoutGlobalScope(TenantScope::class)
            ->where('order_id', 9)->where('type', 'order_payment')->count());
    }
}
```

- [ ] **Step 2: Run — fails**

Run: `cd app && php artisan test --filter=CustomerWalletServiceTest`
Expected: FAIL — `CustomerWallet` chưa tồn tại.

- [ ] **Step 3: Contract**

Create `app/app/Modules/Customers/Contracts/CustomerWallet.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Customers\Contracts;

use CMBcoreSeller\Modules\Customers\Models\CustomerWalletTransaction;

/** Ví trả trước của khách. Module khác (Orders) thao tác ví QUA contract này. SPEC 2026-06-26. */
interface CustomerWallet
{
    public function topup(int $tenantId, int $customerId, int $amount, string $paymentMethod, ?string $note, ?int $userId): CustomerWalletTransaction;

    /** Trừ ví cho đơn (idempotent theo order_id). Throw RuntimeException nếu số dư không đủ. */
    public function deductForOrder(int $tenantId, int $customerId, int $orderId, int $amount, ?int $userId): CustomerWalletTransaction;

    /** Hoàn ví khi huỷ/hoàn đơn (idempotent; no-op nếu không có order_payment hoặc đã refund). */
    public function refundForOrder(int $tenantId, int $customerId, int $orderId, ?int $userId): ?CustomerWalletTransaction;
}
```

- [ ] **Step 4: Service**

Create `app/app/Modules/Customers/Services/CustomerWalletService.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Customers\Services;

use CMBcoreSeller\Modules\Accounting\Contracts\CustomerAdvanceLedger;
use CMBcoreSeller\Modules\Customers\Contracts\CustomerWallet;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Customers\Models\CustomerWalletTransaction;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CustomerWalletService implements CustomerWallet
{
    public function __construct(private readonly CustomerAdvanceLedger $ledger) {}

    public function topup(int $tenantId, int $customerId, int $amount, string $paymentMethod, ?string $note, ?int $userId): CustomerWalletTransaction
    {
        if ($amount <= 0) {
            throw new RuntimeException('Số tiền nạp phải lớn hơn 0.');
        }

        return DB::transaction(function () use ($tenantId, $customerId, $amount, $paymentMethod, $note, $userId) {
            $customer = $this->lockCustomer($tenantId, $customerId);
            // GL trước (trong cùng transaction) — Dr tiền/Cr 131.
            $jeId = $this->ledger->recordTopup($tenantId, $customerId, $amount, $paymentMethod, $note ?: 'Nạp ví khách', $userId);
            $balance = (int) $customer->prepaid_balance + $amount;
            $customer->forceFill(['prepaid_balance' => $balance])->save();

            return $this->log($tenantId, $customerId, null, CustomerWalletTransaction::TYPE_TOPUP, $amount, $balance, $paymentMethod, $jeId, $note, $userId);
        });
    }

    public function deductForOrder(int $tenantId, int $customerId, int $orderId, int $amount, ?int $userId): CustomerWalletTransaction
    {
        if ($amount <= 0) {
            throw new RuntimeException('Số tiền trừ ví phải lớn hơn 0.');
        }

        return DB::transaction(function () use ($tenantId, $customerId, $orderId, $amount, $userId) {
            $existing = CustomerWalletTransaction::withoutGlobalScope(TenantScope::class)
                ->where('order_id', $orderId)->where('type', CustomerWalletTransaction::TYPE_ORDER_PAYMENT)->first();
            if ($existing) {
                return $existing; // idempotent
            }
            $customer = $this->lockCustomer($tenantId, $customerId);
            if ((int) $customer->prepaid_balance < $amount) {
                throw new RuntimeException('Số dư ví không đủ.');
            }
            $balance = (int) $customer->prepaid_balance - $amount;
            $customer->forceFill(['prepaid_balance' => $balance])->save();

            return $this->log($tenantId, $customerId, $orderId, CustomerWalletTransaction::TYPE_ORDER_PAYMENT, -$amount, $balance, null, null, null, $userId);
        });
    }

    public function refundForOrder(int $tenantId, int $customerId, int $orderId, ?int $userId): ?CustomerWalletTransaction
    {
        return DB::transaction(function () use ($tenantId, $customerId, $orderId, $userId) {
            $payment = CustomerWalletTransaction::withoutGlobalScope(TenantScope::class)
                ->where('order_id', $orderId)->where('type', CustomerWalletTransaction::TYPE_ORDER_PAYMENT)->first();
            if (! $payment) {
                return null; // đơn không trả bằng ví
            }
            $already = CustomerWalletTransaction::withoutGlobalScope(TenantScope::class)
                ->where('order_id', $orderId)->where('type', CustomerWalletTransaction::TYPE_REFUND)->exists();
            if ($already) {
                return null; // idempotent
            }
            $amount = abs((int) $payment->amount);
            $customer = $this->lockCustomer($tenantId, $customerId);
            $balance = (int) $customer->prepaid_balance + $amount;
            $customer->forceFill(['prepaid_balance' => $balance])->save();

            return $this->log($tenantId, $customerId, $orderId, CustomerWalletTransaction::TYPE_REFUND, $amount, $balance, null, null, 'Hoàn ví do huỷ/hoàn đơn', $userId);
        });
    }

    private function lockCustomer(int $tenantId, int $customerId): Customer
    {
        $c = Customer::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->whereKey($customerId)->lockForUpdate()->first();
        if (! $c) {
            throw new RuntimeException('Không tìm thấy khách hàng.');
        }

        return $c;
    }

    private function log(int $tenantId, int $customerId, ?int $orderId, string $type, int $amount, int $balanceAfter, ?string $method, ?int $jeId, ?string $note, ?int $userId): CustomerWalletTransaction
    {
        return CustomerWalletTransaction::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenantId, 'customer_id' => $customerId, 'order_id' => $orderId,
            'type' => $type, 'amount' => $amount, 'balance_after' => $balanceAfter,
            'payment_method' => $method, 'journal_entry_id' => $jeId, 'note' => $note,
            'created_by' => $userId, 'created_at' => now(),
        ]);
    }
}
```

- [ ] **Step 5: Bind contract**

Trong `CustomersServiceProvider.php` `register()`:

```php
$this->app->bind(
    \CMBcoreSeller\Modules\Customers\Contracts\CustomerWallet::class,
    \CMBcoreSeller\Modules\Customers\Services\CustomerWalletService::class,
);
```

- [ ] **Step 6: Run — passes**

Run: `cd app && php artisan test --filter=CustomerWalletServiceTest`
Expected: PASS (3 test).

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Customers/Contracts/CustomerWallet.php app/app/Modules/Customers/Services/CustomerWalletService.php app/app/Modules/Customers/CustomersServiceProvider.php app/tests/Feature/Customers/CustomerWalletServiceTest.php
git commit -m "feat(customers): CustomerWalletService (topup/deduct/refund, lock, idempotent) + contract"
```

---

## Task 4: Endpoints topup + transactions + CustomerResource balance

**Files:**
- Create: `app/app/Modules/Customers/Http/Controllers/CustomerWalletController.php`
- Create: `app/app/Modules/Customers/Http/Resources/CustomerWalletTransactionResource.php`
- Modify: `app/app/Modules/Customers/Http/Resources/CustomerResource.php`
- Modify: `app/routes/api.php`
- Test: `app/tests/Feature/Customers/CustomerWalletApiTest.php`

**Interfaces:**
- `POST /api/v1/customers/{id}/wallet/topup` `{amount,payment_method,note?}` → `{data:{balance,transaction}}`.
- `GET /api/v1/customers/{id}/wallet/transactions` → paginated.
- CustomerResource thêm `prepaid_balance`.

- [ ] **Step 1: Write failing test**

Create `app/tests/Feature/Customers/CustomerWalletApiTest.php`:

```php
<?php

namespace Tests\Feature\Customers;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerWalletApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_topup_endpoint_and_resource_balance(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Shop']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        $this->seedAccounting((int) $tenant->getKey());
        $c = Customer::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'phone_hash' => str_repeat('c', 64), 'name' => 'K',
            'first_seen_at' => now(), 'last_seen_at' => now(),
        ]);
        $h = ['X-Tenant-Id' => (string) $tenant->getKey()];

        $this->actingAs($owner)->withHeaders($h)
            ->postJson("/api/v1/customers/{$c->getKey()}/wallet/topup", ['amount' => 250000, 'payment_method' => 'cash'])
            ->assertOk()->assertJsonPath('data.balance', 250000);

        $this->actingAs($owner)->withHeaders($h)->getJson("/api/v1/customers/{$c->getKey()}")
            ->assertOk()->assertJsonPath('data.prepaid_balance', 250000);

        $this->actingAs($owner)->withHeaders($h)->getJson("/api/v1/customers/{$c->getKey()}/wallet/transactions")
            ->assertOk()->assertJsonPath('meta.pagination.total', 1);
    }
}
```

- [ ] **Step 2: Run — fails** (`cd app && php artisan test --filter=CustomerWalletApiTest`) Expected: 404/route missing.

- [ ] **Step 3: CustomerResource** — thêm dòng vào mảng `toArray` (sau `'lifetime_stats'`):

```php
'prepaid_balance' => (int) ($this->prepaid_balance ?? 0),
```

- [ ] **Step 4: Transaction resource**

Create `CustomerWalletTransactionResource.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Customers\Http\Resources;

use CMBcoreSeller\Modules\Customers\Models\CustomerWalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CustomerWalletTransaction */
class CustomerWalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'type' => $this->type,
            'amount' => (int) $this->amount,
            'balance_after' => (int) $this->balance_after,
            'payment_method' => $this->payment_method,
            'note' => $this->note,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
```

- [ ] **Step 5: Controller**

Create `CustomerWalletController.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Customers\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Customers\Contracts\CustomerWallet;
use CMBcoreSeller\Modules\Customers\Http\Resources\CustomerWalletTransactionResource;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Customers\Models\CustomerWalletTransaction;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerWalletController extends Controller
{
    public function topup(Request $request, CurrentTenant $tenant, CustomerWallet $wallet, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('accounting.manage') || $request->user()?->can('orders.create'), 403, 'Bạn không có quyền nạp tiền.');
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:999999999'],
            'payment_method' => ['required', 'in:cash,bank,ewallet'],
            'note' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);
        $customer = Customer::query()->findOrFail($id);
        $tx = $wallet->topup((int) $tenant->id(), (int) $customer->getKey(), (int) $data['amount'], $data['payment_method'], $data['note'] ?? null, $request->user()->getKey());

        return response()->json(['data' => [
            'balance' => (int) $customer->refresh()->prepaid_balance,
            'transaction' => new CustomerWalletTransactionResource($tx),
        ]]);
    }

    public function transactions(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('customers.view'), 403, 'Bạn không có quyền.');
        Customer::query()->findOrFail($id); // tenant-scoped guard
        $page = CustomerWalletTransaction::withoutGlobalScope(TenantScope::class)
            ->where('customer_id', $id)->orderByDesc('id')
            ->paginate(min(100, max(1, (int) $request->query('per_page', 20))))->appends($request->query());

        return response()->json([
            'data' => CustomerWalletTransactionResource::collection($page->getCollection()),
            'meta' => ['pagination' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'total_pages' => $page->lastPage()]],
        ]);
    }
}
```

Lưu ý quyền: kiểm permission key thực tế (memory: RBAC custom). Nếu `accounting.manage`/`customers.view` không tồn tại đúng tên, dùng key sẵn có gần nhất (xem `Role`/permissions seeder). Đừng tự bịa key.

- [ ] **Step 6: Routes** — trong `routes/api.php`, cạnh các route customers (sau dòng `customers/{id}/block`), thêm:

```php
Route::post('customers/{id}/wallet/topup', [\CMBcoreSeller\Modules\Customers\Http\Controllers\CustomerWalletController::class, 'topup'])->whereNumber('id')->name('customers.wallet.topup');
Route::get('customers/{id}/wallet/transactions', [\CMBcoreSeller\Modules\Customers\Http\Controllers\CustomerWalletController::class, 'transactions'])->whereNumber('id')->name('customers.wallet.transactions');
```

- [ ] **Step 7: Run — passes** (`cd app && php artisan test --filter=CustomerWalletApiTest`).

- [ ] **Step 8: Commit**

```bash
git add app/app/Modules/Customers/Http/Controllers/CustomerWalletController.php app/app/Modules/Customers/Http/Resources/CustomerWalletTransactionResource.php app/app/Modules/Customers/Http/Resources/CustomerResource.php app/routes/api.php app/tests/Feature/Customers/CustomerWalletApiTest.php
git commit -m "feat(customers): endpoint nạp ví + lịch sử ví + prepaid_balance trong resource/lookup"
```

---

## Task 5: Trừ ví khi tạo đơn manual

**Files:**
- Modify: `app/app/Modules/Orders/Services/ManualOrderService.php`
- Modify: `app/app/Modules/Orders/Http/Controllers/OrderController.php` (validate `customer_id`,`wallet_amount`)
- Test: `app/tests/Feature/Orders/ManualOrderWalletTest.php`

**Interfaces:**
- Consumes: `CustomerWallet::deductForOrder(...)`.
- Order create nhận thêm `customer_id?:int`, `wallet_amount?:int`. `wallet_amount` ⊆ `prepaid_amount`, ⊆ balance; trừ ví trong transaction tạo đơn.

- [ ] **Step 1: Write failing test**

Create `app/tests/Feature/Orders/ManualOrderWalletTest.php`:

```php
<?php

namespace Tests\Feature\Orders;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Customers\Contracts\CustomerWallet;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockForSku;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ManualOrderWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_order_deducts_wallet_and_sets_cod(): void
    {
        Bus::fake([PushStockForSku::class]);
        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Shop']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        $this->seedAccounting((int) $tenant->getKey());
        $sku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $tenant->getKey(), 'sku_code' => 'S1', 'name' => 'A', 'weight_grams' => 100]);
        app(InventoryLedgerService::class)->adjust((int) $tenant->getKey(), (int) $sku->getKey(), null, 50);
        $c = Customer::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $tenant->getKey(), 'phone_hash' => str_repeat('d', 64), 'name' => 'K', 'first_seen_at' => now(), 'last_seen_at' => now()]);
        app(CustomerWallet::class)->topup((int) $tenant->getKey(), (int) $c->getKey(), 500000, 'cash', null, $owner->getKey());
        $h = ['X-Tenant-Id' => (string) $tenant->getKey()];

        // đơn 300k, trả toàn bộ bằng ví ⇒ COD 0, ví còn 200k
        $id = $this->actingAs($owner)->withHeaders($h)->postJson('/api/v1/orders', [
            'buyer' => ['name' => 'K', 'phone' => '0912345678', 'address' => 'x', 'province' => 'HN'],
            'items' => [['sku_id' => $sku->getKey(), 'name' => 'A', 'quantity' => 2, 'unit_price' => 150000]],
            'customer_id' => $c->getKey(), 'prepaid_amount' => 300000, 'wallet_amount' => 300000,
        ])->assertCreated()->json('data.id');

        $order = Order::withoutGlobalScope(TenantScope::class)->find($id);
        $this->assertSame(0, (int) $order->cod_amount);
        $this->assertSame(300000, (int) $order->prepaid_amount);
        $this->assertSame(200000, (int) $c->refresh()->prepaid_balance);
    }

    public function test_wallet_amount_exceeding_balance_is_rejected(): void
    {
        Bus::fake([PushStockForSku::class]);
        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Shop']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        $this->seedAccounting((int) $tenant->getKey());
        $sku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $tenant->getKey(), 'sku_code' => 'S1', 'name' => 'A', 'weight_grams' => 100]);
        app(InventoryLedgerService::class)->adjust((int) $tenant->getKey(), (int) $sku->getKey(), null, 50);
        $c = Customer::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $tenant->getKey(), 'phone_hash' => str_repeat('e', 64), 'name' => 'K', 'first_seen_at' => now(), 'last_seen_at' => now()]);
        $h = ['X-Tenant-Id' => (string) $tenant->getKey()];

        $this->actingAs($owner)->withHeaders($h)->postJson('/api/v1/orders', [
            'buyer' => ['name' => 'K', 'phone' => '0912345678', 'address' => 'x', 'province' => 'HN'],
            'items' => [['sku_id' => $sku->getKey(), 'name' => 'A', 'quantity' => 1, 'unit_price' => 150000]],
            'customer_id' => $c->getKey(), 'prepaid_amount' => 150000, 'wallet_amount' => 150000,
        ])->assertStatus(422);
        $this->assertSame(0, Order::withoutGlobalScope(TenantScope::class)->count());
    }
}
```

- [ ] **Step 2: Run — fails** (`cd app && php artisan test --filter=ManualOrderWalletTest`).

- [ ] **Step 3: OrderController validation** — trong rule mảng của `store` (POST /orders), thêm:

```php
'customer_id' => ['sometimes', 'nullable', 'integer'],
'wallet_amount' => ['sometimes', 'nullable', 'integer', 'min:0'],
```

(Tìm method tạo đơn manual trong `OrderController` — `store`/`create`; thêm vào `$request->validate([...])`. Truyền cả `customer_id`,`wallet_amount` xuống `ManualOrderService::create($tenantId,$userId,$data)` — `$data` đã là toàn bộ validated, đảm bảo 2 key này nằm trong `$data`.)

- [ ] **Step 4: ManualOrderService — trừ ví**

Inject contract vào constructor:

```php
public function __construct(private readonly \CMBcoreSeller\Modules\Customers\Contracts\CustomerWallet $wallet) {}
```

Trong `create()`, sau khi `$order = DB::transaction(...)` — KHÔNG, phải trừ ví TRONG transaction để rollback nếu thiếu. Sửa: đưa phần trừ ví vào trong closure `DB::transaction`, sau khi `$order` được tạo & trước khi `return $order;`:

```php
// Trừ ví trả trước (nếu đơn dùng ví). wallet_amount ⊆ prepaid_amount ⊆ balance (validate ở service).
$walletAmount = max(0, (int) ($data['wallet_amount'] ?? 0));
$customerId = (int) ($data['customer_id'] ?? 0);
if ($walletAmount > 0) {
    if ($customerId <= 0) {
        throw ValidationException::withMessages(['wallet_amount' => 'Thiếu khách hàng để trừ ví.']);
    }
    if ($walletAmount > $prepaidAmount) {
        throw ValidationException::withMessages(['wallet_amount' => 'Số tiền trừ ví vượt số đã trả trước của đơn.']);
    }
    // deductForOrder throw RuntimeException nếu số dư không đủ ⇒ map sang 422 ở controller (đã catch RuntimeException → ValidationException? nếu chưa, bọc ở đây).
    try {
        $this->wallet->deductForOrder($tenantId, $customerId, (int) $order->getKey(), $walletAmount, $userId);
    } catch (\RuntimeException $e) {
        throw ValidationException::withMessages(['wallet_amount' => $e->getMessage()]);
    }
}
```

Đảm bảo `$prepaidAmount`, `$tenantId`, `$userId`, `$data` nằm trong `use(...)` của closure (thêm `$prepaidAmount` nếu thiếu). `ValidationException` đã `use` sẵn ở đầu file.

- [ ] **Step 5: Run — passes** (`cd app && php artisan test --filter=ManualOrderWalletTest`).

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Orders/Services/ManualOrderService.php app/app/Modules/Orders/Http/Controllers/OrderController.php app/tests/Feature/Orders/ManualOrderWalletTest.php
git commit -m "feat(orders): tạo đơn manual trừ ví trả trước (customer_id+wallet_amount), COD theo prepaid"
```

---

## Task 6: Hoàn ví khi huỷ/hoàn đơn (listener)

**Files:**
- Create: `app/app/Modules/Customers/Listeners/RefundWalletOnOrderCancelled.php`
- Modify: `app/app/Modules/Customers/CustomersServiceProvider.php` (Event::listen)
- Test: `app/tests/Feature/Customers/RefundWalletOnCancelTest.php`

**Interfaces:**
- Consumes: `OrderStatusChanged(order, from, to, source)`; `CustomerWallet::refundForOrder(...)`.
- Khi `to ∈ {Cancelled, ReturnedRefunded}` và đơn có `order_payment` ví → hoàn ví (idempotent).

- [ ] **Step 1: Write failing test**

Create `app/tests/Feature/Customers/RefundWalletOnCancelTest.php`:

```php
<?php

namespace Tests\Feature\Customers;

use CMBcoreSeller\Modules\Customers\Contracts\CustomerWallet;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Orders\Events\OrderStatusChanged;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundWalletOnCancelTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancel_refunds_wallet(): void
    {
        $t = Tenant::create(['name' => 'S']);
        $this->seedAccounting((int) $t->getKey());
        $c = Customer::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $t->getKey(), 'phone_hash' => str_repeat('f', 64), 'name' => 'K', 'first_seen_at' => now(), 'last_seen_at' => now()]);
        $w = app(CustomerWallet::class);
        $w->topup((int) $t->getKey(), (int) $c->getKey(), 300000, 'cash', null, 1);
        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t->getKey(), 'source' => 'manual', 'channel_account_id' => null, 'customer_id' => $c->getKey(),
            'external_order_id' => null, 'order_number' => 'M-1', 'status' => StandardOrderStatus::Processing, 'raw_status' => 'processing',
            'currency' => 'VND', 'grand_total' => 120000, 'item_total' => 120000, 'prepaid_amount' => 120000, 'cod_amount' => 0,
            'placed_at' => now(), 'source_updated_at' => now(), 'tags' => [], 'carrier' => 'manual',
        ]);
        $w->deductForOrder((int) $t->getKey(), (int) $c->getKey(), (int) $order->getKey(), 120000, 1);
        $this->assertSame(180000, (int) $c->refresh()->prepaid_balance);

        event(new OrderStatusChanged($order, StandardOrderStatus::Processing, StandardOrderStatus::Cancelled, 'user'));

        $this->assertSame(300000, (int) $c->refresh()->prepaid_balance);
    }
}
```

- [ ] **Step 2: Run — fails** (`cd app && php artisan test --filter=RefundWalletOnCancelTest`).

- [ ] **Step 3: Listener**

Create `RefundWalletOnOrderCancelled.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Customers\Listeners;

use CMBcoreSeller\Modules\Customers\Contracts\CustomerWallet;
use CMBcoreSeller\Modules\Orders\Events\OrderStatusChanged;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;

/**
 * Hoàn ví trả trước khi đơn bị huỷ/hoàn (idempotent). Đơn không trả bằng ví ⇒ no-op (refundForOrder trả null).
 * SPEC 2026-06-26.
 */
class RefundWalletOnOrderCancelled
{
    public function __construct(private readonly CustomerWallet $wallet) {}

    public function handle(OrderStatusChanged $event): void
    {
        if (! in_array($event->to, [StandardOrderStatus::Cancelled, StandardOrderStatus::ReturnedRefunded], true)) {
            return;
        }
        $order = $event->order;
        if (! $order->customer_id) {
            return;
        }
        $this->wallet->refundForOrder((int) $order->tenant_id, (int) $order->customer_id, (int) $order->getKey(), null);
    }
}
```

- [ ] **Step 4: Register listener** — trong `CustomersServiceProvider::boot()` (cạnh `Event::listen(OrderUpserted...`):

```php
Event::listen(\CMBcoreSeller\Modules\Orders\Events\OrderStatusChanged::class, \CMBcoreSeller\Modules\Customers\Listeners\RefundWalletOnOrderCancelled::class);
```

- [ ] **Step 5: Run — passes** (`cd app && php artisan test --filter=RefundWalletOnCancelTest`).

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Customers/Listeners/RefundWalletOnOrderCancelled.php app/app/Modules/Customers/CustomersServiceProvider.php app/tests/Feature/Customers/RefundWalletOnCancelTest.php
git commit -m "feat(customers): hoàn ví khi huỷ/hoàn đơn (listener OrderStatusChanged, idempotent)"
```

---

## Task 7: FE — màn khách (số dư ví + nạp tiền + lịch sử) & màn tạo đơn (panel ví)

**Files:**
- Modify: `app/resources/js/lib/customers.ts` (hooks + types) — kiểm path thực tế (`features/customers` hoặc `lib/customers`).
- Modify: customer detail component (panel ví).
- Modify: create-order page (panel ví).

**Interfaces:**
- `useCustomerWalletTopup()` → POST topup; `useCustomerWalletTransactions(id)` → GET; Customer type thêm `prepaid_balance`.

- [ ] **Step 1: Tìm file FE** — `cd app && grep -rln "customers/lookup\|useCustomerLookup\|prepaid_amount\|taodon" resources/js | head`. Xác định: hook customers, trang tạo đơn, trang chi tiết khách.

- [ ] **Step 2: Thêm type + hooks** (trong file customers FE):

```ts
// type Customer thêm:
prepaid_balance?: number;

export function useCustomerWalletTopup() {
  const api = useScopedApi();
  const qc = useQueryClient();
  const tenantId = useCurrentTenantId();
  return useMutation({
    mutationFn: async ({ id, amount, payment_method, note }: { id: number; amount: number; payment_method: 'cash'|'bank'|'ewallet'; note?: string }) => {
      const { data } = await api!.post<{ data: { balance: number } }>(`/customers/${id}/wallet/topup`, { amount, payment_method, note });
      return data.data;
    },
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['customers', tenantId] }); qc.invalidateQueries({ queryKey: ['customer-wallet-tx', tenantId] }); },
  });
}

export function useCustomerWalletTransactions(id: number) {
  const api = useScopedApi();
  const tenantId = useCurrentTenantId();
  return useQuery({
    queryKey: ['customer-wallet-tx', tenantId, id],
    queryFn: async () => (await api!.get(`/customers/${id}/wallet/transactions`)).data,
  });
}
```

(Khớp pattern import thực tế: `useScopedApi`, `useCurrentTenantId`, `useQueryClient` — xem các hook customers hiện có.)

- [ ] **Step 3: Customer detail — panel ví**: hiển thị `Số dư ví: {prepaid_balance}đ`, nút "Nạp tiền" (modal: amount + Radio phương thức cash/bank/ewallet + note) gọi `useCustomerWalletTopup`, và bảng lịch sử từ `useCustomerWalletTransactions`. Icon `@ant-design/icons` (WalletOutlined).

- [ ] **Step 4: Create-order — panel ví**: khi `lookup` trả `customer.prepaid_balance > 0`, hiện panel:
  - Hiển thị số dư.
  - Checkbox/Switch "Dùng ví trả trước".
  - Switch "Trừ cả tiền ship vào ví".
  - Tính: `target = trừShip ? grandTotal : grandTotal - shippingFee`; `walletAmount = Math.min(balance, Math.max(0, target))`; set `prepaid_amount` (cộng vào prepaid hiện có nếu có) & gửi `customer_id` + `wallet_amount` khi submit.
  - Hiển thị "COD còn lại: {grandTotal - prepaid_amount}đ" realtime.

- [ ] **Step 5: Gate FE** — chỉ hiện nút "Nạp tiền" nếu user có quyền tương ứng (dùng `useCan` như các trang khác).

- [ ] **Step 6: Quality gate FE**

Run: `cd app && npm run typecheck && npm run lint && npm run build`
Expected: xanh.

- [ ] **Step 7: Commit**

```bash
git add app/resources/js
git commit -m "feat(customers): FE ví trả trước — màn khách (nạp tiền+lịch sử) + panel ví ở tạo đơn (toggle ship, COD realtime)"
```

---

## Task 8: Docs + quality gate tổng

**Files:**
- Modify: `docs/05-api/endpoints.md`

- [ ] **Step 1: endpoints.md** — thêm 2 endpoint wallet + ghi chú POST /orders nhận `customer_id`,`wallet_amount`; lookup/customer resource trả `prepaid_balance`. (`grep -n "customers/lookup\|orders'" docs/05-api/endpoints.md`).

- [ ] **Step 2: Quality gate BE**

Run: `cd app && vendor/bin/pint --test app/Modules/Customers app/Modules/Accounting app/Modules/Orders && vendor/bin/phpstan analyse app/Modules/Customers app/Modules/Accounting/Services/CustomerAdvanceLedgerService.php app/Modules/Accounting/Contracts/CustomerAdvanceLedger.php && php artisan test --filter="Wallet|CustomerAdvanceLedger|ManualOrderWallet|RefundWallet"`
Expected: pint xanh; phpstan KHÔNG thêm lỗi mới (so baseline); test wallet PASS.

- [ ] **Step 3: Commit**

```bash
git add docs/05-api/endpoints.md
git commit -m "docs(customers): endpoints ví trả trước + POST /orders wallet fields"
```

---

## Self-Review

**Spec coverage:** §A schema → Task 1; §B nạp tiền + GL → Task 2,3,4; §C áp ví tạo đơn (partial + toggle ship) → Task 5 (toggle ship = FE tính prepaid/wallet_amount, Task 7) ; §D hạch toán dồn tích 131 → Task 2 (top-up) + PostOnOrderShipped sẵn có (không sửa); §E hoàn ví → Task 6; §6 API/UI → Task 4,7; §9 test → mỗi task có test; docs → Task 8. Edge cases §7: partial (Task 5/7), race lockForUpdate (Task 3), idempotent (Task 3 unique), vượt số dư 422 (Task 5), chưa có customer (wallet_amount=0 ⇒ bỏ qua), chặn sửa prepaid sau tạo = ngoài phạm vi (spec §2/§7 — không task).

**Placeholder scan:** không TBD; mỗi bước có code. Các "kiểm key quyền / path FE thực tế" là chỉ dẫn xác minh tại chỗ (đã trỏ cách tìm), không phải placeholder thiếu.

**Type consistency:** `CustomerWallet::deductForOrder/refundForOrder/topup` chữ ký khớp giữa Task 3 (định nghĩa) và Task 5/6 (dùng). `CustomerAdvanceLedger::recordTopup` khớp Task 2↔3. `customer_id`,`wallet_amount` khớp Task 5 (BE) ↔ Task 7 (FE). `prepaid_balance` khớp Task 1 (model) ↔ 4 (resource) ↔ 7 (FE).
