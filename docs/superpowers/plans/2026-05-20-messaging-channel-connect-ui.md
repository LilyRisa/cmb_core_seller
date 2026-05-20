# UI Kết nối & quản lý kênh nhắn tin — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm trang/luồng UI để kết nối & quản lý Facebook Page (kết nối, xem, kết nối lại, ngắt) và bật/tắt nhắn tin cho Lazada/TikTok, vá các khoảng trống G1–G5 của SPEC-0024.

**Architecture:** Backend thêm permission `messaging.connect` + 3 endpoint (list/disconnect facebook page trong module Messaging; toggle `messaging_enabled` trong module Channels) + đổi đích redirect callback. Frontend thêm trang `/messaging/channels`, mục menu/nav, switch trên trang Channels, dọn emoji/Select. Connector & pipeline messaging giữ nguyên (ADR-0017).

**Tech Stack:** Laravel 11 (PHP 8.3, PHPUnit), React 18 + TypeScript + Ant Design 5 + React Query (Vite). FE không có Vitest ⇒ gate FE = `npm run typecheck` + `npm run lint`.

> **Lệnh chạy test/lint** (chạy từ thư mục `D:\cmb_core_seller\app` — Laravel root + package.json):
> - BE: `php artisan test --filter=<ClassName>` (nếu dùng docker: `docker compose exec app php artisan test --filter=<ClassName>`).
> - FE: `npm run typecheck` và `npm run lint`.
> Mọi đường dẫn file PHP dưới đây gốc từ `D:\cmb_core_seller\app\` (vd `app/Modules/...`). Đường dẫn FE gốc từ `D:\cmb_core_seller\app\resources\js\`.

---

## File Structure

**Tạo mới:**
- `app/Modules/Messaging/Http/Controllers/MessagingChannelController.php` — list + disconnect Facebook page.
- `app/Modules/Messaging/Services/FacebookPageDisconnectService.php` — xoá cascade + unsubscribe webhook.
- `tests/Feature/Messaging/MessagingChannelControllerTest.php` — test list/disconnect + gate.
- `tests/Feature/Channels/ChannelMessagingToggleTest.php` — test toggle `messaging_enabled`.
- `resources/js/pages/MessagingChannelsPage.tsx` — trang Kết nối kênh.

**Sửa:**
- `app/Modules/Tenancy/Enums/Role.php` — doc permission `messaging.connect`.
- `app/Modules/Messaging/Http/Controllers/FacebookOAuthController.php` — gate + redirect target.
- `app/Modules/Messaging/Http/routes.php` — 2 route mới.
- `app/Modules/Channels/Http/Controllers/ChannelAccountController.php` — `setMessaging`.
- `app/Modules/Channels/Http/Resources/ChannelAccountResource.php` — 2 field mới.
- `routes/api.php` — route toggle.
- `tests/Feature/Messaging/MessagingFacebookOAuthTest.php` — cập nhật assert redirect.
- FE: `resources/js/app.tsx`, `components/AppLayout.tsx`, `components/MessagingNav.tsx`, `pages/MessagingPage.tsx`, `pages/MessagingSettingsPage.tsx`, `pages/ChannelsPage.tsx`, `lib/messagingConfig.tsx`, `lib/channels.tsx`.

---

## Task 1: Permission `messaging.connect` + đổi gate connect

**Files:**
- Modify: `app/Modules/Tenancy/Enums/Role.php`
- Modify: `app/Modules/Messaging/Http/Controllers/FacebookOAuthController.php:41`
- Test: `tests/Feature/Messaging/MessagingChannelControllerTest.php` (tạo ở Task này, dùng tiếp Task 3–4)

- [ ] **Step 1: Viết test gate cho connect**

Tạo `tests/Feature/Messaging/MessagingChannelControllerTest.php`:

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagingChannelControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'ChanShop']);
        config([
            'integrations.messaging' => ['facebook_page'],
            'integrations.messaging_facebook_page.app_id' => 'APP123',
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);
        $this->activatePro();
    }

    private function activatePro(): void
    {
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        $now = now();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => $now,
            'current_period_end' => $now->copy()->addMonth(),
        ]);
    }

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($user->getKey(), ['role' => $role->value]);

        return $user;
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_owner_can_start_facebook_connect(): void
    {
        $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->postJson('/api/v1/messaging/facebook/connect')
            ->assertOk()
            ->assertJsonPath('data.authorize_url', fn ($url) => str_contains((string) $url, 'facebook.com'));
    }

    public function test_staff_cs_cannot_start_facebook_connect(): void
    {
        $this->actingAs($this->userWithRole(Role::StaffCs))
            ->withHeaders($this->h())
            ->postJson('/api/v1/messaging/facebook/connect')
            ->assertStatus(403);
    }
}
```

- [ ] **Step 2: Chạy test — kỳ vọng FAIL**

Run: `php artisan test --filter=MessagingChannelControllerTest`
Expected: FAIL — `test_staff_cs_cannot_start_facebook_connect` trả 200 (vì gate hiện là `messaging.ai.config`, mà staff_cs không có nên thực tế đã 403?). LƯU Ý: staff_cs KHÔNG có `messaging.ai.config` ⇒ test 403 có thể PASS sẵn; còn `test_owner...` PASS. Mục đích Task này là **đổi sang permission đúng ngữ nghĩa** — nếu cả 2 PASS trước khi sửa, vẫn tiếp tục đổi gate (Step 3) và đảm bảo vẫn xanh. (TDD ở đây chốt hành vi, không nhất thiết đỏ trước.)

- [ ] **Step 3: Thêm permission `messaging.connect` cho staff (không) + đổi gate**

Trong `app/Modules/Tenancy/Enums/Role.php`, KHÔNG cần thêm cho Owner/Admin (đã có `*`). Để tài liệu hoá, thêm comment cạnh `StaffCs` (không thêm quyền): giữ nguyên danh sách `StaffCs` (staff_cs **không** kết nối kênh).

Sửa `app/Modules/Messaging/Http/Controllers/FacebookOAuthController.php` dòng 41:

```php
        Gate::authorize('messaging.connect');
```

