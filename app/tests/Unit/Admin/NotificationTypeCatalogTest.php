<?php

namespace Tests\Unit\Admin;

use CMBcoreSeller\Modules\Admin\Notifications\NotificationTypeCatalog;
use Tests\TestCase;

class NotificationTypeCatalogTest extends TestCase
{
    public function test_all_returns_expected_codes(): void
    {
        $types = NotificationTypeCatalog::all();

        $this->assertArrayHasKey('support.new_conversation', $types);
        $this->assertArrayHasKey('auth.user_verified', $types);
        $this->assertSame(NotificationTypeCatalog::SUPPORT_NEW_CONVERSATION, 'support.new_conversation');
        $this->assertSame(NotificationTypeCatalog::AUTH_USER_VERIFIED, 'auth.user_verified');
    }

    public function test_is_valid_rejects_unknown_code(): void
    {
        $this->assertTrue(NotificationTypeCatalog::isValid('support.new_conversation'));
        $this->assertTrue(NotificationTypeCatalog::isValid('auth.user_verified'));
        $this->assertFalse(NotificationTypeCatalog::isValid('made.up.type'));
    }
}
