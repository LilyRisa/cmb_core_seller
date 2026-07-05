<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessInfoPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_info_is_stored_as_array(): void
    {
        MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->create([
            'channel_account_id' => 1, 'tenant_id' => 1,
            'business_info' => ['shop_name' => 'Shop A', 'phone' => '0900'],
        ]);

        $meta = MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->find(1);
        $this->assertSame('Shop A', $meta->business_info['shop_name']);
        $this->assertSame('0900', $meta->business_info['phone']);
    }
}
