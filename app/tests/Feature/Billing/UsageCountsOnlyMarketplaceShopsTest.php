<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Services\UsageService;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hạn mức gói "gian hàng" CHỈ đếm sàn TMĐT đã kết nối — loại kênh nhắn tin
 * (facebook_page, lazada_im) khỏi `UsageService::channelAccounts`. Khớp với danh
 * sách "Gian hàng" (ChannelAccountController@index) để số liệu không lệch.
 */
class UsageCountsOnlyMarketplaceShopsTest extends TestCase
{
    use RefreshDatabase;

    private function shop(int $tenantId, string $provider, string $extId, string $status = ChannelAccount::STATUS_ACTIVE): void
    {
        ChannelAccount::query()->create([
            'tenant_id' => $tenantId,
            'provider' => $provider,
            'external_shop_id' => $extId,
            'status' => $status,
        ]);
    }

    public function test_counts_only_active_marketplace_shops(): void
    {
        $tenant = Tenant::create(['name' => 'CountShop']);
        $tid = (int) $tenant->getKey();

        // Kênh nhắn tin — KHÔNG tính.
        $this->shop($tid, 'facebook_page', 'PAGE_1');
        $this->shop($tid, 'lazada_im', 'LZ_IM_1');
        // Sàn TMĐT active — tính.
        $this->shop($tid, 'tiktok', 'TT_1');
        $this->shop($tid, 'shopee', 'SP_1');
        $this->shop($tid, 'lazada', 'LZ_1');
        // Sàn nhưng không active — KHÔNG tính.
        $this->shop($tid, 'tiktok', 'TT_2', ChannelAccount::STATUS_DISABLED);

        $this->assertSame(3, app(UsageService::class)->channelAccounts($tid));
    }
}
