<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Modules\Admin\Models\AdminNotificationRecipient;
use CMBcoreSeller\Modules\Admin\Models\AdminNotificationSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationRecipientModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_scope_and_subscribed_to(): void
    {
        $active = AdminNotificationRecipient::create(['email' => 'a@x.com', 'is_active' => true]);
        $inactive = AdminNotificationRecipient::create(['email' => 'b@x.com', 'is_active' => false]);
        AdminNotificationSubscription::create([
            'admin_notification_recipient_id' => $active->id,
            'notification_type' => 'support.new_conversation',
            'created_at' => now(),
        ]);

        $this->assertTrue($active->load('subscriptions')->subscribedTo('support.new_conversation'));
        $this->assertFalse($active->subscribedTo('auth.user_verified'));

        $activeIds = AdminNotificationRecipient::query()->active()->pluck('id')->all();
        $this->assertContains($active->id, $activeIds);
        $this->assertNotContains($inactive->id, $activeIds);
    }

    public function test_deleting_recipient_cascades_subscriptions(): void
    {
        $recipient = AdminNotificationRecipient::create(['email' => 'c@x.com']);
        AdminNotificationSubscription::create([
            'admin_notification_recipient_id' => $recipient->id,
            'notification_type' => 'auth.user_verified',
            'created_at' => now(),
        ]);

        $recipient->delete();

        $this->assertSame(
            0,
            AdminNotificationSubscription::query()->where('admin_notification_recipient_id', $recipient->id)->count(),
        );
    }
}
