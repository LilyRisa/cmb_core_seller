# Facebook Messaging — Backend Implementation Plan (sync + avatar + inbox management)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add historical message backfill (auto on connect + manual + periodic), page/buyer avatars relayed to object storage, and inbox management (mark-unread, unread filter, app-level block) to the Facebook Page messaging connector — all behind the provider-agnostic Messaging core.

**Architecture:** Hướng A from the spec — a generic capability (`inbound.backfill`) drives a core job `BackfillMessagingChannel` that paginates via the connector and ingests through the existing `MessageIngestionService`. The Facebook connector implements `fetchConversations`/`fetchMessages`/`fetchPageProfile`/`fetchUserProfile` against the Graph API `/conversations` edge. Avatars (expiring Graph URLs) are relayed to MinIO via a small service + queued job. Inbox management adds columns + endpoints + an ingest-time block guard.

**Tech Stack:** Laravel 11 (PHP 8.3), Eloquent, queued Jobs, `Illuminate\Support\Facades\Http` (+ `Http::fake` in tests), PHPUnit. No new Composer dependencies.

**Spec:** `docs/superpowers/specs/2026-05-21-facebook-messaging-sync-and-inbox-design.md` (slices 1–6). Frontend is a separate plan.

---

## File Structure

**Create:**
- `app/app/Modules/Messaging/Database/Migrations/2026_05_21_100001_add_sync_and_avatar_to_messaging_account_meta.php` — meta sync-state + page avatar columns
- `app/app/Modules/Messaging/Database/Migrations/2026_05_21_100002_add_block_and_unread_to_conversations.php` — `blocked_at`, `blocked_by_user_id`, `manually_unread`, `buyer_avatar_path`
- `app/app/Modules/Messaging/Services/MessagingAvatarRelay.php` — download an avatar URL → MinIO, return storage path
- `app/app/Modules/Messaging/Jobs/RelayMessagingAvatar.php` — queued wrapper (page | conversation target)
- `app/app/Modules/Messaging/Jobs/BackfillMessagingChannel.php` — core backfill orchestration
- `app/app/Modules/Messaging/Console/Commands/ReconcileMessagingSync.php` — periodic light backfill
- `app/tests/Unit/Messaging/FacebookBackfillConnectorTest.php` — connector fetch* unit tests
- `app/tests/Feature/Messaging/MessagingBackfillTest.php` — backfill job + sync endpoint feature tests
- `app/tests/Feature/Messaging/MessagingInboxManagementTest.php` — block / unblock / mark-unread / filters / send-guard

**Modify:**
- `app/app/Integrations/Messaging/Facebook/FacebookPageConnector.php` — capability + `fetchConversations`/`fetchMessages`/`fetchPageProfile`/`fetchUserProfile`
- `app/app/Modules/Messaging/Models/MessagingAccountMeta.php` — fillable/casts for new columns
- `app/app/Modules/Messaging/Models/Conversation.php` — fillable/casts/constants for new columns
- `app/app/Modules/Messaging/Services/MediaStorage.php` — add `temporaryUrlForPath()`
- `app/app/Modules/Messaging/Services/MessageIngestionService.php` — skip unread bump when blocked
- `app/app/Modules/Messaging/Http/Controllers/MessagingChannelController.php` — `sync()` + extend `index()` resource
- `app/app/Modules/Messaging/Http/Controllers/ConversationController.php` — `markUnread`/`block`/`unblock` + index filters + clear `manually_unread` on read
- `app/app/Modules/Messaging/Http/Controllers/MessageController.php` — block guard on send
- `app/app/Modules/Messaging/Http/Controllers/FacebookOAuthController.php` — ensure meta + dispatch backfill + page-avatar relay on connect
- `app/app/Modules/Messaging/Http/routes.php` — new routes
- `app/routes/console.php` — schedule reconcile

---

## Conventions (read once)

- Tests extend `Tests\TestCase`; feature tests `use RefreshDatabase`. Multi-tenant: pass header `['X-Tenant-Id' => (string) $tenant->getKey()]`, act as a user attached to the tenant with a `Role` (see existing `MessagingChannelControllerTest`).
- Background services run **without tenant scope**: `Model::withoutGlobalScope(TenantScope::class)`. Tenant comes from `$channelAccount->tenant_id`.
- HTTP to Graph is faked with `Http::fake(['graph.facebook.com/*' => Http::response([...], 200)])` and asserted with `Http::assertSent(...)`.
- Run a single test: `php artisan test --filter <TestClass>` from `app/`. Lint/static: `./vendor/bin/pint` + `./vendor/bin/phpstan analyse` (run from `app/`).
- Commit after each task (messages in Vietnamese to match repo history; end with the Co-Authored-By trailer used in this repo).

---

## Task 1: Migrations + model fields (sync-state, avatars, block, manual-unread)

**Files:**
- Create: `app/app/Modules/Messaging/Database/Migrations/2026_05_21_100001_add_sync_and_avatar_to_messaging_account_meta.php`
- Create: `app/app/Modules/Messaging/Database/Migrations/2026_05_21_100002_add_block_and_unread_to_conversations.php`
- Modify: `app/app/Modules/Messaging/Models/MessagingAccountMeta.php`
- Modify: `app/app/Modules/Messaging/Models/Conversation.php`
- Test: `app/tests/Feature/Messaging/MessagingBackfillTest.php` (first test only — schema smoke)

- [ ] **Step 1: Write the failing test** (`MessagingBackfillTest.php`)

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter MessagingBackfillTest` (from `app/`)
Expected: FAIL — columns do not exist.

- [ ] **Step 3: Write the meta migration**

`2026_05_21_100001_add_sync_and_avatar_to_messaging_account_meta.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** SPEC 2026-05-21: sync-state + page avatar cho backfill Facebook (slice 1). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messaging_account_meta', function (Blueprint $table) {
            $table->string('page_avatar_path', 512)->nullable()->after('settings');
            $table->timestamp('page_avatar_synced_at')->nullable()->after('page_avatar_path');
            $table->string('sync_status', 16)->default('idle')->after('page_avatar_synced_at'); // idle|queued|running|done|failed
            $table->unsignedInteger('sync_total_conversations')->nullable()->after('sync_status');
            $table->unsignedInteger('sync_done_conversations')->default(0)->after('sync_total_conversations');
            $table->unsignedInteger('sync_message_count')->default(0)->after('sync_done_conversations');
            $table->text('sync_cursor')->nullable()->after('sync_message_count');
            $table->timestamp('sync_started_at')->nullable()->after('sync_cursor');
            $table->timestamp('sync_finished_at')->nullable()->after('sync_started_at');
            $table->text('sync_error')->nullable()->after('sync_finished_at');
            $table->timestamp('last_synced_at')->nullable()->after('sync_error');
        });
    }

    public function down(): void
    {
        Schema::table('messaging_account_meta', function (Blueprint $table) {
            $table->dropColumn([
                'page_avatar_path', 'page_avatar_synced_at', 'sync_status',
                'sync_total_conversations', 'sync_done_conversations', 'sync_message_count',
                'sync_cursor', 'sync_started_at', 'sync_finished_at', 'sync_error', 'last_synced_at',
            ]);
        });
    }
};
```

- [ ] **Step 4: Write the conversations migration**

`2026_05_21_100002_add_block_and_unread_to_conversations.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** SPEC 2026-05-21: chặn người dùng (mức ứng dụng) + đánh dấu chưa đọc + avatar relay (slice 1). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->timestamp('blocked_at')->nullable()->after('status');
            $table->foreignId('blocked_by_user_id')->nullable()->after('blocked_at');
            $table->boolean('manually_unread')->default(false)->after('unread_count');
            $table->string('buyer_avatar_path', 512)->nullable()->after('buyer_avatar_url');
            $table->index(['tenant_id', 'blocked_at']);
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'blocked_at']);
            $table->dropColumn(['blocked_at', 'blocked_by_user_id', 'manually_unread', 'buyer_avatar_path']);
        });
    }
};
```

- [ ] **Step 5: Update `MessagingAccountMeta` model**

In `MessagingAccountMeta.php`, extend `$fillable` and `casts()`:

```php
    protected $fillable = [
        'channel_account_id', 'tenant_id',
        'messaging_enabled', 'last_inbound_at', 'last_outbound_at',
        'outbound_window_meta', 'ai_enabled', 'settings',
        // SPEC 2026-05-21: sync-state + page avatar
        'page_avatar_path', 'page_avatar_synced_at', 'sync_status',
        'sync_total_conversations', 'sync_done_conversations', 'sync_message_count',
        'sync_cursor', 'sync_started_at', 'sync_finished_at', 'sync_error', 'last_synced_at',
    ];