(thay cho `Gate::authorize('messaging.ai.config');`)

- [ ] **Step 4: Chạy lại test — kỳ vọng PASS**

Run: `php artisan test --filter=MessagingChannelControllerTest`
Expected: PASS (owner 200, staff_cs 403).

- [ ] **Step 5: Commit**

```bash
git add app/Modules/Tenancy/Enums/Role.php app/Modules/Messaging/Http/Controllers/FacebookOAuthController.php tests/Feature/Messaging/MessagingChannelControllerTest.php
git commit -m "feat(messaging): gate kết nối Facebook Page bằng messaging.connect"
```

---

## Task 2: Đổi đích redirect callback sang /messaging/channels

**Files:**
- Modify: `app/Modules/Messaging/Http/Controllers/FacebookOAuthController.php` (lines 44, 61, 66, 75, 110, 113)
- Modify: `tests/Feature/Messaging/MessagingFacebookOAuthTest.php` (lines 42, 47, 63)

- [ ] **Step 1: Cập nhật test hiện có cho đích redirect mới**

Trong `tests/Feature/Messaging/MessagingFacebookOAuthTest.php`:
- Dòng 42: đổi `'redirect_after' => '/messaging?connected=facebook_page',` → `'redirect_after' => '/messaging/channels?connected=facebook_page',`
- Dòng 47: đổi `->assertRedirect('/messaging?connected=facebook_page');` → `->assertRedirect('/messaging/channels?connected=facebook_page');`
- Dòng 63: đổi `->assertRedirect('/messaging?error=facebook_oauth_state');` → `->assertRedirect('/messaging/channels?error=facebook_oauth_state');`

- [ ] **Step 2: Chạy test — kỳ vọng FAIL**

Run: `php artisan test --filter=MessagingFacebookOAuthTest`
Expected: FAIL — controller vẫn redirect `/messaging?...` (chưa khớp `/messaging/channels?...`).

- [ ] **Step 3: Đổi đích redirect trong controller**

Trong `app/Modules/Messaging/Http/Controllers/FacebookOAuthController.php`:

Dòng 44 (`start`):
```php
        $state = OAuthState::issue(self::PROVIDER, (int) $tenantId, $request->user()?->id, '/messaging/channels?connected=facebook_page');
```

Dòng 61:
```php
            return redirect('/messaging/channels?error=facebook_oauth_'.preg_replace('/[^a-z0-9_]/i', '_', strtolower((string) $request->query('error', 'missing_params'))));
```

Dòng 66:
```php
            return redirect('/messaging/channels?error=facebook_oauth_state');
```

Dòng 75:
```php
                return redirect('/messaging/channels?error=facebook_no_pages');
```

Dòng 110:
```php
            return redirect('/messaging/channels?error=facebook_oauth_failed');
```

Dòng 113:
```php
        return redirect($state->redirect_after ?: '/messaging/channels?connected=facebook_page');
```

- [ ] **Step 4: Chạy lại test — kỳ vọng PASS**

Run: `php artisan test --filter=MessagingFacebookOAuthTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Modules/Messaging/Http/Controllers/FacebookOAuthController.php tests/Feature/Messaging/MessagingFacebookOAuthTest.php
git commit -m "feat(messaging): callback Facebook redirect về /messaging/channels"
```

---

## Task 3: Endpoint GET /messaging/channels (list Facebook page)

**Files:**
- Create: `app/Modules/Messaging/Http/Controllers/MessagingChannelController.php`
- Modify: `app/Modules/Messaging/Http/routes.php`
- Test: `tests/Feature/Messaging/MessagingChannelControllerTest.php` (thêm test)

- [ ] **Step 1: Viết test list**

Thêm vào `tests/Feature/Messaging/MessagingChannelControllerTest.php` (thêm `use ChannelAccount`):

```php
    public function test_index_lists_only_facebook_pages_without_token(): void
    {
        \CMBcoreSeller\Modules\Channels\Models\ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_1', 'shop_name' => 'Shop FB', 'status' => 'active',
            'access_token' => 'SECRET_PAGE_TOKEN', 'messaging_enabled' => true,
        ]);
        // 1 gian hàng sàn — KHÔNG được xuất hiện trong list facebook.
        \CMBcoreSeller\Modules\Channels\Models\ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 'LZ_1', 'shop_name' => 'Shop LZ', 'status' => 'active',
        ]);

        $res = $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->getJson('/api/v1/messaging/channels')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.external_shop_id', 'PAGE_1')
            ->assertJsonPath('data.0.messaging_enabled', true)
            ->assertJsonPath('data.0.token_expired', false);

        // Không lộ token
        $this->assertStringNotContainsString('SECRET_PAGE_TOKEN', $res->getContent());
    }
```

- [ ] **Step 2: Chạy test — kỳ vọng FAIL**

Run: `php artisan test --filter=MessagingChannelControllerTest`
Expected: FAIL — route `GET /api/v1/messaging/channels` chưa tồn tại (404).

- [ ] **Step 3: Tạo controller**

Tạo `app/Modules/Messaging/Http/Controllers/MessagingChannelController.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Services\FacebookPageDisconnectService;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Quản lý kênh nhắn tin Facebook Page cho UI /messaging/channels (SPEC-0024
 * bổ khuyết, design 2026-05-20). List + ngắt kết nối (xoá hẳn). Kết nối &
 * kết nối-lại đi qua FacebookOAuthController (POST facebook/connect).
 */
class MessagingChannelController extends Controller
{
    /** GET /api/v1/messaging/channels — list page Facebook đã kết nối (không trả token). */
    public function index(): JsonResponse
    {
        Gate::authorize('messaging.view');

        $pages = ChannelAccount::query()
            ->where('provider', 'facebook_page')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ChannelAccount $a) => [
                'id' => $a->id,
                'provider' => $a->provider,
                'shop_name' => $a->shop_name,
                'name' => $a->effectiveName(),
                'external_shop_id' => $a->external_shop_id,
                'status' => $a->status,
                'messaging_enabled' => (bool) $a->messaging_enabled,
                'token_expired' => $a->status === ChannelAccount::STATUS_EXPIRED,
                'connected_at' => $a->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $pages]);
    }

    /** DELETE /api/v1/messaging/channels/{id} — ngắt kết nối 1 page (xoá hẳn + cascade). */
    public function destroy(int $id, FacebookPageDisconnectService $service): JsonResponse
    {
        Gate::authorize('messaging.connect');

        $account = ChannelAccount::query()->where('provider', 'facebook_page')->findOrFail($id);
        $externalShopId = $account->external_shop_id;

        $result = $service->disconnect($account);

        AuditLog::record('messaging.facebook.disconnected', null, [
            'external_shop_id' => $externalShopId,
            'conversations_deleted' => $result['conversations'],
        ]);

        return response()->json(['data' => ['ok' => true, 'conversations_deleted' => $result['conversations']]]);
    }
}
```

