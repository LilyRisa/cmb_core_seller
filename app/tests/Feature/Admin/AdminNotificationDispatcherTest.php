<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Modules\Admin\Models\AdminNotificationRecipient;
use CMBcoreSeller\Modules\Admin\Models\AdminNotificationSubscription;
use CMBcoreSeller\Modules\Admin\Notifications\AdminAlertNotification;
use CMBcoreSeller\Modules\Admin\Notifications\NotificationTypeCatalog;
use CMBcoreSeller\Modules\Admin\Notifications\Services\AdminNotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminNotificationDispatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_notify_sends_only_to_active_subscribed_emails(): void
    {
        Notification::fake();

        $subscribed = AdminNotificationRecipient::create(['email' => 'sub@x.com', 'is_active' => true]);
        AdminNotificationSubscription::create([
            'admin_notification_recipient_id' => $subscribed->id,
            'notification_type' => NotificationTypeCatalog::SUPPORT_NEW_CONVERSATION,
            'created_at' => now(),
        ]);

        $notSubscribed = AdminNotificationRecipient::create(['email' => 'nosub@x.com', 'is_active' => true]);
        AdminNotificationSubscription::create([
            'admin_notification_recipient_id' => $notSubscribed->id,
            'notification_type' => NotificationTypeCatalog::AUTH_USER_VERIFIED,
            'created_at' => now(),
        ]);

        $inactive = AdminNotificationRecipient::create(['email' => 'inactive@x.com', 'is_active' => false]);
        AdminNotificationSubscription::create([
            'admin_notification_recipient_id' => $inactive->id,
            'notification_type' => NotificationTypeCatalog::SUPPORT_NEW_CONVERSATION,
            'created_at' => now(),
        ]);

        app(AdminNotificationDispatcher::class)->notify(
            NotificationTypeCatalog::SUPPORT_NEW_CONVERSATION,
            ['tenant_name' => 'Shop Test', 'snippet' => 'Xin chào', 'conversation_id' => 1],
        );

        Notification::assertCount(1);
        Notification::assertSentOnDemand(
            AdminAlertNotification::class,
            fn ($notification, $channels, $notifiable) => in_array('sub@x.com', (array) ($notifiable->routes['mail'] ?? []), true),
        );
    }

    public function test_notify_is_noop_when_nobody_subscribed(): void
    {
        Notification::fake();

        app(AdminNotificationDispatcher::class)->notify(NotificationTypeCatalog::AUTH_USER_VERIFIED, []);

        Notification::assertCount(0);
    }
}
