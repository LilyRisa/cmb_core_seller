<?php

namespace Tests\Feature\Messaging;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MessagingBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrations_add_sync_and_block_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('messaging_account_meta', [
            'page_avatar_path', 'page_avatar_synced_at', 'sync_status',
            'sync_total_conversations', 'sync_done_conversations', 'sync_message_count',
            'sync_cursor', 'sync_started_at', 'sync_finished_at', 'sync_error', 'last_synced_at',
        ]));
        $this->assertTrue(Schema::hasColumns('conversations', [
            'blocked_at', 'blocked_by_user_id', 'manually_unread', 'buyer_avatar_path',
        ]));
    }
}