> `destroy` được implement đầy đủ ở đây nhưng phụ thuộc `FacebookPageDisconnectService` (Task 4). Để test Task 3 chạy được, **chỉ thêm route `index`** ở Step 4; route `destroy` thêm ở Task 4. Controller có sẵn cả 2 method là chủ ý (tránh sửa file 2 lần) — `destroy` chưa được route tới nên không ảnh hưởng test Task 3. Nếu autoload báo thiếu class `FacebookPageDisconnectService`, tạo file rỗng skeleton ở Task 4 trước khi chạy test Task 4 (Task 3 không gọi tới nên không nạp).

- [ ] **Step 4: Thêm route index**

Trong `app/Modules/Messaging/Http/routes.php`, thêm import ở đầu (sau dòng `use ...FacebookOAuthController;`):

```php
use CMBcoreSeller\Modules\Messaging\Http\Controllers\MessagingChannelController;
```

Thêm route ngay sau block `facebook/connect` (sau dòng `->name('messaging.facebook.connect');`):

```php
        // --- Kết nối & quản lý kênh nhắn tin (UI /messaging/channels) ---
        Route::get('channels', [MessagingChannelController::class, 'index'])
            ->name('messaging.channels.index');
```

- [ ] **Step 5: Chạy test — kỳ vọng PASS**

Run: `php artisan test --filter=MessagingChannelControllerTest`
Expected: PASS (test_index_... xanh; owner/staff_cs tests vẫn xanh).

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Messaging/Http/Controllers/MessagingChannelController.php app/Modules/Messaging/Http/routes.php tests/Feature/Messaging/MessagingChannelControllerTest.php
git commit -m "feat(messaging): GET /messaging/channels liệt kê Facebook Page đã kết nối"
```

---

## Task 4: Endpoint DELETE /messaging/channels/{id} + service xoá cascade

**Files:**
- Create: `app/Modules/Messaging/Services/FacebookPageDisconnectService.php`
- Modify: `app/Modules/Messaging/Http/routes.php`
- Test: `tests/Feature/Messaging/MessagingChannelControllerTest.php` (thêm test)

- [ ] **Step 1: Viết test disconnect (cascade + audit + guard)**

Thêm vào `tests/Feature/Messaging/MessagingChannelControllerTest.php`:

```php
    public function test_disconnect_deletes_page_and_cascades(): void
    {
        $account = \CMBcoreSeller\Modules\Channels\Models\ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_7', 'shop_name' => 'FB7', 'status' => 'active',
            'access_token' => 'PAGE_TOKEN', 'messaging_enabled' => true,
        ]);
        $conv = \CMBcoreSeller\Modules\Messaging\Models\Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $account->id,
            'provider' => 'facebook_page', 'external_conversation_id' => 'psid_7',
            'buyer_external_id' => 'psid_7', 'status' => 'open', 'last_message_at' => now(),
        ]);
        $msg = \CMBcoreSeller\Modules\Messaging\Models\Message::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'conversation_id' => $conv->id,
            'direction' => 'inbound', 'kind' => 'text', 'body' => 'hi', 'delivery_status' => 'delivered',
        ]);
        \CMBcoreSeller\Modules\Messaging\Models\MessageAttachment::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'message_id' => $msg->id,
            'kind' => 'image', 'mime' => 'image/jpeg', 'status' => 'pending',
        ]);

        \Illuminate\Support\Facades\Http::fake([
            'graph.facebook.com/*subscribed_apps*' => \Illuminate\Support\Facades\Http::response(['success' => true], 200),
        ]);

        $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->deleteJson("/api/v1/messaging/channels/{$account->id}")
            ->assertOk()
            ->assertJsonPath('data.ok', true)
            ->assertJsonPath('data.conversations_deleted', 1);

        $this->assertDatabaseMissing('channel_accounts', ['id' => $account->id, 'deleted_at' => null]);
        $this->assertDatabaseMissing('conversations', ['id' => $conv->id]);
        $this->assertDatabaseMissing('messages', ['id' => $msg->id]);
        $this->assertDatabaseMissing('message_attachments', ['message_id' => $msg->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'messaging.facebook.disconnected']);
    }

    public function test_disconnect_rejects_non_facebook_account(): void
    {
        $lz = \CMBcoreSeller\Modules\Channels\Models\ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 'LZ_2', 'status' => 'active',
        ]);

        $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->deleteJson("/api/v1/messaging/channels/{$lz->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('channel_accounts', ['id' => $lz->id, 'deleted_at' => null]);
    }

    public function test_staff_cs_cannot_disconnect(): void
    {
        $account = \CMBcoreSeller\Modules\Channels\Models\ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_8', 'status' => 'active',
        ]);

        $this->actingAs($this->userWithRole(Role::StaffCs))
            ->withHeaders($this->h())
            ->deleteJson("/api/v1/messaging/channels/{$account->id}")
            ->assertStatus(403);
    }
```

- [ ] **Step 2: Chạy test — kỳ vọng FAIL**

Run: `php artisan test --filter=MessagingChannelControllerTest`
Expected: FAIL — route DELETE chưa có (404) + class `FacebookPageDisconnectService` chưa tồn tại.

- [ ] **Step 3: Tạo service xoá cascade**

Tạo `app/Modules/Messaging/Services/FacebookPageDisconnectService.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Ngắt kết nối 1 Facebook Page: unsubscribe webhook (best-effort) rồi xoá hẳn
 * channel_account + cascade hội thoại/tin/đính kèm của page đó (design
 * 2026-05-20 §4.1). Tenant scope tự áp qua BelongsToTenant trong route auth.
 */
