<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Contracts\ExpoPushSenderContract;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Services\ExpoPushSender;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\MobileDevice;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * SPEC 0029 — Expo Push digest tích hợp vào `messaging:push-digest`.
 * KHÔNG gửi push thật — mock HTTP facade tới exp.host.
 */
class ExpoPushDigestTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.expo.enabled' => true]);

        $this->tenant = Tenant::create(['name' => 'ExpoShop']);
        app(CurrentTenant::class)->set($this->tenant);

        $account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_E', 'shop_name' => 'Trang E', 'status' => ChannelAccount::STATUS_ACTIVE,
            'messaging_enabled' => true,
        ]);

        // 1 hội thoại có inbound MỚI (sau baseline device.last_notified_at).
        Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $account->getKey(),
            'provider' => 'facebook_page', 'external_conversation_id' => 'PSID_E', 'buyer_external_id' => 'PSID_E',
            'status' => Conversation::STATUS_OPEN, 'last_message_at' => now(), 'last_inbound_at' => now(),
        ]);
    }

    private function device(string $token, array $overrides = []): MobileDevice
    {
        return MobileDevice::query()->create(array_merge([
            'tenant_id' => $this->tenant->getKey(),
            'user_id' => 1,
            'expo_push_token' => $token,
            'platform' => 'ios',
            'last_seen_at' => now()->subHour(),
            'last_notified_at' => now()->subHour(),
        ], $overrides));
    }

    public function test_contract_is_bound_to_expo_push_sender(): void
    {
        $this->assertInstanceOf(ExpoPushSender::class, app(ExpoPushSenderContract::class));
    }

    public function test_digest_sends_expo_push_for_inactive_device_with_new_messages(): void
    {
        Http::fake([
            'exp.host/*' => Http::response(['data' => [['status' => 'ok', 'id' => 'abc123']]], 200),
        ]);

        $device = $this->device('ExponentPushToken[aaa]');

        $this->artisan('messaging:push-digest')->assertSuccessful();

        Http::assertSent(function ($request) use ($device) {
            $body = $request->data()[0] ?? [];

            return str_contains($request->url(), 'exp.host')
                && ($body['to'] ?? null) === $device->expo_push_token
                && ($body['body'] ?? null) === 'Bạn có tin nhắn mới';
        });

        $this->assertTrue($device->fresh()->last_notified_at->gt(now()->subMinute()));
    }

    public function test_digest_skips_device_with_no_new_messages(): void
    {
        Http::fake();

        // Baseline = now ⇒ inbound (now) không mới hơn ⇒ không push.
        $this->device('ExponentPushToken[bbb]', ['last_notified_at' => now()->addSecond()]);

        $this->artisan('messaging:push-digest')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_digest_skips_active_device(): void
    {
        Http::fake();

        // last_seen_at mới (heartbeat) ⇒ thiết bị đang hoạt động ⇒ bỏ qua.
        $this->device('ExponentPushToken[active]', ['last_seen_at' => now()]);

        $this->artisan('messaging:push-digest')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_digest_removes_device_on_not_registered_error(): void
    {
        Http::fake([
            'exp.host/*' => Http::response([
                'data' => [['status' => 'error', 'details' => ['error' => 'DeviceNotRegistered']]],
            ], 200),
        ]);

        $device = $this->device('ExponentPushToken[expired]');

        $this->artisan('messaging:push-digest')->assertSuccessful();

        $this->assertDatabaseMissing('mobile_devices', ['id' => $device->getKey()]);
    }

    public function test_digest_skips_expo_when_disabled(): void
    {
        config(['services.expo.enabled' => false]);
        Http::fake();

        $this->device('ExponentPushToken[disabled]');

        $this->artisan('messaging:push-digest')->assertSuccessful();

        Http::assertNothingSent();
    }
}
