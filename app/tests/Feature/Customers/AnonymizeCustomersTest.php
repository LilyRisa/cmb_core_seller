<?php

namespace Tests\Feature\Customers;

use CMBcoreSeller\Modules\Channels\Events\DataDeletionRequested;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Customers\Jobs\AnonymizeCustomersForShop;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Customers\Services\CustomerAnonymizer;
use CMBcoreSeller\Modules\Customers\Support\CustomerPhoneNormalizer;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class AnonymizeCustomersTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $shopA;

    private ChannelAccount $shopB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->shopA = $this->makeAccount('A');
        $this->shopB = $this->makeAccount('B');
    }

    private function makeAccount(string $suffix): ChannelAccount
    {
        return ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'tiktok',
            'external_shop_id' => 'shop-'.$suffix, 'shop_name' => 'Shop '.$suffix, 'status' => ChannelAccount::STATUS_ACTIVE,
        ]);
    }

    private function customerWithOrders(string $phone, string $name, array $accounts): Customer
    {
        $c = Customer::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'phone_hash' => CustomerPhoneNormalizer::hash($phone),
            'phone' => $phone, 'name' => $name, 'lifetime_stats' => ['orders_total' => count($accounts)],
            'addresses_meta' => [['address' => 'Số 1', 'city' => 'Hà Nội']], 'tags' => [],
            'first_seen_at' => now(), 'last_seen_at' => now(),
        ]);
        foreach ($accounts as $i => $acc) {
            Order::withoutGlobalScope(TenantScope::class)->create([
                'tenant_id' => $this->tenant->getKey(), 'source' => 'tiktok', 'channel_account_id' => $acc->getKey(), 'customer_id' => $c->getKey(),
                'external_order_id' => "{$name}-{$i}", 'order_number' => "{$name}-{$i}", 'status' => StandardOrderStatus::Completed, 'raw_status' => 'X',
                'currency' => 'VND', 'grand_total' => 100000, 'item_total' => 100000, 'placed_at' => now(), 'has_issue' => false, 'tags' => [], 'source_updated_at' => now(),
            ]);
        }

        return $c;
    }

    public function test_single_shop_customer_is_anonymized_multi_shop_kept(): void
    {
        $single = $this->customerWithOrders('0987654321', 'Single', [$this->shopA]);
        $multi = $this->customerWithOrders('0911111111', 'Multi', [$this->shopA, $this->shopB]);

        $count = app(CustomerAnonymizer::class)->anonymizeForShop((int) $this->tenant->getKey(), (int) $this->shopA->getKey());
        $this->assertSame(1, $count);

        $single->refresh();
        $this->assertTrue($single->isAnonymized());
        $this->assertSame(CustomerAnonymizer::PLACEHOLDER, $single->phone);
        $this->assertNull($single->name);
        $this->assertSame([], $single->addresses_meta);
        $this->assertSame(CustomerPhoneNormalizer::hash('0987654321'), $single->phone_hash);   // kept (non-identifying aggregate key)
        $this->assertSame(1, $single->lifetime_stats['orders_total']);                          // stats kept

        $multi->refresh();
        $this->assertFalse($multi->isAnonymized());
        $this->assertSame('0911111111', $multi->phone);
        $this->assertSame('Multi', $multi->name);
    }

    public function test_data_deletion_event_dispatches_anonymize_job(): void
    {
        Bus::fake();
        DataDeletionRequested::dispatch($this->shopA);
        Bus::assertDispatched(AnonymizeCustomersForShop::class,
            fn ($j) => $j->tenantId === (int) $this->tenant->getKey() && $j->channelAccountId === (int) $this->shopA->getKey());
    }
}