class FacebookPageDisconnectService
{
    public function __construct(private MediaStorage $media) {}

    /** @return array{conversations:int} */
    public function disconnect(ChannelAccount $account): array
    {
        $this->unsubscribeWebhook($account);

        $convIds = Conversation::query()
            ->where('channel_account_id', $account->getKey())
            ->pluck('id');

        $deletedConversations = 0;
        DB::transaction(function () use ($account, $convIds, &$deletedConversations) {
            if ($convIds->isNotEmpty()) {
                $messageIds = Message::query()->whereIn('conversation_id', $convIds)->pluck('id');
                if ($messageIds->isNotEmpty()) {
                    $this->deleteAttachments($messageIds);
                    Message::query()->whereIn('id', $messageIds)->delete();
                }
                $deletedConversations = Conversation::query()->whereIn('id', $convIds)->delete();
            }
            MessagingAccountMeta::query()->where('channel_account_id', $account->getKey())->delete();
            $account->forceDelete(); // hard delete (model dùng SoftDeletes)
        });

        return ['conversations' => (int) $deletedConversations];
    }

    /** @param  Collection<int, int>  $messageIds */
    private function deleteAttachments(Collection $messageIds): void
    {
        MessageAttachment::query()->whereIn('message_id', $messageIds)
            ->each(function (MessageAttachment $att) {
                if ($att->storage_path) {
                    try {
                        $this->media->disk()->delete($att->storage_path);
                    } catch (\Throwable $e) {
                        Log::warning('messaging.disconnect.media_delete_failed', ['path' => $att->storage_path, 'error' => $e->getMessage()]);
                    }
                }
            });
        MessageAttachment::query()->whereIn('message_id', $messageIds)->delete();
    }

    private function unsubscribeWebhook(ChannelAccount $account): void
    {
        $token = (string) $account->access_token;
        if ($token === '') {
            return;
        }
        $version = (string) config('integrations.messaging_facebook_page.graph_version', 'v19.0');
        try {
            Http::timeout(15)->delete("https://graph.facebook.com/{$version}/{$account->external_shop_id}/subscribed_apps", [
                'access_token' => $token,
            ]);
        } catch (\Throwable $e) {
            Log::warning('messaging.disconnect.unsubscribe_failed', ['page' => $account->external_shop_id, 'error' => $e->getMessage()]);
        }
    }
}
```

- [ ] **Step 4: Thêm route destroy**

Trong `app/Modules/Messaging/Http/routes.php`, ngay dưới route `channels` index (Task 3):

```php
        Route::delete('channels/{id}', [MessagingChannelController::class, 'destroy'])
            ->whereNumber('id')->name('messaging.channels.destroy');
```

- [ ] **Step 5: Chạy test — kỳ vọng PASS**

Run: `php artisan test --filter=MessagingChannelControllerTest`
Expected: PASS (cascade xoá hết, audit ghi, lazada 404, staff_cs 403).

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Messaging/Services/FacebookPageDisconnectService.php app/Modules/Messaging/Http/routes.php tests/Feature/Messaging/MessagingChannelControllerTest.php
git commit -m "feat(messaging): DELETE /messaging/channels/{id} ngắt kết nối Facebook Page + cascade"
```

---

## Task 5: Toggle messaging_enabled cho Lazada/TikTok (module Channels)

**Files:**
- Modify: `app/Modules/Channels/Http/Controllers/ChannelAccountController.php`
- Modify: `app/Modules/Channels/Http/Resources/ChannelAccountResource.php`
- Modify: `routes/api.php` (sau dòng 93)
- Test: `tests/Feature/Channels/ChannelMessagingToggleTest.php`

- [ ] **Step 1: Viết test toggle**

Tạo `tests/Feature/Channels/ChannelMessagingToggleTest.php`:

```php
<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelMessagingToggleTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'TogShop']);
        config(['integrations.messaging' => ['lazada_chat']]);
        $this->app->forgetInstance(MessagingRegistry::class);
    }

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($user->getKey(), ['role' => $role->value]);

        return $user;
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function account(string $provider): ChannelAccount
    {
        return ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => $provider,
            'external_shop_id' => $provider.'_1', 'shop_name' => $provider, 'status' => 'active',
            'messaging_enabled' => false,
        ]);
    }

    public function test_owner_enables_messaging_for_lazada(): void
    {
        $a = $this->account('lazada');

        $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->patchJson("/api/v1/channel-accounts/{$a->id}/messaging", ['messaging_enabled' => true])
            ->assertOk()
            ->assertJsonPath('data.messaging_enabled', true);

        $this->assertDatabaseHas('channel_accounts', ['id' => $a->id, 'messaging_enabled' => true]);
    }

    public function test_toggle_rejected_for_provider_without_messaging_connector(): void
    {
        // tiktok_chat KHÔNG bật trong config ⇒ registry không có ⇒ 422.
        $a = $this->account('tiktok');

        $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->patchJson("/api/v1/channel-accounts/{$a->id}/messaging", ['messaging_enabled' => true])
            ->assertStatus(422);
    }

    public function test_staff_cs_cannot_toggle(): void
    {
        $a = $this->account('lazada');

        $this->actingAs($this->userWithRole(Role::StaffCs))
            ->withHeaders($this->h())
            ->patchJson("/api/v1/channel-accounts/{$a->id}/messaging", ['messaging_enabled' => true])
            ->assertStatus(403);
    }
}
```

- [ ] **Step 2: Chạy test — kỳ vọng FAIL**

Run: `php artisan test --filter=ChannelMessagingToggleTest`
Expected: FAIL — route PATCH `.../messaging` chưa có (404/405).

- [ ] **Step 3: Thêm method `setMessaging` vào controller**

Trong `app/Modules/Channels/Http/Controllers/ChannelAccountController.php`, thêm imports ở đầu (cạnh các `use` hiện có):

```php
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
```

Thêm method (đặt sau `update`, trước `resync`):

