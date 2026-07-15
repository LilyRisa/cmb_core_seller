<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Modules\Admin\Models\AdminNotificationRecipient;
use CMBcoreSeller\Modules\Admin\Models\AdminNotificationSubscription;
use CMBcoreSeller\Modules\Admin\Notifications\AdminAlertNotification;
use CMBcoreSeller\Modules\Admin\Notifications\NotificationTypeCatalog;
use CMBcoreSeller\Modules\Support\Events\SupportNewConversationOpened;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotifyAdminsOnNewSupportConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscribed_admin_receives_alert_on_new_conversation(): void
    {
        Notification::fake();
        $tenant = Tenant::create(['name' => 'Shop Alert Test']);
        $recipient = AdminNotificationRecipient::create(['email' => 'ops@x.com', 'is_active' => true]);
        AdminNotificationSubscription::create([
            'admin_notification_recipient_id' => $recipient->id,
            'notification_type' => NotificationTypeCatalog::SUPPORT_NEW_CONVERSATION,
            'created_at' => now(),
        ]);

        event(new SupportNewConversationOpened(1, $tenant->getKey(), null, 'Cần hỗ trợ gấp'));

        Notification::assertSentOnDemand(
            AdminAlertNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'ops@x.com',
        );
    }

    public function test_unsubscribed_admin_does_not_receive_alert(): void
    {
        Notification::fake();
        $tenant = Tenant::create(['name' => 'Shop Alert Test 2']);
        AdminNotificationRecipient::create(['email' => 'other@x.com', 'is_active' => true]); // không subscribe

        event(new SupportNewConversationOpened(1, $tenant->getKey(), null, 'Nội dung'));

        Notification::assertCount(0);
    }
}
