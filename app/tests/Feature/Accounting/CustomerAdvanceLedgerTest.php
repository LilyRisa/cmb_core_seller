<?php

namespace Tests\Feature\Accounting;

use CMBcoreSeller\Modules\Accounting\Contracts\CustomerAdvanceLedger;
use CMBcoreSeller\Modules\Accounting\Services\AccountingSetupService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAdvanceLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_topup_posts_dr_cash_cr_131(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);
        app(AccountingSetupService::class)->run((int) $tenant->getKey(), 2026);

        $jeId = app(CustomerAdvanceLedger::class)->recordTopup(
            (int) $tenant->getKey(), customerId: 1, amount: 200000, paymentMethod: 'cash', memo: 'Nạp ví', userId: 1
        );

        $this->assertGreaterThan(0, $jeId);
        $this->assertDatabaseHas('journal_lines', ['cr_amount' => 200000, 'account_code' => '131', 'party_type' => 'customer', 'party_id' => 1]);
        $this->assertDatabaseHas('journal_lines', ['dr_amount' => 200000, 'account_code' => '1111']);
    }
}