```php
    /**
     * PATCH /api/v1/channel-accounts/{id}/messaging — bật/tắt nhắn tin cho gian
     * hàng (Lazada/TikTok dùng chung token — ADR-0019). Chỉ provider có
     * messaging connector đang bật. Quyền `messaging.connect`.
     */
    public function setMessaging(Request $request, int $id, MessagingRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('messaging.connect'), 403, 'Chỉ chủ sở hữu / quản trị mới bật nhắn tin.');

        $account = ChannelAccount::query()->findOrFail($id);
        $data = $request->validate(['messaging_enabled' => ['required', 'boolean']]);

        $code = self::messagingCodeFor($account->provider);
        abort_unless($code !== null && $registry->has($code), 422, 'Kênh này chưa hỗ trợ nhắn tin.');

        $account->forceFill(['messaging_enabled' => $data['messaging_enabled']])->save();
        AuditLog::record('messaging.channel.toggle', $account, ['messaging_enabled' => $data['messaging_enabled']]);

        return response()->json(['data' => new ChannelAccountResource($account)]);
    }

    /** Map provider gian hàng → messaging connector code (ADR-0019). */
    private static function messagingCodeFor(string $provider): ?string
    {
        return match ($provider) {
            'lazada' => 'lazada_chat',
            'tiktok' => 'tiktok_chat',
            'shopee' => 'shopee_chat',
            'facebook_page' => 'facebook_page',
            default => null,
        };
    }
```

- [ ] **Step 4: Thêm route**

Trong `routes/api.php`, ngay sau dòng 93 (`channel-accounts/{id}` PATCH update):

```php
            Route::patch('channel-accounts/{id}/messaging', [ChannelAccountController::class, 'setMessaging'])->whereNumber('id')->name('channel-accounts.messaging');
```

- [ ] **Step 5: Thêm 2 field vào ChannelAccountResource**

Trong `app/Modules/Channels/Http/Resources/ChannelAccountResource.php`, thêm vào mảng `toArray` (trước dòng `'has_shop_cipher'`):

```php
            'messaging_enabled' => (bool) $this->messaging_enabled,
            'messaging_available' => app(\CMBcoreSeller\Integrations\Messaging\MessagingRegistry::class)->has(match ($this->provider) {
                'lazada' => 'lazada_chat',
                'tiktok' => 'tiktok_chat',
                'shopee' => 'shopee_chat',
                'facebook_page' => 'facebook_page',
                default => '',
            }),
```

- [ ] **Step 6: Chạy test — kỳ vọng PASS**

Run: `php artisan test --filter=ChannelMessagingToggleTest`
Expected: PASS (lazada bật OK, tiktok 422, staff_cs 403).

- [ ] **Step 7: Chạy lại nhóm test Channels để chắc không vỡ resource**

Run: `php artisan test --filter=ChannelConnectFlowTest`
Expected: PASS (resource thêm field không phá assert cũ).

- [ ] **Step 8: Commit**

```bash
git add app/Modules/Channels/Http/Controllers/ChannelAccountController.php app/Modules/Channels/Http/Resources/ChannelAccountResource.php routes/api.php tests/Feature/Channels/ChannelMessagingToggleTest.php
git commit -m "feat(channels): toggle messaging_enabled cho Lazada/TikTok + expose ở resource"
```

---

## Task 6: FE — route + menu + nav (G1)

**Files:**
- Modify: `resources/js/app.tsx`
- Modify: `resources/js/components/AppLayout.tsx`
- Modify: `resources/js/components/MessagingNav.tsx`

- [ ] **Step 1: Thêm import + route trong app.tsx**

Trong `resources/js/app.tsx`, thêm import gần dòng 30 (cạnh `import { MessagingSettingsPage } ...`):

```tsx
import { MessagingChannelsPage } from '@/pages/MessagingChannelsPage';
```

Thêm route ngay sau dòng 96 (`<Route path="messaging" element={<MessagingPage />} />`):

```tsx
                <Route path="messaging/channels" element={<MessagingChannelsPage />} />
```

- [ ] **Step 2: Thêm mục menu "Kết nối kênh" trong AppLayout**

Trong `resources/js/components/AppLayout.tsx`, thêm vào children của menu `messaging` ngay sau dòng 44 (`{ key: '/messaging', label: <Link to="/messaging">Hộp thư</Link> }`):

```tsx
                { key: '/messaging/channels', label: <Link to="/messaging/channels">Kết nối kênh</Link> },
```

- [ ] **Step 3: Thêm option "Kết nối kênh" trong MessagingNav**

Trong `resources/js/components/MessagingNav.tsx`, thêm vào `options` ngay sau `{ label: 'Hộp thư', value: '/messaging' }` (dòng 9):

```tsx
        { label: 'Kết nối kênh', value: '/messaging/channels' },
```

- [ ] **Step 4: Typecheck**

Run: `npm run typecheck`
Expected: LỖI — `MessagingChannelsPage` chưa tồn tại (Task 7 tạo). (Tạm chấp nhận; sẽ xanh sau Task 7. KHÔNG commit Task 6 riêng — gộp commit ở cuối Task 7.)

> Vì route import phụ thuộc trang ở Task 7, Task 6 và 7 commit chung (xem Task 7 Step cuối).

---

## Task 7: FE — trang MessagingChannelsPage + hooks (G2+G3)

**Files:**
- Create: `resources/js/pages/MessagingChannelsPage.tsx`
- Modify: `resources/js/lib/messagingConfig.tsx`

- [ ] **Step 1: Thêm hooks vào messagingConfig.tsx**

Thêm vào cuối `resources/js/lib/messagingConfig.tsx` (sau `useConnectFacebook`):

```tsx
// --- Quản lý kênh Facebook Page (UI /messaging/channels) -------------------

export interface MessagingChannel {
    id: number;
    provider: string;
    shop_name: string | null;
    name: string;
    external_shop_id: string;
    status: string;
    messaging_enabled: boolean;
    token_expired: boolean;
    connected_at: string | null;
}

export function useMessagingChannels() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['messaging', 'channels', tenantId],
        enabled: api != null,
        queryFn: async () => (await api!.get<{ data: MessagingChannel[] }>('/messaging/channels')).data.data,
    });
}

export function useDisconnectFacebookPage() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => { await api!.delete(`/messaging/channels/${id}`); },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'channels'] }),
    });
}
```

