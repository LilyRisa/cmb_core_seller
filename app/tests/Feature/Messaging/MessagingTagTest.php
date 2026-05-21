<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\MessagingTag;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagingTagTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'TagShop']);
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);
        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_TAG', 'status' => 'active', 'access_token' => 'T', 'messaging_enabled' => true,
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

    // -----------------------------------------------------------------------
    // Tag CRUD
    // -----------------------------------------------------------------------

    public function test_owner_can_create_tag(): void
    {
        $res = $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson('/api/v1/messaging/tags', ['name' => 'VIP', 'color' => '#FF5733'])
            ->assertStatus(201);

        $res->assertJsonPath('data.name', 'VIP');
        $res->assertJsonPath('data.color', '#FF5733');
        $this->assertDatabaseHas('messaging_tags', ['name' => 'VIP', 'color' => '#FF5733', 'tenant_id' => $this->tenant->getKey()]);
    }

    public function test_create_tag_invalid_color_returns_422(): void
    {
        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson('/api/v1/messaging/tags', ['name' => 'Bad', 'color' => 'notacolor'])
            ->assertStatus(422);
    }

    public function test_create_tag_missing_name_returns_422(): void
    {
        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson('/api/v1/messaging/tags', ['color' => '#FF5733'])
            ->assertStatus(422);
    }

    public function test_create_duplicate_tag_name_same_tenant_returns_422(): void
    {
        MessagingTag::query()->create(['tenant_id' => $this->tenant->getKey(), 'name' => 'Dup', 'color' => '#AABBCC']);

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson('/api/v1/messaging/tags', ['name' => 'Dup', 'color' => '#112233'])
            ->assertStatus(422);
    }

    public function test_list_returns_only_current_tenant_tags(): void
    {
        // Tags for this tenant
        MessagingTag::query()->create(['tenant_id' => $this->tenant->getKey(), 'name' => 'Alpha', 'color' => '#111111']);
        MessagingTag::query()->create(['tenant_id' => $this->tenant->getKey(), 'name' => 'Beta', 'color' => '#222222']);

        // Tag for another tenant — should NOT appear
        $other = Tenant::create(['name' => 'OtherShop']);
        MessagingTag::withoutGlobalScopes()->create(['tenant_id' => $other->getKey(), 'name' => 'Gamma', 'color' => '#333333']);

        $res = $this->actingAs($this->actor())->withHeaders($this->h())
            ->getJson('/api/v1/messaging/tags')
            ->assertOk();

        $names = collect($res->json('data'))->pluck('name')->all();
        $this->assertContains('Alpha', $names);
        $this->assertContains('Beta', $names);
        $this->assertNotContains('Gamma', $names);
    }

    public function test_update_tag_changes_name_and_color(): void
    {
        $tag = MessagingTag::query()->create(['tenant_id' => $this->tenant->getKey(), 'name' => 'Old', 'color' => '#AAAAAA']);

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->patchJson("/api/v1/messaging/tags/{$tag->id}", ['name' => 'New', 'color' => '#BBBBBB'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New')
            ->assertJsonPath('data.color', '#BBBBBB');

        $this->assertDatabaseHas('messaging_tags', ['id' => $tag->id, 'name' => 'New', 'color' => '#BBBBBB']);
    }

    public function test_delete_removes_tag_and_strips_from_conversations(): void
    {
        $tag = MessagingTag::query()->create(['tenant_id' => $this->tenant->getKey(), 'name' => 'ToDelete', 'color' => '#CCCCCC']);
        $tagId = $tag->id;

        // Attach tag to a conversation
        $c = $this->conv(['tags' => [$tagId]]);

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->deleteJson("/api/v1/messaging/tags/{$tagId}")
            ->assertOk()
            ->assertJsonPath('data.ok', true);

        $this->assertDatabaseMissing('messaging_tags', ['id' => $tagId]);

        // Tag id must be stripped from conversation.tags
        $c->refresh();
        $this->assertNotContains($tagId, (array) ($c->tags ?? []));
    }

    public function test_viewer_cannot_create_tag(): void
    {
        $this->actingAs($this->actor(Role::Viewer))->withHeaders($this->h())
            ->postJson('/api/v1/messaging/tags', ['name' => 'X', 'color' => '#AABBCC'])
            ->assertStatus(403);
    }

    public function test_viewer_cannot_delete_tag(): void
    {
        $tag = MessagingTag::query()->create(['tenant_id' => $this->tenant->getKey(), 'name' => 'Y', 'color' => '#AABBCC']);

        $this->actingAs($this->actor(Role::Viewer))->withHeaders($this->h())
            ->deleteJson("/api/v1/messaging/tags/{$tag->id}")
            ->assertStatus(403);
    }

    // -----------------------------------------------------------------------
    // Attach tags via PATCH conversations/{id}, then filter ?tags=ID
    // -----------------------------------------------------------------------

    public function test_attach_tag_via_patch_conversation_then_filter(): void
    {
        $tag = MessagingTag::query()->create(['tenant_id' => $this->tenant->getKey(), 'name' => 'Hot', 'color' => '#FF0000']);
        $c1 = $this->conv();
        $this->conv(); // another conversation without the tag

        // Attach tag to c1 via PATCH conversations/{id}
        $this->actingAs($this->actor())->withHeaders($this->h())
            ->patchJson("/api/v1/messaging/conversations/{$c1->id}", ['tags' => [$tag->id]])
            ->assertOk();

        // Filter by tag id — only c1 should appear
        $res = $this->actingAs($this->actor())->withHeaders($this->h())
            ->getJson("/api/v1/messaging/conversations?tags={$tag->id}")
            ->assertOk();

        $ids = collect($res->json('data'))->pluck('id')->all();
        $this->assertContains($c1->id, $ids);
        $this->assertCount(1, $ids);
    }

    // -----------------------------------------------------------------------
    // Inbox filters: read / unread / has_phone / tags
    // -----------------------------------------------------------------------

    public function test_filter_read_returns_only_conversations_with_zero_unread(): void
    {
        $this->conv(['unread_count' => 0, 'buyer_name' => 'Read']);
        $this->conv(['unread_count' => 3, 'buyer_name' => 'Unread']);

        $res = $this->actingAs($this->actor())->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations?read=true')
            ->assertOk();

        $names = collect($res->json('data'))->pluck('buyer_name')->all();
        $this->assertContains('Read', $names);
        $this->assertNotContains('Unread', $names);
    }

    public function test_filter_unread_returns_only_conversations_with_unread_count_gt_zero(): void
    {
        $this->conv(['unread_count' => 0, 'buyer_name' => 'ReadGuy']);
        $this->conv(['unread_count' => 2, 'buyer_name' => 'UnreadGuy']);

        $res = $this->actingAs($this->actor())->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations?unread=true')
            ->assertOk();

        $names = collect($res->json('data'))->pluck('buyer_name')->all();
        $this->assertContains('UnreadGuy', $names);
        $this->assertNotContains('ReadGuy', $names);
    }

    public function test_filter_has_phone_returns_only_conversations_with_phone(): void
    {
        $this->conv(['has_phone' => true, 'detected_phone' => '0912345678', 'buyer_name' => 'WithPhone']);
        $this->conv(['has_phone' => false, 'buyer_name' => 'NoPhone']);

        $res = $this->actingAs($this->actor())->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations?has_phone=true')
            ->assertOk();

        $names = collect($res->json('data'))->pluck('buyer_name')->all();
        $this->assertContains('WithPhone', $names);
        $this->assertNotContains('NoPhone', $names);
    }

    public function test_filter_tags_csv_returns_matching_conversations(): void
    {
        $tag1 = MessagingTag::query()->create(['tenant_id' => $this->tenant->getKey(), 'name' => 'T1', 'color' => '#111111']);
        $tag2 = MessagingTag::query()->create(['tenant_id' => $this->tenant->getKey(), 'name' => 'T2', 'color' => '#222222']);

        $c1 = $this->conv(['tags' => [$tag1->id], 'buyer_name' => 'HasTag1']);
        $c2 = $this->conv(['tags' => [$tag2->id], 'buyer_name' => 'HasTag2']);
        $this->conv(['tags' => [], 'buyer_name' => 'NoTag']);

        // Filter by tag1 only
        $res = $this->actingAs($this->actor())->withHeaders($this->h())
            ->getJson("/api/v1/messaging/conversations?tags={$tag1->id}")
            ->assertOk();

        $names = collect($res->json('data'))->pluck('buyer_name')->all();
        $this->assertContains('HasTag1', $names);
        $this->assertNotContains('HasTag2', $names);
        $this->assertNotContains('NoTag', $names);

        // Filter by tag1,tag2 (OR logic) — both should appear
        $res2 = $this->actingAs($this->actor())->withHeaders($this->h())
            ->getJson("/api/v1/messaging/conversations?tags={$tag1->id},{$tag2->id}")
            ->assertOk();

        $names2 = collect($res2->json('data'))->pluck('buyer_name')->all();
        $this->assertContains('HasTag1', $names2);
        $this->assertContains('HasTag2', $names2);
        $this->assertNotContains('NoTag', $names2);
    }

    public function test_conversation_resource_includes_has_phone_and_detected_phone(): void
    {
        $c = $this->conv(['has_phone' => true, 'detected_phone' => '0987654321']);

        $res = $this->actingAs($this->actor())->withHeaders($this->h())
            ->getJson("/api/v1/messaging/conversations/{$c->id}")
            ->assertOk();

        $conv = $res->json('data.conversation');
        $this->assertTrue($conv['has_phone']);
        $this->assertSame('0987654321', $conv['detected_phone']);
    }
}