```

```php
    protected function casts(): array
    {
        return [
            'messaging_enabled' => 'boolean',
            'ai_enabled' => 'boolean',
            'last_inbound_at' => 'datetime',
            'last_outbound_at' => 'datetime',
            'outbound_window_meta' => 'array',
            'settings' => 'encrypted:array',
            // SPEC 2026-05-21
            'page_avatar_synced_at' => 'datetime',
            'sync_started_at' => 'datetime',
            'sync_finished_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }
```

Add status constants near the top of the class:

```php
    public const SYNC_IDLE = 'idle';
    public const SYNC_QUEUED = 'queued';
    public const SYNC_RUNNING = 'running';
    public const SYNC_DONE = 'done';
    public const SYNC_FAILED = 'failed';
```

- [ ] **Step 6: Update `Conversation` model**

In `Conversation.php`, add to `$fillable`: `'blocked_at', 'blocked_by_user_id', 'manually_unread', 'buyer_avatar_path'`. Add to `casts()`: `'blocked_at' => 'datetime'`, `'manually_unread' => 'boolean'`. Add a scope:

```php
    public function scopeNotBlocked(Builder $q): Builder
    {
        return $q->whereNull('blocked_at');
    }
```

- [ ] **Step 7: Run test to verify it passes**

Run: `php artisan test --filter MessagingBackfillTest`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add app/app/Modules/Messaging/Database/Migrations/2026_05_21_10000{1,2}_*.php \
        app/app/Modules/Messaging/Models/MessagingAccountMeta.php \
        app/app/Modules/Messaging/Models/Conversation.php \
        app/tests/Feature/Messaging/MessagingBackfillTest.php
git commit -m "feat(messaging): cột sync-state/avatar (meta) + blocked/manually_unread (conversations)"
```

---

## Task 2: Connector — capability + `fetchPageProfile` / `fetchUserProfile`

**Files:**
- Modify: `app/app/Integrations/Messaging/Facebook/FacebookPageConnector.php`
- Test: `app/tests/Unit/Messaging/FacebookBackfillConnectorTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector;
use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookSignatureVerifier;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookBackfillConnectorTest extends TestCase
{
    private function connector(): FacebookPageConnector
    {
        return new FacebookPageConnector(
            ['app_secret' => 'x', 'graph_version' => 'v19.0', 'app_id' => 'app123'],
            new FacebookSignatureVerifier,
        );
    }

    private function auth(): MessagingAuthContext
    {
        return new MessagingAuthContext(
            channelAccountId: 1, provider: 'facebook_page',
            externalShopId: 'PAGE_123', accessToken: 'PAGE_TOKEN',
        );
    }

    public function test_supports_backfill_capability(): void
    {
        $this->assertTrue($this->connector()->supports('inbound.backfill'));
    }

    public function test_fetch_page_profile_returns_name_and_avatar(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'name' => 'My Shop Page',
                'picture' => ['data' => ['url' => 'https://cdn.fb/pageavatar.jpg']],
                'id' => 'PAGE_123',
            ], 200),
        ]);

        $profile = $this->connector()->fetchPageProfile($this->auth());

        $this->assertSame('My Shop Page', $profile['name']);
        $this->assertSame('https://cdn.fb/pageavatar.jpg', $profile['avatar_url']);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/PAGE_123')
            && str_contains($r->url(), 'fields=name%2Cpicture')
            || str_contains($r->url(), 'fields=name,picture'));
    }

    public function test_fetch_user_profile_returns_name_and_avatar(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'name' => 'Nguyen Van A',
                'profile_pic' => 'https://cdn.fb/psidavatar.jpg',
                'id' => 'PSID_999',
            ], 200),
        ]);

        $profile = $this->connector()->fetchUserProfile($this->auth(), 'PSID_999');

        $this->assertSame('Nguyen Van A', $profile['name']);
        $this->assertSame('https://cdn.fb/psidavatar.jpg', $profile['avatar_url']);
    }

    public function test_fetch_profile_failure_returns_nulls(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'no']], 400)]);
        $profile = $this->connector()->fetchUserProfile($this->auth(), 'PSID_X');
        $this->assertNull($profile['name']);
        $this->assertNull($profile['avatar_url']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter FacebookBackfillConnectorTest`
Expected: FAIL — `supports('inbound.backfill')` false; `fetchPageProfile`/`fetchUserProfile` undefined.

- [ ] **Step 3: Add the capability**

In `FacebookPageConnector::capabilities()`, add the line:

```php
            'inbound.webhook' => true,
            'inbound.polling' => false,
            'inbound.backfill' => true,   // SPEC 2026-05-21: backfill lịch sử qua /conversations
            'outbound.text' => true,
```

- [ ] **Step 4: Add the profile methods**

Add to `FacebookPageConnector` (after `registerWebhooks`):

```php
    /**
     * Lấy tên + avatar của page. URL avatar (picture.data.url) là CDN sẽ hết hạn —
     * caller relay vào object storage. Lỗi ⇒ trả null (best-effort).
     *
     * @return array{name: ?string, avatar_url: ?string}
     */
    public function fetchPageProfile(MessagingAuthContext $auth): array
    {
        $res = Http::timeout(20)->get($this->graphUrl($auth->externalShopId), [
            'fields' => 'name,picture{url}',
            'access_token' => $auth->accessToken,
        ]);

        if (! $res->successful()) {
            return ['name' => null, 'avatar_url' => null];
        }

        return [
            'name' => $res->json('name'),
            'avatar_url' => $res->json('picture.data.url'),
        ];
    }

    /**
     * Lấy tên + profile_pic của buyer theo PSID. profile_pic URL hết hạn ⇒ relay.
     * Cần app review "Business Asset User Profile Access" với page người khác (dev mode
     * chạy với tester). Lỗi ⇒ null (không chặn backfill).
     *
     * @return array{name: ?string, avatar_url: ?string}
     */
    public function fetchUserProfile(MessagingAuthContext $auth, string $psid): array
    {
        $res = Http::timeout(20)->get($this->graphUrl($psid), [
            'fields' => 'name,profile_pic',
            'access_token' => $auth->accessToken,
        ]);

        if (! $res->successful()) {
            return ['name' => null, 'avatar_url' => null];
        }

        return [
            'name' => $res->json('name'),
            'avatar_url' => $res->json('profile_pic'),
        ];
    }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter FacebookBackfillConnectorTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/app/Integrations/Messaging/Facebook/FacebookPageConnector.php \
        app/tests/Unit/Messaging/FacebookBackfillConnectorTest.php
git commit -m "feat(messaging): connector facebook capability inbound.backfill + fetchPage/UserProfile"
```

---

## Task 3: Connector — `fetchConversations`

**Files:**
- Modify: `app/app/Integrations/Messaging/Facebook/FacebookPageConnector.php`
- Test: `app/tests/Unit/Messaging/FacebookBackfillConnectorTest.php`

- [ ] **Step 1: Write the failing test** (append methods to the class)

```php
    public function test_fetch_conversations_maps_thread_and_psid(): void
    {
        Http::fake([
            'graph.facebook.com/*conversations*' => Http::response([
                'data' => [[
                    'id' => 't_aaa',
                    'updated_time' => '2026-05-20T10:00:00+0000',
                    'message_count' => 12,
                    'snippet' => 'tin gần nhất',
                    'participants' => ['data' => [
                        ['id' => 'PAGE_123', 'name' => 'My Page'],
                        ['id' => 'PSID_999', 'name' => 'Nguyen Van A'],
                    ]],
                ]],
                'paging' => ['cursors' => ['after' => 'CURSOR_2'], 'next' => 'https://graph.facebook.com/next'],
            ], 200),
        ]);

        $page = $this->connector()->fetchConversations($this->auth(), ['pageSize' => 25]);

        $this->assertCount(1, $page->items);
        $dto = $page->items[0];
        $this->assertSame('PSID_999', $dto->externalConversationId);
        $this->assertSame('PSID_999', $dto->buyerExternalId);
        $this->assertSame('Nguyen Van A', $dto->buyerName);
        $this->assertSame('t_aaa', $dto->raw['fb_thread_id']);
        $this->assertSame(12, $dto->raw['message_count']);
        $this->assertSame('CURSOR_2', $page->nextCursor);
        $this->assertTrue($page->hasMore);

        Http::assertSent(fn ($r) => str_contains($r->url(), '/PAGE_123/conversations')
            && str_contains($r->url(), 'platform=MESSENGER'));
    }

    public function test_fetch_conversations_paginates_with_after_cursor(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['data' => [], 'paging' => []], 200)]);

        $this->connector()->fetchConversations($this->auth(), ['cursor' => 'CURSOR_2', 'pageSize' => 25]);

        Http::assertSent(fn ($r) => str_contains($r->url(), 'after=CURSOR_2'));
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter FacebookBackfillConnectorTest`
Expected: FAIL — `fetchConversations` still throws `UnsupportedOperation`.

- [ ] **Step 3: Replace `fetchConversations`**

Replace the existing throwing `fetchConversations` body. Add `use` imports at top if missing: `ConversationDTO`. Implementation:

```php
    public function fetchConversations(MessagingAuthContext $auth, array $query = []): Page
    {
        $params = [
            'platform' => 'MESSENGER',
            'fields' => 'id,updated_time,message_count,snippet,participants{id,name}',
            'limit' => (int) ($query['pageSize'] ?? 25),
            'access_token' => $auth->accessToken,
        ];
        if (! empty($query['cursor'])) {
            $params['after'] = (string) $query['cursor'];
        }

        $res = Http::timeout(30)->get($this->graphUrl($auth->externalShopId.'/conversations'), $params);
        if (! $res->successful()) {
            $this->throwGraphError($res, 'fetchConversations');
        }

        $items = [];
        foreach ((array) $res->json('data', []) as $row) {
            $threadId = (string) ($row['id'] ?? '');
            $psid = null;
            $buyerName = null;
            foreach ((array) ($row['participants']['data'] ?? []) as $p) {
                if ((string) ($p['id'] ?? '') !== $auth->externalShopId) {
                    $psid = (string) ($p['id'] ?? '');
                    $buyerName = $p['name'] ?? null;
                    break;
                }
            }
            if ($psid === null || $psid === '') {
                continue; // không xác định được buyer ⇒ bỏ qua hội thoại
            }

            $items[] = new ConversationDTO(
                externalConversationId: $psid,
                buyerExternalId: $psid,
                buyerName: $buyerName,
                buyerAvatarUrl: null,            // lấy riêng qua fetchUserProfile (relay)
                lastMessageAt: isset($row['updated_time']) ? CarbonImmutable::parse($row['updated_time']) : null,
                lastMessagePreview: $row['snippet'] ?? null,
                unreadCount: null,
                raw: [
                    'fb_thread_id' => $threadId,
                    'message_count' => (int) ($row['message_count'] ?? 0),
                    'updated_time' => $row['updated_time'] ?? null,
                ],
            );
        }

        $after = $res->json('paging.cursors.after');
        $hasMore = $res->json('paging.next') !== null;

        return new Page($items, $after ? (string) $after : null, (bool) $hasMore);
    }
```

Add this private helper near `send()` (used by both fetch methods — Task 4 reuses it):

```php
    /** Ném lỗi Graph; map rate-limit (code 80006) sang RuntimeException nhận diện được để job backoff. */
    private function throwGraphError(\Illuminate\Http\Client\Response $res, string $op): never
    {
        $error = (array) $res->json('error');
        $code = (int) ($error['code'] ?? 0);
        if ($code === 80006) {
            throw new \RuntimeException('FACEBOOK_RATE_LIMIT: '.$op);
        }
        throw new \RuntimeException("Facebook {$op} failed: ".$res->body());
    }
```

> The job (Task 6) detects rate limiting via `str_contains($e->getMessage(), 'FACEBOOK_RATE_LIMIT')`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter FacebookBackfillConnectorTest`
Expected: PASS (all profile + conversations tests).

- [ ] **Step 5: Commit**

```bash
git add app/app/Integrations/Messaging/Facebook/FacebookPageConnector.php \
        app/tests/Unit/Messaging/FacebookBackfillConnectorTest.php
git commit -m "feat(messaging): connector facebook fetchConversations (/conversations edge, cursor)"
```

---

## Task 4: Connector — `fetchMessages`

**Files:**
- Modify: `app/app/Integrations/Messaging/Facebook/FacebookPageConnector.php`
- Test: `app/tests/Unit/Messaging/FacebookBackfillConnectorTest.php`

- [ ] **Step 1: Write the failing test** (append)

```php
    public function test_fetch_messages_maps_direction_and_attachments(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'id' => 't_aaa',
                'messages' => ['data' => [
                    [
                        'id' => 'm_out', 'message' => 'Chào anh', 'created_time' => '2026-05-20T10:01:00+0000',
                        'from' => ['id' => 'PAGE_123', 'name' => 'My Page'],
                    ],
                    [
                        'id' => 'm_in', 'message' => '', 'created_time' => '2026-05-20T10:00:00+0000',
                        'from' => ['id' => 'PSID_999', 'name' => 'A'],
                        'attachments' => ['data' => [[
                            'mime_type' => 'image/jpeg', 'name' => 'photo.jpg',
                            'image_data' => ['url' => 'https://cdn.fb/photo.jpg'],
                        ]]],
                    ],
                ]],
            ], 200),
        ]);

        $page = $this->connector()->fetchMessages($this->auth(), 'PSID_999', ['thread_id' => 't_aaa', 'pageSize' => 50]);

        $this->assertCount(2, $page->items);

        $out = $page->items[0];
        $this->assertSame('m_out', $out->externalMessageId);
        $this->assertSame('PSID_999', $out->externalConversationId);   // PSID, không phải thread id
        $this->assertSame('outbound', $out->direction->value);
        $this->assertSame('text', $out->kind->value);

        $in = $page->items[1];
        $this->assertSame('inbound', $in->direction->value);
        $this->assertSame('image', $in->kind->value);
        $this->assertCount(1, $in->attachments);
        $this->assertSame('https://cdn.fb/photo.jpg', $in->attachments[0]->externalUrl);
        $this->assertSame('image/jpeg', $in->attachments[0]->mime);

        Http::assertSent(fn ($r) => str_contains($r->url(), '/t_aaa'));
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter FacebookBackfillConnectorTest`
Expected: FAIL — `fetchMessages` still throws.

- [ ] **Step 3: Replace `fetchMessages`**

Add `use` imports if missing: `MessageDTO`, `MessageDirection`, `MessageKind`, `MediaRefDTO`. Replace the throwing `fetchMessages`:

```php
    public function fetchMessages(MessagingAuthContext $auth, string $externalConversationId, array $query = []): Page
    {
        // Backfill địa chỉ tin theo THREAD id (Graph) truyền qua $query['thread_id'];
        // mỗi MessageDTO mang externalConversationId = PSID để ingest khớp hội thoại
        // (Send API/webhook đều dùng PSID).
        $threadId = (string) ($query['thread_id'] ?? $externalConversationId);
        $limit = (int) ($query['pageSize'] ?? 50);

        $res = Http::timeout(30)->get($this->graphUrl($threadId), [
            'fields' => "messages.limit({$limit}){id,message,created_time,from,attachments{mime_type,name,image_data,video_data,file_url}}",
            'access_token' => $auth->accessToken,
        ]);
        if (! $res->successful()) {
            $this->throwGraphError($res, 'fetchMessages');
        }

        $items = [];
        foreach ((array) $res->json('messages.data', []) as $row) {
            $fromId = (string) ($row['from']['id'] ?? '');
            $direction = $fromId === $auth->externalShopId ? MessageDirection::Outbound : MessageDirection::Inbound;

            $attachments = [];
            foreach ((array) ($row['attachments']['data'] ?? []) as $att) {
                $attachments[] = $this->mapBackfillAttachment((array) $att);
            }
            $kind = $attachments !== [] ? $attachments[0]->kind : MessageKind::Text;

            $items[] = new MessageDTO(
                externalConversationId: $externalConversationId,
                externalMessageId: (string) ($row['id'] ?? ''),
                buyerExternalId: $externalConversationId,
                direction: $direction,
                kind: $kind,
                body: ($row['message'] ?? '') !== '' ? (string) $row['message'] : null,
                attachments: $attachments,
                sentAt: isset($row['created_time']) ? CarbonImmutable::parse($row['created_time']) : null,
                raw: $row,
            );
        }

        return new Page($items, null, false);
    }

    /** @param array<string,mixed> $att */
    private function mapBackfillAttachment(array $att): MediaRefDTO
    {
        $mime = (string) ($att['mime_type'] ?? 'application/octet-stream');
        $url = $att['image_data']['url'] ?? $att['video_data']['url'] ?? $att['file_url'] ?? null;
        $kind = match (true) {
            isset($att['image_data']) || str_starts_with($mime, 'image/') => MessageKind::Image,
            isset($att['video_data']) || str_starts_with($mime, 'video/') => MessageKind::Video,
            default => MessageKind::File,
        };

        return new MediaRefDTO(
            kind: $kind,
            mime: $mime,
            externalUrl: $url !== null ? (string) $url : null,
            filename: $att['name'] ?? null,
        );
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter FacebookBackfillConnectorTest`
Expected: PASS.

- [ ] **Step 5: Static analysis + commit**

Run: `./vendor/bin/pint app/app/Integrations/Messaging/Facebook/FacebookPageConnector.php` then:

```bash
git add app/app/Integrations/Messaging/Facebook/FacebookPageConnector.php \
        app/tests/Unit/Messaging/FacebookBackfillConnectorTest.php
git commit -m "feat(messaging): connector facebook fetchMessages (thread→PSID, echo→outbound, attachments)"
```

---

## Task 5: Avatar relay (`MediaStorage::temporaryUrlForPath` + service + job)

**Files:**
- Modify: `app/app/Modules/Messaging/Services/MediaStorage.php`
- Create: `app/app/Modules/Messaging/Services/MessagingAvatarRelay.php`
- Create: `app/app/Modules/Messaging/Jobs/RelayMessagingAvatar.php`
- Test: `app/tests/Feature/Messaging/MessagingBackfillTest.php` (append)

- [ ] **Step 1: Write the failing test** (append to `MessagingBackfillTest`)

```php
    public function test_relay_messaging_avatar_stores_conversation_avatar(): void
    {
        $disk = (string) config('messaging.media_disk', 'local');
        \Illuminate\Support\Facades\Storage::fake($disk);
        \Illuminate\Support\Facades\Http::fake([
            'cdn.fb/*' => \Illuminate\Support\Facades\Http::response('IMAGEBYTES', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $tenant = \CMBcoreSeller\Modules\Tenancy\Models\Tenant::create(['name' => 'AvShop']);
        $account = \CMBcoreSeller\Modules\Channels\Models\ChannelAccount::query()->create([
            'tenant_id' => $tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_A', 'status' => 'active', 'messaging_enabled' => true,
        ]);
        $conv = \CMBcoreSeller\Modules\Messaging\Models\Conversation::query()->create([
            'tenant_id' => $tenant->getKey(), 'channel_account_id' => $account->id,
            'provider' => 'facebook_page', 'external_conversation_id' => 'psid_a',
            'buyer_external_id' => 'psid_a', 'status' => 'open', 'last_message_at' => now(),
        ]);

        (new \CMBcoreSeller\Modules\Messaging\Jobs\RelayMessagingAvatar(
            'conversation', $conv->id, 'https://cdn.fb/psidavatar.jpg'
        ))->handle(app(\CMBcoreSeller\Modules\Messaging\Services\MessagingAvatarRelay::class));

        $conv->refresh();
        $this->assertNotNull($conv->buyer_avatar_path);
        \Illuminate\Support\Facades\Storage::disk($disk)->assertExists($conv->buyer_avatar_path);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter MessagingBackfillTest`
Expected: FAIL — class `RelayMessagingAvatar` / `MessagingAvatarRelay` not found.

- [ ] **Step 3: Add `temporaryUrlForPath` to `MediaStorage`**

```php
    /**
     * Signed URL TTL ngắn cho 1 storage_path bất kỳ (avatar). Null nếu path rỗng.
     * Disk local không hỗ trợ temporaryUrl ⇒ fallback url().
     */
    public function temporaryUrlForPath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        $disk = $this->disk();
        $ttl = (int) config('messaging.signed_url_ttl', 300);
        try {
            return $disk->temporaryUrl($path, now()->addSeconds($ttl));
        } catch (\Throwable) {
            try {
                return $disk->url($path);
            } catch (\Throwable) {
                return null;
            }
        }
    }
```

- [ ] **Step 4: Create `MessagingAvatarRelay` service**

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Tải avatar (page / buyer) từ URL CDN Facebook (hết hạn) về object storage,
 * trả storage_path ổn định. Best-effort: lỗi ⇒ trả null (không chặn backfill).
 */
class MessagingAvatarRelay
{
    public function __construct(private MediaStorage $storage) {}

    public function relay(int $tenantId, string $url): ?string
    {
        try {
            $res = Http::timeout(20)->retry(2, 300)->get($url);
            if (! $res->successful()) {
                return null;
            }
            $body = $res->body();
            if ($body === '' || strlen($body) > 5 * 1024 * 1024) { // avatar > 5MB ⇒ bỏ
                return null;
            }
            $path = "tenants/{$tenantId}/messaging/avatars/".Str::uuid()->toString().'.jpg';
            $this->storage->disk()->put($path, $body);

            return $path;
        } catch (\Throwable) {
            return null;
        }
    }
}
```

- [ ] **Step 5: Create `RelayMessagingAvatar` job**

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Messaging\Services\MessagingAvatarRelay;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Relay avatar về object storage rồi gán path. target:
 *   - 'page'         → messaging_account_meta.page_avatar_path (id = channel_account_id)
 *   - 'conversation' → conversations.buyer_avatar_path (id = conversation_id)
 */
class RelayMessagingAvatar implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $target,
        public int $id,
        public string $url,
    ) {}

    public function handle(MessagingAvatarRelay $relay): void
    {
        if ($this->target === 'conversation') {
            $conv = Conversation::withoutGlobalScope(TenantScope::class)->find($this->id);
            if (! $conv) {
                return;
            }
            $path = $relay->relay((int) $conv->tenant_id, $this->url);
            if ($path) {
                $conv->forceFill(['buyer_avatar_path' => $path])->save();
            }

            return;
        }

        if ($this->target === 'page') {
            $meta = MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->find($this->id);
            if (! $meta) {
                return;
            }
            $path = $relay->relay((int) $meta->tenant_id, $this->url);
            if ($path) {
                $meta->forceFill(['page_avatar_path' => $path, 'page_avatar_synced_at' => now()])->save();
            }
        }
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter MessagingBackfillTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Messaging/Services/MediaStorage.php \
        app/app/Modules/Messaging/Services/MessagingAvatarRelay.php \
        app/app/Modules/Messaging/Jobs/RelayMessagingAvatar.php \
        app/tests/Feature/Messaging/MessagingBackfillTest.php
git commit -m "feat(messaging): relay avatar (page/buyer) về object storage + MediaStorage::temporaryUrlForPath"
```

---

## Task 6: `BackfillMessagingChannel` job

**Files:**
- Create: `app/app/Modules/Messaging/Jobs/BackfillMessagingChannel.php`
- Test: `app/tests/Feature/Messaging/MessagingBackfillTest.php` (append)

**Behavior:** set `sync_status=running`; relay page avatar; paginate `fetchConversations` newest→older, stop when a conversation's `updated_time < now-{days}`; per conversation upsert buyer fields + `meta.fb_thread_id`, dispatch buyer-avatar relay, call `fetchMessages` then `ingest()` each message **without firing events** (no auto-reply on history), dispatch `DownloadInboundMedia` for pending inbound attachments; bump counters; persist `sync_cursor`; on rate-limit `release()` with backoff; on completion set `done` + `last_synced_at`. Skip conversations already `blocked_at`.

- [ ] **Step 1: Write the failing test** (append to `MessagingBackfillTest`)

```php
    private function fbAccount(): array
    {
        $tenant = \CMBcoreSeller\Modules\Tenancy\Models\Tenant::create(['name' => 'SyncShop']);
        $account = \CMBcoreSeller\Modules\Channels\Models\ChannelAccount::query()->create([
            'tenant_id' => $tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_123', 'status' => 'active',
            'access_token' => 'PAGE_TOKEN', 'messaging_enabled' => true,
        ]);
        \CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta::query()->create([
            'channel_account_id' => $account->id, 'tenant_id' => $tenant->getKey(), 'messaging_enabled' => true,
        ]);
        config([
            'integrations.messaging' => ['facebook_page'],
            'integrations.messaging_facebook_page.app_id' => 'APP', 'integrations.messaging_facebook_page.app_secret' => 'S',
            'messaging.backfill.days' => 90,
        ]);
        $this->app->forgetInstance(\CMBcoreSeller\Integrations\Messaging\MessagingRegistry::class);

        return [$tenant, $account];
    }

    public function test_backfill_ingests_conversations_and_messages_idempotently(): void
    {
        \Illuminate\Support\Facades\Bus::fake([
            \CMBcoreSeller\Modules\Messaging\Jobs\RelayMessagingAvatar::class,
            \CMBcoreSeller\Modules\Messaging\Jobs\DownloadInboundMedia::class,
        ]);
        [$tenant, $account] = $this->fbAccount();

        \Illuminate\Support\Facades\Http::fake([
            'graph.facebook.com/*/conversations*' => \Illuminate\Support\Facades\Http::response([
                'data' => [[
                    'id' => 't_aaa', 'updated_time' => now()->subDay()->toIso8601String(), 'message_count' => 2,
                    'participants' => ['data' => [
                        ['id' => 'PAGE_123', 'name' => 'Page'], ['id' => 'PSID_999', 'name' => 'A'],
                    ]],
                ]],
                'paging' => [],
            ], 200),
            'graph.facebook.com/*t_aaa*' => \Illuminate\Support\Facades\Http::response([
                'id' => 't_aaa',
                'messages' => ['data' => [
                    ['id' => 'm_1', 'message' => 'hi', 'created_time' => now()->subDay()->toIso8601String(), 'from' => ['id' => 'PSID_999']],
                    ['id' => 'm_2', 'message' => 'hello', 'created_time' => now()->subDay()->toIso8601String(), 'from' => ['id' => 'PAGE_123']],
                ]],
            ], 200),
            'graph.facebook.com/*' => \Illuminate\Support\Facades\Http::response(['name' => 'Page', 'picture' => ['data' => ['url' => 'https://cdn.fb/p.jpg']]], 200),
        ]);

        \CMBcoreSeller\Modules\Messaging\Jobs\BackfillMessagingChannel::dispatchSync($account->id);

        $this->assertDatabaseHas('conversations', [
            'channel_account_id' => $account->id, 'external_conversation_id' => 'PSID_999', 'buyer_name' => 'A',
        ]);
        $this->assertSame(2, \CMBcoreSeller\Modules\Messaging\Models\Message::query()->count());
        $meta = \CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta::query()->find($account->id);
        $this->assertSame('done', $meta->sync_status);
        $this->assertSame(2, $meta->sync_message_count);
        $this->assertSame(1, $meta->sync_done_conversations);
        $this->assertNotNull($meta->last_synced_at);

        // Chạy lại ⇒ dedupe, không nhân đôi.
        \CMBcoreSeller\Modules\Messaging\Jobs\BackfillMessagingChannel::dispatchSync($account->id);
        $this->assertSame(2, \CMBcoreSeller\Modules\Messaging\Models\Message::query()->count());
    }

    public function test_backfill_stops_at_cutoff_and_skips_old_conversations(): void
    {
        \Illuminate\Support\Facades\Bus::fake([
            \CMBcoreSeller\Modules\Messaging\Jobs\RelayMessagingAvatar::class,
            \CMBcoreSeller\Modules\Messaging\Jobs\DownloadInboundMedia::class,
        ]);
        [$tenant, $account] = $this->fbAccount();

        \Illuminate\Support\Facades\Http::fake([
            'graph.facebook.com/*/conversations*' => \Illuminate\Support\Facades\Http::response([
                'data' => [[
                    'id' => 't_old', 'updated_time' => now()->subDays(120)->toIso8601String(), 'message_count' => 1,
                    'participants' => ['data' => [['id' => 'PAGE_123'], ['id' => 'PSID_OLD']]],
                ]],
                'paging' => ['cursors' => ['after' => 'C2'], 'next' => 'https://x'],
            ], 200),
            'graph.facebook.com/*' => \Illuminate\Support\Facades\Http::response(['name' => 'Page'], 200),
        ]);

        \CMBcoreSeller\Modules\Messaging\Jobs\BackfillMessagingChannel::dispatchSync($account->id);

        // Hội thoại cũ hơn cutoff ⇒ không nạp; dừng phân trang.
        $this->assertDatabaseMissing('conversations', ['external_conversation_id' => 'PSID_OLD']);
        $meta = \CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta::query()->find($account->id);
        $this->assertSame('done', $meta->sync_status);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter MessagingBackfillTest`
Expected: FAIL — `BackfillMessagingChannel` not found.

- [ ] **Step 3: Create the job**

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use CMBcoreSeller\Integrations\Messaging\DTO\ConversationDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Backfill lịch sử hội thoại/tin nhắn cho 1 channel_account (provider-agnostic —
 * chỉ chạy khi connector supports('inbound.backfill')). Hướng A, SPEC 2026-05-21.
 *
 * Idempotent: ingest dedupe theo (conversation_id, external_message_id). KHÔNG fire
 * MessageReceived (tránh auto-reply/AI trên tin lịch sử) — chỉ relay media inbound.
 */
class BackfillMessagingChannel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public int $channelAccountId) {}

    public function handle(MessagingRegistry $registry, MessageIngestionService $ingestion): void
    {
        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->find($this->channelAccountId);
        if (! $account || ! $registry->has($account->provider)) {
            return;
        }
        $connector = $registry->for($account->provider);
        if (! $connector->supports('inbound.backfill')) {
            return;
        }

        $meta = $this->ensureMeta($account);
        $auth = new MessagingAuthContext(
            channelAccountId: (int) $account->getKey(),
            provider: $account->provider,
            externalShopId: (string) $account->external_shop_id,
            accessToken: (string) $account->access_token,
        );

        $meta->forceFill([
            'sync_status' => MessagingAccountMeta::SYNC_RUNNING,
            'sync_started_at' => $meta->sync_started_at ?? now(),
            'sync_error' => null,
        ])->save();

        // Avatar page (best-effort, queued).
        $pageProfile = $connector->fetchPageProfile($auth);
        if (! empty($pageProfile['avatar_url'])) {
            RelayMessagingAvatar::dispatch('page', (int) $account->getKey(), (string) $pageProfile['avatar_url']);
        }

        $cutoff = now()->subDays((int) config('messaging.backfill.days', 90));
        $perPage = (int) config('messaging.backfill.conversations_per_page', 25);
        $msgLimit = (int) config('messaging.backfill.messages_per_conversation', 50);
        $cursor = $meta->sync_cursor;

        try {
            do {
                $page = $connector->fetchConversations($auth, ['cursor' => $cursor, 'pageSize' => $perPage]);
                $reachedCutoff = false;

                foreach ($page->items as $convDto) {
                    /** @var ConversationDTO $convDto */
                    $updatedAt = $convDto->lastMessageAt;
                    if ($updatedAt instanceof \DateTimeInterface && Carbon::instance($updatedAt)->lt($cutoff)) {
                        $reachedCutoff = true;
                        break;
                    }

                    $threadId = (string) ($convDto->raw['fb_thread_id'] ?? '');
                    $conversation = $this->upsertConversation($account, $convDto, $threadId);
                    if ($conversation->blocked_at !== null) {
                        $meta->forceFill(['sync_done_conversations' => $meta->sync_done_conversations + 1])->save();
                        continue;
                    }

                    if (! empty($convDto->buyerName)) {
                        $profile = $connector->fetchUserProfile($auth, $convDto->externalConversationId);
                        if (! empty($profile['avatar_url'])) {
                            RelayMessagingAvatar::dispatch('conversation', (int) $conversation->id, (string) $profile['avatar_url']);
                        }
                    }

                    $msgPage = $connector->fetchMessages($auth, $convDto->externalConversationId, [
                        'thread_id' => $threadId, 'pageSize' => $msgLimit,
                    ]);
                    foreach ($msgPage->items as $msgDto) {
                        $result = $ingestion->ingest($account, $msgDto);
                        if ($result['created']) {
                            $meta->forceFill(['sync_message_count' => $meta->sync_message_count + 1])->save();
                            // Relay media inbound (KHÔNG fire MessageReceived — không auto-reply tin cũ).
                            if ($result['message']->isInbound() && $result['message']->attachments_count > 0) {
                                $result['message']->attachments()
                                    ->withoutGlobalScope(TenantScope::class)
                                    ->where('status', MessageAttachment::STATUS_PENDING)
                                    ->get()
                                    ->each(fn (MessageAttachment $a) => DownloadInboundMedia::dispatch($a->id));
                            }
                        }
                    }

                    $meta->forceFill(['sync_done_conversations' => $meta->sync_done_conversations + 1])->save();
                }

                $cursor = $page->nextCursor;
                $meta->forceFill(['sync_cursor' => $cursor])->save();
            } while (! $reachedCutoff && $cursor !== null && $page->hasMore);
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'FACEBOOK_RATE_LIMIT')) {
                // Giữ cursor + status running; thử lại sau (backoff).
                $this->release(120);

                return;
            }
            $meta->forceFill([
                'sync_status' => MessagingAccountMeta::SYNC_FAILED,
                'sync_error' => substr($e->getMessage(), 0, 250),
            ])->save();
            Log::warning('messaging.backfill.failed', ['account' => $account->id, 'error' => $e->getMessage()]);

            return;
        }

        $meta->forceFill([
            'sync_status' => MessagingAccountMeta::SYNC_DONE,
            'sync_finished_at' => now(),
            'last_synced_at' => now(),
            'sync_cursor' => null,
        ])->save();
    }

    private function ensureMeta(ChannelAccount $account): MessagingAccountMeta
    {
        return MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->firstOrCreate(
            ['channel_account_id' => (int) $account->getKey()],
            ['tenant_id' => (int) $account->tenant_id, 'messaging_enabled' => true],
        );
    }

    private function upsertConversation(ChannelAccount $account, ConversationDTO $dto, string $threadId): Conversation
    {
        $conv = Conversation::withoutGlobalScope(TenantScope::class)->firstOrNew([
            'channel_account_id' => (int) $account->getKey(),
            'external_conversation_id' => $dto->externalConversationId,
        ]);
        if (! $conv->exists) {
            $conv->tenant_id = (int) $account->tenant_id;
            $conv->provider = $account->messagingConnectorCode() ?? $account->provider;
            $conv->buyer_external_id = $dto->buyerExternalId;
            $conv->status = Conversation::STATUS_OPEN;
            $conv->unread_count = 0;
            $conv->message_count = 0;
            $conv->last_message_at = $dto->lastMessageAt ?? now();
        }
        $conv->buyer_name = $dto->buyerName ?? $conv->buyer_name;
        $meta = (array) ($conv->meta ?? []);
        $meta['fb_thread_id'] = $threadId;
        $conv->meta = $meta;
        $conv->save();

        return $conv;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter MessagingBackfillTest`
Expected: PASS (idempotent + cutoff tests).

- [ ] **Step 5: Static analysis + commit**

Run: `./vendor/bin/pint app/app/Modules/Messaging/Jobs/BackfillMessagingChannel.php`

```bash
git add app/app/Modules/Messaging/Jobs/BackfillMessagingChannel.php \
        app/tests/Feature/Messaging/MessagingBackfillTest.php
git commit -m "feat(messaging): job BackfillMessagingChannel (paginate + ingest dedupe + cutoff + avatar)"
```

---

## Task 7: Endpoints — `POST channels/{id}/sync`, extend `index`, dispatch backfill on connect

**Files:**
- Modify: `app/app/Modules/Messaging/Http/Controllers/MessagingChannelController.php`
- Modify: `app/app/Modules/Messaging/Http/Controllers/FacebookOAuthController.php`
- Modify: `app/app/Modules/Messaging/Http/routes.php`
- Test: `app/tests/Feature/Messaging/MessagingBackfillTest.php` (append)

- [ ] **Step 1: Write the failing test** (append)

```php
    public function test_sync_endpoint_dispatches_backfill_for_owner(): void
    {
        \Illuminate\Support\Facades\Bus::fake([\CMBcoreSeller\Modules\Messaging\Jobs\BackfillMessagingChannel::class]);
        $this->seed(\CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder::class);
        [$tenant, $account] = $this->fbAccount();
        $this->activateProFor($tenant);

        $owner = $this->ownerFor($tenant);
        $this->actingAs($owner)->withHeaders(['X-Tenant-Id' => (string) $tenant->getKey()])
            ->postJson("/api/v1/messaging/channels/{$account->id}/sync")
            ->assertStatus(202)
            ->assertJsonPath('data.ok', true);

        \Illuminate\Support\Facades\Bus::assertDispatched(\CMBcoreSeller\Modules\Messaging\Jobs\BackfillMessagingChannel::class,
            fn ($job) => $job->channelAccountId === $account->id);

        $meta = \CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta::query()->find($account->id);
        $this->assertSame('queued', $meta->sync_status);
    }

    public function test_channels_index_returns_avatar_count_and_sync(): void
    {
        $this->seed(\CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder::class);
        [$tenant, $account] = $this->fbAccount();
        $this->activateProFor($tenant);
        \CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta::query()->where('channel_account_id', $account->id)
            ->update(['sync_status' => 'done', 'sync_message_count' => 5, 'sync_done_conversations' => 2]);
        \CMBcoreSeller\Modules\Messaging\Models\Conversation::query()->create([
            'tenant_id' => $tenant->getKey(), 'channel_account_id' => $account->id, 'provider' => 'facebook_page',
            'external_conversation_id' => 'p1', 'buyer_external_id' => 'p1', 'status' => 'open',
            'message_count' => 3, 'last_message_at' => now(),
        ]);

        $this->actingAs($this->ownerFor($tenant))->withHeaders(['X-Tenant-Id' => (string) $tenant->getKey()])
            ->getJson('/api/v1/messaging/channels')->assertOk()
            ->assertJsonPath('data.0.message_count', 3)
            ->assertJsonPath('data.0.sync.status', 'done')
            ->assertJsonPath('data.0.sync.message_count', 5);
    }
```

Add these helpers to `MessagingBackfillTest` (mirrors `MessagingChannelControllerTest`):

```php
    private function activateProFor(\CMBcoreSeller\Modules\Tenancy\Models\Tenant $tenant): void
    {
        $plan = \CMBcoreSeller\Modules\Billing\Models\Plan::query()->where('code', \CMBcoreSeller\Modules\Billing\Models\Plan::CODE_PRO)->firstOrFail();
        \CMBcoreSeller\Modules\Billing\Models\Subscription::query()->create([
            'tenant_id' => $tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => \CMBcoreSeller\Modules\Billing\Models\Subscription::STATUS_ACTIVE,
            'billing_cycle' => \CMBcoreSeller\Modules\Billing\Models\Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);
    }

    private function ownerFor(\CMBcoreSeller\Modules\Tenancy\Models\Tenant $tenant): \CMBcoreSeller\Models\User
    {
        $user = \CMBcoreSeller\Models\User::factory()->create(['email_verified_at' => now()]);
        $tenant->users()->attach($user->getKey(), ['role' => \CMBcoreSeller\Modules\Tenancy\Enums\Role::Owner->value]);

        return $user;
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter MessagingBackfillTest`
Expected: FAIL — route `channels/{id}/sync` 404; `sync` key absent from index.

- [ ] **Step 3: Add routes** (`Http/routes.php`, inside the `api/v1/messaging` group, next to existing channels routes)

```php
        Route::post('channels/{id}/sync', [MessagingChannelController::class, 'sync'])
            ->whereNumber('id')->name('messaging.channels.sync');     // messaging.connect
```

- [ ] **Step 4: Extend `MessagingChannelController`**

Inject `MediaStorage` and add `sync`; rewrite `index` mapping. Full controller head + methods:

```php
    public function __construct(private \CMBcoreSeller\Modules\Messaging\Services\MediaStorage $media) {}

    public function index(): JsonResponse
    {
        Gate::authorize('messaging.view');

        $pages = ChannelAccount::query()
            ->where('provider', 'facebook_page')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (ChannelAccount $a) {
                $meta = \CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta::query()->find($a->id);
                $liveCount = (int) \CMBcoreSeller\Modules\Messaging\Models\Conversation::query()
                    ->where('channel_account_id', $a->id)->sum('message_count');

                return [
                    'id' => $a->id,
                    'provider' => $a->provider,
                    'shop_name' => $a->shop_name,
                    'name' => $a->effectiveName(),
                    'external_shop_id' => $a->external_shop_id,
                    'status' => $a->status,
                    'messaging_enabled' => (bool) $a->messaging_enabled,
                    'token_expired' => $a->status === ChannelAccount::STATUS_EXPIRED,
                    'connected_at' => $a->created_at?->toIso8601String(),
                    'avatar_url' => $this->media->temporaryUrlForPath($meta?->page_avatar_path),
                    'message_count' => $liveCount,
                    'sync' => [
                        'status' => $meta?->sync_status ?? 'idle',
                        'total' => $meta?->sync_total_conversations,
                        'done' => (int) ($meta?->sync_done_conversations ?? 0),
                        'message_count' => (int) ($meta?->sync_message_count ?? 0),
                        'started_at' => $meta?->sync_started_at?->toIso8601String(),
                        'finished_at' => $meta?->sync_finished_at?->toIso8601String(),
                        'last_synced_at' => $meta?->last_synced_at?->toIso8601String(),
                        'error' => $meta?->sync_error,
                    ],
                ];
            });

        return response()->json(['data' => $pages]);
    }

    /** POST /channels/{id}/sync — đồng bộ lại thủ công. */
    public function sync(int $id): JsonResponse
    {
        Gate::authorize('messaging.connect');

        $account = ChannelAccount::query()->where('provider', 'facebook_page')->findOrFail($id);

        \CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta::query()->updateOrCreate(
            ['channel_account_id' => $account->id],
            ['tenant_id' => $account->tenant_id, 'sync_status' => \CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta::SYNC_QUEUED],
        );
        \CMBcoreSeller\Modules\Messaging\Jobs\BackfillMessagingChannel::dispatch($account->id);
        AuditLog::record('messaging.facebook.sync.requested', null, ['external_shop_id' => $account->external_shop_id]);

        return response()->json(['data' => ['ok' => true]], 202);
    }
```

- [ ] **Step 5: Dispatch backfill on OAuth connect** (`FacebookOAuthController::callback`)

After the `$connected++;` line (still inside the `foreach ($pages as $page)` loop, after webhook subscribe), add meta-ensure + dispatch:

```php
                \CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta::withoutGlobalScope(\CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope::class)
                    ->updateOrCreate(
                        ['channel_account_id' => (int) $account->getKey()],
                        ['tenant_id' => (int) $account->tenant_id, 'messaging_enabled' => true,
                         'sync_status' => \CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta::SYNC_QUEUED],
                    );
                \CMBcoreSeller\Modules\Messaging\Jobs\BackfillMessagingChannel::dispatch((int) $account->getKey());
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter MessagingBackfillTest`
Expected: PASS. Also run the existing channel test to ensure no regression: `php artisan test --filter MessagingChannelControllerTest` (the index shape grew but existing asserts on `external_shop_id`/`messaging_enabled`/`token_expired` still hold).

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Messaging/Http/Controllers/MessagingChannelController.php \
        app/app/Modules/Messaging/Http/Controllers/FacebookOAuthController.php \
        app/app/Modules/Messaging/Http/routes.php \
        app/tests/Feature/Messaging/MessagingBackfillTest.php
git commit -m "feat(messaging): endpoint sync + channels resource (avatar/count/sync) + backfill khi connect"
```

---

## Task 8: Periodic reconcile command + schedule

**Files:**
- Create: `app/app/Modules/Messaging/Console/Commands/ReconcileMessagingSync.php`
- Modify: `app/routes/console.php`
- Test: `app/tests/Feature/Messaging/MessagingBackfillTest.php` (append)

- [ ] **Step 1: Write the failing test** (append)

```php
    public function test_reconcile_command_dispatches_backfill_for_active_pages(): void
    {
        \Illuminate\Support\Facades\Bus::fake([\CMBcoreSeller\Modules\Messaging\Jobs\BackfillMessagingChannel::class]);
        [$tenant, $account] = $this->fbAccount();

        $this->artisan('messaging:reconcile-sync')->assertExitCode(0);

        \Illuminate\Support\Facades\Bus::assertDispatched(\CMBcoreSeller\Modules\Messaging\Jobs\BackfillMessagingChannel::class,
            fn ($job) => $job->channelAccountId === $account->id);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter MessagingBackfillTest`
Expected: FAIL — command `messaging:reconcile-sync` not registered.

- [ ] **Step 3: Create the command**

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Console\Commands;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Jobs\BackfillMessagingChannel;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Console\Command;

/**
 * Lưới an toàn: định kỳ dispatch backfill cho mọi page messaging_enabled mà
 * connector hỗ trợ inbound.backfill — vá tin webhook lọt. Backfill idempotent.
 */
class ReconcileMessagingSync extends Command
{
    protected $signature = 'messaging:reconcile-sync';

    protected $description = 'Định kỳ đối soát đồng bộ tin nhắn (backfill) cho các kênh hỗ trợ.';

    public function handle(MessagingRegistry $registry): int
    {
        ChannelAccount::withoutGlobalScope(TenantScope::class)
            ->where('status', ChannelAccount::STATUS_ACTIVE)
            ->where('messaging_enabled', true)
            ->orderBy('id')
            ->each(function (ChannelAccount $a) use ($registry) {
                if ($registry->has($a->provider) && $registry->for($a->provider)->supports('inbound.backfill')) {
                    BackfillMessagingChannel::dispatch((int) $a->getKey());
                }
            });

        return self::SUCCESS;
    }
}
```

> Commands in this repo auto-register via the module service provider's `commands()` / `load`. If `messaging:reconcile-sync` is not found when running the test, verify how sibling commands (`messaging:auto-reply-tick` → `AutoReplyTick`) are registered (likely `$this->commands([...])` in `MessagingServiceProvider`) and add `ReconcileMessagingSync::class` to that same array.

- [ ] **Step 4: Schedule it** (`app/routes/console.php`, in the SPEC-0024 messaging block)

```php
// SPEC 2026-05-21: mỗi giờ đối soát backfill (vá tin webhook lọt) — backfill idempotent.
Schedule::command('messaging:reconcile-sync')->hourly()->onOneServer()->withoutOverlapping();
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter MessagingBackfillTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Messaging/Console/Commands/ReconcileMessagingSync.php \
        app/routes/console.php
# include the service provider if you edited it to register the command
git commit -m "feat(messaging): command + schedule messaging:reconcile-sync (đối soát backfill định kỳ)"
```

---

## Task 9: Inbox management — block / unblock / mark-unread + filters + send guard

**Files:**
- Modify: `app/app/Modules/Messaging/Http/Controllers/ConversationController.php`
- Modify: `app/app/Modules/Messaging/Http/Controllers/MessageController.php`
- Modify: `app/app/Modules/Messaging/Services/MessageIngestionService.php`
- Modify: `app/app/Modules/Messaging/Http/routes.php`
- Test: `app/tests/Feature/Messaging/MessagingInboxManagementTest.php`

- [ ] **Step 1: Write the failing test** (`MessagingInboxManagementTest.php`)

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\DTO\MessageDirection;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagingInboxManagementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'InboxShop']);
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);
        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_1', 'status' => 'active', 'access_token' => 'T', 'messaging_enabled' => true,
        ]);
    }

    private function actor(Role $role = Role::Owner): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($u->getKey(), ['role' => $role->value]);

        return $u;
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function conv(array $attrs = []): Conversation
    {
        return Conversation::query()->create(array_merge([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $this->account->id,
            'provider' => 'facebook_page', 'external_conversation_id' => 'psid_'.uniqid(),
            'buyer_external_id' => 'psid', 'status' => 'open', 'last_message_at' => now(),
        ], $attrs));
    }

    public function test_mark_unread_sets_flag_and_read_clears_it(): void
    {
        $c = $this->conv(['unread_count' => 0]);

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$c->id}/unread")->assertOk();
        $this->assertTrue((bool) $c->fresh()->manually_unread);

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$c->id}/read")->assertOk();
        $this->assertFalse((bool) $c->fresh()->manually_unread);
    }

    public function test_unread_filter_includes_manually_unread(): void
    {
        $this->conv(['unread_count' => 0, 'manually_unread' => true, 'buyer_name' => 'Manual']);
        $this->conv(['unread_count' => 0, 'manually_unread' => false, 'buyer_name' => 'Read']);

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations?unread=true')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.buyer_name', 'Manual');
    }

    public function test_block_hides_conversation_and_unblock_restores(): void
    {
        $c = $this->conv(['buyer_name' => 'Spammer']);

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$c->id}/block")->assertOk();
        $this->assertNotNull($c->fresh()->blocked_at);

        // ẩn khỏi inbox mặc định
        $this->actingAs($this->actor())->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations')->assertOk()->assertJsonCount(0, 'data');
        // hiện trong tab "đã chặn"
        $this->actingAs($this->actor())->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations?blocked=true')->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->deleteJson("/api/v1/messaging/conversations/{$c->id}/block")->assertOk();
        $this->assertNull($c->fresh()->blocked_at);
    }

    public function test_sending_to_blocked_conversation_returns_422(): void
    {
        $c = $this->conv(['blocked_at' => now()]);

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$c->id}/messages", ['body' => 'hi'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'CONVERSATION_BLOCKED');
    }

    public function test_ingest_into_blocked_conversation_does_not_bump_unread(): void
    {
        $c = $this->conv(['blocked_at' => now(), 'unread_count' => 0]);

        app(MessageIngestionService::class)->ingest($this->account, new MessageDTO(
            externalConversationId: $c->external_conversation_id,
            externalMessageId: 'm_blocked_1',
            buyerExternalId: $c->buyer_external_id,
            direction: MessageDirection::Inbound,
            kind: MessageKind::Text,
            body: 'spam again',
        ));

        $c->refresh();
        $this->assertSame(0, (int) $c->unread_count); // không tăng unread cho hội thoại đã chặn
        $this->assertDatabaseHas('messages', ['external_message_id' => 'm_blocked_1']); // vẫn lưu (audit)
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter MessagingInboxManagementTest`
Expected: FAIL — routes missing; block guard absent; ingest bumps unread.

- [ ] **Step 3: Add routes** (`Http/routes.php`, in the `api/v1/messaging` group)

```php
        Route::post('conversations/{id}/unread', [ConversationController::class, 'markUnread'])
            ->whereNumber('id')->name('messaging.conversations.unread');     // messaging.view
        Route::post('conversations/{id}/block', [ConversationController::class, 'block'])
            ->whereNumber('id')->name('messaging.conversations.block');      // messaging.reply
        Route::delete('conversations/{id}/block', [ConversationController::class, 'unblock'])
            ->whereNumber('id')->name('messaging.conversations.unblock');    // messaging.reply
```

- [ ] **Step 4: `ConversationController` — index filters, markRead clear, markUnread, block, unblock**

In `index()`, replace the status/unread filter block with:

```php
        $showBlocked = $request->boolean('blocked');
        if ($status = $request->query('status')) {
            $q->whereIn('status', explode(',', (string) $status));
        } else {
            $q->where('status', '!=', Conversation::STATUS_SPAM);
        }
        if ($showBlocked) {
            $q->whereNotNull('blocked_at');
        } else {
            $q->whereNull('blocked_at');  // ẩn hội thoại đã chặn khỏi inbox mặc định
        }
        if ($request->boolean('unread')) {
            $q->where(function ($qq) {
                $qq->where('unread_count', '>', 0)->orWhere('manually_unread', true);
            });
        }
```

In `markRead()`, add `'manually_unread' => false` to the update:

```php
        $conv->update(['unread_count' => 0, 'manually_unread' => false]);
```

Add the three new actions:

```php
    public function markUnread(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.view');
        $conv = Conversation::query()->findOrFail($id);
        $conv->update(['manually_unread' => true]);

        return response()->json(['data' => (new ConversationResource($conv))->toArray($request)]);
    }

    public function block(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.reply');
        $conv = Conversation::query()->findOrFail($id);
        $conv->update([
            'blocked_at' => now(),
            'blocked_by_user_id' => $request->user()->id,
            'status' => Conversation::STATUS_SPAM,
        ]);
        \CMBcoreSeller\Modules\Tenancy\Models\AuditLog::record('messaging.conversation.blocked', null, [
            'conversation_id' => $conv->id, 'buyer_external_id' => $conv->buyer_external_id,
        ]);

        return response()->json(['data' => (new ConversationResource($conv))->toArray($request)]);
    }

    public function unblock(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.reply');
        $conv = Conversation::query()->findOrFail($id);
        $conv->update(['blocked_at' => null, 'blocked_by_user_id' => null, 'status' => Conversation::STATUS_OPEN]);
        \CMBcoreSeller\Modules\Tenancy\Models\AuditLog::record('messaging.conversation.unblocked', null, [
            'conversation_id' => $conv->id,
        ]);

        return response()->json(['data' => (new ConversationResource($conv))->toArray($request)]);
    }
```

> Verify `ConversationResource` exposes `blocked_at` and `manually_unread` (Frontend plan relies on them). If it whitelists fields, add `'blocked_at' => $this->blocked_at?->toIso8601String()` and `'manually_unread' => (bool) $this->manually_unread`. This is also asserted indirectly by `test_block_*` (the `data` payload is returned).

- [ ] **Step 5: `MessageController` — block guard on all send actions**

Add a private helper and call it at the start of `sendText`, `sendMedia` (after `findOrFail`, before connector resolution). `sendTemplate` delegates to `sendText`, so guarding `sendText` covers it:

```php
    private function assertNotBlocked(\CMBcoreSeller\Modules\Messaging\Models\Conversation $conv): ?JsonResponse
    {
        if ($conv->blocked_at !== null) {
            return response()->json([
                'error' => ['code' => 'CONVERSATION_BLOCKED', 'message' => 'Hội thoại đã bị chặn — bỏ chặn để gửi tin.'],
            ], 422);
        }

        return null;
    }
```

In `sendText`, right after `$conv = Conversation::query()->findOrFail($conversationId);`:

```php
        if ($blocked = $this->assertNotBlocked($conv)) {
            return $blocked;
        }
```

Add the identical guard in `sendMedia` right after its `$conv = Conversation::query()->findOrFail($conversationId);`.

- [ ] **Step 6: `MessageIngestionService` — don't bump unread for blocked conversations**

In `updateConversationOnNewMessage`, change the inbound branch to respect block:

```php
        if ($message->isInbound()) {
            if ($conversation->blocked_at === null) {
                $conversation->unread_count++;
            }
            $conversation->last_inbound_at = $message->created_at;
            // Tin mới đẩy snoozed/resolved về open — nhưng KHÔNG bỏ chặn.
            if ($conversation->blocked_at === null
                && in_array($conversation->status, [Conversation::STATUS_SNOOZED, Conversation::STATUS_RESOLVED], true)) {
                $conversation->status = Conversation::STATUS_OPEN;
                $conversation->snoozed_until = null;
            }
        } else {
            $conversation->last_outbound_at = $message->created_at;
        }
```

- [ ] **Step 7: Run test to verify it passes**

Run: `php artisan test --filter MessagingInboxManagementTest`
Expected: PASS. Then run the messaging suite to catch regressions: `php artisan test --filter Messaging`.

- [ ] **Step 8: Static analysis + commit**

Run: `./vendor/bin/pint app/app/Modules/Messaging` then `./vendor/bin/phpstan analyse` (from `app/`).

```bash
git add app/app/Modules/Messaging/Http/Controllers/ConversationController.php \
        app/app/Modules/Messaging/Http/Controllers/MessageController.php \
        app/app/Modules/Messaging/Services/MessageIngestionService.php \
        app/app/Modules/Messaging/Http/routes.php \
        app/app/Modules/Messaging/Http/Resources/ConversationResource.php \
        app/tests/Feature/Messaging/MessagingInboxManagementTest.php
git commit -m "feat(messaging): chặn/bỏ chặn + đánh dấu chưa đọc + lọc (unread/blocked) + guard gửi khi blocked"
```

---

## Final verification

- [ ] **Run the full messaging test suite**

Run: `php artisan test --filter Messaging` (from `app/`)
Expected: all green, including pre-existing `MessagingChannelControllerTest`, `MessagingMediaTest`, `MessagingWebhookIngestTest`, `FacebookPageConnectorTest`.

- [ ] **Lint + static analysis**

Run: `./vendor/bin/pint --test` and `./vendor/bin/phpstan analyse` (from `app/`). Fix any findings, re-run, commit if needed.

- [ ] **Confirm config knobs exist (optional)**

The backfill reads `config('messaging.backfill.days', 90)`, `messaging.backfill.conversations_per_page` (25), `messaging.backfill.messages_per_conversation` (50) — all have safe defaults. Optionally add a `'backfill' => [...]` block to `app/config/messaging.php` to make them tunable via env.

---

## Self-review notes (spec coverage)

- Avatars: page (Task 5/6/7) + buyer (Task 6) relayed to storage; served via `temporaryUrlForPath` (Task 7). ✓
- Auto-sync all pages: backfill on connect (Task 7) + manual sync endpoint (Task 7) + periodic reconcile (Task 8). ✓
- Sync progress + message count: `sync` object + `message_count` in channels resource (Task 7). ✓
- Mark-unread / unread filter: Task 9. ✓
- Block (app-level, no FB API): Task 9 (block/unblock + ingest drop + send guard + hide). ✓
- Send media/emoji: backend already complete (`MessageController@sendMedia`); UI is the Frontend plan. (No backend task needed.)
- Official-API constraints baked in: cursor pagination + cutoff (Task 3/6), rate-limit 80006 backoff (Task 3/6), expiring avatar URLs relayed (Task 5/6). ✓