- [ ] **Step 2: Tạo trang MessagingChannelsPage.tsx**

Tạo `resources/js/pages/MessagingChannelsPage.tsx`:

```tsx
import { useEffect } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { App as AntApp, Button, Card, Empty, Popconfirm, Space, Spin, Tag, Typography } from 'antd';
import { DisconnectOutlined, FacebookFilled, KeyOutlined } from '@ant-design/icons';
import { MessagingNav } from '@/components/MessagingNav';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import { useConnectFacebook, useDisconnectFacebookPage, useMessagingChannels } from '@/lib/messagingConfig';

const { Text } = Typography;

/** Thông điệp cho mã `?error=` từ callback Facebook (FacebookOAuthController). */
const FB_ERRORS: Record<string, string> = {
    facebook_no_pages: 'Tài khoản chưa quản lý Page nào hoặc bạn chưa cấp quyền Page khi đăng nhập.',
    facebook_oauth_state: 'Phiên kết nối đã hết hạn. Vui lòng thử lại.',
    facebook_oauth_failed: 'Kết nối Facebook thất bại. Vui lòng thử lại sau.',
};

/** /messaging/channels — kết nối & quản lý Facebook Page (design 2026-05-20). */
export function MessagingChannelsPage() {
    const { message } = AntApp.useApp();
    const [params, setParams] = useSearchParams();
    const canConnect = useCan('messaging.connect');
    const connectFb = useConnectFacebook();
    const { data: channels, isLoading } = useMessagingChannels();
    const disconnect = useDisconnectFacebookPage();

    useEffect(() => {
        const connected = params.get('connected');
        const err = params.get('error');
        if (connected === 'facebook_page') {
            message.success('Đã kết nối Facebook Page!');
            params.delete('connected'); setParams(params, { replace: true });
        } else if (err) {
            message.error({ content: FB_ERRORS[err] ?? 'Bạn đã huỷ hoặc Facebook từ chối cấp quyền.', duration: 12 });
            params.delete('error'); setParams(params, { replace: true });
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const handleConnect = () => connectFb.mutate(undefined, {
        onSuccess: (d) => { window.location.href = d.authorize_url; },
        onError: (e) => message.error(errorMessage(e, 'Không khởi tạo được kết nối. Quản trị viên cần bật facebook_page.')),
    });

    const pages = channels ?? [];

    return (
        <div>
            <MessagingNav />
            <PageHeader title="Kết nối kênh" subtitle="Kết nối Facebook Page để nhận & trả lời tin nhắn Messenger ngay trong hộp thư." />

            <Card title={<><FacebookFilled style={{ color: '#1877F2' }} /> Facebook Page</>} style={{ marginBottom: 16 }}>
                <Space direction="vertical" size={12} style={{ display: 'flex' }}>
                    <Button type="primary" icon={<FacebookFilled />} loading={connectFb.isPending} onClick={handleConnect} disabled={!canConnect}>
                        Kết nối Facebook Page
                    </Button>
                    {isLoading ? (
                        <div style={{ textAlign: 'center', padding: 24 }}><Spin /></div>
                    ) : pages.length === 0 ? (
                        <Empty description="Chưa kết nối Page nào" />
                    ) : pages.map((p) => (
                        <Card key={p.id} size="small" styles={{ body: { padding: 12 } }}>
                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 12 }}>
                                <Space direction="vertical" size={2}>
                                    <Space size={6}>
                                        <Text strong>{p.name}</Text>
                                        <Tag color={p.token_expired ? 'red' : 'green'}>{p.token_expired ? 'Hết hạn token' : 'Đang hoạt động'}</Tag>
                                    </Space>
                                    <Text type="secondary" style={{ fontSize: 12 }}>Page ID: {p.external_shop_id}</Text>
                                </Space>
                                {canConnect && (
                                    <Space>
                                        {p.token_expired && (
                                            <Button size="small" type="primary" icon={<KeyOutlined />} loading={connectFb.isPending} onClick={handleConnect}>Kết nối lại</Button>
                                        )}
                                        <Popconfirm
                                            title="Ngắt kết nối Page?"
                                            description="Sẽ gỡ Page và xoá toàn bộ hội thoại liên quan, không khôi phục được."
                                            okText="Ngắt kết nối" okButtonProps={{ danger: true }} cancelText="Huỷ"
                                            onConfirm={() => disconnect.mutate(p.id, {
                                                onSuccess: () => message.success('Đã ngắt kết nối Page.'),
                                                onError: (e) => message.error(errorMessage(e)),
                                            })}
                                        >
                                            <Button size="small" danger icon={<DisconnectOutlined />}>Ngắt kết nối</Button>
                                        </Popconfirm>
                                    </Space>
                                )}
                            </div>
                        </Card>
                    ))}
                </Space>
            </Card>

            <Card title="Lazada / TikTok">
                <Text type="secondary">Lazada/TikTok dùng chung kết nối với Gian hàng. Bật nhắn tin tại <Link to="/channels">trang Gian hàng</Link>.</Text>
            </Card>
        </div>
    );
}
```

- [ ] **Step 3: Typecheck + lint (gồm cả Task 6)**

Run: `npm run typecheck`
Expected: PASS (không lỗi type).

Run: `npm run lint`
Expected: PASS (không lỗi eslint).

- [ ] **Step 4: Commit (gộp Task 6 + 7)**

```bash
git add resources/js/app.tsx resources/js/components/AppLayout.tsx resources/js/components/MessagingNav.tsx resources/js/pages/MessagingChannelsPage.tsx resources/js/lib/messagingConfig.tsx
git commit -m "feat(messaging): trang /messaging/channels — kết nối/quản lý Facebook Page + menu/nav"
```

---

## Task 8: FE — switch "Bật nhắn tin" trên ChannelsPage (G5)

**Files:**
- Modify: `resources/js/lib/channels.tsx`
- Modify: `resources/js/pages/ChannelsPage.tsx`

