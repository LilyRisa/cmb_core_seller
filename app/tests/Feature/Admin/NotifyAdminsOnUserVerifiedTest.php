<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Admin\Models\AdminNotificationRecipient;
use CMBcoreSeller\Modules\Admin\Models\AdminNotificationSubscription;
use CMBcoreSeller\Modules\Admin\Notifications\AdminAlertNotification;
use CMBcoreSeller\Modules\Admin\Notifications\NotificationTypeCatalog;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotifyAdminsOnUserVerifiedTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscribed_admin_receives_alert_when_user_verified(): void
    {
        Notification::fake();
        $recipient = AdminNotificationRecipient::create(['email' => 'growth@x.com', 'is_active' => true]);
        AdminNotificationSubscription::create([
            'admin_notification_recipient_id' => $recipient->id,
            'notification_type' => NotificationTypeCatalog::AUTH_USER_VERIFIED,
            'created_at' => now(),
        ]);
        $user = User::factory()->unverified()->create(['name' => 'Chị Lan', 'email' => 'lan@shop.vn']);

        event(new Verified($user));

        Notification::assertSentOnDemand(
            AdminAlertNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'growth@x.com',
        );
    }

    public function test_unsubscribed_admin_does_not_receive_alert(): void
    {
        Notification::fake();
        AdminNotificationRecipient::create(['email' => 'other@x.com', 'is_active' => true]); // không subscribe
        $user = User::factory()->unverified()->create();

        event(new Verified($user));

        // Verify no AdminAlertNotification was sent (check notifications property via reflection)
        $notifications = (new \ReflectionClass(Notification::getFacadeRoot()))->getProperty('notifications');
        $notifications->setAccessible(true);
        $sent = $notifications->getValue(Notification::getFacadeRoot());

        $adminAlerts = collect($sent)->flatMap(fn ($n) => $n)->filter(
            fn ($item) => isset($item['notification']) && $item['notification'] instanceof AdminAlertNotification
        );
        $this->assertEmpty($adminAlerts);
    }
}
