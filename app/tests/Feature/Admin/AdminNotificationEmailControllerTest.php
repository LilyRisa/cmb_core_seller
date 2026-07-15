<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Admin\Models\AdminNotificationRecipient;
use CMBcoreSeller\Modules\Admin\Notifications\AdminAlertNotification;
use CMBcoreSeller\Modules\Admin\Notifications\NotificationTypeCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminNotificationEmailControllerTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminUser::factory()->create();
    }

    public function test_guest_cannot_access(): void
    {
        $this->getJson('/api/v1/admin/notification-emails')->assertUnauthorized();
    }

    public function test_admin_can_create_recipient_with_subscriptions(): void
    {
        $this->actingAs($this->admin, 'admin_web')
            ->postJson('/api/v1/admin/notification-emails', [
                'email' => 'alerts@x.com',
                'label' => 'Đội vận hành',
                'notification_types' => [NotificationTypeCatalog::SUPPORT_NEW_CONVERSATION],
            ])
            ->assertCreated()
            ->assertJsonPath('data.email', 'alerts@x.com')
            ->assertJsonPath('data.notification_types.0', NotificationTypeCatalog::SUPPORT_NEW_CONVERSATION);
    }

    public function test_duplicate_email_rejected(): void
    {
        AdminNotificationRecipient::create(['email' => 'dup@x.com']);

        $this->actingAs($this->admin, 'admin_web')
            ->postJson('/api/v1/admin/notification-emails', [
                'email' => 'dup@x.com',
                'notification_types' => [NotificationTypeCatalog::AUTH_USER_VERIFIED],
            ])
            ->assertStatus(422);
    }

    public function test_invalid_notification_type_rejected(): void
    {
        $this->actingAs($this->admin, 'admin_web')
            ->postJson('/api/v1/admin/notification-emails', [
                'email' => 'bad@x.com',
                'notification_types' => ['made.up'],
            ])
            ->assertStatus(422);
    }

    public function test_update_overrides_subscriptions(): void
    {
        $recipient = AdminNotificationRecipient::create(['email' => 'switch@x.com']);
        $recipient->subscriptions()->create([
            'notification_type' => NotificationTypeCatalog::SUPPORT_NEW_CONVERSATION, 'created_at' => now(),
        ]);

        $this->actingAs($this->admin, 'admin_web')
            ->patchJson("/api/v1/admin/notification-emails/{$recipient->id}", [
                'notification_types' => [NotificationTypeCatalog::AUTH_USER_VERIFIED],
            ])
            ->assertOk()
            ->assertJsonPath('data.notification_types', [NotificationTypeCatalog::AUTH_USER_VERIFIED]);
    }

    public function test_delete_removes_recipient(): void
    {
        $recipient = AdminNotificationRecipient::create(['email' => 'gone@x.com']);

        $this->actingAs($this->admin, 'admin_web')
            ->deleteJson("/api/v1/admin/notification-emails/{$recipient->id}")
            ->assertOk();

        $this->assertNull(AdminNotificationRecipient::find($recipient->id));
    }

    public function test_send_test_email(): void
    {
        Notification::fake();
        $recipient = AdminNotificationRecipient::create(['email' => 'test@x.com']);

        $this->actingAs($this->admin, 'admin_web')
            ->postJson("/api/v1/admin/notification-emails/{$recipient->id}/test")
            ->assertOk()
            ->assertJsonPath('data.sent', true);

        // NOTE: AnonymousNotifiable::route() stores the on-demand route as a scalar
        // (`$this->routes[$channel] = $route`), not an array — so `routes['mail']` is
        // the email string itself, not a list. Same behavioral strength as the brief's
        // `in_array(..., $notifiable->routes['mail'], true)`, adapted to the real shape.
        Notification::assertSentOnDemand(
            AdminAlertNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'test@x.com',
        );
    }

    public function test_types_endpoint_lists_catalog(): void
    {
        $this->actingAs($this->admin, 'admin_web')
            ->getJson('/api/v1/admin/notification-emails/types')
            ->assertOk()
            ->assertJsonFragment(['code' => NotificationTypeCatalog::SUPPORT_NEW_CONVERSATION]);
    }
}