- [ ] **Step 1: Thêm field vào type + hook trong channels.tsx**

Trong `resources/js/lib/channels.tsx`, thêm 2 field vào interface `ChannelAccount` (sau `has_shop_cipher: boolean;`, dòng 20):

```tsx
    messaging_enabled: boolean;
    messaging_available: boolean;
```

Thêm hook vào cuối file:

```tsx
/** Bật/tắt nhắn tin cho gian hàng (Lazada/TikTok dùng chung token — ADR-0019). */
export function useSetChannelMessaging() {
    const api = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (vars: { id: number; messaging_enabled: boolean }) => {
            await api!.patch(`/channel-accounts/${vars.id}/messaging`, { messaging_enabled: vars.messaging_enabled });
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['channel-accounts', tenantId] }),
    });
}
```

- [ ] **Step 2: Thêm switch vào ShopCard + wire trong ChannelsPage**

Trong `resources/js/pages/ChannelsPage.tsx`:

(a) Thêm `Switch` vào import antd dòng 3:

```tsx
import { Alert, Button, Card, Col, Empty, Input, Modal, Result, Row, Space, Switch, Tag, Tooltip, Typography } from 'antd';
```

(b) Thêm `MessageOutlined` vào import icons dòng 5:

```tsx
import { CheckCircleOutlined, ClockCircleOutlined, DeleteOutlined, EditOutlined, KeyOutlined, MessageOutlined, PlusOutlined, ReloadOutlined } from '@ant-design/icons';
```

(c) Thêm hook import dòng 11:

```tsx
import { ChannelAccount, useChannelAccounts, useConnectChannel, useDeleteChannelAccount, useOutboundIp, useRenameChannel, useResyncChannel, useSetChannelMessaging } from '@/lib/channels';
```

(d) Mở rộng props `ShopCard` (dòng 61) để nhận callback toggle. Thay chữ ký `ShopCard` thành:

```tsx
function ShopCard({ account, canManage, onResync, onDelete, onRename, onReauthorize, reauthorizing, onToggleMessaging, togglingMessaging }: { account: ChannelAccount; canManage: boolean; onResync: () => void; onDelete: () => void; onRename: () => void; onReauthorize: () => void; reauthorizing: boolean; onToggleMessaging: (v: boolean) => void; togglingMessaging: boolean }) {
```

(e) Thêm khối switch trong `ShopCard`, ngay trước thẻ đóng `</Card>` (sau khối `account.status === 'expired'` Alert, sau dòng 94):

```tsx
            {canManage && account.messaging_available && (
                <div style={{ marginTop: 12, display: 'flex', alignItems: 'center', gap: 8 }}>
                    <MessageOutlined style={{ color: '#8c8c8c' }} />
                    <Typography.Text style={{ fontSize: 13 }}>Nhận & trả lời tin nhắn trong Hộp thư</Typography.Text>
                    <Switch size="small" checked={account.messaging_enabled} loading={togglingMessaging} onChange={onToggleMessaging} />
                </div>
            )}
```

(f) Trong `ChannelsPage`, khai báo hook (sau dòng 109 `const rename = useRenameChannel();`):

```tsx
    const setMessaging = useSetChannelMessaging();
```

(g) Truyền props vào `<ShopCard>` (trong `.map`, sau `reauthorizing={...}` dòng 205):

```tsx
                                    onToggleMessaging={(v) => setMessaging.mutate({ id: a.id, messaging_enabled: v }, {
                                        onSuccess: () => message.success(v ? 'Đã bật nhắn tin cho gian hàng.' : 'Đã tắt nhắn tin.'),
                                        onError: (e) => message.error(errorMessage(e)),
                                    })}
                                    togglingMessaging={setMessaging.isPending && setMessaging.variables?.id === a.id}
```

- [ ] **Step 3: Typecheck + lint**

Run: `npm run typecheck`
Expected: PASS.

Run: `npm run lint`
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add resources/js/lib/channels.tsx resources/js/pages/ChannelsPage.tsx
git commit -m "feat(channels): switch bật nhắn tin Lazada/TikTok trên trang Gian hàng"
```

---

## Task 9: FE cleanup — MessagingSettingsPage (bỏ card + Select→Radio) + MessagingPage (bỏ emoji + nav)

**Files:**
- Modify: `resources/js/pages/MessagingSettingsPage.tsx`
- Modify: `resources/js/pages/MessagingPage.tsx`

- [ ] **Step 1: Gỡ card kết nối + đổi Select→Radio trong MessagingSettingsPage**

Thay toàn bộ nội dung `resources/js/pages/MessagingSettingsPage.tsx` bằng:

```tsx
import { useEffect } from 'react';
import { App as AntApp, Alert, Button, Card, Form, Radio, Space, Spin, Switch, Typography } from 'antd';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import { useMessagingSettings, useSaveMessagingSettings } from '@/lib/messagingConfig';
import { MessagingNav } from '@/components/MessagingNav';

const { Text } = Typography;

/** /settings/messaging — chọn AI provider + bật AI / auto-mode (SPEC-0024 §6.2).
 *  Kết nối kênh đã chuyển sang /messaging/channels. */
export function MessagingSettingsPage() {
    const { message } = AntApp.useApp();
    const canConfig = useCan('messaging.ai.config');
    const { data, isLoading } = useMessagingSettings();
    const save = useSaveMessagingSettings();
    const [form] = Form.useForm();

    useEffect(() => {
        if (data) {
            form.setFieldsValue({ ai_provider_code: data.ai_provider_code ?? '', ai_enabled: data.ai_enabled, auto_mode: data.auto_mode });
        }
    }, [data, form]);

    if (isLoading) return <div style={{ textAlign: 'center', padding: 48 }}><Spin /></div>;

    const providers = data?.available_providers ?? [];
    const submit = () => form.validateFields().then((v) => {
        save.mutate({ ...v, ai_provider_code: v.ai_provider_code === '' ? null : v.ai_provider_code }, {
            onSuccess: () => message.success('Đã lưu cài đặt tin nhắn'),
            onError: (e) => message.error(errorMessage(e)),
        });
    });

    return (
        <div>
            <MessagingNav />
            <Card title="Cài đặt AI tin nhắn" style={{ maxWidth: 640 }}>
                {providers.length === 0 && (
                    <Alert type="warning" showIcon style={{ marginBottom: 16 }}
                        message="Chưa có AI provider khả dụng"
                        description="Quản trị viên hệ thống cần thêm & bật provider (Claude/OpenAI) trong /admin/ai-providers trước." />
                )}
                <Form form={form} layout="vertical" disabled={!canConfig}>
                    <Form.Item name="ai_provider_code" label="AI provider" extra="Chọn 1 trong các provider quản trị viên đã bật.">
                        <Radio.Group>
                            <Space direction="vertical">
                                <Radio value="">Không dùng AI</Radio>
                                {providers.map((p) => <Radio key={p.code} value={p.code}>{p.name}</Radio>)}
                            </Space>
                        </Radio.Group>
                    </Form.Item>
                    <Form.Item name="ai_enabled" label="Bật AI gợi ý trả lời" valuePropName="checked"><Switch /></Form.Item>
                    <Form.Item name="auto_mode" label="Tự động trả lời (auto-mode)" valuePropName="checked"
                        extra={<Text type="secondary">AI tự gửi với tin an toàn; tin nhạy cảm (khiếu nại/hoàn tiền/khẩn) sẽ chuyển NV. Cần gói Business.</Text>}>
                        <Switch />
                    </Form.Item>
                    {canConfig && <Button type="primary" loading={save.isPending} onClick={submit}>Lưu</Button>}
                </Form>
            </Card>
        </div>
    );
}
```

- [ ] **Step 2: Bỏ emoji + thêm nav trong MessagingPage**

Trong `resources/js/pages/MessagingPage.tsx`:

(a) Thêm icon vào import dòng 3:

```tsx
import { RobotOutlined, SendOutlined, ShopOutlined } from '@ant-design/icons';
```

(b) Thêm import nav (sau import messaging lib, ~dòng 16):

```tsx
import { MessagingNav } from '@/components/MessagingNav';
```

(c) Thay emoji `📍` dòng 123. Đổi:

```tsx
                                                    <Text type="secondary" style={{ fontSize: 11 }} ellipsis>📍 {c.channel_account_name}</Text>
```

thành:

```tsx
                                                    <Text type="secondary" style={{ fontSize: 11 }} ellipsis><ShopOutlined /> {c.channel_account_name}</Text>
```

(d) Bọc layout 3 cột bằng wrapper có nav. Đổi dòng 72–73:

```tsx
    return (
        <div style={{ display: 'flex', height: 'calc(100vh - 96px)', gap: 12 }}>
```

thành:

```tsx
    return (
        <div>
            <MessagingNav />
            <div style={{ display: 'flex', height: 'calc(100vh - 150px)', gap: 12 }}>
```

và đổi thẻ đóng cuối cùng của component (dòng 208 `</div>` — thẻ đóng của div flex ngoài cùng) thành 2 thẻ đóng:

```tsx
            </div>
        </div>
    );
```

> Lưu ý: chỉ thêm 1 cấp `<div>` bọc ngoài + `<MessagingNav/>`; div flex 3 cột giữ nguyên cấu trúc bên trong, chỉ đổi `height` 96→150 để chừa chỗ cho thanh nav.

- [ ] **Step 3: Typecheck + lint**

Run: `npm run typecheck`
Expected: PASS.

Run: `npm run lint`
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add resources/js/pages/MessagingSettingsPage.tsx resources/js/pages/MessagingPage.tsx
git commit -m "refactor(messaging): gỡ card kết nối khỏi Settings (Select→Radio) + bỏ emoji, thêm nav inbox"
```

---

## Task 10: Verify toàn bộ + đóng nhánh

- [ ] **Step 1: Chạy toàn bộ test messaging + channels**

Run: `php artisan test --filter=Messaging`
Expected: PASS toàn bộ.

Run: `php artisan test --filter=Channel`
Expected: PASS toàn bộ.

- [ ] **Step 2: FE typecheck + lint toàn bộ**

Run: `npm run typecheck`
Expected: PASS.

Run: `npm run lint`
Expected: PASS.

- [ ] **Step 3: Cập nhật tài liệu kênh (đối chiếu spec)**

Trong `docs/04-channels/facebook-messenger-setup.md` §8 bước 2, đổi câu mô tả đường đi UI từ "**Cài đặt → Tin nhắn → Kết nối Facebook Page**" thành "**Tin nhắn → Kết nối kênh → Kết nối Facebook Page**" và đổi đích redirect thành `/messaging/channels?connected=facebook_page`.

```bash
git add docs/04-channels/facebook-messenger-setup.md
git commit -m "docs(messaging): cập nhật đường đi UI kết nối Facebook Page (/messaging/channels)"
```

- [ ] **Step 4: Hoàn tất nhánh**

REQUIRED SUB-SKILL: dùng `superpowers:finishing-a-development-branch` để quyết định merge/PR.

---

## Self-Review (đã rà)

- **Spec coverage:** G1 (Task 6 menu/nav + Task 7 trang), G2 (Task 2 redirect + Task 7 toast/banner), G3 (Task 3 list + Task 4 disconnect cascade + Task 7 nút kết nối lại/ngắt), G4 (Task 1 permission), G5 (Task 5 toggle BE + Task 8 switch FE), phụ: emoji + Select (Task 9). Tất cả mục §10 spec đều có task.
- **Placeholder scan:** Không có TODO/TBD; mọi step có code/lệnh thật. Task 1 ghi rõ trường hợp test có thể xanh sẵn (đổi gate vẫn thực hiện) — không phải placeholder.
- **Type consistency:** `MessagingChannel` (messagingConfig.tsx) khớp shape `index` controller. `useSetChannelMessaging({id, messaging_enabled})` khớp route `PATCH channel-accounts/{id}/messaging {messaging_enabled}`. `messaging_available`/`messaging_enabled` thêm đồng bộ ở resource (BE) + interface (FE). `messagingCodeFor` map khớp giữa controller toggle và resource. Tên `FacebookPageDisconnectService::disconnect()` dùng nhất quán Task 3 & 4.
